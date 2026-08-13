<?php

namespace App\Http\Middleware;

use App\Support\CustomerContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(CustomerContext::isAdmin(), 403);

        return $next($request);
    }
}
