<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class Client extends Authenticatable
{
    protected $table = 'z_klienci';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['haslo', 'auth_key'];

    public function getAuthPassword(): string
    {
        return (string) $this->haslo;
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function isActive(): bool
    {
        return (int) $this->aktywny === 1;
    }

    public function isAdmin(): bool
    {
        return $this->nazwa === 'admin';
    }

    public function passwordMatches(string $password): bool
    {
        return Hash::check($password, (string) $this->haslo);
    }

    public function ipRanges(): HasMany
    {
        return $this->hasMany(IpRange::class, 'id_klient');
    }

    public static function findActiveByLogin(string $login): ?self
    {
        return static::query()->where('login', $login)->where('aktywny', 1)->first();
    }

    public static function findActiveByAuthKey(string $authKey): ?self
    {
        return static::query()->where('auth_key', $authKey)->where('aktywny', 1)->first();
    }

    public static function ensureMapaOkresyColumn(): void
    {
        static $ready = false;
        if ($ready || Schema::hasColumn('z_klienci', 'mapaOkresy')) {
            $ready = true;

            return;
        }
        Schema::table('z_klienci', function (Blueprint $table) {
            $table->unsignedTinyInteger('mapaOkresy')->default(24);
        });
        $ready = true;
    }
}
