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
}
