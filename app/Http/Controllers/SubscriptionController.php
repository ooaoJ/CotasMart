<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function show(): View { return view('subscription.show'); }
}

