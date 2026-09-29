<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'CotaSmart')</title>
    <link rel="stylesheet" href="{{ asset('css/cotasmart.css') }}">
</head>
<body class="marketing-body">
    <header class="marketing-header">
        <div class="shell nav">
            <a class="wordmark" href="{{ route('home') }}">
                <i></i>
                CotaSmart
            </a>

            <nav>
                <a href="{{ route('home') }}#produto">Produto</a>
                <a href="{{ route('pricing') }}">Planos</a>

                @auth
                    <a class="btn dark"
                       href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('app.dashboard') }}">
                        Abrir plataforma
                    </a>
                @else
                    <a href="{{ route('login') }}">Entrar</a>
                    <a class="btn dark" href="{{ route('register') }}">
                        Testar gratuitamente
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    @if($errors->any())
        <div class="shell notice error">{{ $errors->first() }}</div>
    @endif

    <main>
        @yield('content')
    </main>

    <footer class="marketing-footer">
        <div class="shell footer-grid">
            <div>
                <a class="wordmark light" href="{{ route('home') }}">
                    <i></i>
                    CotaSmart
                </a>
                <p>Histórico e inteligência para decisões de compra.</p>
            </div>

            <p>© {{ date('Y') }} CotaSmart</p>
        </div>
    </footer>
</body>
</html>

