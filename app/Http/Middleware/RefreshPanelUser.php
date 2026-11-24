<?php

namespace App\Http\Middleware;

use App\Services\Auth\LoginService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshPanelUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app(LoginService::class)->keepPanelSession($request)) {
            return redirect('/klient');
        }

        return $next($request);
    }
}
