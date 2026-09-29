@extends('layouts.app')

@section('title', 'Assinatura — CotaSmart')

@section('content')
    <div class="app-heading">
        <div>
            <span class="kicker">ASSINATURA</span>
            <h1>Seu plano</h1>
            <p>Gerencie o acesso à plataforma.</p>
        </div>
    </div>

    <div class="subscription-status">
        <div>
            <span>STATUS ATUAL</span>
            <strong>{{ strtoupper(auth()->user()->subscription_status) }}</strong>

            @if(auth()->user()->subscription_status === 'trial')
                <p>
                    Seu teste termina em
                    {{ auth()->user()->trial_ends_at?->format('d/m/Y') }}.
                </p>
            @elseif(auth()->user()->subscription_status === 'active')
                <p>Sua assinatura está ativa.</p>
            @else
                <p>Escolha um plano para recuperar o acesso.</p>
            @endif
        </div>

        <div>
            <span>PLANO</span>
            <strong>{{ strtoupper(auth()->user()->plan ?? 'NENHUM') }}</strong>
        </div>
    </div>

    <div class="billing-note">
        <h2>Pagamento ainda não conectado</h2>
        <p>
            A proteção por assinatura já está funcionando. A próxima etapa é
            integrar Mercado Pago ou Stripe e ativar os planos somente após a
            confirmação segura do webhook.
        </p>
    </div>
@endsection

