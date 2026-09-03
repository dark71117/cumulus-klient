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
            ->assertSee('Województwo')
            ->assertSee('Stacja')
            ->assertSee('Depesza')
            ->assertSee('Szczecin')
            ->assertSee('12205')
            ->assertSee('12205 11784 62601 10141=')
            ->assertSee('analiza-synop-open')
            ->assertSee('Rozbiór depeszy SYNOP')
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

    public function test_analiza_explain_breaks_down_synop_groups(): void
    {
        $client = $this->seedAnalizaClient();
        $this->actingAs($client);
        CustomerContext::put($client);

        $res = $this->post('/klient/analiza', [
            'mode' => 'explain',
            'synop' => '12205 11784 62601 10141=',
            'station' => 'Szczecin',
            'station_id' => '12205',
            'termin' => '2026-09-02 06:00:00',
        ])->assertOk()->assertJsonPath('ok', true);

        $html = $res->json('html');
        $this->assertStringContainsString('Szczecin (12205)', $html);
        $this->assertStringContainsString('14.1 °C', $html);
        $this->assertStringContainsString('IIiii', $html);
        $this->assertStringContainsString('irixhVV', $html);
        $this->assertStringContainsString('1sTTT', $html);
    }

    public function test_analiza_explain_shows_metar_instead_of_fake_synop_groups(): void
    {
        $client = $this->seedAnalizaClient();
        $this->actingAs($client);
        CustomerContext::put($client);

        $html = $this->post('/klient/analiza', [
            'mode' => 'explain',
            'kind' => 'metar',
            'synop' => 'EPGD 030700Z 27011KT 9999 FEW030 16/12 Q1015=',
            'station' => 'Gdańsk',
            'station_id' => '12140',
            'termin' => '2026-09-03 09:00:00',
        ])->assertOk()->assertJsonPath('ok', true)->json('html');

        $this->assertStringContainsString('EPGD 030700Z', $html);
        $this->assertStringContainsString('METAR', $html);
        $this->assertStringContainsString('To nie jest depesza SYNOP', $html);
        $this->assertStringNotContainsString('irixhVV', $html);
    }

    public function test_analiza_hour_shows_metar_when_synop_raw_is_empty(): void
    {
        $client = $this->seedAnalizaClient();
        DB::table('z_listastacji')->insert([
            'idStacji' => 12140,
            'nazwaStacji' => 'Gdańsk',
            'region' => 'Pomorskie',
            'aktywna' => 1,
        ]);
        DB::table('z_uprawnieniadepesze')->insert([
            'idKlienta' => $client->id,
            'idStacji' => 12140,
            'aktywna' => 1,
            'lp' => 2,
        ]);
        DB::table('z_depesze_archiwum_new')->insert([
            'idStacji' => 12140,
            'termin' => '2026-09-02 06:00:00',
            'temp' => 16.0,
            'wilgotnosc' => 77,
            'cisnienieMorze' => 1015.0,
            'zrodlo' => 'ogimet',
            'synop' => '12140 04/60 72807 10160=',
            'metar' => 'EPGD 020400Z 27011KT 9999 FEW030 16/12 Q1015=',
        ]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $html = $this->post('/klient/analiza', [
            'mode' => 'hour',
            'source' => 'ogimet',
            'termin' => '2026-09-02 06:00:00',
        ])->assertOk()->json('html');

        $this->assertStringContainsString('id="analiza-datatable"', $html);
        $this->assertStringContainsString('<thead>', $html);
        $this->assertStringContainsString('Gdańsk', $html);
        $this->assertStringContainsString('EPGD 020400Z', $html);
        $this->assertStringContainsString('data-kind="metar"', $html);
        $this->assertStringContainsString('analiza-source-kind', $html);
    }

    public function test_analiza_explain_rejects_empty_synop(): void
    {
        $client = $this->seedAnalizaClient();
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/analiza', ['mode' => 'explain', 'synop' => ' '])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);
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

    public function test_analiza_table_scrolls_inside_viewport_above_footer(): void
    {
        $js = file_get_contents(public_path('js/analiza.js'));
        $this->assertIsString($js);
        $this->assertStringContainsString("scrollX: true", $js);
        $this->assertStringContainsString("scrollY:", $js);
        $this->assertStringContainsString('function fitAnalizaScroll', $js);
        $this->assertStringContainsString("addClass('analiza-dt')", $js);

        $clientJs = file_get_contents(public_path('js/client.js'));
        $this->assertIsString($clientJs);
        $this->assertStringContainsString("toggleClass('has-analiza'", $clientJs);
        $this->assertStringContainsString('has-map has-analiza', $clientJs);

        $css = file_get_contents(public_path('css/layout.css'));
        $this->assertIsString($css);
        $this->assertStringContainsString('#content.has-analiza', $css);
        $this->assertStringContainsString('.analiza-dt', $css);
        $this->assertStringContainsString('.dt-scroll-body', $css);
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
