<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InscricaoController;


Route::get('/webhook', function () {
    return response()->json(['message' => 'Webhook endpoint']);
});
Route::get('/webhook/stripe', function () {
    return response()->json(['message' => 'Stripe Webhook endpoint']);
});
Route::get('/webhook/stripe/test', function () {
    return response()->json(['message' => 'Stripe Webhook Test endpoint']);
});
Route::get('/webhook/stripe/test/{id}', function ($id) {
    return response()->json(['message' => 'Stripe Webhook Test endpoint with ID: ' . $id]);
});
Route::get('/webhook/stripe/test/{id}/details', function ($id) {
    return response()->json(['message' => 'Stripe Webhook Test endpoint with ID: ' . $id . ' and details']);
});
Route::get('/webhook/stripe/test/{id}/details/{detail}', function ($id, $detail) {
    return response()->json(['message' => 'Stripe Webhook Test endpoint with ID: ' . $id . ' and detail: ' . $detail]);
});
Route::get('/webhook/stripe/test/{id}/details/{detail}/extra/{extra}', function ($id, $detail, $extra) {
    return response()->json(['message' => 'Stripe Webhook Test endpoint with ID: ' . $id . ' and detail: ' . $detail . ' and extra: ' . $extra]);
});
Route::post('/webhook', 'WebhookController@handleWebhook');

Route::post('/webhook/stripe', [StripeWebhookController::class, 'handle']);

// Rota para a página principal (onde os botões estarão)
Route::get('/', function () {
    return view('inscricao');
});

// Rota para inscrição
Route::post('/inscricao', [InscricaoController::class, 'inscrever'])->name('inscricao');

// Rota para cancelar inscrição
Route::post('/cancelar-inscricao', [InscricaoController::class, 'cancelarInscricao'])->name('cancelar-inscricao');
