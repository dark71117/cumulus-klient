<?php

namespace Tests\Unit;

use App\Support\AnalysisTable;
use Tests\TestCase;

class AnalysisTableTest extends TestCase
{
    public function test_item_formats_aaxx_codes_and_prefers_synop_raw(): void
    {
        $row = (object) [
            'region' => 'Zachodniopomorskie',
            'nazwaStacji' => 'Szczecin',
            'idStacji' => 12205,
            'zachmurzenie' => 6,
            'zachmurzeniePodstawa' => '>2500',
            'widzialnosc' => '35',
            'wiatrK' => 220,
            'wiatrMS' => 3,
            'porywy' => 18,
            'temp' => 14.1,
            'wilgotnosc' => 82,
            'cisnienieMorze' => 1016.0,
            'cisnienieStacja' => 1012.5,
            'zjawisko' => 1,
            'zjawiskoTXT' => 'chmury zanikają',
            'zachmurzenieTXT' => 'umiarkowane',
            'wiatr' => 'pd-zach / 11',
            'zjawiskoKolor' => '',
            'termin' => '2026-09-02 06:00:00',
            'synop' => 'zrekonstruowane',
            'synop_raw' => '12205 11784 62601 10141=',
        ];
        $item = AnalysisTable::item($row);

        $this->assertSame('Szczecin', $item['nazwaStacji']);
        $this->assertSame(12205, $item['idStacji']);
        $this->assertSame('6', $item['n']);
        $this->assertSame('>2500', $item['h']);
        $this->assertSame('35', $item['vv']);
        $this->assertSame('220/3*5', $item['windAaxx']);
        $this->assertSame('14.1', $item['temp']);
        $this->assertSame('82', $item['uu']);
        $this->assertSame('1016.0', $item['qnh']);
        $this->assertSame('1012.5', $item['qfe']);
        $this->assertSame('1 chmury zanikają', $item['zjawiskoTXT']);
        $this->assertSame('06:00', $item['czas']);
        $this->assertSame('12205 11784 62601 10141=', $item['synopRaw']);
        $this->assertSame('synop', $item['sourceKind']);
    }

    public function test_missing_values_are_slash_and_legacy_synop_is_fallback(): void
    {
        $item = AnalysisTable::item((object) [
            'nazwaStacji' => 'Gdańsk',
            'idStacji' => 12100,
            'temp' => -99,
            'zachmurzenie' => -99,
            'termin' => '2026-09-02 07:00:00',
            'synop' => '12100 04/// /0000=',
        ]);

        $this->assertSame('/', $item['n']);
        $this->assertSame('/', $item['temp']);
        $this->assertSame('/', $item['windAaxx']);
        $this->assertSame('12100 04/// /0000=', $item['synopRaw']);
        $this->assertSame('synop', $item['sourceKind']);
    }

    public function test_metar_is_shown_when_synop_raw_is_missing(): void
    {
        $item = AnalysisTable::item((object) [
            'nazwaStacji' => 'Gdańsk',
            'idStacji' => 12140,
            'temp' => 16.0,
            'termin' => '2026-09-03 09:00:00',
            'synop' => '12140 04/60 72807 10160=',
            'metar' => 'EPGD 030700Z 27011KT 9999 FEW030 16/12 Q1015=',
        ]);

        $this->assertSame('EPGD 030700Z 27011KT 9999 FEW030 16/12 Q1015=', $item['synopRaw']);
        $this->assertSame('metar', $item['sourceKind']);
        $this->assertTrue(AnalysisTable::isMetar($item['synopRaw']));
        $this->assertFalse(AnalysisTable::isMetar('12205 11784 62601 10141='));
    }

    public function test_parse_numeric_skips_missing_and_reads_visibility_text(): void
    {
        $this->assertNull(AnalysisTable::parseNumeric(-99));
        $this->assertNull(AnalysisTable::parseNumeric('/'));
        $this->assertSame(10.0, AnalysisTable::parseNumeric('&ge; 10'));
        $this->assertSame(14.1, AnalysisTable::parseNumeric('14.1'));
    }

    public function test_numeric_fields_omit_generic_pressure_precip_period_and_weather_code(): void
    {
        $labels = AnalysisTable::numericFields('z_depesze_archiwum_new');

        $this->assertArrayHasKey('cisnienieMorze', $labels);
        $this->assertArrayHasKey('wysokoscOpadu', $labels);
        $this->assertArrayNotHasKey('cisnienie', $labels);
        $this->assertArrayNotHasKey('okresOpadu', $labels);
        $this->assertArrayNotHasKey('zjawisko', $labels);
        $this->assertNotContains('Ciśnienie [hPa]', $labels);
        $this->assertNotContains('Okres opadu [h]', $labels);
        $this->assertNotContains('Kod zjawiska ww', $labels);
    }
}
