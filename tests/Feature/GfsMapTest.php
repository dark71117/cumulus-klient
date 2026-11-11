<?php

namespace Tests\Feature;

use App\Services\Panel\GfsMapService;
use App\Support\CustomerContext;
use Tests\TestCase;

class GfsMapTest extends TestCase
{
    private string $maps;

    protected function setUp(): void
    {
        parent::setUp();
        $this->maps = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gfs-maps-'.uniqid();
        mkdir($this->maps.DIRECTORY_SEPARATOR.'2026.11.10.06'.DIRECTORY_SEPARATOR.'Polska', 0777, true);
        mkdir($this->maps.DIRECTORY_SEPARATOR.'2026.11.09.18'.DIRECTORY_SEPARATOR.'Europa', 0777, true);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        file_put_contents($this->maps.'/2026.11.10.06/Polska/synop3.001.png', $png);
        file_put_contents($this->maps.'/2026.11.10.06/Polska/precip6h.006.png', $png);
        file_put_contents($this->maps.'/2026.11.10.06/Polska/synop3.024.png', $png);
        config(['cumulus.gfs_maps_path' => $this->maps]);
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->maps);
        parent::tearDown();
    }

    public function test_guest_cannot_open_map_image(): void
    {
        $this->get('/klient/gfs/obraz/2026.11.10.06/Polska/synop3/001')
            ->assertRedirect('/klient');
    }

    public function test_logged_in_tab_uses_desktop_labels(): void
    {
        $client = $this->makeClient();
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->post('/klient/content', ['tab' => 'gfsMapTab'])
            ->assertOk()
            ->assertSee('Mapa synoptyka v.3', false)
            ->assertSee('Synop 3', false)
            ->assertSee('Opad za 6h', false)
            ->assertSee('Jedna mapa', false)
            ->assertSee('Dwie mapy', false)
            ->assertSee('Cztery mapy', false)
            ->assertSee('2026.11.10.06', false)
            ->assertSee('>Polska<', false)
            ->assertSee('>Europa<', false)
            ->assertSee('PEŁNY EKRAN', false);
    }

    public function test_image_and_frames_follow_run_region_and_fallback_file(): void
    {
        $client = $this->makeClient();
        $this->actingAs($client);
        CustomerContext::put($client);

        $this->get('/klient/gfs/obraz/2026.11.10.06/Polska/synop3/001')
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->get('/klient/gfs/obraz/2026.11.10.06/Polska/precip6h/006')
            ->assertOk();

        $this->get('/klient/gfs/obraz/2026.11.10.06/Polska/synop3/002')
            ->assertNotFound();

        $frames = $this->getJson('/klient/gfs/klatki/2026.11.10.06/Polska/synop3')
            ->assertOk()
            ->json();

        $this->assertSame(['001', '024'], $frames['hours']);
        $this->assertSame('WT', $frames['days'][0]['label']);
        $this->assertSame('10.11.2026', $frames['days'][0]['date']);
        $this->assertSame('07', $frames['days'][0]['clocks'][0]['clock']);
        $this->assertSame('001', $frames['days'][0]['clocks'][0]['lead']);
        $this->assertSame('ŚR', $frames['days'][1]['label']);
        $this->assertSame('11.11.2026', $frames['days'][1]['date']);
        $this->assertSame('06', $frames['days'][1]['clocks'][0]['clock']);
    }

    public function test_newest_run_is_first_and_unknown_product_is_rejected(): void
    {
        $service = app(GfsMapService::class);
        $this->assertSame(['2026.11.10.06', '2026.11.09.18'], $service->runs());
        $this->assertNull($service->imagePath('2026.11.10.06', 'Polska', 'nieMaTakiej', '001'));
        $this->assertNull($service->frames('2026.11.10.06', 'Polska', '..'));
        $this->assertNull($service->imagePath('2026.11.10.06', 'Polska', 'synop3', '../01'));
    }

    public function test_frames_carry_file_time_and_runs_for_the_live_menu(): void
    {
        touch($this->maps.'/2026.11.10.06/Polska/synop3.001.png', 1700000000);
        touch($this->maps.'/2026.11.10.06/Polska/synop3.024.png', 1700001111);
        copy(
            $this->maps.'/2026.11.10.06/Polska/precip6h.006.png',
            $this->maps.'/2026.11.10.06/Polska/precip6h_2.006.png'
        );
        touch($this->maps.'/2026.11.10.06/Polska/precip6h.006.png', 1700000000);
        touch($this->maps.'/2026.11.10.06/Polska/precip6h_2.006.png', 1700003333);
        $client = $this->makeClient();
        $this->actingAs($client);
        CustomerContext::put($client);

        $frames = $this->getJson('/klient/gfs/klatki/2026.11.10.06/Polska/synop3')
            ->assertOk()
            ->json();

        $this->assertSame(1700000000, $frames['stamps']['001']);
        $this->assertSame(1700001111, $frames['stamps']['024']);
        $this->assertSame(['2026.11.10.06', '2026.11.09.18'], $frames['runs']);

        $precip = $this->getJson('/klient/gfs/klatki/2026.11.10.06/Polska/precip6h')
            ->assertOk()
            ->json();
        $this->assertSame(1700003333, $precip['stamps']['006']);
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            is_dir($path) ? $this->deleteTree($path) : unlink($path);
        }
        rmdir($dir);
    }
}
