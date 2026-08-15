<?php

namespace Tests\Unit;

use App\Support\ImgwMap;
use Tests\TestCase;

class ImgwMapTest extends TestCase
{
    public function test_cloud_icon_uses_phenomenon_then_cloud_cover(): void
    {
        $this->assertSame('w61.png', ImgwMap::cloudIcon('61', 8));
        $this->assertSame('w01.png', ImgwMap::cloudIcon('N', 2));
        $this->assertSame('w05.png', ImgwMap::cloudIcon('N', 8));
        $this->assertSame('w01.png', ImgwMap::cloudIcon('N', 99));
        $this->assertSame('', ImgwMap::cloudIcon('N', -99));
    }

    public function test_latest_by_station_keeps_newest_row(): void
    {
        $older = (object) ['idStacji' => 1, 'termin' => '2026-08-15 18:00:00', 'temp' => 10];
        $newer = (object) ['idStacji' => 1, 'termin' => '2026-08-15 19:00:00', 'temp' => 20];
        $other = (object) ['idStacji' => 2, 'termin' => '2026-08-15 18:00:00', 'temp' => 8];

        $latest = ImgwMap::latestByStation([$older, $other, $newer]);
        $this->assertCount(2, $latest);
        $byId = [];
        foreach ($latest as $row) {
            $byId[(int) $row->idStacji] = $row;
        }
        $this->assertSame(20, $byId[1]->temp);
        $this->assertSame(8, $byId[2]->temp);
    }

    public function test_hour_without_measurement_shows_bd_not_copied_icons(): void
    {
        $wroclaw18 = $this->mapRow(12424, 'Wrocław', '2026-08-15 18:00:00', 10, 190, 480);
        $wroclaw19 = $this->mapRow(12424, 'Wrocław', '2026-08-15 19:00:00', 20, 190, 480);
        $krakow19 = $this->mapRow(1, 'Kraków', '2026-08-15 19:00:00', 8, 120, 80);

        $frames = ImgwMap::frames(
            [$wroclaw19, $krakow19],
            [$wroclaw18, $wroclaw19, $krakow19],
            '2026-08-15 19:00:00',
            ['geo_lat' => 51.1, 'geo_lon' => 17.0]
        );

        $this->assertCount(2, $frames);
        $this->assertSame('18:00', $frames[0]['hour']);
        $this->assertSame('19:00', $frames[1]['hour']);
        $this->assertSame('15.08.2026', $frames[0]['date']);
        $this->assertSame('15.08.2026', $frames[1]['date']);
        $byHour = [];
        foreach ($frames as $frame) {
            $byHour[$frame['hour']] = $frame;
        }
        $this->assertCount(2, $byHour['18:00']['points']);
        $this->assertCount(2, $byHour['19:00']['points']);
        $temps18 = array_column($byHour['18:00']['points'], 'temp', 'name');
        $temps19 = array_column($byHour['19:00']['points'], 'temp', 'name');
        $this->assertSame('10', $temps18['Wrocław']);
        $this->assertSame('BD', $temps18['Kraków']);
        $this->assertSame('20', $temps19['Wrocław']);
        $this->assertSame('8', $temps19['Kraków']);
        $krakow18 = null;
        foreach ($byHour['18:00']['points'] as $point) {
            if ($point['name'] === 'Kraków') {
                $krakow18 = $point;
            }
        }
        $this->assertNotNull($krakow18);
        $this->assertTrue($krakow18['missing']);
        $this->assertSame('', $krakow18['icon']);
    }

    public function test_frame_keeps_date_when_hour_crosses_midnight(): void
    {
        $late = $this->mapRow(12424, 'Wrocław', '2026-08-15 23:00:00', 18, 190, 480);
        $early = $this->mapRow(12424, 'Wrocław', '2026-08-16 00:00:00', 17, 190, 480);
        $frames = ImgwMap::frames([$early], [$late, $early], '2026-08-16 00:00:00', [
            'geo_lat' => 51.1,
            'geo_lon' => 17.0,
        ]);

        $this->assertCount(2, $frames);
        $this->assertSame('23:00', $frames[0]['hour']);
        $this->assertSame('15.08.2026', $frames[0]['date']);
        $this->assertSame('0:00', $frames[1]['hour']);
        $this->assertSame('16.08.2026', $frames[1]['date']);
    }

    public function test_playback_ignores_stale_termins_beyond_12_hours(): void
    {
        $stale = $this->mapRow(12424, 'Wrocław', '2025-11-05 14:00:00', 1, 190, 480);
        $now = $this->mapRow(12424, 'Wrocław', '2026-08-15 15:00:00', 20, 190, 480);
        $frames = ImgwMap::frames([$now, $stale], [$now, $stale], '2026-08-15 15:00:00', [
            'geo_lat' => 51.1,
            'geo_lon' => 17.0,
        ]);
        $this->assertCount(1, $frames);
        $this->assertSame('15:00', $frames[0]['hour']);
        $this->assertSame('15.08.2026', $frames[0]['date']);
    }

    private function mapRow(int $id, string $name, string $termin, float $temp, int $x, int $y): object
    {
        return (object) [
            'idStacji' => $id,
            'nazwaStacji' => $name,
            'termin' => $termin,
            'temp' => $temp,
            'pozX' => $x,
            'pozY' => $y,
            'zjawiskoIkona' => 'N',
            'zachmurzenie' => 2,
            'zjawiskoTXT' => '',
        ];
    }
}
