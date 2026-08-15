<?php

namespace App\Support;

class ImgwRegion
{
    public static function isEurope(string $region): bool
    {
        return str_starts_with(mb_strtoupper(trim($region)), 'EUROPA');
    }
}
