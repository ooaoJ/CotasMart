<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Search;
use Illuminate\View\View;

class UserDashboardController extends Controller
{
    public function index(): View
    {
        $popular = Product::with(['brand:id,name'])->withMin(['offers as lowest_price' => fn ($q) => $q->where('availability', true)], 'current_price')->where('status', 'active')->orderByDesc('popularity_score')->limit(6)->get();
        $recentSearches = Search::where('user_id', auth()->id())->latest('searched_at')->limit(6)->get();
        return view('app.dashboard', compact('popular', 'recentSearches'));
    }
}

