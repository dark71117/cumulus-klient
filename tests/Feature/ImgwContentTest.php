<?php

namespace Tests\Feature;

use App\Support\CustomerContext;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImgwContentTest extends TestCase
{
    public function test_table_without_pressure_does_not_crash(): void
    {
        $client = $this->makeClient(['IMGW' => 1, 'wojDepesze' => 1]);
        DB::table('z_listastacji')->insert([
            'idStacji' => 1,
            'nazwaStacji' => 'Wrocław',
            'region' => 'Dolnośląskie',
            'aktywna' => 1,
        ]);
        DB::table('z_depesze')->insert([
            'idStacji' => 1,
            'termin' => now()->format('Y-m-d H:00:00'),
            'temp' => 12.3,
            'tempOdcz' => 11.0,
            'zachmurzenieTXT' => 'małe',
            'zjawiskoTXT' => '',
            'widzialnosc' => '50',
            'wiatr' => 'pn / 4',
            'cisnienieTXT' => '',
        ]);
        DB::table('z_uprawnieniadepesze')->insert([
            'idKlienta' => $client->id,
            'idStacji' => 1,
            'aktywna' => 1,
            'cisnienie' => 0,
            'lp' => 1,
        ]);

        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/content', ['tab' => 'imgwTab'])
            ->assertOk()
            ->assertSee('Wrocław')
            ->assertDontSee('Błąd 404');
    }

    public function test_empty_depesze_shows_brak_danych(): void
    {
        $client = $this->makeClient(['IMGW' => 1]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/content', ['tab' => 'imgwTab'])
            ->assertOk()
            ->assertSee('Brak danych');
    }
}
