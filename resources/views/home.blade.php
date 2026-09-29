@extends('layouts.marketing')

@section('title', 'CotaSmart — inteligência de preços')

@section('content')
    <section class="landing-hero">
        <div class="shell hero-grid">
            <div>
                <span class="kicker">INTELIGÊNCIA DE PREÇOS</span>

                <h1>
                    O preço de hoje só faz sentido quando você conhece o histórico.
                </h1>

                <p>
                    Centralize ofertas, acompanhe variações e tome decisões de
                    compra com dados rastreáveis.
                </p>

                <div class="hero-actions">
                    @auth
                        <a class="btn primary"
                           href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('app.dashboard') }}">
                            Abrir plataforma
                        </a>
                    @else
                        <a class="btn primary" href="{{ route('register') }}">
                            Começar teste gratuito
                        </a>
                    @endauth

                    <a class="text-link" href="#produto">
                        Conhecer a plataforma →
                    </a>
                </div>

                @guest
                    <small>7 dias gratuitos. Sem cartão de crédito.</small>
                @endguest
            </div>

            <div class="data-card">
                <div class="data-head">
                    <span>Notebook Acer Aspire 5</span>
                    <b>Atualizado agora</b>
                </div>

                <div class="data-price">
                    <small>MENOR PREÇO</small>
                    <strong>R$ 3.749,00</strong>
                    <em>↓ 8,4% em 30 dias</em>
                </div>

                <div class="mini-chart">
                    <span style="height: 72%"></span>
                    <span style="height: 64%"></span>
                    <span style="height: 78%"></span>
                    <span style="height: 54%"></span>
                    <span style="height: 49%"></span>
                    <span style="height: 38%"></span>
                    <span style="height: 31%"></span>
                </div>

                <div class="data-sources">
                    <span>5 ofertas monitoradas</span>
                    <span>Últimos 90 dias</span>
                </div>
            </div>
        </div>
    </section>

    <section class="proof">
        <div class="shell proof-grid">
            <div>
                <strong>Histórico confiável</strong>
                <p>Cada coleta permanece registrada. Nenhum preço anterior é sobrescrito.</p>
            </div>

            <div>
                <strong>Comparação clara</strong>
                <p>Produto, frete, vendedor e condição de pagamento no mesmo lugar.</p>
            </div>

            <div>
                <strong>Atualização inteligente</strong>
                <p>Itens mais procurados recebem prioridade automática de atualização.</p>
            </div>
        </div>
    </section>

    <section id="produto" class="shell feature-section">
        <div class="feature-intro">
            <span class="kicker">UM SISTEMA, NÃO UMA PLANILHA</span>
            <h2>Da consulta até a decisão, sem perder a origem do dado.</h2>
        </div>

        <div class="feature-list">
            <article>
                <span>01</span>
                <div>
                    <h3>Consulte</h3>
                    <p>Pesquise por produto, modelo ou característica técnica.</p>
                </div>
            </article>

            <article>
                <span>02</span>
                <div>
                    <h3>Compare</h3>
                    <p>Veja ofertas válidas e o custo total com frete.</p>
                </div>
            </article>

            <article>
                <span>03</span>
                <div>
                    <h3>Analise</h3>
                    <p>Entenda a variação pelo histórico e identifique preços fora do padrão.</p>
                </div>
            </article>
        </div>
    </section>

    <section class="closing">
        <div class="shell">
            <h2>Decisões melhores começam com dados melhores.</h2>

            @auth
                <a class="btn light"
                   href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('app.dashboard') }}">
                    Acessar plataforma
                </a>
            @else
                <a class="btn light" href="{{ route('register') }}">
                    Criar minha conta
                </a>
            @endauth
        </div>
    </section>
@endsection

