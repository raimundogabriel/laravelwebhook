<?php

namespace App\Providers;

use App\Model\Tax;
use App\Model\Transaction;
use App\Model\Withdrawal;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class PaymentsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Get subscription monthly interval.
     *
     * @param $transactionType
     * @return int
     */
    public static function getSubscriptionMonthlyIntervalByTransactionType($transactionType)
    {
        $interval = 1;
        if ($transactionType != null) {
            switch ($transactionType) {
                case Transaction::YEARLY_SUBSCRIPTION:
                    $interval = 12;
                    break;
                case Transaction::THREE_MONTHS_SUBSCRIPTION:
                    $interval = 3;
                    break;
                case Transaction::SIX_MONTHS_SUBSCRIPTION:
                    $interval = 6;
                    break;
                default:
                    $interval = 1;
                    break;
            }
        }

        return $interval;
    }

    /**
     * Get withdrawal limit amounts.
     * @return string
     */
    public static function getWithdrawalAmountLimitations()
    {
        $withdrawalsMinAmount = SettingsServiceProvider::getWebsiteFormattedAmount('20');
        if (getSetting('payments.withdrawal_min_amount') != null && getSetting('payments.withdrawal_min_amount') > 0) {
            $withdrawalsMinAmount = SettingsServiceProvider::getWebsiteFormattedAmount(getSetting('payments.withdrawal_min_amount'));
        }
        $withdrawalsMaxAmount = SettingsServiceProvider::getWebsiteFormattedAmount('500');
        if (getSetting('payments.withdrawal_max_amount') != null && getSetting('payments.withdrawal_max_amount') > 0) {
            $withdrawalsMaxAmount = SettingsServiceProvider::getWebsiteFormattedAmount(getSetting('payments.withdrawal_max_amount'));
        }

        return __('Amount').' ('.$withdrawalsMinAmount.' min, '.$withdrawalsMaxAmount.' max)';
    }

    /**
     * Get deposit limit amounts.
     * @return string
     */
    public static function getDepositLimitAmounts()
    {
        $depositMinAmount = SettingsServiceProvider::getWebsiteFormattedAmount('5');
        if (getSetting('payments.deposit_min_amount') != null && getSetting('payments.deposit_min_amount') > 0) {
            $depositMinAmount = SettingsServiceProvider::getWebsiteFormattedAmount(getSetting('payments.deposit_min_amount'));
        }
        $depositMaxAmount = SettingsServiceProvider::getWebsiteFormattedAmount('500');
        if (getSetting('payments.deposit_max_amount') != null && getSetting('payments.deposit_max_amount') > 0) {
            $depositMaxAmount = SettingsServiceProvider::getWebsiteFormattedAmount(getSetting('payments.deposit_max_amount'));
        }

        return __('Amount').' ('.$depositMinAmount.' min, '.$depositMaxAmount.' max)';
    }

    /**
     * Get withdrawals minimum amount.
     * @return \Illuminate\Config\Repository|int|mixed|null
     */
    public static function getWithdrawalMinimumAmount() {
        return
            getSetting('payments.withdrawal_min_amount') != null
            && getSetting('payments.withdrawal_min_amount') > 0
                ? getSetting('payments.withdrawal_min_amount') : 20;
    }

    /**
     * Get withdrawals maximum amount.
     * @return \Illuminate\Config\Repository|int|mixed|null
     */
    public static function getWithdrawalMaximumAmount() {
        return
            getSetting('payments.withdrawal_max_amount') != null
            && getSetting('payments.withdrawal_max_amount') > 0
                ? getSetting('payments.withdrawal_max_amount') : 500;
    }

    /**
     * Get deposit minimum amount.
     * @return \Illuminate\Config\Repository|int|mixed|null
     */
    public static function getDepositMinimumAmount() {
        return
            getSetting('payments.deposit_min_amount') != null
            && getSetting('payments.deposit_min_amount') > 0
                ? getSetting('payments.deposit_min_amount') : 5;
    }

    /**
     * Get deposit maximum amount.
     * @return \Illuminate\Config\Repository|int|mixed|null
     */
    public static function getDepositMaximumAmount() {
        return
            getSetting('payments.deposit_max_amount') != null
            && getSetting('payments.deposit_max_amount') > 0
                ? getSetting('payments.deposit_max_amount') : 500;
    }

    /**
     * Creates transaction for an approved withdrawal.
     * @param $withdrawal
     */
    public static function createTransactionForWithdrawal($withdrawal) {
        try{
            if($withdrawal->status === Withdrawal::APPROVED_STATUS){
                $data = [];
                $data['recipient_user_id'] = $withdrawal->user_id;
                $data['sender_user_id'] = $withdrawal->user_id;
                $data['type'] = Transaction::WITHDRAWAL_TYPE;
                $data['amount'] = $withdrawal->amount - $withdrawal->fee;
                $data['payment_provider'] = $withdrawal->payment_method;
                $data['currency'] = SettingsServiceProvider::getAppCurrencyCode();
                $data['status'] = Transaction::APPROVED_STATUS;

                Transaction::create($data);
            }
        } catch (\Exception $e){
            Log::channel('withdrawals')->error($e->getMessage());
        }
    }

    /**
     * Fetch withdrawals allowed payment methods from admin panel.
     * @return array
     */
    public static function getWithdrawalsAllowedPaymentMethods() {
        $allowedPaymentMethods = [];
        if(getSetting('payments.withdrawal_payment_methods')) {
            $allowedPaymentMethods = explode(', ', getSetting('payments.withdrawal_payment_methods'));
        }

        // adds Stripe Connect to the list if enabled
        if(getSetting('payments.withdrawal_enable_stripe_connect')) {
            $allowedPaymentMethods[] = 'Stripe Connect';
        }

        // adds a default value in case there is nothing set in admin panel
        if(empty($allowedPaymentMethods)){
            $allowedPaymentMethods[] = 'Other';
        }

        return $allowedPaymentMethods;
    }

    /**
     * Checks if CCBill keys are provided in admin panel.
     * @return bool
     */
    public static function ccbillCredentialsProvided() {
        return getSetting('payments.ccbill_account_number') && (getSetting('payments.ccbill_subaccount_number_recurring')
                || getSetting('payments.ccbill_subaccount_number_one_time'))
            && getSetting('payments.ccbill_flex_form_id') && getSetting('payments.ccbill_salt_key') && !getSetting('payments.ccbill_checkout_disabled');
    }

    /**
     * Calculate taxes for transaction.
     * @param $transaction
     * @return float[]
     */
    public static function calculateTaxesForTransaction($transaction)
    {
        $taxes = [
            'inclusiveTaxesAmount' => 0.00,
            'exclusiveTaxesAmount' => 0.00,
            'fixedTaxesAmount' => 0.00,
        ];

        $transactionTaxes = json_decode($transaction['taxes'], true);
        if ($transaction != null && $transactionTaxes != null) {
            if (isset($transactionTaxes['data']) && is_array($transactionTaxes['data'])) {
                foreach ($transactionTaxes['data'] as $tax) {
                    if (isset($tax['taxType']) && isset($tax['taxAmount'])) {
                        if ($tax['taxType'] === Tax::INCLUSIVE_TYPE) {
                            $taxes['inclusiveTaxesAmount'] += $tax['taxAmount'];
                        } elseif ($tax['taxType'] === Tax::EXCLUSIVE_TYPE) {
                            $taxes['exclusiveTaxesAmount'] += $tax['taxAmount'];
                        } elseif ($tax['taxType'] === Tax::FIXED_TYPE) {
                            $taxes['fixedTaxesAmount'] += $tax['taxAmount'];
                        }
                    }
                }
            }
        }

        return $taxes;
    }

    /**
     * @param $transaction
     * @return float
     */
    public static function getTransactionAmountWithTaxesDeducted($transaction) {
        $amount = $transaction->amount;
        $transactionTaxes = self::calculateTaxesForTransaction($transaction);
        if($transactionTaxes['inclusiveTaxesAmount'] > 0){
            $amount = $amount - $transactionTaxes['inclusiveTaxesAmount'];
        }

        if($transactionTaxes['exclusiveTaxesAmount'] > 0){
            $amount = $amount - $transactionTaxes['exclusiveTaxesAmount'];
        }

        if($transactionTaxes['fixedTaxesAmount'] > 0){
            $amount = $amount - $transactionTaxes['fixedTaxesAmount'];
        }

        return $amount;
    }

    public static function getPaymentDescriptionByTransaction($transaction)
    {
        $description = 'Default payment description';
        if ($transaction != null) {
            $recipientUsername = null;
            if ($transaction->recipient_user_id != null) {
                $recipientUser = User::query()->where(['id' => $transaction->recipient_user_id])->first();
                if ($recipientUser != null) {
                    $recipientUsername = $recipientUser->name;
                }
            }

            if (self::isSubscriptionPayment($transaction->type)) {
                if ($recipientUsername == null) {
                    $recipientUsername = 'creator';
                }

                $description = $recipientUsername.' for '.SettingsServiceProvider::getWebsiteFormattedAmount($transaction->amount);
            } else {
                if ($transaction->type === Transaction::DEPOSIT_TYPE) {
                    $description = SettingsServiceProvider::getWebsiteFormattedAmount($transaction->amount).' '.__('wallet top-up');
                } elseif ($transaction->type === Transaction::TIP_TYPE || $transaction->type === Transaction::CHAT_TIP_TYPE) {
                    $tipPaymentDescription = SettingsServiceProvider::getWebsiteFormattedAmount($transaction->amount).' tip';
                    if ($transaction->recipient_user_id != null) {
                        $recipientUser = User::query()->where(['id' => $transaction->recipient_user_id])->first();
                        if ($recipientUser != null) {
                            $tipPaymentDescription = $tipPaymentDescription.' for '.$recipientUser->name;
                        }
                    }

                    $description = $tipPaymentDescription;
                } elseif ($transaction->type === Transaction::POST_UNLOCK) {
                    $description = __('Unlock post for').' '.SettingsServiceProvider::getWebsiteFormattedAmount($transaction->amount);
                } elseif ($transaction->type === Transaction::STREAM_ACCESS) {
                    $description = __('Join streaming for').' '.SettingsServiceProvider::getWebsiteFormattedAmount($transaction->amount);
                } elseif ($transaction->type === Transaction::MESSAGE_UNLOCK) {
                    $description = __('Unlock message for').' '.SettingsServiceProvider::getWebsiteFormattedAmount($transaction->amount);
                }
            }
        }

        return $description;
    }

    public static function isSubscriptionPayment($transactionType): bool
    {
        return $transactionType != null
            && ($transactionType === Transaction::SIX_MONTHS_SUBSCRIPTION
                || $transactionType === Transaction::THREE_MONTHS_SUBSCRIPTION
                || $transactionType === Transaction::ONE_MONTH_SUBSCRIPTION
                || $transactionType === Transaction::YEARLY_SUBSCRIPTION);
    }
}
