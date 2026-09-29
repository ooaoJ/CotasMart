<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Source;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfferManagementController extends Controller
{
    public function index(): View
    {
        $offers = Offer::with(['product', 'source'])->latest('last_checked_at')->paginate(20);
        return view('admin.offers.index', compact('offers'));
    }

    public function create(): View
    {
        return view('admin.offers.form', ['offer' => new Offer(), 'products' => Product::orderBy('name')->get(), 'sources' => Source::where('active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['last_checked_at'] = now();
        Offer::create($data);
        return redirect()->route('admin.ofertas.index')->with('success', 'Oferta cadastrada e histórico registrado.');
    }

    public function edit(Offer $oferta): View
    {
        return view('admin.offers.form', ['offer' => $oferta, 'products' => Product::orderBy('name')->get(), 'sources' => Source::where('active', true)->orderBy('name')->get()]);
    }

    public function update(Request $request, Offer $oferta): RedirectResponse
    {
        $data = $this->validated($request);
        $data['last_checked_at'] = now();
        $oferta->update($data);
        return redirect()->route('admin.ofertas.index')->with('success', 'Oferta atualizada.');
    }

    public function destroy(Offer $oferta): RedirectResponse
    {
        $oferta->delete();
        return back()->with('success', 'Oferta removida.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'product_id' => ['required', 'exists:products,id'], 'source_id' => ['required', 'exists:sources,id'],
            'external_identifier' => ['nullable', 'string', 'max:255'], 'title' => ['required', 'string', 'max:255'],
            'seller' => ['nullable', 'string', 'max:255'], 'url' => ['required', 'url'],
            'current_price' => ['required', 'numeric', 'min:0'], 'shipping_price' => ['nullable', 'numeric', 'min:0'],
            'installment_price' => ['nullable', 'numeric', 'min:0'], 'payment_condition' => ['nullable', 'string', 'max:255'],
            'availability' => ['nullable', 'boolean'],
        ]) + ['availability' => $request->boolean('availability')];
    }
}

