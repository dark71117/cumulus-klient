<?php

namespace App\Services\Auth;

use App\Models\Client;
use App\Models\IpLog;
use App\Models\IpRange;
use App\Models\PanelUser;
use App\Support\CustomerContext;
use App\Support\PanelAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;

class LoginService
{
    public function attempt(string $login, string $password, bool $remember, Request $request): Client
    {
        PanelUser::ensureTable();
        Client::ensureAccountColumns();
        $panelUser = PanelUser::query()->where('login', $login)->first();
        if ($panelUser) {
            if (! $panelUser->isActive() || ! $panelUser->passwordMatches($password)) {
                throw ValidationException::withMessages([
                    'login' => 'Błędny login lub hasło.',
                ]);
            }
            $client = $this->activeClient((int) $panelUser->id_klient, $request);
            $this->startSession($client, $request, $remember, $panelUser);

            return $client;
        }

        $client = Client::findActiveByLogin($login);
        if (! $client || ! $client->passwordMatches($password)) {
            throw ValidationException::withMessages([
                'login' => 'Błędny login lub hasło.',
            ]);
        }
        if ($this->ipIsBlocked($client->id, $request->ip())) {
            throw ValidationException::withMessages([
                'login' => 'Błędny login lub hasło.',
            ]);
        }

        $this->startSession($client, $request, $remember, null);
        if ($client->isAdmin()) {
            $this->impersonateSoleClient();
        }

        return $client;
    }

    public function loginFromRememberCookie(Request $request): bool
    {
        $raw = $request->cookie('cumulus_remember');
        if (! $raw) {
            return false;
        }

        $data = json_decode($raw, true);
        if (! is_array($data) || ($data['remember'] ?? 0) != 1) {
            return false;
        }

        if (($data['kind'] ?? '') === 'user') {
            return $this->loginUserFromCookie($data, $request);
        }

        $client = Client::findActiveByAuthKey((string) ($data['auth_key'] ?? ''));
        if (! $client || (int) $client->id !== (int) ($data['id'] ?? 0)) {
            return false;
        }

        if ($this->ipIsBlocked($client->id, $request->ip())) {
            return false;
        }

        Auth::login($client, false);
        CustomerContext::put($client);
        PanelAccess::forget();
        if ($client->isAdmin()) {
            $this->impersonateSoleClient();
        }
        $this->recordIp($client, $request->ip());

        return true;
    }

    private function loginUserFromCookie(array $data, Request $request): bool
    {
        PanelUser::ensureTable();
        $panelUser = PanelUser::findActiveByAuthKey((string) ($data['auth_key'] ?? ''));
        if (! $panelUser || (int) $panelUser->id !== (int) ($data['id'] ?? 0)) {
            return false;
        }
        $client = Client::query()->where('id', $panelUser->id_klient)->where('aktywny', 1)->first();
        if (! $client || $this->ipIsBlocked($client->id, $request->ip())) {
            return false;
        }

        Auth::login($client, false);
        CustomerContext::put($client);
        PanelAccess::put($panelUser);
        $this->recordIp($client, $request->ip());

        return true;
    }

    private function activeClient(int $clientId, Request $request): Client
    {
        $client = Client::query()->where('id', $clientId)->where('aktywny', 1)->first();
        if (! $client || $this->ipIsBlocked($client->id, $request->ip())) {
            throw ValidationException::withMessages([
                'login' => 'Błędny login lub hasło.',
            ]);
        }

        return $client;
    }

    private function startSession(Client $client, Request $request, bool $remember, ?PanelUser $panelUser): void
    {
        Auth::login($client, false);
        $request->session()->regenerate();
        CustomerContext::put($client);
        if ($panelUser) {
            PanelAccess::put($panelUser);
            $this->rememberUser($panelUser, $remember);
        } else {
            PanelAccess::forget();
            $this->rememberCookie($client, $remember);
        }
        $this->recordIp($client, $request->ip());
    }

    private function rememberUser(PanelUser $user, bool $remember): void
    {
        if ($remember && $user->auth_key) {
            Cookie::queue('cumulus_remember', json_encode([
                'remember' => 1,
                'kind' => 'user',
                'id' => $user->id,
                'auth_key' => $user->auth_key,
            ]), 60 * 24 * 30);

            return;
        }

        Cookie::queue(Cookie::forget('cumulus_remember'));
    }

    public function impersonate(int $id): void
    {
        $client = Client::query()->where('id', $id)->where('aktywny', 1)->first();
        if ($client) {
            CustomerContext::put($client);
        }
    }

    public function impersonateSoleClient(): bool
    {
        if (! CustomerContext::isAdmin()) {
            return false;
        }
        $clients = Client::query()
            ->where('aktywny', 1)
            ->where('nazwa', '<>', 'admin')
            ->orderBy('nazwa')
            ->get(['id']);
        if ($clients->count() !== 1) {
            return false;
        }
        $this->impersonate((int) $clients->first()->id);

        return true;
    }

    public function keepPanelSession(Request $request): bool
    {
        $current = PanelAccess::current();
        if ($current === null) {
            return $this->refreshFirmSession();
        }

        PanelUser::ensureTable();
        $user = PanelUser::query()->find((int) ($current['id'] ?? 0));
        $clientId = (int) (auth()->id() ?? 0);
        if (! $user || ! $user->isActive() || (int) $user->id_klient !== $clientId) {
            $this->logout($request);

            return false;
        }

        PanelAccess::put($user);

        return true;
    }

    private function refreshFirmSession(): bool
    {
        $auth = auth()->user();
        if (! $auth instanceof Client || $auth->isAdmin()) {
            return true;
        }

        Client::ensureAccountColumns();
        $fresh = Client::query()->find($auth->id);
        if (! $fresh || ! $fresh->isActive()) {
            $this->logout(request());

            return false;
        }

        Auth::setUser($fresh);
        if (CustomerContext::id() === (int) $fresh->id) {
            CustomerContext::put($fresh);
        }

        return true;
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Cookie::queue(Cookie::forget('cumulus_remember'));
    }

    private function ipIsBlocked(int $clientId, ?string $ip): bool
    {
        if (! config('cumulus.login_ip') || ! $ip) {
            return false;
        }

        foreach (IpRange::query()->where('id_klient', $clientId)->get() as $range) {
            if ($range->contains($ip)) {
                return true;
            }
        }

        return false;
    }

    private function rememberCookie(Client $client, bool $remember): void
    {
        if ($remember && $client->auth_key) {
            Cookie::queue('cumulus_remember', json_encode([
                'remember' => 1,
                'id' => $client->id,
                'auth_key' => $client->auth_key,
            ]), 60 * 24 * 30);
        } else {
            Cookie::queue(Cookie::forget('cumulus_remember'));
        }
    }

    private function recordIp(Client $client, ?string $ip): void
    {
        if (! config('cumulus.save_ip') || $client->isAdmin() || ! $ip) {
            return;
        }

        $log = IpLog::query()->where('id_klient', $client->id)->where('ip', $ip)->first();
        if ($log) {
            $log->licznik = (int) $log->licznik + 1;
            $log->host = @gethostbyaddr($ip) ?: '';
            $log->czas = now()->format('Y-m-d H:i:s');
            $log->save();

            return;
        }

        IpLog::query()->insert([
            'id_klient' => $client->id,
            'ip' => $ip,
            'host' => @gethostbyaddr($ip) ?: '',
            'licznik' => 1,
            'czas' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
