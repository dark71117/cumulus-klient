<?php

namespace Tests\Unit;

use App\Services\Synop\SynopDecoder;
use DateTimeImmutable;
use DateTimeZone;
use Tests\TestCase;

class OgimetCliParseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once base_path('scripts/gfs-ogimet/Lib/Ogimet/OgimetParse.php');
    }

    public function test_cli_csv_parser_reads_current_hour(): void
    {
        $body = <<<TXT
12205,2026,08,17,21,00,AAXX 17211 12205 45784 82502 10162 20127 30088 40097 51001 333 81/50 88/58 91203 91302=
12100,2026,08,17,20,00,AAXX 17201 12100 11581 62101 10166 20137 30080 40087 57010=
TXT;
        $items = \OgimetParse::parseGetsynopCsv($body);
        $this->assertCount(2, $items);
        $this->assertSame(12205, $items[0]['stationId']);
        $this->assertSame('2026-08-17 21:00:00', $items[0]['observedAtUtc']->format('Y-m-d H:i:s'));
        $this->assertStringStartsWith('12205', $items[0]['raw']);

        $hour = \OgimetParse::filterHour(
            $items,
            new DateTimeImmutable('2026-08-17 21:00:00', new DateTimeZone('UTC'))
        );
        $this->assertCount(1, $hour);
        $this->assertSame(12205, $hour[0]['stationId']);
    }

    public function test_cli_txt_parser_joins_wrapped_lines(): void
    {
        $body = <<<TXT
# latest SYNOP reports from Poland
202608171800 0-20000-0-12205 12205 AAXX 17181 12205 11784 62601 10171 20140 30087 40096 57005 69932 70182 86500
                   333 10211 20171==
TXT;
        $items = \OgimetParse::parseUltimosTxt($body);
        $this->assertCount(1, $items);
        $this->assertSame(12205, $items[0]['stationId']);
        $this->assertStringContainsString('70182', $items[0]['raw']);
        $this->assertStringContainsString('333', $items[0]['raw']);
    }

    public function test_decoder_accepts_datetime_immutable(): void
    {
        $raw = '12205 11784 62601 10171 20140 30087 40096 57005 69932 70182 86500 333 10211 20171';
        $observed = new DateTimeImmutable('2026-08-17 18:00:00', new DateTimeZone('UTC'));
        $record = (new SynopDecoder)->decode($raw, $observed, 1);

        $this->assertNotNull($record);
        $this->assertSame(17.1, $record['temp']);
        $this->assertSame('2026-08-17 20:00:00', $record['termin']);
        $this->assertStringContainsString('chmury zanikają', $record['zjawiskoTXT']);
    }
}
