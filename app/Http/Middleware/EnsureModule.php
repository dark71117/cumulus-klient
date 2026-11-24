<?php

namespace App\Http\Middleware;

use App\Support\PanelAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless(PanelAccess::allows($module), 403);

        return $next($request);
    }
}
