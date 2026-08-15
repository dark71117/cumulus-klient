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
            ->assertSee('actualTable')
            ->assertDontSee('imgw-datatable')
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

    public function test_classic_map_tab_renders_bitmap_stage(): void
    {
        $client = $this->makeClient(['mapaWarunkow' => 1]);
        DB::table('z_listastacji')->insert([
            'idStacji' => 12424,
            'nazwaStacji' => 'Wrocław',
            'region' => 'Dolnośląskie',
            'aktywna' => 1,
            'pozX' => 190,
            'pozY' => 480,
            'pozWX' => 480,
            'pozWY' => 340,
        ]);
        DB::table('z_depesze')->insert([
            'idStacji' => 12424,
            'termin' => now()->format('Y-m-d H:00:00'),
            'temp' => 12.3,
            'zjawiskoIkona' => 'N',
            'zachmurzenie' => 2,
        ]);
        DB::table('z_uprawnieniadepesze')->insert([
            'idKlienta' => $client->id,
            'idStacji' => 12424,
            'aktywna' => 1,
            'lp' => 1,
        ]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/content', ['tab' => 'imgwMapTab'])
            ->assertOk()
            ->assertSee('actualMapStage')
            ->assertSee('Wrocław')
            ->assertDontSee('imgw-leaflet');
    }

    public function test_map_new_tab_renders_leaflet_points(): void
    {
        $client = $this->makeClient(['mapaWarunkow' => 1]);
        DB::table('z_listastacji')->insert([
            'idStacji' => 12424,
            'nazwaStacji' => 'Wrocław',
            'region' => 'Dolnośląskie',
            'aktywna' => 1,
            'pozX' => 190,
            'pozY' => 480,
            'pozWX' => 480,
            'pozWY' => 340,
        ]);
        DB::table('z_depesze')->insert([
            'idStacji' => 12424,
            'termin' => now()->format('Y-m-d H:00:00'),
            'temp' => 12.3,
            'zjawiskoIkona' => 'N',
            'zachmurzenie' => 2,
        ]);
        DB::table('z_uprawnieniadepesze')->insert([
            'idKlienta' => $client->id,
            'idStacji' => 12424,
            'aktywna' => 1,
            'lp' => 1,
        ]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/content', ['tab' => 'imgwMapNewTab'])
            ->assertOk()
            ->assertSee('imgw-leaflet')
            ->assertSee('Wrocław')
            ->assertSee('51.103')
            ->assertSee('16.9')
            ->assertSee('wojewodztwa_pl.geojson')
            ->assertSee('imgw-map-header')
            ->assertSee('imgw-dt-head')
            ->assertSee('imgw-dt-title')
            ->assertSee('(co 1 godzinę)')
            ->assertSee('id="imgw-map-hour"', false)
            ->assertSee('Automatyczne przewijanie')
            ->assertSee('Zwłoka')
            ->assertSee('id="imgw-map-frames"', false)
            ->assertDontSee('actualMapStage');
    }

    public function test_map_new_builds_hour_frames_for_playback(): void
    {
        $client = $this->makeClient(['mapaWarunkow' => 1]);
        DB::table('z_listastacji')->insert([
            [
                'idStacji' => 12424,
                'nazwaStacji' => 'Wrocław',
                'region' => 'Dolnośląskie',
                'aktywna' => 1,
                'pozX' => 190,
                'pozY' => 480,
                'pozWX' => 480,
                'pozWY' => 340,
            ],
            [
                'idStacji' => 1,
                'nazwaStacji' => 'Kraków',
                'region' => 'Małopolskie',
                'aktywna' => 1,
                'pozX' => 120,
                'pozY' => 80,
                'pozWX' => 200,
                'pozWY' => 200,
            ],
        ]);
        DB::table('z_depesze')->insert([
            [
                'idStacji' => 12424,
                'termin' => '2026-08-15 18:00:00',
                'temp' => 10,
                'zjawiskoIkona' => 'N',
                'zachmurzenie' => 2,
            ],
            [
                'idStacji' => 12424,
                'termin' => '2026-08-15 19:00:00',
                'temp' => 20,
                'zjawiskoIkona' => 'N',
                'zachmurzenie' => 3,
            ],
            [
                'idStacji' => 1,
                'termin' => '2026-08-15 19:00:00',
                'temp' => 8,
                'zjawiskoIkona' => 'N',
                'zachmurzenie' => 2,
            ],
        ]);
        DB::table('z_uprawnieniadepesze')->insert([
            ['idKlienta' => $client->id, 'idStacji' => 12424, 'aktywna' => 1, 'lp' => 1],
            ['idKlienta' => $client->id, 'idStacji' => 1, 'aktywna' => 1, 'lp' => 2],
        ]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $html = $this->post('/klient/content', ['tab' => 'imgwMapNewTab'])
            ->assertOk()
            ->assertSee('id="imgw-map-hour"', false)
            ->assertSee('19:00')
            ->getContent();

        preg_match('/id="imgw-map-frames">([^<]*)<\/script>/', $html, $match);
        $this->assertNotEmpty($match[1] ?? null);
        $frames = json_decode($match[1], true);
        $this->assertIsArray($frames);
        $this->assertCount(2, $frames);
        $this->assertSame('18:00', $frames[0]['hour']);
        $this->assertSame('19:00', $frames[1]['hour']);
        $this->assertCount(2, $frames[0]['points']);
        $this->assertCount(2, $frames[1]['points']);
        $temps18 = array_column($frames[0]['points'], 'temp', 'name');
        $temps19 = array_column($frames[1]['points'], 'temp', 'name');
        $this->assertSame('10', $temps18['Wrocław']);
        $this->assertSame('BD', $temps18['Kraków']);
        $this->assertSame('20', $temps19['Wrocław']);
        $this->assertSame('8', $temps19['Kraków']);
        $krakow18 = collect($frames[0]['points'])->firstWhere('name', 'Kraków');
        $this->assertIsArray($krakow18);
        $this->assertTrue($krakow18['missing']);
        $this->assertSame('', $krakow18['icon']);
        $this->assertSame(1, (int) (preg_match('/data-current="1"/', $html)));
    }

    public function test_map_new_playback_script_and_icon_size(): void
    {
        $js = file_get_contents(public_path('js/imgw-map.js'));
        $this->assertIsString($js);
        $this->assertStringContainsString('function startImgwPlay', $js);
        $this->assertStringContainsString('function stepImgwHour', $js);
        $this->assertStringContainsString('is-missing', $js);
        $this->assertStringContainsString("'BD'", $js);

        $css = file_get_contents(public_path('css/layout.css'));
        $this->assertIsString($css);
        $this->assertMatchesRegularExpression('/\.imgw-pin-temp\s*\{[^}]*font:\s*700 16px/s', $css);
        $this->assertMatchesRegularExpression('/\.imgw-pin-icon img\s*\{[^}]*width:\s*36px/s', $css);
    }

    public function test_table_new_tab_renders_datatable(): void
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
            'widzialnosc' => '&ge; 10',
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

        $this->post('/klient/content', ['tab' => 'imgwTableNewTab'])
            ->assertOk()
            ->assertSee('imgw-datatable-pl')
            ->assertSee('Polska')
            ->assertSee('Wrocław')
            ->assertSee('Dolnośląskie')
            ->assertSee('Województwo')
            ->assertSee('≥ 10')
            ->assertDontSee('&ge;')
            ->assertDontSee('imgw-datatable-eu')
            ->assertDontSee('actualTable')
            ->assertDontSee('Błąd 404');
    }

    public function test_table_new_empty_depesze_shows_brak_danych(): void
    {
        $client = $this->makeClient(['IMGW' => 1]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/content', ['tab' => 'imgwTableNewTab'])
            ->assertOk()
            ->assertSee('Brak danych')
            ->assertDontSee('imgw-datatable');
    }

    public function test_table_new_splits_poland_and_europe(): void
    {
        $client = $this->makeClient(['IMGW' => 1, 'wojDepesze' => 1]);
        DB::table('z_listastacji')->insert([
            [
                'idStacji' => 1,
                'nazwaStacji' => 'Wrocław',
                'region' => 'Dolnośląskie',
                'aktywna' => 1,
            ],
            [
                'idStacji' => 2,
                'nazwaStacji' => 'Bruksela',
                'region' => 'EUROPA CENTRALNA',
                'aktywna' => 1,
            ],
        ]);
        $termin = now()->format('Y-m-d H:00:00');
        DB::table('z_depesze')->insert([
            [
                'idStacji' => 1,
                'termin' => $termin,
                'temp' => 12.3,
                'tempOdcz' => 11.0,
                'zachmurzenieTXT' => 'małe',
                'zjawiskoTXT' => '',
                'widzialnosc' => '50',
                'wiatr' => 'pn / 4',
                'cisnienieTXT' => '',
            ],
            [
                'idStacji' => 2,
                'termin' => $termin,
                'temp' => 8.0,
                'tempOdcz' => 6.0,
                'zachmurzenieTXT' => 'duże',
                'zjawiskoTXT' => '',
                'widzialnosc' => '10',
                'wiatr' => 'zach / 22',
                'cisnienieTXT' => '',
            ],
        ]);
        DB::table('z_uprawnieniadepesze')->insert([
            ['idKlienta' => $client->id, 'idStacji' => 1, 'aktywna' => 1, 'cisnienie' => 0, 'lp' => 1],
            ['idKlienta' => $client->id, 'idStacji' => 2, 'aktywna' => 1, 'cisnienie' => 0, 'lp' => 2],
        ]);

        $this->actingAs($client);
        CustomerContext::put($client);

        $html = $this->post('/klient/content', ['tab' => 'imgwTableNewTab'])
            ->assertOk()
            ->assertSee('imgw-datatable-pl')
            ->assertSee('imgw-datatable-eu')
            ->assertSee('Polska')
            ->assertSee('Europa')
            ->assertSee('id="imgw-dt-search"', false)
            ->getContent();

        $pl = strpos($html, 'id="imgw-datatable-pl"');
        $eu = strpos($html, 'id="imgw-datatable-eu"');
        $wroclaw = strpos($html, 'Wrocław');
        $bruksela = strpos($html, 'Bruksela');
        $this->assertNotFalse($pl);
        $this->assertNotFalse($eu);
        $this->assertLessThan($eu, $pl);
        $this->assertGreaterThan($pl, $wroclaw);
        $this->assertLessThan($eu, $wroclaw);
        $this->assertGreaterThan($eu, $bruksela);
    }

    public function test_table_new_marks_delayed_city_cell_not_whole_row(): void
    {
        $client = $this->makeClient(['IMGW' => 1]);
        $actual = now()->startOfHour();
        DB::table('z_listastacji')->insert([
            ['idStacji' => 1, 'nazwaStacji' => 'Wrocław', 'region' => 'Dolnośląskie', 'aktywna' => 1],
            ['idStacji' => 2, 'nazwaStacji' => 'Amsterdam', 'region' => 'EUROPA ZACHODNIA', 'aktywna' => 1],
            ['idStacji' => 3, 'nazwaStacji' => 'Kopenhaga', 'region' => 'EUROPA PÓŁNOCNA', 'aktywna' => 1],
        ]);
        DB::table('z_depesze')->insert([
            [
                'idStacji' => 1,
                'termin' => $actual->format('Y-m-d H:i:s'),
                'temp' => 12.3,
                'widzialnosc' => '50',
                'wiatr' => 'pn / 4',
                'zjawiskoKolor' => '',
            ],
            [
                'idStacji' => 2,
                'termin' => $actual->copy()->subHours(1)->format('Y-m-d H:i:s'),
                'temp' => 9.0,
                'widzialnosc' => '10',
                'wiatr' => 'zach / 22',
                'zjawiskoKolor' => 'deszcz',
            ],
            [
                'idStacji' => 3,
                'termin' => $actual->copy()->subHours(2)->format('Y-m-d H:i:s'),
                'temp' => 4.0,
                'widzialnosc' => '8',
                'wiatr' => 'pn / 11',
                'zjawiskoKolor' => '',
            ],
        ]);
        DB::table('z_uprawnieniadepesze')->insert([
            ['idKlienta' => $client->id, 'idStacji' => 1, 'aktywna' => 1, 'lp' => 1],
            ['idKlienta' => $client->id, 'idStacji' => 2, 'aktywna' => 1, 'lp' => 2],
            ['idKlienta' => $client->id, 'idStacji' => 3, 'aktywna' => 1, 'lp' => 3],
        ]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $html = $this->post('/klient/content', ['tab' => 'imgwTableNewTab'])
            ->assertOk()
            ->assertSee('Amsterdam')
            ->assertSee('Kopenhaga')
            ->assertSee('imgw-delay-1')
            ->assertSee('imgw-delay-2')
            ->assertSee('imgwdeszcz')
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<td[^>]*class="[^"]*imgw-delay-1[^"]*"[^>]*>\s*Amsterdam/u',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<td[^>]*class="[^"]*imgw-delay-2[^"]*"[^>]*>\s*Kopenhaga/u',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<td[^>]*class="[^"]*imgw-delay-[12][^"]*"[^>]*>\s*Wrocław/u',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<tr class="imgwdeszcz">[\s\S]*?Amsterdam[\s\S]*?<\/tr>/u',
            $html
        );
    }

    public function test_table_new_export_uses_applied_filter(): void
    {
        $js = file_get_contents(public_path('js/imgw-table.js'));
        $this->assertIsString($js);
        $this->assertStringContainsString("search: 'applied'", $js);
        $this->assertStringContainsString("page: 'all'", $js);
        $this->assertStringContainsString("extend: 'excelHtml5'", $js);
        $this->assertStringContainsString("extend: 'pdfHtml5'", $js);
        $this->assertStringContainsString('imgwMergedExportData', $js);
        $this->assertStringContainsString('imgw-datatable-pl', $js);
        $this->assertStringContainsString('imgw-datatable-eu', $js);
        $this->assertStringContainsString('pageLength: 100', $js);
        $this->assertStringContainsString("[10, 25, 50, 100, -1], [10, 25, 50, 100, 'max']", $js);
    }

    public function test_table_new_renders_phenomenon_html_instead_of_escaped_tags(): void
    {
        $client = $this->makeClient(['IMGW' => 1]);
        DB::table('z_listastacji')->insert([
            'idStacji' => 1,
            'nazwaStacji' => 'RUS - Moskwa',
            'region' => 'EUROPA WSCHODNIA',
            'aktywna' => 1,
        ]);
        DB::table('z_depesze')->insert([
            'idStacji' => 1,
            'termin' => now()->format('Y-m-d H:00:00'),
            'temp' => 21.0,
            'zjawiskoTXT' => 'słaby &lt;span class="pogrubione"&gt;deszcz&lt;/span&gt; (ciągły)',
            'widzialnosc' => '10',
            'wiatr' => 'zach / 11',
        ]);
        DB::table('z_uprawnieniadepesze')->insert([
            'idKlienta' => $client->id,
            'idStacji' => 1,
            'aktywna' => 1,
            'lp' => 1,
        ]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/content', ['tab' => 'imgwTableNewTab'])
            ->assertOk()
            ->assertSee('RUS - Moskwa')
            ->assertSee('<span class="pogrubione">deszcz</span>', false)
            ->assertSee('data-export="słaby deszcz (ciągły)"', false)
            ->assertDontSee('&lt;span', false);
    }
}
