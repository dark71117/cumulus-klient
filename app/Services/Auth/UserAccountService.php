<?php

namespace App\Services\Auth;

use App\Models\Client;
use App\Models\PanelUser;
use App\Support\CustomerContext;
use App\Support\PanelAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserAccountService
{
    public function save(int $clientId, array $data, ?PanelUser $user = null): PanelUser
    {
        $user ??= new PanelUser(['id_klient' => $clientId]);
        $user->id_klient = $clientId;
        $user->login = $data['login'];
        $user->nazwa = trim((string) ($data['nazwa'] ?? ''));
        $user->poziom = (int) $data['poziom'];
        $user->aktywny = ! empty($data['aktywny']) ? 1 : 0;
        foreach (array_keys(PanelAccess::MODULES) as $module) {
            $user->{'mod_'.$module} = ! empty($data['modules'][$module]) ? 1 : 0;
        }
        if ($user->exists === false || ($data['haslo'] ?? '') !== '') {
            $user->haslo = Hash::make((string) $data['haslo']);
            $user->auth_key = bin2hex(random_bytes(16));
        }
        if (! $user->auth_key) {
            $user->auth_key = bin2hex(random_bytes(16));
        }
        $user->save();

        if ((int) $user->id === PanelAccess::id()) {
            PanelAccess::put($user);
        }

        return $user;
    }

    public function changeCredentials(PanelUser $user, string $login, string $password): void
    {
        $user->login = $login;
        if ($password !== '') {
            $user->haslo = Hash::make($password);
            $user->auth_key = bin2hex(random_bytes(16));
        }
        $user->save();
        PanelAccess::put($user);
    }

    public function saveFirm(Client $client, array $data): Client
    {
        Client::ensureAccountColumns();
        $client->login = $data['login'];
        $client->poziom_konta = PanelAccess::LEVEL_USER;
        if (array_key_exists('aktywny', $data)) {
            $client->login_aktywny = ! empty($data['aktywny']) ? 1 : 0;
        }
        foreach (array_keys(PanelAccess::MODULES) as $module) {
            $client->{'mod_'.$module} = ! empty($data['modules'][$module]) ? 1 : 0;
        }
        if (($data['haslo'] ?? '') !== '') {
            $client->haslo = Hash::make((string) $data['haslo']);
            $client->auth_key = bin2hex(random_bytes(16));
        }
        $client->save();
        $fresh = $client->fresh();
        if ((int) auth()->id() === (int) $fresh->id) {
            Auth::setUser($fresh);
        }
        if (CustomerContext::id() === (int) $fresh->id) {
            CustomerContext::put($fresh);
        }

        return $fresh;
    }
}
