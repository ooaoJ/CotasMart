@extends('layouts.app')

@section('title', 'Assinatura — CotaSmart')

@section('content')

    <div class="app-heading">
        <div>
            <span class="kicker">ASSINATURA</span>
            <h1>CotaSmart</h1>
            <p>Gerencie seu acesso à plataforma.</p>
        </div>
    </div>

    <div class="subscription-status">

        <div>
            <span>STATUS ATUAL</span>

            @if(auth()->user()->subscription_status === 'active')

                <strong>ATIVA</strong>
                <p>Sua assinatura está ativa.</p>

            @elseif(
                auth()->user()->subscription_status === 'trial'
                && auth()->user()->trial_ends_at?->isFuture()
            )

                <strong>TESTE GRATUITO</strong>

                <p>
                    Seu período gratuito termina em
                    {{ auth()->user()->trial_ends_at->format('d/m/Y') }}.
                </p>

            @else

                <strong>INATIVA</strong>

                <p>
                    Seu teste gratuito terminou.
                    Assine para continuar usando o CotaSmart.
                </p>

            @endif
        </div>


        <div>
            <span>VALOR</span>

            <strong>
                R$ {{ number_format($price, 2, ',', '.') }}
            </strong>

            <p>por mês</p>
        </div>

    </div>


    <div class="billing-note">

        @if(auth()->user()->subscription_status === 'active')

            <h2>Sua assinatura está ativa</h2>

            @if($subscription?->next_payment_at)

                <p>
                    Próxima cobrança:
                    {{ $subscription->next_payment_at->format('d/m/Y') }}
                </p>

            @endif

            <form
                method="POST"
                action="{{ route('subscription.cancel') }}"
                onsubmit="return confirm('Tem certeza que deseja cancelar sua assinatura?')"
            >
                @csrf
                @method('DELETE')

                <button type="submit">
                    Cancelar assinatura
                </button>
            </form>


        @elseif(
            auth()->user()->subscription_status === 'trial'
            && auth()->user()->trial_ends_at?->isFuture()
        )

            <h2>Seu teste gratuito está ativo</h2>

            <p>
                Você ainda possui acesso completo ao CotaSmart.
                Caso queira, já pode garantir sua assinatura mensal.
            </p>

            <form
                method="POST"
                action="{{ route('subscription.checkout') }}"
            >
                @csrf

                <button type="submit">
                    Assinar por
                    R$ {{ number_format($price, 2, ',', '.') }}/mês
                </button>
            </form>


        @else

            <h2>Continue usando o CotaSmart</h2>

            <p>
                Assine para recuperar seu acesso a todos os recursos
                da plataforma.
            </p>

            <div style="margin: 24px 0;">

                <strong style="font-size: 32px;">
                    R$ {{ number_format($price, 2, ',', '.') }}
                </strong>

                <span>/mês</span>

            </div>

            <form
                method="POST"
                action="{{ route('subscription.checkout') }}"
            >
                @csrf

                <button type="submit">
                    Assinar CotaSmart
                </button>
            </form>

        @endif

    </div>

@endsection