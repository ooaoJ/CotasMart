@extends('layouts.app')
@section('title','Resultados — CotaSmart')
@section('content')
<div class="app-heading"><div><span class="kicker">CONSULTA</span><h1>Resultados para “{{ $query }}”</h1><p>{{ $products->total() }} produto(s) correspondente(s).</p></div></div>
<form class="main-search" action="{{ route('app.search') }}"><input name="q" value="{{ $query }}" minlength="2" required><button>Nova consulta</button></form>
<section class="content-block"><div class="clean-grid">@forelse($products as $product)<a class="clean-card" href="{{ route('app.products.show',$product) }}"><span>{{ $product->brand?->name }} · {{ $product->category?->name }}</span><h3>{{ $product->name }}</h3><small>{{ $product->model }} · {{ $product->offers_count }} oferta(s)</small><div><b>{{ $product->lowest_price?'R$ '.number_format($product->lowest_price,2,',','.'):'Sem preço disponível' }}</b><em>Analisar →</em></div></a>@empty<div class="blank-state">Nenhum produto encontrado. Futuramente essa busca poderá entrar automaticamente na fila de coleta.</div>@endforelse</div><div class="pagination">{{ $products->links() }}</div></section>
@endsection

