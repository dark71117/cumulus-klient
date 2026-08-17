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

    public function test_decode_converts_utc_observation_to_local_hour(): void
    {
        $record = (new SynopDecoder)->decode(
            '12205 11784 62601 10171 20140 30087 40096 57005 69932 70182 86500',
            Carbon::parse('2026-08-17 20:00:00', 'UTC'),
            1
        );

        $this->assertSame('2026-08-17 22:00:00', $record['termin']);
    }

    public function test_current_hour_parser_keeps_only_requested_utc_hour(): void
    {
        $body = <<<TXT
202608171900 0-20000-0-12205 12205 AAXX 17191 12205 11784 62601 10171 20140 30087 40096 57005 69932 70182 86500=
202608172000 0-20000-0-12205 12205 AAXX 17201 12205 11784 62601 10171 20140 30087 40096 57005 69932 70182 86500=
TXT;
        $client = new class($body) extends OgimetClient {
            public function __construct(private string $fixture) {}

            public function rangePoland(Carbon $fromUtc, Carbon $toUtc): array
            {
                return $this->parseUltimosTxt($this->fixture);
            }
        };
        $items = $client->currentHourPoland(Carbon::parse('2026-08-17 22:00:00', 'Europe/Warsaw'));
        $this->assertCount(1, $items);
        $this->assertSame('2026-08-17 20:00:00', $items[0]['observedAtUtc']->format('Y-m-d H:i:s'));
    }
}
