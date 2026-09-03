<?php

namespace Tests\Unit;

use App\Support\Synop\SynopExplainer;
use Tests\TestCase;

class SynopExplainerTest extends TestCase
{
    public function test_explains_full_ogimet_groups_on_this_example(): void
    {
        $raw = '12205 11784 62601 10171 20140 30087 40096 57005 69932 70182 86500 333 10211 20171=';
        $out = (new SynopExplainer)->explain($raw);
        $byKey = [];
        foreach ($out['groups'] as $group) {
            $byKey[$group['key']] = $group;
        }

        $this->assertSame($raw, $out['raw']);
        $this->assertSame('Indeks stacji WMO', $byKey['IIiii']['title']);
        $this->assertStringContainsString('12205', $byKey['IIiii']['meaning']);
        $this->assertStringContainsString('widzialność 50 km', $byKey['irixhVV']['meaning']);
        $this->assertStringContainsString('260°', $byKey['Nddff']['meaning']);
        $this->assertStringContainsString('17.1 °C', $byKey['1sTTT']['meaning']);
        $this->assertStringContainsString('14.0 °C', $byKey['2sTdTdTd']['meaning']);
        $this->assertStringContainsString('1008.7 hPa', $byKey['3P0P0P0P0']['meaning']);
        $this->assertStringContainsString('1009.6 hPa', $byKey['4PPPP']['meaning']);
        $this->assertStringContainsString('0.5 hPa', $byKey['5appp']['meaning']);
        $this->assertStringContainsString('0.3 mm', $byKey['6RRRt']['meaning']);
        $this->assertStringContainsString('chmury zanikają', $byKey['7wwW1W2']['meaning']);
        $this->assertSame('Znacznik sekcji 3', $byKey['333']['title']);
        $this->assertStringContainsString('21.1 °C', $byKey['1sTxTxTx']['meaning']);
        $this->assertSame('=', $out['groups'][array_key_last($out['groups'])]['token']);
    }

    public function test_explains_aaxx_header_and_missing_codes(): void
    {
        $out = (new SynopExplainer)->explain('AAXX 17211 12205 04/// /2402 10203 40112');
        $this->assertSame('AAXX', $out['groups'][0]['token']);
        $this->assertStringContainsString('lądowa', $out['groups'][0]['meaning']);
        $this->assertStringContainsString('21 UTC', $out['groups'][1]['meaning']);
        $this->assertStringContainsString('20.3 °C', $out['groups'][5]['meaning']);
        $this->assertStringContainsString('niepodana', $out['groups'][3]['meaning']);
        $this->assertStringContainsString('240°', $out['groups'][4]['meaning']);
    }
}
