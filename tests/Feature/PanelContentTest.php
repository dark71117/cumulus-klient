<?php

namespace Tests\Feature;

use App\Support\CustomerContext;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PanelContentTest extends TestCase
{
    public function test_forecast_tab_renders_content(): void
    {
        $client = $this->makeClient(['prognozaDzis' => 1]);
        DB::table('z_prognozydzis')->insert([
            'id' => 1,
            'tresc' => '<p>Prognoza testowa Wrocław</p>',
            'temat' => 'aktualna',
            'data' => now()->toDateString(),
            'prognozaPL' => 0,
            'robocza' => 0,
            'biezaca' => 1,
        ]);
        DB::table('z_prognozydzisklienci')->insert([
            'idPrognozy' => 1,
            'idKlienta' => $client->id,
            'biezaca' => 1,
        ]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/content', ['tab' => 'forecast1Tab'])
            ->assertOk()
            ->assertSee('Prognoza testowa Wrocław');
    }

    public function test_warning_tab_renders_content(): void
    {
        $client = $this->makeClient(['ostrzezeniaTXT' => 1]);
        DB::table('z_ostrzezenia')->insert([
            'id' => 1,
            'tresc' => 'Ostrzeżenie burzowe',
            'data' => now()->toDateString(),
            'biezaca' => 1,
        ]);
        DB::table('z_ostrzezeniaklienci')->insert([
            'idPrognozy' => 1,
            'idKlienta' => $client->id,
            'biezaca' => 1,
        ]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/content', ['tab' => 'warningTab'])
            ->assertOk()
            ->assertSee('Ostrzeżenie burzowe');
    }

    public function test_calendar_tab_renders(): void
    {
        $client = $this->makeClient();
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/content', ['tab' => 'sunTab'])
            ->assertOk()
            ->assertSee('Imieniny');
    }

    public function test_imgw_map_without_data_is_not_500(): void
    {
        $client = $this->makeClient(['mapaWarunkow' => 1]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/content', ['tab' => 'imgwMapTab'])
            ->assertOk()
            ->assertSee('Brak danych');
    }

    public function test_gddkia_tab_without_stations_is_not_500(): void
    {
        $client = $this->makeClient(['GDDKIAwoj' => 1, 'GDDKIAdrogi' => 1]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/content', ['tab' => 'gddkiaRegionTab'])->assertOk();
        $this->post('/klient/content', ['tab' => 'gddkiaRoadTab'])->assertOk();
    }

    public function test_panel_menu_starts_with_table_map_and_addons(): void
    {
        $client = $this->makeClient([
            'IMGW' => 1,
            'mapaWarunkow' => 1,
            'GDDKIAwoj' => 1,
            'GDDKIAdrogi' => 1,
            'ostrzezeniaTXT' => 1,
            'zdjeciaSat' => 1,
        ]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $page = $this->get('/klient')->assertOk();
        $html = $page->getContent();
        $this->assertStringContainsString('id="imgwTab"', $html);
        $this->assertStringContainsString('>Tabela</div>', $html);
        $this->assertStringContainsString('id="imgwTableNewTab"', $html);
        $this->assertStringContainsString('>Tabela NEW</div>', $html);
        $this->assertStringContainsString('id="imgwTableNew2Tab"', $html);
        $this->assertStringContainsString('>Tabela NEW2</div>', $html);
        $this->assertStringContainsString('imgw-table.js', $html);
        $this->assertStringContainsString('imgw-table-hour.js', $html);
        $this->assertStringContainsString('>Mapa</div>', $html);
        $this->assertStringContainsString('>Mapa NEW</div>', $html);
        $this->assertStringContainsString('id="imgwMapNewTab"', $html);
        $this->assertStringContainsString('id="imgwMapNew2Tab"', $html);
        $this->assertStringContainsString('>Mapa NEW2</div>', $html);
        $this->assertStringContainsString('>Dodatki</div>', $html);
        $this->assertStringContainsString('https://meteomax.pl', $html);
        $this->assertStringContainsString('https://meteomax.eu', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringNotContainsString('id="forecastTab"', $html);
        $this->assertStringNotContainsString('Zdjęcia satelitarne', $html);
        $this->assertStringNotContainsString('Radar / Burze', $html);
        $this->assertStringNotContainsString('id="satPhotoTab"', $html);
        $this->assertStringNotContainsString('id="radarTab"', $html);
        $this->assertStringNotContainsString('Atlas chmur', $html);
        $this->assertStringNotContainsString('Teoria meteorologii', $html);
        $this->assertLessThan(
            strpos($html, 'id="imgwMapNewTab"'),
            strpos($html, 'id="imgwTableNewTab"')
        );
        $this->assertLessThan(
            strpos($html, 'id="imgwTableNew2Tab"'),
            strpos($html, 'id="imgwMapNewTab"')
        );
        $this->assertLessThan(
            strpos($html, 'id="imgwMapNew2Tab"'),
            strpos($html, 'id="imgwTableNew2Tab"')
        );
        $this->assertLessThan(
            strpos($html, 'id="imgwTab"'),
            strpos($html, 'id="imgwMapNew2Tab"')
        );
        $this->assertLessThan(
            strpos($html, 'id="imgwMapTab"'),
            strpos($html, 'id="imgwTab"')
        );
        $this->assertLessThan(
            strpos($html, 'id="addonsTab"'),
            strpos($html, 'id="imgwMapTab"')
        );
    }

    public function test_actual_tabs_omit_satellite_and_radar(): void
    {
        $client = $this->makeClient(['IMGW' => 1, 'zdjeciaSat' => 1]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $tabs = app(\App\Services\Panel\MenuTabsService::class)->actualTabs();
        $this->assertArrayHasKey('imgwTab', $tabs);
        $this->assertArrayHasKey('imgwTableNewTab', $tabs);
        $this->assertArrayHasKey('imgwMapTab', $tabs);
        $this->assertArrayHasKey('imgwTableNew2Tab', $tabs);
        $this->assertArrayHasKey('imgwMapNew2Tab', $tabs);
        $this->assertArrayNotHasKey('satPhotoTab', $tabs);
        $this->assertSame(1, $tabs['imgwTab']['active']);
        $this->assertSame(1, $tabs['imgwTableNewTab']['active']);
    }

    public function test_meteomax_disabled_shows_link(): void
    {
        $client = $this->makeClient(['mapaPrognozy' => 1]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/content', ['tab' => 'forecast5Tab'])
            ->assertOk()
            ->assertSee('meteomax.pl');
    }

    public function test_meteomax_region_query_string_is_routed(): void
    {
        $client = $this->makeClient(['mapaPrognozy' => 1]);
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->get('/klient/mmregion?id=1&b=0&c=0')->assertOk();
        $this->get('/klient/mmchart/1')->assertOk();
        $this->assertNotNull(app(\App\Services\Panel\MeteomaxService::class)->mapsScriptPath());
    }

    public function test_non_admin_cannot_open_ipadmin(): void
    {
        $client = $this->makeClient();
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->get('/klient/ipadmin')->assertForbidden();
    }

    public function test_admin_can_open_ipadmin(): void
    {
        $admin = $this->makeClient(['login' => 'admin', 'nazwa' => 'admin']);
        $this->actingAs($admin);
        CustomerContext::put($admin);

        $this->get('/klient/ipadmin')->assertOk()->assertSee('adresów IP');
    }

    public function test_admin_bar_is_above_logout(): void
    {
        $admin = $this->makeClient(['login' => 'admin', 'nazwa' => 'admin']);
        $this->actingAs($admin);
        CustomerContext::put($admin);

        $html = $this->get('/klient')->assertOk()->assertSee('sidebar-admin-island')->getContent();
        $this->assertLessThan(strpos($html, 'id="logoutBtn"'), strpos($html, 'sidebar-admin-island'));
    }

    public function test_ipadmin_ajax_stays_as_fragment(): void
    {
        $admin = $this->makeClient(['login' => 'admin', 'nazwa' => 'admin']);
        $target = $this->makeClient(['login' => 'firma', 'nazwa' => 'FIRMA']);
        $this->actingAs($admin);
        CustomerContext::put($admin);

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get('/klient/ipadmin')
            ->assertOk()
            ->assertSee('adresów IP')
            ->assertSee('ipadmin-pane')
            ->assertDontSee('app-sidebar');

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post('/klient/ipadmin', ['IpAdmin' => ['id' => $target->id]])
            ->assertOk()
            ->assertSee('FIRMA')
            ->assertSee('Pokaż dziennik')
            ->assertDontSee('app-sidebar');
    }

    public function test_ipadmin_can_add_and_delete_range_via_ajax(): void
    {
        $admin = $this->makeClient(['login' => 'admin', 'nazwa' => 'admin']);
        $target = $this->makeClient(['login' => 'firma', 'nazwa' => 'FIRMA']);
        $this->actingAs($admin);
        CustomerContext::put($admin);
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post('/klient/ipadmin', ['IpAdmin' => ['id' => $target->id]]);

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post('/klient/ipadmin/ip', [
                'id_klient' => $target->id,
                'opis' => 'blokada biura',
                'ip1' => '10.0.0.1',
                'ip2' => '10.0.0.10',
            ])
            ->assertOk()
            ->assertSee('blokada biura')
            ->assertSee('Pomyślnie zapisano dane');

        $rangeId = \App\Models\IpRange::query()->where('id_klient', $target->id)->value('id');
        $this->assertNotNull($rangeId);

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get('/klient/ipdelete/'.$rangeId)
            ->assertOk()
            ->assertSee('Usunięto zakres IP')
            ->assertDontSee('blokada biura');
    }
}
