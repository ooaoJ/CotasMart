@extends('layouts.app')
@section('title','Ofertas — Painel')
@section('content')
<section class="container section"><div class="admin-head"><div><a class="back" href="{{ route('admin.dashboard') }}">← Painel</a><h1>Ofertas</h1></div><a class="button" href="{{ route('admin.ofertas.create') }}">+ Nova oferta</a></div><div class="panel table-wrap"><table><thead><tr><th>Produto</th><th>Fonte</th><th>Preço</th><th>Frete</th><th>Disponível</th><th>Ações</th></tr></thead><tbody>@foreach($offers as $offer)<tr><td>{{ $offer->product->name }}</td><td>{{ $offer->source->name }}</td><td><strong>R$ {{ number_format($offer->current_price,2,',','.') }}</strong></td><td>R$ {{ number_format($offer->shipping_price ?? 0,2,',','.') }}</td><td>{{ $offer->availability?'Sim':'Não' }}</td><td class="actions"><a href="{{ route('admin.ofertas.edit',$offer) }}">Editar</a><form method="POST" action="{{ route('admin.ofertas.destroy',$offer) }}">@csrf @method('DELETE')<button class="danger-link">Excluir</button></form></td></tr>@endforeach</tbody></table></div>{{ $offers->links() }}</section>
@endsection

