<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Product;
use App\Models\Search;
use App\Models\Source;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'products' => Product::count(),
            'offers' => Offer::where('availability', true)->count(),
            'sources' => Source::where('active', true)->count(),
            'searches' => Search::where('searched_at', '>=', now()->subDays(30))->count(),
        ];
        $latestSearches = Search::latest('searched_at')->limit(8)->get();
        $staleProducts = Product::where('next_update_at', '<', now())->orWhereNull('last_updated_at')->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'latestSearches', 'staleProducts'));
    }
}
