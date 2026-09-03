<?php

namespace Tests\Feature;

use App\Support\CustomerContext;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalizaContentTest extends TestCase
{
    public function test_analiza_tab_shows_ogimet_hour_and_raw_synop(): void
    {
        $client = $this->seedAnalizaClient();
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/content', ['tab' => 'analizaTab'])
            ->assertOk()
            ->assertSee('Analiza depesz SYNOP')
            ->assertSee('Szczecin')
            ->assertSee('12205')
            ->assertSee('12205 11784 62601 10141=')
            ->assertSee('chmury zanikają')
            ->assertSee('umiarkowane')
            ->assertSee('14.1')
            ->assertDontSee('20.3');
    }

    public function test_analiza_source_imgw_reads_old_tables(): void
    {
        $client = $this->seedAnalizaClient();
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/analiza', [
            'mode' => 'hour',
            'source' => 'imgw',
            'termin' => '2026-09-02 06:00:00',
        ])->assertOk()
            ->assertJsonPath('ok', true)
            ->assertSee('20.3')
            ->assertDontSee('14.1');
    }

    public function test_analiza_jump_uses_archive_hour_and_neighbors(): void
    {
        $client = $this->seedAnalizaClient();
        $this->actingAs($client);
        CustomerContext::put($client);

        $res = $this->post('/klient/analiza', [
            'mode' => 'hour',
            'source' => 'ogimet',
            'termin' => '2026-09-01 18:00:00',
        ])->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('meta.termin', '2026-09-01 18:00:00')
            ->assertJsonPath('meta.prev', '2026-09-01 12:00:00')
            ->assertJsonPath('meta.next', '2026-09-02 06:00:00');

        $this->assertStringContainsString('12.0', $res->getContent());
        $this->assertStringContainsString('12205 11784 62601 10120=', $res->getContent());
        $this->assertStringNotContainsString('14.1', $res->json('html'));
    }

    public function test_analiza_stats_min_max_avg_ignore_missing(): void
    {
        $client = $this->seedAnalizaClient();
        $this->actingAs($client);
        CustomerContext::put($client);

        $res = $this->post('/klient/analiza', [
            'mode' => 'stats',
            'source' => 'ogimet',
            'station' => 12205,
            'from' => '2026-09-01 12:00',
            'to' => '2026-09-02 06:00',
        ])->assertOk()->assertJsonPath('ok', true);

        $html = $res->json('html');
        $this->assertStringContainsString('Szczecin', $html);
        $this->assertStringContainsString('12.0', $html);
        $this->assertStringContainsString('14.1', $html);
        $this->assertStringContainsString('13.0', $html);
        $this->assertStringContainsString('3', $html);
    }

    public function test_analiza_stats_rejects_inverted_range(): void
    {
        $client = $this->seedAnalizaClient();
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/analiza', [
            'mode' => 'stats',
            'source' => 'ogimet',
            'station' => 12205,
            'from' => '2026-09-02 06:00',
            'to' => '2026-09-01 06:00',
        ])->assertOk()->assertSee('Niepoprawny zakres dat.');
    }

    private function seedAnalizaClient()
    {
        $client = $this->makeClient(['IMGW' => 1]);
        DB::table('z_listastacji')->insert([
            'idStacji' => 12205,
            'nazwaStacji' => 'Szczecin',
            'region' => 'Zachodniopomorskie',
            'aktywna' => 1,
        ]);
        DB::table('z_uprawnieniadepesze')->insert([
            'idKlienta' => $client->id,
            'idStacji' => 12205,
            'aktywna' => 1,
            'lp' => 1,
        ]);
        $ogimet = [
            'idStacji' => 12205,
            'zachmurzenie' => 6,
            'zachmurzenieTXT' => 'umiarkowane',
            'zachmurzeniePodstawa' => '>2500',
            'widzialnosc' => '35',
            'wiatrK' => 220,
            'wiatrMS' => 3,
            'wiatr' => 'pd-zach / 11',
            'wilgotnosc' => 80,
            'cisnienieMorze' => 1016.0,
            'cisnienieStacja' => 1012.0,
            'zjawisko' => 1,
            'zjawiskoTXT' => 'chmury zanikają',
            'zrodlo' => 'ogimet',
        ];
        DB::table('z_depesze_new')->insert($ogimet + [
            'termin' => '2026-09-02 06:00:00',
            'temp' => 14.1,
            'synop' => '12205 11784 62601 10141',
            'synop_raw' => '12205 11784 62601 10141=',
        ]);
        DB::table('z_depesze_archiwum_new')->insert([
            $ogimet + [
                'termin' => '2026-09-01 12:00:00',
                'temp' => 12.0,
                'synop_raw' => '12205 11784 62601 10120=',
            ],
            $ogimet + [
                'termin' => '2026-09-01 18:00:00',
                'temp' => 13.0,
                'synop_raw' => '12205 11784 62601 10120=',
            ],
            $ogimet + [
                'termin' => '2026-09-02 06:00:00',
                'temp' => 14.1,
                'synop_raw' => '12205 11784 62601 10141=',
            ],
        ]);
        DB::table('z_depesze')->insert([
            'idStacji' => 12205,
            'termin' => '2026-09-02 06:00:00',
            'temp' => 20.3,
            'zachmurzenieTXT' => 'małe',
            'zjawiskoTXT' => '',
        ]);
        DB::table('z_depesze_archiwum')->insert([
            'idStacji' => 12205,
            'termin' => '2026-09-02 06:00:00',
            'temp' => 20.3,
            'zachmurzenieTXT' => 'małe',
            'zjawiskoTXT' => '',
        ]);

        return $client;
    }
}
