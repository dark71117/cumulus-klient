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
            ->assertDontSee('actualMapStage');
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

        $this->post('/klient/content', ['tab' => 'imgwTableNewTab'])
            ->assertOk()
            ->assertSee('imgw-datatable')
            ->assertSee('Wrocław')
            ->assertSee('Dolnośląskie')
            ->assertSee('Województwo')
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

    public function test_table_new_export_uses_applied_filter(): void
    {
        $js = file_get_contents(public_path('js/imgw-table.js'));
        $this->assertIsString($js);
        $this->assertStringContainsString("search: 'applied'", $js);
        $this->assertStringContainsString("page: 'all'", $js);
        $this->assertStringContainsString("extend: 'excelHtml5'", $js);
        $this->assertStringContainsString("extend: 'pdfHtml5'", $js);
    }
}
