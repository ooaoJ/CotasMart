<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payments\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use MercadoPago\Exceptions\InvalidWebhookSignatureException;
use MercadoPago\Webhook\WebhookSignatureValidator;
use Throwable;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MercadoPagoService $mercadoPago
    ): Response {

        Log::info('WEBHOOK MERCADO PAGO RECEBIDO', [
            'headers' => $request->headers->all(),
            'query' => $request->query(),
            'body' => $request->all(0)
        ]);

        if (! $this->validSignature($request)) {
            return response('Invalid signature', 401);
        }

        try {

            $type = $request->input('type')
                ?? $request->query('type');

            $dataId = $request->input('data.id')
                ?? $request->query('data.id')
                ?? $request->query('data_id');

            if (! $dataId) {
                return response('OK', 200);
            }

            match ($type) {
                'subscription_preapproval'
                    => $this->handleSubscription(
                        $dataId,
                        $mercadoPago
                    ),

                'subscription_authorized_payment'
                    => $this->handleAuthorizedPayment(
                        $dataId,
                        $mercadoPago
                    ),

                default => null,
            };

        } catch (Throwable $exception) {

            Log::error(
                'Erro no webhook do Mercado Pago',
                [
                    'message' => $exception->getMessage(),
                    'payload' => $request->all(),
                ]
            );

            /*
             * Retornamos 500 para o Mercado Pago tentar enviar novamente.
             */
            return response('Webhook error', 500);
        }

        return response('OK', 200);
    }


    private function handleSubscription(
        string $subscriptionId,
        MercadoPagoService $mercadoPago
    ): void {
        $data = $mercadoPago->getSubscription(
            $subscriptionId
        );

        $subscription = Subscription::where(
            'provider_subscription_id',
            $subscriptionId
        )->first();

        /*
         * Fallback usando external_reference.
         */
        if (! $subscription && ! empty($data['external_reference'])) {

            $user = User::find(
                $data['external_reference']
            );

            if ($user) {

                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'provider' => 'mercadopago',
                    'provider_subscription_id' => $subscriptionId,
                    'amount' => $data['auto_recurring']['transaction_amount']
                        ?? config('cotasmart.subscription.price'),
                    'status' => $data['status'] ?? 'pending',
                ]);

            }
        }

        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => $data['status'] ?? $subscription->status,

            'started_at' => $data['date_created']
                ?? $subscription->started_at,

            'next_payment_at' => $data['next_payment_date']
                ?? null,

            'canceled_at' =>
                ($data['status'] ?? null) === 'cancelled'
                    ? now()
                    : $subscription->canceled_at,
        ]);

        $this->syncUserAccess(
            $subscription,
            $data['status'] ?? null
        );
    }


    private function handleAuthorizedPayment(
        string $invoiceId,
        MercadoPagoService $mercadoPago
    ): void {
        $invoice = $mercadoPago->getAuthorizedPayment(
            $invoiceId
        );

        $subscriptionId = $invoice['preapproval_id']
            ?? null;

        if (! $subscriptionId) {
            return;
        }

        $subscription = Subscription::where(
            'provider_subscription_id',
            $subscriptionId
        )->first();

        if (! $subscription) {
            return;
        }

        $paymentData = $invoice['payment'] ?? [];

        $paymentStatus = $paymentData['status']
            ?? $invoice['summarized']
            ?? $invoice['status']
            ?? 'pending';

        Payment::updateOrCreate(
            [
                'provider_invoice_id' => (string) $invoiceId,
            ],
            [
                'user_id' => $subscription->user_id,

                'subscription_id' => $subscription->id,

                'provider' => 'mercadopago',

                'provider_payment_id' =>
                    isset($paymentData['id'])
                        ? (string) $paymentData['id']
                        : null,

                'amount' =>
                    $invoice['transaction_amount']
                    ?? $subscription->amount,

                'status' => $paymentStatus,

                'paid_at' =>
                    $paymentStatus === 'approved'
                        ? now()
                        : null,

                'payload' => $invoice,
            ]
        );

        if ($paymentStatus === 'approved') {

            $subscription->update([
                'status' => 'authorized',
            ]);

            $subscription->user->update([
                'subscription_status' => 'active',
                'subscription_ends_at' => null,
            ]);

        } elseif (
            in_array(
                $paymentStatus,
                ['rejected', 'cancelled'],
                true
            )
        ) {

            /*
             * Não estou desativando o usuário imediatamente aqui.
             *
             * O Mercado Pago possui novas tentativas automáticas
             * de cobrança para assinaturas.
             */
        }
    }


    private function syncUserAccess(
        Subscription $subscription,
        ?string $providerStatus
    ): void {
        $user = $subscription->user;

        if ($providerStatus === 'authorized') {

            $user->update([
                'subscription_status' => 'active',
                'subscription_ends_at' => null,
            ]);

            return;
        }

        if (
            in_array(
                $providerStatus,
                [
                    'cancelled',
                    'paused',
                ],
                true
            )
        ) {

            $user->update([
                'subscription_status' => 'inactive',
                'subscription_ends_at' => now(),
            ]);
        }
    }


    private function validSignature(
        Request $request
    ): bool {
        $secret = config(
            'services.mercadopago.webhook_secret'
        );

        /*
         * Enquanto ainda estivermos configurando os testes,
         * não aceite webhooks sem secret em produção.
         */
        if (! $secret) {
            return app()->environment('local');
        }

        $signature = $request->header(
            'x-signature'
        );

        $requestId = $request->header(
            'x-request-id'
        );

        $dataId = $request->query('data.id')
            ?? $request->query('data_id')
            ?? $request->input('data.id');

        if (
            ! $signature
            || ! $requestId
            || ! $dataId
        ) {
            return false;
        }

        try {

            WebhookSignatureValidator::validate(
                $signature,
                $requestId,
                (string) $dataId,
                $secret
            );

            return true;

        } catch (
            InvalidWebhookSignatureException $exception
        ) {

            return false;

        }
    }
}