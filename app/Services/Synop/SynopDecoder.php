<?php

namespace App\Services\Synop;

use App\Support\Synop\WmoWeather;
use Carbon\CarbonInterface;

class SynopDecoder
{
    private const MISSING = -99.0;

    /**
     * @return array<string, mixed>|null
     */
    public function decode(string $raw, ?CarbonInterface $observedAtUtc = null, int $windUnit = 1): ?array
    {
        $groups = $this->groups($raw);
        if ($groups === [] || ! preg_match('/^\d{5}$/', $groups[0])) {
            return null;
        }

        $stationId = (int) array_shift($groups);
        $ixhVV = array_shift($groups) ?? '';
        $windGroup = array_shift($groups) ?? '';
        if ($ixhVV === '' || $windGroup === '') {
            return null;
        }

        $wxInd = $this->digit($ixhVV, 1, 1);
        $cloudBase = $this->cloudBase($this->char($ixhVV, 2));
        $visibility = $this->visibility(substr($ixhVV, 3, 2));
        $clouds = $this->oktas($this->char($windGroup, 0));
        [$dir, $speedMs, $windTxt, $gustKmh] = $this->wind($windGroup, $groups, $windUnit);

        $temp = self::MISSING;
        $dew = self::MISSING;
        $pStation = self::MISSING;
        $pSea = self::MISSING;
        $tendency = -99;
        $precip = self::MISSING;
        $precipPeriod = -99;
        $weather = -99;
        $section = 1;

        foreach ($groups as $group) {
            if ($group === '333' || $group === '555') {
                $section = (int) $group[0];
                continue;
            }
            if ($section === 1) {
                $this->section1($group, $temp, $dew, $pStation, $pSea, $tendency, $precip, $precipPeriod, $weather);
            } elseif ($section === 3 && str_starts_with($group, '911') && ctype_digit(substr($group, 3, 2))) {
                $gustMs = $this->speedToMs((int) substr($group, 3, 2), $windUnit);
                $gustKmh = (int) round($gustMs * 3.6);
            }
        }

        $tempOdcz = WmoWeather::windChill($temp, $speedMs > 0 ? $speedMs * 3.6 : 0);
        $weatherMeta = WmoWeather::describe($weather, $wxInd);
        $humidity = $this->humidity($temp, $dew);
        $termin = $observedAtUtc?->copy()->timezone(config('app.timezone'))->startOfHour();

        $windDisplay = $windTxt;
        if ($speedMs > 0 && $windTxt !== 'cisza' && $windTxt !== 'zmienny') {
            $kmh = (int) round($speedMs * 3.6);
            $windDisplay .= ' / '.$kmh;
            if ($gustKmh > $kmh) {
                $windDisplay .= ' / '.$gustKmh;
            }
        }

        return [
            'idStacji' => $stationId,
            'termin' => $termin?->format('Y-m-d H:i:s'),
            'zTerminu' => 1,
            'temp' => $temp,
            'tempOdcz' => $tempOdcz,
            'cisnienie' => $pSea,
            'cisnienieTXT' => WmoWeather::pressure($pStation, $pSea, $tendency),
            'wiatrP' => $speedMs > 0 ? (int) round($speedMs * 3.6) : ($windTxt === 'cisza' ? 0 : -99),
            'wiatrK' => $dir,
            'porywy' => $gustKmh > 0 ? $gustKmh : -99,
            'wiatr' => $windDisplay,
            'wiatrMS' => $speedMs,
            'widzialnosc' => $visibility,
            'zachmurzenie' => $clouds,
            'zachmurzenieTXT' => WmoWeather::clouds($clouds),
            'zjawisko' => $weather,
            'zjawiskoTXT' => $weatherMeta['zjawiskoTXT'],
            'zjawiskoKolor' => $weatherMeta['zjawiskoKolor'],
            'zjawiskoPoprzednie' => $weatherMeta['zjawiskoPoprzednie'],
            'zjawiskoIkona' => $weatherMeta['zjawiskoIkona'],
            'zachmurzeniePodstawa' => $cloudBase,
            'wilgotnosc' => $humidity,
            'wysokoscOpadu' => $precip,
            'okresOpadu' => $precipPeriod,
            'zjawiskoSynop' => $weatherMeta['zjawiskoSynop'],
            'cisnienieStacja' => $pStation,
            'cisnienieMorze' => $pSea,
            'synop' => rtrim(preg_replace('/\s+/', ' ', $raw) ?? $raw, "=\n "),
            'synop_raw' => self::rawMessage($raw),
            'metar' => '',
            'zrodlo' => 'ogimet',
        ];
    }

    public static function rawMessage(string $raw): string
    {
        $stripped = rtrim(preg_replace('/\s+/', ' ', trim($raw)) ?? $raw, "=\n\r ");

        return $stripped === '' ? '' : $stripped.'=';
    }

    /** @return list<string> */
    private function groups(string $raw): array
    {
        $raw = strtoupper(trim($raw));
        $raw = preg_replace('/\s+/', ' ', $raw) ?? $raw;
        $raw = rtrim($raw, '=');
        $parts = array_values(array_filter(explode(' ', $raw), fn ($p) => $p !== ''));
        if (isset($parts[0]) && $parts[0] === 'AAXX') {
            array_shift($parts);
            if (isset($parts[0]) && preg_match('/^\d{5}$/', $parts[0])) {
                array_shift($parts);
            }
        }

        return $parts;
    }

    private function section1(
        string $group,
        float &$temp,
        float &$dew,
        float &$pStation,
        float &$pSea,
        int &$tendency,
        float &$precip,
        int &$precipPeriod,
        int &$weather
    ): void {
        $first = $group[0] ?? '';
        if ($first === '1') {
            $temp = $this->signedTenths($group);
        } elseif ($first === '2' && ! str_starts_with($group, '29')) {
            $dew = $this->signedTenths($group);
        } elseif ($first === '3') {
            $pStation = $this->pressure($group);
        } elseif ($first === '4' && ($group[1] ?? '') !== '8') {
            $pSea = $this->pressure($group);
        } elseif ($first === '5' && ctype_digit($group) && strlen($group) === 5) {
            $tendency = (int) $group[1];
        } elseif ($first === '6' && ctype_digit(substr($group, 1, 3))) {
            $precip = $this->precipMm(substr($group, 1, 3));
            $precipPeriod = $this->precipHours((int) substr($group, 4, 1));
        } elseif ($first === '7' && strlen($group) >= 3) {
            $ww = substr($group, 1, 2);
            if (ctype_digit($ww)) {
                $weather = (int) $ww;
            }
        }
    }

    /** @param list<string> $groups */
    private function wind(string $group, array $groups, int $windUnit): array
    {
        $dirCode = substr($group, 1, 2);
        $ff = substr($group, 3, 2);
        $dir = -99;
        $txt = '';
        $ms = 0.0;
        if ($dirCode === '00' && ($ff === '00' || $ff === '//')) {
            return [-99, 0.0, 'cisza', 0];
        }
        if ($dirCode === '99') {
            $txt = 'zmienny';
        } elseif (ctype_digit($dirCode)) {
            $dir = (int) $dirCode * 10;
            $txt = $this->dirName($dir);
        }
        if (ctype_digit($ff)) {
            $ms = $this->speedToMs((int) $ff, $windUnit);
        }
        $gust = 0;
        foreach ($groups as $item) {
            if (str_starts_with($item, '911') && ctype_digit(substr($item, 3, 2))) {
                $gust = (int) round($this->speedToMs((int) substr($item, 3, 2), $windUnit) * 3.6);
            }
        }
        if ($ms <= 0 && $txt !== 'zmienny') {
            return [-99, 0.0, 'cisza', 0];
        }

        return [$dir, $ms, $txt, $gust];
    }

    private function dirName(int $deg): string
    {
        return match (true) {
            $deg <= 22, $deg >= 338 => 'pn',
            $deg <= 68 => 'pn-wsch',
            $deg <= 112 => 'wsch',
            $deg <= 158 => 'pd-wsch',
            $deg <= 202 => 'pd',
            $deg <= 248 => 'pd-zach',
            $deg <= 291 => 'zach',
            default => 'pn-zach',
        };
    }

    private function visibility(string $vv): string
    {
        if ($vv === '' || str_contains($vv, '/')) {
            return '-';
        }
        $code = (int) $vv;
        $km = match (true) {
            $code <= 50 => $code / 10,
            $code >= 56 && $code <= 80 => $code - 50,
            $code >= 81 && $code <= 88 => 35 + ($code - 81) * 5,
            $code === 89 => 70,
            $code === 90 => 0.05,
            $code === 91 => 0.05,
            $code === 92 => 0.2,
            $code === 93 => 0.5,
            $code === 94 => 1.0,
            $code === 95 => 2.0,
            $code === 96 => 4.0,
            $code === 97 => 10.0,
            $code === 98 => 20.0,
            $code === 99 => 50.0,
            default => null,
        };
        if ($km === null) {
            return '-';
        }
        if ($code === 89 || $code === 99) {
            return '&ge; '.((int) $km);
        }
        if ($km < 0.1) {
            return sprintf('%0.2f', $km);
        }
        if ($km < 5) {
            return sprintf('%0.1f', $km);
        }

        return (string) (int) $km;
    }

    private function cloudBase(string $h): string|int
    {
        return match ($h) {
            '0' => 0,
            '1' => 50,
            '2' => 100,
            '3' => 200,
            '4' => 300,
            '5' => 600,
            '6' => 1000,
            '7' => 1500,
            '8' => 2000,
            '9' => '>2500',
            default => -99,
        };
    }

    private function oktas(string $n): int
    {
        return ctype_digit($n) ? (int) $n : -99;
    }

    private function signedTenths(string $group): float
    {
        if (strlen($group) < 5 || ! ctype_digit(substr($group, 2, 3))) {
            return self::MISSING;
        }
        $value = ((int) substr($group, 2, 3)) / 10;
        $sign = $group[1] ?? '0';
        if ($sign === '1') {
            return -$value;
        }

        return $sign === '0' ? $value : self::MISSING;
    }

    private function pressure(string $group): float
    {
        $pppp = substr($group, 1, 4);
        if (! ctype_digit($pppp)) {
            return self::MISSING;
        }
        $tenths = (int) $pppp;

        return $tenths >= 5000 ? $tenths / 10 : 1000 + $tenths / 10;
    }

    private function precipMm(string $rrr): float
    {
        $code = (int) $rrr;
        if ($code === 990) {
            return 0.0;
        }
        if ($code >= 991 && $code <= 999) {
            return ($code - 990) / 10;
        }

        return (float) $code;
    }

    private function precipHours(int $t): int
    {
        return match ($t) {
            1 => 6, 2 => 12, 3 => 18, 4 => 24, 5 => 1, 6 => 2, 7 => 3, 8 => 9, 9 => 15,
            default => -99,
        };
    }

    private function humidity(float $temp, float $dew): int
    {
        if ($temp <= -99 || $dew <= -99) {
            return -99;
        }
        $e = 6.112 * exp((17.67 * $dew) / ($dew + 243.5));
        $es = 6.112 * exp((17.67 * $temp) / ($temp + 243.5));
        if ($es <= 0) {
            return -99;
        }

        return (int) round(max(0, min(100, 100 * $e / $es)));
    }

    private function speedToMs(int $ff, int $iw): float
    {
        if (in_array($iw, [3, 4], true)) {
            return $ff * 0.514444;
        }

        return (float) $ff;
    }

    private function digit(string $group, int $pos, int $fallback): int
    {
        $ch = $this->char($group, $pos);

        return ctype_digit($ch) ? (int) $ch : $fallback;
    }

    private function char(string $group, int $pos): string
    {
        return $group[$pos] ?? '/';
    }
}
