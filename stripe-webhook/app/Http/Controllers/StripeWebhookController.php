<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Webhook;
use App\Models\Payment; // Modelo que você vai usar para salvar o pagamento (se necessário)

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Defina sua chave secreta
        Stripe::setApiKey(env('STRIPE_SECRET'));

        // Pegue o payload e a assinatura do cabeçalho
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $event = null;

        try {
            // Verifique a assinatura do webhook da Stripe
            $event = Webhook::constructEvent(
                $payload, $sig_header, env('STRIPE_WEBHOOK_SECRET')
            );
        } catch (\UnexpectedValueException $e) {
            // Erro de assinatura inválida
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            // Erro geral
            return response()->json(['error' => 'Webhook error'], 400);
        }

        // Agora você pode verificar o tipo de evento
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object; // Acesse o objeto de pagamento

                // Salvar ou processar o pagamento, se necessário
                Payment::create([
                    'payment_id' => $paymentIntent->id,
                    'amount' => $paymentIntent->amount_received,
                    'status' => 'succeeded',
                ]);

                // Ação adicional, como enviar um e-mail de confirmação
                break;

            case 'payment_intent.failed':
                // Lógica para falha no pagamento
                break;

            // Adicione mais casos de eventos conforme necessário
        }

        return response()->json(['status' => 'success']);
    }
}
