<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CotaSmart')</title>
    <link rel="stylesheet" href="{{ asset('css/cotasmart.css') }}">
    @stack('head')
</head>
<body class="app-body">
    <aside class="sidebar">
        <a class="wordmark light" href="{{ route('app.dashboard') }}">
            <i></i>
            CotaSmart
        </a>

        <div class="workspace">
            <span>ESPAÇO DE TRABALHO</span>
            <strong>{{ auth()->user()->name }}</strong>
            <small>{{ ucfirst(auth()->user()->plan ?? 'sem plano') }}</small>
        </div>

        <nav class="side-nav">
            <a class="{{ request()->routeIs('app.dashboard') ? 'active' : '' }}"
               href="{{ route('app.dashboard') }}">
                Visão geral
            </a>

            <a class="{{ request()->routeIs('app.search') || request()->routeIs('app.products.*') ? 'active' : '' }}"
               href="{{ route('app.dashboard') }}#buscar">
                Consultar preços
            </a>

            @if(auth()->user()->isAdmin())
                <span>ADMINISTRAÇÃO</span>

                <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                   href="{{ route('admin.dashboard') }}">
                    Indicadores
                </a>

                <a class="{{ request()->routeIs('admin.produtos.*') ? 'active' : '' }}"
                   href="{{ route('admin.produtos.index') }}">
                    Produtos
                </a>

                <a class="{{ request()->routeIs('admin.ofertas.*') ? 'active' : '' }}"
                   href="{{ route('admin.ofertas.index') }}">
                    Ofertas
                </a>

                <a class="{{ request()->routeIs('admin.catalog.*') ? 'active' : '' }}"
                   href="{{ route('admin.catalog.index') }}">
                    Catálogo
                </a>
            @endif
        </nav>

        <div class="sidebar-bottom">
            <a href="{{ route('subscription.show') }}">Assinatura</a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Sair da conta</button>
            </form>
        </div>
    </aside>

    <div class="app-main">
        <header class="app-top">
            <span class="mobile-brand">CotaSmart</span>

            <div class="user-dot">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
        </header>

        @if(session('success'))
            <div class="notice success">{{ session('success') }}</div>
        @endif

        @if(session('warning'))
            <div class="notice warning">{{ session('warning') }}</div>
        @endif

        @if($errors->any())
            <div class="notice error">{{ $errors->first() }}</div>
        @endif

        <main class="app-content">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>