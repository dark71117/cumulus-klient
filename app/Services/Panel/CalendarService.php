<?php

namespace App\Services\Panel;

use App\Support\Astro;
use App\Support\CustomerContext;
use App\Support\Nameday;

class CalendarService
{
    public function data(): array
    {
        $customer = CustomerContext::get();

        return [
            'names' => Nameday::getNames(),
            'astro' => Astro::getData((float) ($customer['geo_lat'] ?? 51.1), (float) ($customer['geo_lon'] ?? 17.0)),
        ];
    }
}
