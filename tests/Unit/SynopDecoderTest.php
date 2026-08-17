<?php

namespace Tests\Unit;

use App\Services\Synop\OgimetClient;
use App\Services\Synop\SynopDecoder;
use Carbon\Carbon;
use Tests\TestCase;

class SynopDecoderTest extends TestCase
{
    public function test_full_ogimet_synop_has_weather_clouds_and_temperature(): void
    {
        $raw = '12205 11784 62601 10171 20140 30087 40096 57005 69932 70182 86500 333 10211 20171';
        $record = (new SynopDecoder)->decode($raw, Carbon::parse('2026-08-17 18:00:00', 'UTC'), 1);

        $this->assertNotNull($record);
        $this->assertSame(12205, $record['idStacji']);
        $this->assertSame(17.1, $record['temp']);
        $this->assertSame(6, $record['zachmurzenie']);
        $this->assertSame('umiarkowane', $record['zachmurzenieTXT']);
        $this->assertSame(1, $record['zjawisko']);
        $this->assertStringContainsString('chmury zanikają', $record['zjawiskoTXT']);
        $this->assertNotSame('', $record['zjawiskoIkona']);
        $this->assertSame('ogimet', $record['zrodlo']);
        $this->assertSame('zach', explode(' / ', $record['wiatr'])[0]);
    }

    public function test_short_backup_synop_has_temperature_but_no_weather(): void
    {
        $raw = '12205 04/// /2402 10203 40112';
        $record = (new SynopDecoder)->decode($raw, Carbon::parse('2026-08-17 18:00:00', 'UTC'), 1);

        $this->assertNotNull($record);
        $this->assertSame(20.3, $record['temp']);
        $this->assertSame(-99, $record['zjawisko']);
        $this->assertSame('', $record['zjawiskoTXT']);
        $this->assertSame(-99, $record['zachmurzenie']);
        $this->assertSame('', $record['zachmurzenieTXT']);
    }

    public function test_ogimet_txt_parser_joins_wrapped_lines(): void
    {
        $body = <<<TXT
# latest SYNOP reports from Poland
202608171800 0-20000-0-12205 12205 AAXX 17181 12205 11784 62601 10171 20140 30087 40096 57005 69932 70182 86500
                   333 10211 20171==
TXT;
        $items = (new OgimetClient)->parseUltimosTxt($body);
        $this->assertCount(1, $items);
        $this->assertSame(12205, $items[0]['stationId']);
        $this->assertSame('2026-08-17 18:00:00', $items[0]['observedAtUtc']->format('Y-m-d H:i:s'));
        $this->assertStringContainsString('70182', $items[0]['raw']);
        $this->assertStringContainsString('333', $items[0]['raw']);
    }
}
