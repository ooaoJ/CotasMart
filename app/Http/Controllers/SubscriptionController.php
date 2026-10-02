<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\Payments\MercadoPagoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class SubscriptionController extends Controller
{
    public function show(Request $request): View
    {
        $subscription = $request->user()
            ->subscription;

        return view('subscription.show', [
            'subscription' => $subscription,
            'price' => config('cotasmart.subscription.price'),
        ]);
    }

    public function checkout(
        Request $request,
        MercadoPagoService $mercadoPago
    ): RedirectResponse {
        $user = $request->user();

        if ($user->subscription_status === 'active') {
            return redirect()
                ->route('subscription.show')
                ->with('warning', 'Sua assinatura já está ativa.');
        }

        try {
            $data = $mercadoPago->createSubscription($user);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'provider' => 'mercadopago',
                'provider_subscription_id' => $data['id'],
                'amount' => config('cotasmart.subscription.price'),
                'status' => $data['status'] ?? 'pending',
                'next_payment_at' => $data['next_payment_date'] ?? null,
            ]);

            if (empty($data['init_point'])) {
                $subscription->delete();

                return back()->withErrors([
                    'payment' => 'O Mercado Pago não retornou o link de pagamento.',
                ]);
            }

            return redirect()->away($data['init_point']);

        } catch (Throwable $exception) {

            report($exception);

            return back()->withErrors([
                'payment' => 'Não foi possível iniciar sua assinatura. Tente novamente.',
            ]);
        }
    }

    public function return(Request $request): RedirectResponse
    {
        return redirect()
            ->route('subscription.show')
            ->with(
                'success',
                'Recebemos seu retorno do Mercado Pago. Estamos confirmando sua assinatura.'
            );
    }

    public function cancel(
        Request $request,
        MercadoPagoService $mercadoPago
    ): RedirectResponse {
        $subscription = $request->user()->subscription;

        if (
            ! $subscription
            || ! $subscription->provider_subscription_id
        ) {
            return back()->withErrors([
                'subscription' => 'Nenhuma assinatura foi encontrada.',
            ]);
        }

        try {

            $mercadoPago->cancelSubscription(
                $subscription->provider_subscription_id
            );

            $subscription->update([
                'status' => 'cancelled',
                'canceled_at' => now(),
            ]);

            $request->user()->update([
                'subscription_status' => 'inactive',
                'subscription_ends_at' => now(),
            ]);

            return redirect()
                ->route('subscription.show')
                ->with('success', 'Sua assinatura foi cancelada.');

        } catch (Throwable $exception) {

            report($exception);

            return back()->withErrors([
                'subscription' => 'Não foi possível cancelar a assinatura.',
            ]);
        }
    }
}