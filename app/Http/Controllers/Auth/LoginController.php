<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\LoginService;
use App\Support\CustomerContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private LoginService $loginService) {}

    public function show(Request $request): View|RedirectResponse
    {
        if (auth()->check() && ! empty(CustomerContext::get()['login'])) {
            return app(\App\Http\Controllers\KlientController::class)->index();
        }
        if ($this->loginService->loginFromRememberCookie($request)) {
            return app(\App\Http\Controllers\KlientController::class)->index();
        }

        return view('klient.login', ['ipAddress' => $request->ip()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);
        $this->loginService->attempt(
            $data['login'],
            $data['password'],
            $request->boolean('remember'),
            $request
        );

        return redirect('/klient');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->loginService->logout($request);

        return redirect('/klient');
    }
}
