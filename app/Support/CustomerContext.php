<?php

namespace App\Support;

use App\Models\Client;

class CustomerContext
{
    public static function get(): array
    {
        return session('customer', []);
    }

    public static function id(): int
    {
        return (int) (self::get()['id'] ?? 0);
    }

    public static function put(Client $client): void
    {
        $data = $client->getAttributes();
        $data['prognozaTV'] = ((int) ($data['grupa'] ?? 0) === 2) ? 1 : 0;
        session(['customer' => $data]);
    }

    public static function flag(string $key): bool
    {
        return (int) (self::get()[$key] ?? 0) === 1;
    }

    public static function isAdmin(): bool
    {
        $user = auth()->user();

        return $user instanceof Client && $user->nazwa === 'admin';
    }
}
