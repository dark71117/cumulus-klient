<?php

namespace Tests\Feature;

use App\Support\CustomerContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OgimetSynopImportTest extends TestCase
{
    public function test_import_command_saves_full_synop_to_new_tables(): void
    {
        Http::fake([
            '*' => Http::response(<<<TXT
202608171800 0-20000-0-12205 12205 AAXX 17181 12205 11784 62601 10171 20140 30087 40096 57005 69932 70182 86500
                   333 10211 20171==
TXT, 200),
        ]);

        $this->artisan('synop:import-ogimet')
            ->assertSuccessful()
            ->expectsOutputToContain('zapisano 1');

        $row = DB::table('z_depesze_new')->where('idStacji', 12205)->first();
        $this->assertNotNull($row);
        $this->assertEquals(17.1, $row->temp);
        $this->assertSame('umiarkowane', $row->zachmurzenieTXT);
        $this->assertStringContainsString('chmury zanikają', (string) $row->zjawiskoTXT);
        $this->assertSame('ogimet', $row->zrodlo);
        $this->assertSame(1, DB::table('z_depesze_archiwum_new')->where('idStacji', 12205)->count());
    }

    public function test_new2_tabs_read_ogimet_tables_not_live_ones(): void
    {
        $client = $this->makeClient(['IMGW' => 1, 'mapaWarunkow' => 1]);
        DB::table('z_listastacji')->insert([
            'idStacji' => 12205,
            'nazwaStacji' => 'Szczecin',
            'region' => 'Zachodniopomorskie',
            'aktywna' => 1,
            'pozX' => 10,
            'pozY' => 10,
        ]);
        DB::table('z_uprawnieniadepesze')->insert([
            'idKlienta' => $client->id,
            'idStacji' => 12205,
            'aktywna' => 1,
            'lp' => 1,
        ]);
        DB::table('z_depesze')->insert([
            'idStacji' => 12205,
            'termin' => '2026-08-17 20:00:00',
            'temp' => 20.3,
            'zachmurzenieTXT' => '',
            'zjawiskoTXT' => '',
            'zjawiskoIkona' => 'N',
        ]);
        DB::table('z_depesze_new')->insert([
            'idStacji' => 12205,
            'termin' => '2026-08-17 20:00:00',
            'temp' => 17.1,
            'zachmurzenieTXT' => 'umiarkowane',
            'zjawiskoTXT' => 'chmury zanikają',
            'zjawiskoIkona' => 'N',
            'zachmurzenie' => 6,
            'zrodlo' => 'ogimet',
        ]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/content', ['tab' => 'imgwTableNewTab'])
            ->assertOk()
            ->assertSee('20.3')
            ->assertDontSee('chmury zanikają');
        $this->post('/klient/content', ['tab' => 'imgwTableNew2Tab'])
            ->assertOk()
            ->assertSee('17.1')
            ->assertSee('chmury zanikają')
            ->assertSee('umiarkowane');
        $this->post('/klient/content', ['tab' => 'imgwMapNew2Tab'])->assertOk();
    }

    public function test_store_does_not_replace_current_row_with_older_hour(): void
    {
        DB::table('z_depesze_new')->insert([
            'idStacji' => 12205,
            'termin' => '2026-08-17 22:00:00',
            'temp' => 17.1,
            'zrodlo' => 'ogimet',
        ]);
        $store = app(\App\Services\Synop\SynopStore::class);
        $store->save([
            'idStacji' => 12205,
            'termin' => '2026-08-17 21:00:00',
            'temp' => 16.0,
            'zrodlo' => 'ogimet',
            'synop' => '12205 04/// /2402 10203 40112',
        ]);

        $row = DB::table('z_depesze_new')->where('idStacji', 12205)->first();
        $this->assertSame('2026-08-17 22:00:00', $row->termin);
        $this->assertEquals(17.1, $row->temp);
        $this->assertSame(1, DB::table('z_depesze_archiwum_new')->where('termin', '2026-08-17 21:00:00')->count());
    }
}
