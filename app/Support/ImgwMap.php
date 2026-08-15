<?php

namespace App\Support;

use Carbon\Carbon;

class ImgwMap
{
    public const PLAYBACK_HOURS = 12;
    public static function cloudIcon(string $zjaw, mixed $clouds): string
    {
        $zjaw = trim($zjaw);
        if ($zjaw !== 'N' && $zjaw !== '' && $zjaw != -99) {
            return 'w'.$zjaw.'.png';
        }
        $map = [0 => 'w01.png', 1 => 'w01.png', 2 => 'w01.png', 3 => 'w02.png', 4 => 'w02.png', 5 => 'w03.png', 6 => 'w03.png', 7 => 'w04.png', 8 => 'w05.png'];

        $clouds = (int) $clouds;
        if ($clouds < 0) {
            return '';
        }

        return $map[$clouds] ?? 'w01.png';
    }

    public static function isNight(string $termin, float $lat, float $lon): bool
    {
        $ts = Carbon::parse($termin)->getTimestamp();
        $sun = @date_sun_info($ts, $lat, $lon);
        if (! is_array($sun) || ! isset($sun['sunrise'], $sun['sunset'])) {
            return false;
        }

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
            'name' => (string) $row->nazwaStacji,
            'lat' => $coords[0],
            'lon' => $coords[1],
            'temp' => $temp,
            'icon' => $ikona ? parse_url(asset('images/ikony2/'.$ikona), PHP_URL_PATH) : '',
            'text' => ImgwText::plain($row->zjawiskoTXT ?? ''),
        ];
    }

    public static function frames(iterable $latestRows, iterable $historyRows, string $maxTermin, array $customer): array
    {
        $lat = (float) ($customer['geo_lat'] ?? 52);
        $lon = (float) ($customer['geo_lon'] ?? 19);
        $byStation = self::rowsByStation($historyRows, $latestRows);
        $frames = [];
        foreach (self::hourKeys($byStation, $maxTermin) as $termin) {
            $frames[] = self::frameFromStations($termin, $byStation, $lat, $lon);
        }

        return $frames;
    }

    public static function rowsByStation(iterable $historyRows, iterable $latestRows): array
    {
        $byStation = [];
        foreach ([$historyRows, $latestRows] as $set) {
            foreach ($set as $row) {
                $id = (int) $row->idStacji;
                $key = (string) $row->termin;
                $byStation[$id][$key] = $row;
            }
        }
        foreach ($byStation as $id => $rows) {
            ksort($byStation[$id]);
        }

        return $byStation;
    }

    public static function hourKeys(array $byStation, string $maxTermin, int $maxHours = self::PLAYBACK_HOURS): array
    {
        $max = Carbon::parse($maxTermin)->startOfHour();
        $floor = $max->copy()->subHours($maxHours);
        $hours = [$max->format('Y-m-d H:00:00') => true];
        foreach ($byStation as $rows) {
            foreach ($rows as $row) {
                if (empty($row->termin)) {
                    continue;
                }
                $termin = Carbon::parse($row->termin)->startOfHour();
                if ($termin->lt($floor) || $termin->gt($max)) {
                    continue;
                }
                $hours[$termin->format('Y-m-d H:00:00')] = true;
            }
        }
        ksort($hours);

        return array_keys($hours);
    }

    public static function rowAtHour(array $rows, string $hourKey): ?object
    {
        foreach ($rows as $row) {
            if (Carbon::parse($row->termin)->format('Y-m-d H') === Carbon::parse($hourKey)->format('Y-m-d H')) {
                return $row;
            }
        }

        return null;
    }

    public static function missingPoint(object $row, bool $night): ?array
    {
        $point = self::point($row, $night);
        if ($point === null) {
            return null;
        }
        $point['temp'] = 'BD';
        $point['icon'] = '';
        $point['text'] = 'brak danych';
        $point['missing'] = true;

        return $point;
    }

    public static function frameFromStations(string $termin, array $byStation, float $lat, float $lon): array
    {
        $night = self::isNight($termin, $lat, $lon);
        $hourKey = Carbon::parse($termin)->format('Y-m-d H:00:00');
        $points = [];
        foreach ($byStation as $rows) {
            $match = self::rowAtHour($rows, $hourKey);
            $anchor = $match ?? (reset($rows) ?: null);
            if (! $anchor) {
                continue;
            }
            $point = $match ? self::point($match, $night) : self::missingPoint($anchor, $night);
            if ($point !== null) {
                $points[] = $point;
            }
        }

        $at = Carbon::parse($termin);

        return [
            'hour' => $at->format('G').':00',
            'date' => $at->format('d.m.Y'),
            'termin' => $at->format('Y-m-d H:i:s'),
            'night' => $night ? 1 : 0,
            'points' => $points,
        ];
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
}
