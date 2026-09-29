<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasAccess()) {
            return redirect()->route('subscription.show')->with('warning', 'Escolha um plano para continuar usando o CotaSmart.');
        }

        return $next($request);
    }
}

