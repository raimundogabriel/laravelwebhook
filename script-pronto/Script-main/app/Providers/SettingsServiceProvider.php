<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
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
        if (!InstallerServiceProvider::checkIfInstalled()) {
            return false;
        }

        // Overriding config values for 3rd party implementations with DB values
        config(['laravel-ffmpeg.ffmpeg.binaries' => getSetting('media.ffmpeg_path', config('laravel-ffmpeg.ffmpeg.binaries'))]);
        config(['laravel-ffmpeg.ffprobe.binaries' => getSetting('media.ffprobe_path', config('laravel-ffmpeg.ffprobe.binaries'))]);

        // Websockets settings handling
        config(['broadcasting.default' => 'pusher']);
        if(self::hasPusherSettings()){
            if (getSetting('websockets.pusher_app_key')) {
                config(['broadcasting.connections.pusher.key' => getSetting('websockets.pusher_app_key')]);
            }
            if (getSetting('websockets.pusher_app_id')) {
                config(['broadcasting.connections.pusher.app_id' => getSetting('websockets.pusher_app_id')]);
            }
            if (getSetting('websockets.pusher_app_secret')) {
                config(['broadcasting.connections.pusher.secret' => getSetting('websockets.pusher_app_secret')]);
            }
            if (getSetting('websockets.pusher_app_cluster')) {
                config(['broadcasting.connections.pusher.options.cluster' => getSetting('websockets.pusher_app_cluster')]);
            }
        }
        if(self::hasSoketiSettings()){
            if (getSetting('websockets.soketi_app_key')) {
                config(['broadcasting.connections.soketi.key' => getSetting('websockets.soketi_app_key')]);
            }
            if (getSetting('websockets.soketi_app_id')) {
                config(['broadcasting.connections.soketi.app_id' => getSetting('websockets.soketi_app_id')]);
            }
            if (getSetting('websockets.soketi_app_secret')) {
                config(['broadcasting.connections.soketi.secret' => getSetting('websockets.soketi_app_secret')]);
            }
            if (getSetting('websockets.soketi_host_address')) {
                config(['broadcasting.connections.soketi.options.host' => getSetting('websockets.soketi_host_address')]);
            }
            if (getSetting('websockets.soketi_host_port')) {
                config(['broadcasting.connections.soketi.options.port' => getSetting('websockets.soketi_host_port')]);
            }
            if (getSetting('websockets.soketi_use_TSL')) {
                config(['broadcasting.connections.soketi.options.scheme' => 'https']);
                config(['broadcasting.connections.soketi.options.useTLS' => true]);
            }
        }
        if(getSetting('websockets.driver') == 'soketi'){
            config(['broadcasting.connections.pusher' => config('broadcasting.connections.soketi')]);
        }

        config(['paypal.settings.mode' => getSetting('payments.paypal_live_mode') ? 'live' : 'sandbox']);

        if (getSetting('payments.paypal_client_id')) {
            config(['paypal.client_id' => getSetting('payments.paypal_client_id')]);
        }

        if (getSetting('payments.paypal_secret')) {
            config(['paypal.secret' => getSetting('payments.paypal_secret')]);
        }

        // Overriding default config values for logos & favicons, appending public path to them
        config(['app.site.light_logo' => asset(config('app.site.light_logo'))]);
        config(['app.site.dark_logo' => asset(config('app.site.dark_logo'))]);
        config(['app.site.favicon' => asset(config('app.site.favicon'))]);
        config(['app.admin.icon_image' => asset(config('app.admin.icon_image'))]);

        config(['mail.driver' => getSetting('emails.driver')]);
        config(['mail.from.name' => getSetting('emails.from_name') ? getSetting('emails.from_name') : __("Admin")]);
        config(['mail.from.address' => getSetting('emails.from_address') ? getSetting('emails.from_address') : "no-reply@domain.com"]);

        config(['mail.host' => getSetting('emails.smtp_host')]);
        config(['mail.port' => getSetting('emails.smtp_port')]);
        config(['mail.encryption' => getSetting('emails.smtp_encryption')]);
        config(['mail.username' => getSetting('emails.smtp_username')]);
        config(['mail.password' => getSetting('emails.smtp_password')]);

        config(['services.mailgun.domain' => getSetting('emails.mailgun_domain')]);
        config(['services.mailgun.secret' => getSetting('emails.mailgun_secret')]);
        config(['services.mailgun.endpoint' => getSetting('emails.mailgun_endpoint')]);

        // Storage
        $awsRegion = getSetting('storage.aws_region') != null ? getSetting('storage.aws_region') : 'us-east-1';
        config(['filesystems.disks.s3.key' => getSetting('storage.aws_access_key')]);
        config(['filesystems.disks.s3.secret' => getSetting('storage.aws_secret_key')]);
        config(['filesystems.disks.s3.region' => $awsRegion]);
        config(['filesystems.disks.s3.bucket' => getSetting('storage.aws_bucket_name')]);

        config(['filesystems.disks.wasabi.key' => getSetting('storage.was_access_key')]);
        config(['filesystems.disks.wasabi.secret' => getSetting('storage.was_secret_key')]);
        config(['filesystems.disks.wasabi.region' => getSetting('storage.was_region')]);
        config(['filesystems.disks.wasabi.bucket' => getSetting('storage.was_bucket_name')]);
        config(['filesystems.disks.wasabi.endpoint' => 'https://s3.'.getSetting('storage.was_region').'.wasabisys.com/']);

        config(['filesystems.disks.do_spaces.key' => getSetting('storage.do_access_key')]);
        config(['filesystems.disks.do_spaces.secret' => getSetting('storage.do_secret_key')]);
        config(['filesystems.disks.do_spaces.region' => getSetting('storage.do_region')]);
        config(['filesystems.disks.do_spaces.bucket' => getSetting('storage.do_bucket_name')]);
        config(['filesystems.disks.do_spaces.endpoint' => 'https://'.getSetting('storage.do_region').'.digitaloceanspaces.com']);

        config(['filesystems.disks.minio.key' => getSetting('storage.minio_access_key')]);
        config(['filesystems.disks.minio.secret' => getSetting('storage.minio_secret_key')]);
        config(['filesystems.disks.minio.region' => getSetting('storage.minio_region')]);
        config(['filesystems.disks.minio.bucket' => getSetting('storage.minio_bucket_name')]);
        config(['filesystems.disks.minio.endpoint' => rtrim(getSetting('storage.minio_endpoint'), '/')]);
        config(['filesystems.disks.minio.url' => rtrim(getSetting('storage.minio_endpoint'), '/').'/'.getSetting('storage.minio_bucket_name').'/']);

        config(['filesystems.disks.pushr.key' => getSetting('storage.pushr_access_key')]);
        config(['filesystems.disks.pushr.secret' => getSetting('storage.pushr_secret_key')]);
        config(['filesystems.disks.pushr.bucket' => getSetting('storage.pushr_bucket_name')]);
        config(['filesystems.disks.pushr.endpoint' => rtrim(getSetting('storage.pushr_endpoint'), '/')]);
        config(['filesystems.disks.pushr.url' => getSetting('storage.pushr_cdn_hostname')]);

        self::setDefaultStorageDriver();

        config(['services.ses.key' => getSetting('storage.aws_access_key')]);
        config(['services.ses.secret' => getSetting('storage.aws_secret_key')]);
        config(['services.ses.s3.region' => $awsRegion]);

        config(['queue.connections.sqs.key' => getSetting('storage.aws_access_key')]);
        config(['queue.connections.sqs.secret' => getSetting('storage.aws_secret_key')]);
        config(['queue.connections.sqs.region' => $awsRegion]);

        if (getSetting('payments.currency_code') != null && !empty(getSetting('payments.currency_code'))) {
            config(['app.site.currency_code' => getSetting('payments.currency_code')]);
        }

        if (getSetting('payments.currency_symbol') !== null && !empty(getSetting('payments.currency_symbol'))) {
            config(['app.site.currency_symbol' => getSetting('payments.currency_symbol')]);
        }

        config(['app.url' => getSetting('site.app_url')]);
        config(['filesystems.disks.public.url' =>  getSetting('site.app_url').'/storage']);

        config(['laravelpwa.manifest.name' => getSetting('site.name')]);
        config(['laravelpwa.manifest.short_name' => getSetting('site.name')]);

        // PWA overrides
        config(['laravelpwa.manifest.icons.192x192.path' => asset(config('laravelpwa.manifest.icons.192x192.path'))]);
        config(['laravelpwa.manifest.icons.512x512.path' => asset(config('laravelpwa.manifest.icons.512x512.path'))]);
        config(['laravelpwa.manifest.theme_color' => "#".getSetting('colors.theme_color_code')]);
        foreach(config('laravelpwa.manifest.splash') as $key => $entry){
            config(["laravelpwa.manifest.splash.$key" => asset(config("laravelpwa.manifest.splash.$key"))]);
        }

        // Social logins overrides
        if (getSetting('social.facebook_client_id')) {
            config(['services.facebook.client_id' => getSetting('social.facebook_client_id')]);
            config(['services.facebook.client_secret' => getSetting('social.facebook_secret')]);
            config(['services.facebook.redirect' => rtrim(getSetting('site.app_url'), '/').'/socialAuth/facebook/callback']);
        }
        if (getSetting('social.twitter_client_id')) {
            config(['services.twitter.client_id' => getSetting('social.twitter_client_id')]);
            config(['services.twitter.client_secret' => getSetting('social.twitter_secret')]);
            config(['services.twitter.redirect' => rtrim(getSetting('site.app_url'), '/').'/socialAuth/twitter/callback']);
        }
        if (getSetting('social.google_client_id')) {
            config(['services.google.client_id' => getSetting('social.google_client_id')]);
            config(['services.google.client_secret' => getSetting('social.google_secret')]);
            config(['services.google.redirect' => rtrim(getSetting('site.app_url'), '/').'/socialAuth/google/callback']);
        }

        // Allow proxied requests, fixing 403 email verify issues on nginx and load balancers
        // TODO: Check if this still works with L9
        config(['trustedproxy.proxies' => '*']);

        if(getSetting('security.captcha_driver') !== 'none'){
            if(getSetting('security.captcha_driver') == 'recaptcha'){
                if(getSetting('security.recaptcha_site_key')){
                    config(['captcha.sitekey' => getSetting('security.recaptcha_site_key')]);
                }
                if(getSetting('security.recaptcha_site_secret_key')){
                    config(['captcha.secret' => getSetting('security.recaptcha_site_secret_key')]);
                }
            }
            if(getSetting('security.captcha_driver') == 'hcaptcha'){
                if(getSetting('security.hcaptcha_site_key')){
                    config(['captcha.sitekey' => getSetting('security.hcaptcha_site_key')]);
                }
                if(getSetting('security.hcaptcha_site_secret_key')){
                    config(['captcha.secret' => getSetting('security.hcaptcha_site_secret_key')]);
                }
            }
            if(getSetting('security.captcha_driver') == 'turnstile'){
                if(getSetting('security.turnstile_site_key')){
                    config(['captcha.sitekey' => getSetting('security.turnstile_site_key')]);
                }
                if(getSetting('security.turnstile_site_secret_key')){
                    config(['captcha.secret' => getSetting('security.turnstile_site_secret_key')]);
                }
            }

            if(config('captcha.sitekey') && config('captcha.secret')){
                config(['captcha.driver' => getSetting('security.captcha_driver')]);
            }
        }

        if(getSetting('profiles.allow_hyperlinks')){
            config(['purifier.settings.default' => array_merge(config('purifier.settings.default'), [
                'HTML.Allowed' => 'b,strong,blockquote,code,pre,i,em,u,ul,ol,li,p,br,span,a[href|title]',
            ])]);
        }
    }

    /**
     * Gets site's currency symbol with currency code fallback.
     * @return \Illuminate\Config\Repository|mixed|string
     */
    public static function getWebsiteCurrencySymbol()
    {
        $symbol = '$';
        if (getSetting('payments.currency_symbol') != null && !empty(getSetting('payments.currency_symbol'))) {
            $symbol = getSetting('payments.currency_symbol');
        } elseif (getSetting('payments.currency_code') != null && !empty(getSetting('payments.currency_code'))) {
            $symbol = getSetting('payments.currency_code');
        }

        return $symbol;
    }

    /**
     * Gets site's currency symbol.
     * @return bool|\Illuminate\Config\Repository|mixed
     */
    public static function getAppCurrencySymbol()
    {
        if (getSetting('payments.currency_symbol') != null && !empty(getSetting('payments.currency_symbol'))) {
            return getSetting('payments.currency_symbol');
        }

        return false;
    }

    /**
     * Gets site's currency code.
     * @return \Illuminate\Config\Repository|mixed|string
     */
    public static function getAppCurrencyCode()
    {
        $symbol = 'USD';
        if (getSetting('payments.currency_code') != null && !empty(getSetting('payments.currency_code'))) {
            $symbol = getSetting('payments.currency_code');
        }

        return $symbol;
    }

    /**
     * Check if website has pusher settings set.
     * @return bool
     */
    private static function hasPusherSettings() {
        return getSetting('websockets.pusher_app_cluster')
            && getSetting('websockets.pusher_app_key')
            && getSetting('websockets.pusher_app_secret')
            && getSetting('websockets.pusher_app_id');
    }

    /**
     * Check if website has soketi settings set.
     * @return bool
     */
    private static function hasSoketiSettings() {
        return getSetting('websockets.soketi_host_address')
            && getSetting('websockets.soketi_host_port')
            && getSetting('websockets.soketi_app_id')
            && getSetting('websockets.soketi_app_key')
            && getSetting('websockets.soketi_app_secret');
    }

    /**
     * Check if admin provided CCBill DataLink credentials.
     * @return bool
     */
    public static function providedCCBillSubscriptionCancellingCredentials() {
        return getSetting('payments.ccbill_datalink_username')
            && getSetting('payments.payments.ccbill_datalink_password');
    }

    public static function allowWithdrawals($user) {
        return !getSetting('payments.withdrawal_allow_only_for_verified')
            || (getSetting('payments.withdrawal_allow_only_for_verified')
                && $user->email_verified_at
                && $user->birthdate
                && ($user->verification && $user->verification->status == 'verified'));
    }

    public static function setDefaultStorageDriver($storageDriver = false) {
        if($storageDriver === false){
            $storageDriver = getSetting('storage.driver') != null ? getSetting('storage.driver') : 'public';
        }
        config(['filesystems.default' => $storageDriver]);
        config(['filesystems.defaultFilesystemDriver' => $storageDriver]);
        config(['voyager.storage.disk' => $storageDriver]);
    }

    public static function getWebsiteCurrencyPosition() {
        $currencyPosition = 'left';
        $adminCurrencyPosition = getSetting('payments.currency_position');

        if(!empty($adminCurrencyPosition)) {
            $currencyPosition = $adminCurrencyPosition;
        }

        return $currencyPosition;
    }

    /**
     * @return bool
     */
    public static function leftAlignedCurrencyPosition() {
        return self::getWebsiteCurrencyPosition() === 'left';
    }

    /**
     * Format amount using the website currency symbol and the currency position
     * The default is symbol in front of amount if not specified in admin.
     *
     * @param $amount
     * @return string
     */
    public static function getWebsiteFormattedAmount($amount) {
        $currencySymbol = self::getWebsiteCurrencySymbol();

        return self::leftAlignedCurrencyPosition() ? $currencySymbol.$amount : $amount.$currencySymbol;
    }
}
