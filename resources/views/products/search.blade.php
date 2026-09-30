@extends('layouts.app')

@section('title', 'Resultados — CotaSmart')

@section('content')
<style>
    .search-page {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
        padding: 42px 0 70px;
    }

    .search-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 28px;
    }

    .search-header h1 {
        margin: 6px 0 8px;
        font-size: clamp(30px, 4vw, 48px);
        line-height: 1.05;
    }

    .search-header p {
        margin: 0;
        color: #64748b;
    }

    .search-kicker {
        color: #2563eb;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .12em;
    }

    .search-form {
        display: flex;
        gap: 10px;
        width: min(100%, 720px);
        margin-bottom: 28px;
        padding: 8px;
        border: 1px solid #dbe3ef;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 12px 35px rgba(15, 23, 42, .06);
    }

    .search-form input {
        flex: 1;
        min-width: 0;
        border: 0;
        outline: 0;
        padding: 13px 15px;
        font: inherit;
        background: transparent;
    }

    .search-form button {
        border: 0;
        border-radius: 11px;
        padding: 0 22px;
        color: #fff;
        background: #1769e0;
        font-weight: 700;
        cursor: pointer;
    }

    .search-form button:disabled {
        cursor: wait;
        opacity: .7;
    }

    .search-message {
        margin-bottom: 22px;
        padding: 14px 16px;
        border-radius: 12px;
        color: #854d0e;
        background: #fef9c3;
        border: 1px solid #fde68a;
    }

    .search-status {
        margin-bottom: 22px;
        color: #64748b;
        font-size: 14px;
    }

    .search-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }

    .product-result {
        display: flex;
        flex-direction: column;
        overflow: hidden;
        min-height: 390px;
        color: inherit;
        text-decoration: none;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: #fff;
        transition: transform .2s, box-shadow .2s;
    }

    .product-result:hover {
        transform: translateY(-3px);
        box-shadow: 0 18px 45px rgba(15, 23, 42, .1);
    }

    .product-image {
        display: grid;
        place-items: center;
        height: 190px;
        padding: 20px;
        background: #f8fafc;
    }

    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .product-placeholder {
        color: #94a3b8;
        font-weight: 700;
    }

    .product-content {
        display: flex;
        flex: 1;
        flex-direction: column;
        padding: 20px;
    }

    .product-brand {
        margin-bottom: 8px;
        color: #2563eb;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .product-content h2 {
        margin: 0 0 7px;
        font-size: 18px;
        line-height: 1.35;
    }

    .product-model {
        color: #64748b;
        font-size: 13px;
    }

    .product-footer {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 12px;
        margin-top: auto;
        padding-top: 22px;
    }

    .product-price small {
        display: block;
        margin-bottom: 3px;
        color: #64748b;
    }

    .product-price strong {
        color: #0f172a;
        font-size: 23px;
    }

    .product-offers {
        color: #64748b;
        font-size: 13px;
        text-align: right;
    }

    .empty-results {
        padding: 50px 25px;
        text-align: center;
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
        background: #f8fafc;
    }

    .empty-results h2 {
        margin-top: 0;
    }

    .search-pagination {
        margin-top: 30px;
    }

    @media (max-width: 900px) {
        .search-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 620px) {
        .search-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .search-form {
            align-items: stretch;
            flex-direction: column;
        }

        .search-form button {
            min-height: 48px;
        }

        .search-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="search-page">
    <header class="search-header">
        <div>
            <span class="search-kicker">RESULTADOS</span>

            <h1>{{ $query }}</h1>

            <p>
                {{ $products->total() }}
                {{ $products->total() === 1 ? 'produto encontrado' : 'produtos encontrados' }}
            </p>
        </div>
    </header>

    <form
        id="product-search-form"
        class="search-form"
        action="{{ route('app.search') }}"
        method="GET"
    >
        <input
            type="search"
            name="q"
            value="{{ $query }}"
            minlength="2"
            maxlength="255"
            required
            placeholder="Ex.: Notebook Dell com 16GB até 4 mil"
            autocomplete="off"
        >

        <button id="search-button" type="submit">
            Pesquisar
        </button>
    </form>

    @if($warning)
        <div class="search-message">
            {{ $warning }}
        </div>
    @endif

    @if($externalSearchPerformed)
        <div class="search-status">
            Novas ofertas foram consultadas e adicionadas à base.
        </div>
    @else
        <div class="search-status">
            Resultados carregados da base do CotaSmart.
        </div>
    @endif

    @if($products->isNotEmpty())
        <section class="search-grid">
            @foreach($products as $product)
                <a
                    class="product-result"
                    href="{{ route('app.products.show', $product) }}"
                >
                    <div class="product-image">
                        @if($product->image)
                            <img
                                src="{{ $product->image }}"
                                alt="{{ $product->name }}"
                                loading="lazy"
                            >
                        @else
                            <span class="product-placeholder">
                                Sem imagem
                            </span>
                        @endif
                    </div>

                    <div class="product-content">
                        <span class="product-brand">
                            {{ $product->brand?->name ?? 'Marca não informada' }}
                        </span>

                        <h2>{{ $product->name }}</h2>

                        @if($product->model)
                            <span class="product-model">
                                Modelo: {{ $product->model }}
                            </span>
                        @endif

                        <footer class="product-footer">
                            <div class="product-price">
                                <small>A partir de</small>

                                <strong>
                                    @if($product->lowest_price !== null)
                                        R$ {{ number_format(
                                            $product->lowest_price,
                                            2,
                                            ',',
                                            '.'
                                        ) }}
                                    @else
                                        Sem preço
                                    @endif
                                </strong>
                            </div>

                            <span class="product-offers">
                                {{ $product->offers_count }}
                                {{ $product->offers_count === 1 ? 'oferta' : 'ofertas' }}
                            </span>
                        </footer>
                    </div>
                </a>
            @endforeach
        </section>

        <div class="search-pagination">
            {{ $products->links() }}
        </div>
    @else
        <section class="empty-results">
            <h2>Nenhum produto encontrado</h2>

            <p>
                Tente informar outra marca, modelo, memória ou faixa de preço.
            </p>
        </section>
    @endif
</main>

<script>
    const searchForm = document.getElementById(
        'product-search-form'
    );

    const searchButton = document.getElementById(
        'search-button'
    );

    searchForm.addEventListener('submit', () => {
        searchButton.disabled = true;
        searchButton.textContent = 'Pesquisando...';
    });
</script>
@endsection