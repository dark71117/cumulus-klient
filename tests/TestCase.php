<?php

namespace Tests;

use App\Models\Client;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->createKlientTables();
    }

    protected function createKlientTables(): void
    {
        if (Schema::hasTable('z_klienci')) {
            return;
        }
        Schema::create('z_klienci', function (Blueprint $table) {
            $table->increments('id');
            $table->string('login');
            $table->string('haslo');
            $table->string('auth_key')->nullable();
            $table->string('nazwa');
            $table->integer('aktywny')->default(1);
            $table->integer('grupa')->default(0);
            $table->integer('IMGW')->default(0);
            $table->integer('mapaWarunkow')->default(0);
            $table->integer('GDDKIAwoj')->default(0);
            $table->integer('GDDKIAdrogi')->default(0);
            $table->integer('zdjeciaSat')->default(0);
            $table->integer('prognozaDzis')->default(0);
            $table->integer('prognozaJutro')->default(0);
            $table->integer('prognozaDluga')->default(0);
            $table->integer('prognozaInna')->default(0);
            $table->integer('mapaPrognozy')->default(0);
            $table->integer('ostrzezeniaTXT')->default(0);
            $table->integer('wojDepesze')->default(0);
            $table->float('geo_lat')->nullable();
            $table->float('geo_lon')->nullable();
            $table->unsignedTinyInteger('mapaOkresy')->default(12);
        });
        Schema::create('ip_klienci', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_klient');
            $table->string('opis');
            $table->string('ip1');
            $table->string('ip2');
        });
        Schema::create('ip_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_klient');
            $table->string('ip');
            $table->string('host')->nullable();
            $table->dateTime('czas');
            $table->integer('licznik')->default(1);
        });
        $this->createDepeszeTable('z_depesze');
        $this->createDepeszeTable('z_depesze_archiwum');
        $this->createDepeszeTable('z_depesze_new');
        $this->createDepeszeTable('z_depesze_archiwum_new');
        Schema::create('z_listastacji', function (Blueprint $table) {
            $table->unsignedInteger('idStacji')->primary();
            $table->string('nazwaStacji')->nullable();
            $table->string('region')->nullable();
            $table->integer('aktywna')->default(1);
            $table->integer('pozX')->default(0);
            $table->integer('pozY')->default(0);
            $table->integer('pozWX')->default(0);
            $table->integer('pozWY')->default(0);
        });
        Schema::create('z_uprawnieniadepesze', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('idKlienta');
            $table->unsignedInteger('idStacji');
            $table->integer('aktywna')->default(1);
            $table->integer('cisnienie')->default(0);
            $table->integer('lp')->default(0);
        });
        Schema::create('z_prognozydzis', function (Blueprint $table) {
            $table->increments('id');
            $table->text('tresc')->nullable();
            $table->string('temat')->nullable();
            $table->date('data')->nullable();
            $table->integer('prognozaPL')->default(0);
            $table->integer('robocza')->default(0);
            $table->integer('biezaca')->default(0);
        });
        Schema::create('z_prognozydzisklienci', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('idPrognozy');
            $table->unsignedInteger('idKlienta');
            $table->integer('biezaca')->default(0);
        });
        Schema::create('z_ostrzezenia', function (Blueprint $table) {
            $table->increments('id');
            $table->text('tresc')->nullable();
            $table->date('data')->nullable();
            $table->integer('biezaca')->default(0);
        });
        Schema::create('z_ostrzezeniaklienci', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('idPrognozy');
            $table->unsignedInteger('idKlienta');
            $table->integer('biezaca')->default(0);
        });
        Schema::create('g_blokada', function (Blueprint $table) {
            $table->increments('id');
            $table->text('tekst')->nullable();
        });
    }

    protected function makeClient(array $overrides = []): Client
    {
        return Client::query()->create(array_merge([
            'login' => 'test',
            'haslo' => Hash::make('secret'),
            'auth_key' => str_repeat('a', 32),
            'nazwa' => 'TEST',
            'aktywny' => 1,
            'grupa' => 0,
            'IMGW' => 1,
            'geo_lat' => 51.1,
            'geo_lon' => 17.0,
        ], $overrides));
    }

    private function createDepeszeTable(string $name): void
    {
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('idStacji')->nullable();
            $table->dateTime('termin')->nullable();
            $table->float('temp')->nullable();
            $table->float('tempOdcz')->nullable();
            $table->string('zachmurzenieTXT')->nullable();
            $table->string('zjawiskoTXT')->nullable();
            $table->string('widzialnosc')->nullable();
            $table->string('wiatr')->nullable();
            $table->string('zjawiskoKolor')->nullable();
            $table->string('zjawisko')->nullable();
            $table->string('zjawiskoPoprzednie')->nullable();
            $table->string('cisnienieTXT')->nullable();
            $table->integer('zachmurzenie')->nullable();
            $table->string('zjawiskoIkona')->nullable();
            $table->string('zrodlo')->nullable();
            $table->text('synop')->nullable();
        });
    }
}
