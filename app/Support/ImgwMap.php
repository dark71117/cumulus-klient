<?php

namespace App\Support;

use Carbon\Carbon;

class ImgwMap
{
    public static function cloudIcon(string $zjaw, mixed $clouds): string
    {
        if ($zjaw !== 'N' && $zjaw !== '' && $zjaw != -99) {
            return 'w'.$zjaw.'.png';
        }
        $map = [0 => 'w01.png', 1 => 'w01.png', 2 => 'w01.png', 3 => 'w02.png', 4 => 'w02.png', 5 => 'w03.png', 6 => 'w03.png', 7 => 'w04.png', 8 => 'w05.png'];

        return $map[(int) $clouds] ?? '';
    }

    public static function isNight(string $termin, float $lat, float $lon): bool
    {
        $ts = Carbon::parse($termin)->getTimestamp();
        $sun = date_sun_info($ts, $lat, $lon);

        return $ts <= $sun['sunrise'] || $ts >= $sun['sunset'];
    }

    public static function point(object $row, bool $night): ?array
    {
        $coords = ImgwStationCoords::latLon(
            (int) $row->idStacji,
            (int) $row->pozX,
            (int) $row->pozY
        );
        if ($coords === null) {
            return null;
        }
        $temp = ($row->temp !== null && $row->temp != -99) ? number_format((float) $row->temp, 0) : '';
        if ($temp === '-0') {
            $temp = '0';
        }
        $zjaw = explode(';', (string) $row->zjawiskoIkona);
        $ikona = self::cloudIcon($zjaw[0] ?? 'N', $row->zachmurzenie);
        if ($night && $ikona) {
            $nightIcon = 'n'.$ikona;
            if (file_exists(public_path('images/ikony2/'.$nightIcon))) {
                $ikona = $nightIcon;
            }
        }

        return [
            'id' => (int) $row->idStacji,
            'name' => $row->nazwaStacji,
            'lat' => $coords[0],
            'lon' => $coords[1],
            'temp' => $temp,
            'icon' => $ikona ? asset('images/ikony2/'.$ikona) : '',
            'text' => ImgwText::plain($row->zjawiskoTXT ?? ''),
        ];
    }

    public static function frames(iterable $latestRows, iterable $historyRows, string $maxTermin, array $customer): array
    {
        $lat = (float) ($customer['geo_lat'] ?? 52);
        $lon = (float) ($customer['geo_lon'] ?? 19);
        $maxKey = Carbon::parse($maxTermin)->format('Y-m-d H:00:00');
        $byHour = [];
        foreach ($historyRows as $row) {
            $key = Carbon::parse($row->termin)->format('Y-m-d H:00:00');
            if ($key === $maxKey) {
                continue;
            }
            $byHour[$key][] = $row;
        }
        ksort($byHour);
        $frames = [];
        foreach ($byHour as $termin => $rows) {
            $frames[] = self::frame($termin, $rows, $lat, $lon);
        }
        $frames[] = self::frame($maxTermin, $latestRows, $lat, $lon);

        return $frames;
    }

    public static function latestByStation(iterable $rows): array
    {
        $latest = [];
        foreach ($rows as $row) {
            $id = (int) $row->idStacji;
            if (! isset($latest[$id]) || strcmp((string) $row->termin, (string) $latest[$id]->termin) > 0) {
                $latest[$id] = $row;
            }
        }

        return array_values($latest);
    }

    public static function frame(string $termin, iterable $rows, float $lat, float $lon): array
    {
        $night = self::isNight($termin, $lat, $lon);
        $points = [];
        foreach ($rows as $row) {
            $point = self::point($row, $night);
            if ($point !== null) {
                $points[] = $point;
            }
        }

        return [
            'hour' => Carbon::parse($termin)->format('G').':00',
            'termin' => Carbon::parse($termin)->format('Y-m-d H:i:s'),
            'night' => $night ? 1 : 0,
            'points' => $points,
        ];
    }
}
