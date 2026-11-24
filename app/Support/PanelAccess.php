<?php

namespace App\Support;

use App\Models\Client;
use App\Models\PanelUser;

class PanelAccess
{
    public const LEVEL_USER = 1;

    public const LEVEL_ADMIN = 2;

    public const LEVEL_SUPER = 3;

    public const LEVELS = [
        self::LEVEL_USER => 'Użytkownik',
        self::LEVEL_ADMIN => 'Administrator',
        self::LEVEL_SUPER => 'Super admin',
    ];

    public const MODULES = [
        'tabela' => 'Tabela',
        'mapa' => 'Mapa',
        'gfs' => 'Mapy GFS',
        'analiza' => 'Analiza',
        'dodatki' => 'Dodatki',
    ];

    private const TABS = [
        'imgwTab' => 'tabela',
        'imgwTableNewTab' => 'tabela',
        'imgwTableNew2Tab' => 'tabela',
        'imgwMapTab' => 'mapa',
        'imgwMapNewTab' => 'mapa',
        'imgwMapNew2Tab' => 'mapa',
        'analizaTab' => 'analiza',
        'gfsMapTab' => 'gfs',
        'sunTab' => 'dodatki',
        'warningTab' => 'dodatki',
        'archive5Tab' => 'dodatki',
        'gddkiaRegionTab' => 'dodatki',
        'gddkiaRoadTab' => 'dodatki',
        'animationTab' => 'dodatki',
        'forecast1Tab' => 'dodatki',
        'forecast2Tab' => 'dodatki',
        'forecast3Tab' => 'dodatki',
        'forecast4Tab' => 'dodatki',
        'forecast5Tab' => 'dodatki',
        'archive1Tab' => 'dodatki',
        'archive2Tab' => 'dodatki',
        'archive3Tab' => 'dodatki',
        'archive4Tab' => 'dodatki',
    ];

    public static function put(PanelUser $user): void
    {
        $data = [
            'id' => (int) $user->id,
            'id_klient' => (int) $user->id_klient,
            'login' => (string) $user->login,
            'poziom' => (int) $user->poziom,
        ];
        foreach (array_keys(self::MODULES) as $module) {
            $data['mod_'.$module] = (int) $user->{'mod_'.$module} === 1 ? 1 : 0;
        }
        session(['panel_user' => $data]);
    }

    public static function forget(): void
    {
        session()->forget('panel_user');
    }

    public static function current(): ?array
    {
        $data = session('panel_user');

        return is_array($data) && ! empty($data['id']) ? $data : null;
    }

    public static function id(): int
    {
        return (int) (self::current()['id'] ?? 0);
    }

    public static function isPanelUser(): bool
    {
        return self::current() !== null;
    }

    public static function allows(string $module): bool
    {
        if (! isset(self::MODULES[$module])) {
            return false;
        }
        if (self::isPanelUser()) {
            return (int) (self::current()['mod_'.$module] ?? 0) === 1;
        }
        if (CustomerContext::isAdmin()) {
            return true;
        }
        $customer = CustomerContext::get();
        $key = 'mod_'.$module;
        if (! array_key_exists($key, $customer)) {
            return true;
        }

        return (int) $customer[$key] === 1;
    }

    public static function allowsTab(string $tab): bool
    {
        $module = self::TABS[$tab] ?? null;

        return $module === null || self::allows($module);
    }

    public static function actorLevel(): int
    {
        if (self::isPanelUser()) {
            return (int) (self::current()['poziom'] ?? 0);
        }
        $user = auth()->user();
        if (! $user instanceof Client) {
            return 0;
        }
        if ($user->nazwa === 'admin') {
            return self::LEVEL_SUPER;
        }

        return (int) ($user->poziom_konta ?? self::LEVEL_USER);
    }

    public static function canManageUsers(): bool
    {
        return self::actorLevel() >= self::LEVEL_ADMIN;
    }

    public static function managedClientId(): int
    {
        if (self::isPanelUser()) {
            return (int) (self::current()['id_klient'] ?? 0);
        }
        $customer = CustomerContext::get();
        if (($customer['nazwa'] ?? '') === 'admin') {
            return 0;
        }

        return (int) ($customer['id'] ?? 0);
    }

    public static function assignableLevels(): array
    {
        $level = self::actorLevel();
        if ($level >= self::LEVEL_SUPER) {
            return self::LEVELS;
        }
        if ($level >= self::LEVEL_ADMIN) {
            return [
                self::LEVEL_USER => self::LEVELS[self::LEVEL_USER],
                self::LEVEL_ADMIN => self::LEVELS[self::LEVEL_ADMIN],
            ];
        }

        return [];
    }

    public static function canAssign(int $level): bool
    {
        return array_key_exists($level, self::assignableLevels());
    }

    public static function canEdit(PanelUser $user): bool
    {
        return (int) $user->id_klient === self::managedClientId()
            && self::canAssign((int) $user->poziom);
    }

    public static function canEditFirm(Client $client): bool
    {
        return (int) $client->id === self::managedClientId()
            && $client->nazwa !== 'admin'
            && self::canAssign((int) ($client->poziom_konta ?? self::LEVEL_USER));
    }

    public static function canDelete(PanelUser $user): bool
    {
        return self::canEdit($user) && (int) $user->id !== self::id();
    }
}
