<?php

namespace Tests\Unit;

use App\Support\ImgwStationCoords;
use Tests\TestCase;

class ImgwStationCoordsTest extends TestCase
{
    public function test_known_station_uses_geographic_coordinates(): void
    {
        [$lat, $lon] = ImgwStationCoords::latLon(12424);
        $this->assertEqualsWithDelta(51.103, $lat, 0.01);
        $this->assertEqualsWithDelta(16.900, $lon, 0.01);
    }

    public function test_unknown_station_falls_back_to_bitmap_pixels(): void
    {
        $coords = ImgwStationCoords::latLon(1, 120, 80);
        $this->assertNotNull($coords);
        $this->assertGreaterThan(49, $coords[0]);
        $this->assertLessThan(55, $coords[0]);
        $this->assertGreaterThan(14, $coords[1]);
        $this->assertLessThan(24, $coords[1]);
    }

    public function test_station_without_position_is_skipped(): void
    {
        $this->assertNull(ImgwStationCoords::latLon(99999));
    }
}
