<?php

namespace Tests\Unit;

use App\Support\ImgwRegion;
use Tests\TestCase;

class ImgwRegionTest extends TestCase
{
    public function test_voivodeship_is_not_europe(): void
    {
        $this->assertFalse(ImgwRegion::isEurope('Dolnośląskie'));
        $this->assertFalse(ImgwRegion::isEurope('Kujawsko-Pomorskie'));
        $this->assertFalse(ImgwRegion::isEurope(''));
    }

    public function test_europa_prefix_is_europe(): void
    {
        $this->assertTrue(ImgwRegion::isEurope('EUROPA CENTRALNA'));
        $this->assertTrue(ImgwRegion::isEurope('Europa Wschodnia'));
        $this->assertTrue(ImgwRegion::isEurope(' europa północna '));
    }
}
