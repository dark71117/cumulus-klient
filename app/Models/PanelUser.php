<?php

namespace App\Models;

use App\Support\PanelAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class PanelUser extends Model
{
    protected $table = 'z_uzytkownicy';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['haslo', 'auth_key'];

    public function isActive(): bool
    {
        return (int) $this->aktywny === 1;
    }

    public function passwordMatches(string $password): bool
    {
        return Hash::check($password, (string) $this->haslo);
    }

    public function levelLabel(): string
    {
        return PanelAccess::LEVELS[(int) $this->poziom] ?? (string) $this->poziom;
    }

    public function moduleLabels(): string
    {
        $labels = [];
        foreach (PanelAccess::MODULES as $key => $label) {
            if ((int) $this->{'mod_'.$key} === 1) {
                $labels[] = $label;
            }
        }

        return $labels === [] ? 'brak' : implode(', ', $labels);
    }

    public static function findActiveByLogin(string $login): ?self
    {
        return static::query()->where('login', $login)->where('aktywny', 1)->first();
    }

    public static function findActiveByAuthKey(string $authKey): ?self
    {
        if ($authKey === '') {
            return null;
        }

        return static::query()->where('auth_key', $authKey)->where('aktywny', 1)->first();
    }

    public static function ensureTable(): void
    {
        static $ready = false;
        if ($ready || Schema::hasTable('z_uzytkownicy')) {
            $ready = true;

            return;
        }
        Schema::create('z_uzytkownicy', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_klient')->index();
            $table->string('login', 40)->unique();
            $table->string('haslo');
            $table->string('auth_key', 64)->nullable();
            $table->string('nazwa', 100)->default('');
            $table->unsignedTinyInteger('poziom')->default(1);
            $table->unsignedTinyInteger('aktywny')->default(1);
            $table->unsignedTinyInteger('mod_tabela')->default(1);
            $table->unsignedTinyInteger('mod_mapa')->default(1);
            $table->unsignedTinyInteger('mod_gfs')->default(1);
            $table->unsignedTinyInteger('mod_analiza')->default(1);
            $table->unsignedTinyInteger('mod_dodatki')->default(1);
        });
        $ready = true;
    }
}
