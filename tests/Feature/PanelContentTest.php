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
