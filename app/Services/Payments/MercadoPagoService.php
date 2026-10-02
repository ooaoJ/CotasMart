<?php

namespace App\Services\Payments;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MercadoPagoService
{
    private string $baseUrl = 'https://api.mercadopago.com';

    private function http()
    {
        return Http::withToken(
            config('services.mercadopago.access_token')
        )
        ->acceptJson()
        ->asJson();
    }

    public function createSubscription(User $user): array
    {
        $response = $this->http()->post(
            $this->baseUrl . '/preapproval',
            [
                'reason' => 'Assinatura CotaSmart',

                'external_reference' => (string) $user->id,

                'payer_email' => $user->email,

                'auto_recurring' => [
                    'frequency' => 1,
                    'frequency_type' => 'months',
                    'transaction_amount' => config(
                        'cotasmart.subscription.price'
                    ),
                    'currency_id' => config(
                        'cotasmart.subscription.currency'
                    ),
                ],

                'back_url' => config('services.mercadopago.back_url'),

                'notification_url' => config('services.mercadopago.notification_url'),

                'status' => 'pending',
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Erro ao criar assinatura no Mercado Pago: '
                . $response->body()
            );
        }

        return $response->json();
    }

    public function getSubscription(string $subscriptionId): array
    {
        $response = $this->http()->get(
            $this->baseUrl . '/preapproval/' . $subscriptionId
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Erro ao consultar assinatura no Mercado Pago: '
                . $response->body()
            );
        }

        return $response->json();
    }

    public function getAuthorizedPayment(string|int $invoiceId): array
    {
        $response = $this->http()->get(
            $this->baseUrl . '/authorized_payments/' . $invoiceId
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Erro ao consultar cobrança no Mercado Pago: '
                . $response->body()
            );
        }

        return $response->json();
    }

    public function cancelSubscription(string $subscriptionId): array
    {
        $response = $this->http()->put(
            $this->baseUrl . '/preapproval/' . $subscriptionId,
            [
                'status' => 'cancelled',
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Erro ao cancelar assinatura no Mercado Pago: '
                . $response->body()
            );
        }

        return $response->json();
    }
}