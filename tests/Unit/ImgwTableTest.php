<?php

namespace Tests\Unit;

use App\Support\ImgwTable;
use Tests\TestCase;

class ImgwTableTest extends TestCase
{
    public function test_frames_use_delay_window_and_omit_future_or_missing(): void
    {
        $wroclaw18 = $this->tableRow(1, 'Wrocław', 'Dolnośląskie', '2026-08-15 18:00:00', 10);
        $wroclaw19 = $this->tableRow(1, 'Wrocław', 'Dolnośląskie', '2026-08-15 19:00:00', 20);
        $krakow19 = $this->tableRow(2, 'Kraków', 'Małopolskie', '2026-08-15 19:00:00', 8);

        $frames = ImgwTable::frames(
            [$wroclaw19, $krakow19],
            [$wroclaw18, $wroclaw19, $krakow19],
            '2026-08-15 19:00:00',
            24
        );

        $this->assertCount(2, $frames);
        $this->assertSame('18:00', $frames[0]['hour']);
        $this->assertSame('15.08.2026', $frames[0]['date']);
        $this->assertSame('19:00', $frames[1]['hour']);
        $names18 = array_column($frames[0]['rows'], 'nazwaStacji');
        $names19 = array_column($frames[1]['rows'], 'nazwaStacji');
        $this->assertSame(['Wrocław'], $names18);
        $this->assertSame(['Wrocław', 'Kraków'], $names19);
        $this->assertSame('10.0', $frames[0]['rows'][0]['temp']);
        $this->assertSame(0, $frames[0]['rows'][0]['godzina']);
    }

    public function test_frame_marks_station_delayed_up_to_two_hours(): void
    {
        $now = $this->tableRow(1, 'Wrocław', 'Dolnośląskie', '2026-08-15 19:00:00', 20);
        $hourAgo = $this->tableRow(2, 'Amsterdam', 'EUROPA ZACHODNIA', '2026-08-15 18:00:00', 9);
        $twoAgo = $this->tableRow(3, 'Kopenhaga', 'EUROPA PÓŁNOCNA', '2026-08-15 17:00:00', 4);
        $tooOld = $this->tableRow(4, 'Oslo', 'EUROPA PÓŁNOCNA', '2026-08-15 16:00:00', 1);

        $frames = ImgwTable::frames(
            [$now, $hourAgo, $twoAgo, $tooOld],
            [$now, $hourAgo, $twoAgo, $tooOld],
            '2026-08-15 19:00:00',
            24
        );

        $current = $frames[count($frames) - 1];
        $byName = [];
        foreach ($current['rows'] as $row) {
            $byName[$row['nazwaStacji']] = $row;
        }
        $this->assertSame(0, $byName['Wrocław']['godzina']);
        $this->assertSame(1, $byName['Amsterdam']['godzina']);
        $this->assertSame(2, $byName['Kopenhaga']['godzina']);
        $this->assertArrayNotHasKey('Oslo', $byName);
        $this->assertSame(0, $byName['Wrocław']['europe']);
        $this->assertSame(1, $byName['Amsterdam']['europe']);
        $this->assertStringContainsString('pogrubione', ImgwTable::item(
            $this->tableRow(1, 'Wrocław', 'Dolnośląskie', '2026-08-15 19:00:00', 20, 'słaby &lt;span class="pogrubione"&gt;deszcz&lt;/span&gt;'),
            null
        )['zjawiskoTXT']);
    }

    public function test_row_near_hour_prefers_exact_match(): void
    {
        $older = $this->tableRow(1, 'Wrocław', 'Dolnośląskie', '2026-08-15 18:00:00', 10);
        $exact = $this->tableRow(1, 'Wrocław', 'Dolnośląskie', '2026-08-15 19:00:00', 20);
        $match = ImgwTable::rowNearHour([$older, $exact], '2026-08-15 19:00:00');
        $this->assertNotNull($match);
        $this->assertEquals(20, $match->temp);
    }

    private function tableRow(
        int $id,
        string $name,
        string $region,
        string $termin,
        float $temp,
        string $zjawiskoTXT = ''
    ): object {
        return (object) [
            'idStacji' => $id,
            'nazwaStacji' => $name,
            'region' => $region,
            'termin' => $termin,
            'temp' => $temp,
            'tempOdcz' => $temp - 1,
            'zachmurzenieTXT' => 'małe',
            'zjawiskoTXT' => $zjawiskoTXT,
            'zjawiskoKolor' => '',
            'zjawiskoPoprzednie' => '',
            'widzialnosc' => '10',
            'wiatr' => 'pn / 4',
        ];
    }
}
