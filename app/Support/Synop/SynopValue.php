<?php

namespace App\Support\Synop;

class SynopValue
{
    /** @param list<array{code: string, text: string}> $bits */
    public static function item(string $token, string $key, string $title, string $meaning, array $bits = []): array
    {
        return compact('token', 'key', 'title', 'meaning', 'bits');
    }

    public static function unknown(string $g, int $section): array
    {
        return self::item($g, $g, 'Nierozpoznana grupa', 'Nie rozpoznano tego członu w sekcji '.$section.'.');
    }

    public static function signedTenths(string $g): ?float
    {
        if (strlen($g) < 5 || ! ctype_digit(substr($g, 2, 3))) {
            return null;
        }
        $value = ((int) substr($g, 2, 3)) / 10;
        $sign = $g[1] ?? '0';
        if ($sign === '1') {
            return -$value;
        }

        return $sign === '0' ? $value : null;
    }

    public static function pressure(string $g): ?float
    {
        $pppp = substr($g, 1, 4);
        if (! ctype_digit($pppp)) {
            return null;
        }
        $tenths = (int) $pppp;

        return $tenths >= 5000 ? $tenths / 10 : 1000 + $tenths / 10;
    }

    public static function humidity(float $temp, float $dew): ?int
    {
        $e = 6.112 * exp((17.67 * $dew) / ($dew + 243.5));
        $es = 6.112 * exp((17.67 * $temp) / ($temp + 243.5));
        if ($es <= 0) {
            return null;
        }

        return (int) round(max(0, min(100, 100 * $e / $es)));
    }

    public static function deg(float $value): string
    {
        return sprintf('%0.1f °C', $value);
    }

    public static function windText(string $dd, string $ff, string $unit): string
    {
        if ($dd === '00' && ($ff === '00' || $ff === '//')) {
            return 'Cisza.';
        }
        if ($dd === '99') {
            return 'Wiatr zmienny, '.self::speedText($ff, $unit).'.';
        }

        return self::dirText($dd).', '.self::speedText($ff, $unit).'.';
    }

    public static function dirText(string $dd): string
    {
        if ($dd === '' || str_contains($dd, '/')) {
            return 'kierunek niepodany';
        }
        if ($dd === '00') {
            return 'cisza (brak kierunku)';
        }
        if ($dd === '99') {
            return 'kierunek zmienny';
        }
        if (! ctype_digit($dd)) {
            return 'kierunek nieznany';
        }
        $deg = (int) $dd * 10;

        return 'kierunek '.$deg.'° ('.SynopCodes::dirName($deg).')';
    }

    public static function speedText(string $ff, string $unit): string
    {
        if ($ff === '' || str_contains($ff, '/')) {
            return 'prędkość niepodana';
        }
        if ($ff === '99') {
            return 'prędkość ≥ 99 — patrz grupa 00fff';
        }

        return ctype_digit($ff) ? 'prędkość '.(int) $ff.' '.$unit : 'prędkość nieznana';
    }

    public static function snowDepth(string $sss): string
    {
        if ($sss === '' || str_contains($sss, '/') || ! ctype_digit($sss)) {
            return 'grubość pokrywy niepodana';
        }
        $code = (int) $sss;

        return match (true) {
            $code === 997 => 'mniej niż 0,5 cm',
            $code === 998 => 'pokrywa nieciągła',
            $code === 999 => 'pomiar niemożliwy',
            default => $code.' cm śniegu',
        };
    }

    public static function precip24(string $r24): string
    {
        if (! ctype_digit($r24)) {
            return 'Opad 24-godzinny niepodany.';
        }
        $code = (int) $r24;
        if ($code === 9999) {
            return 'Ślad opadu z 24 godz.';
        }

        return 'Suma z 24 godz.: '.($code / 10).' mm.';
    }
}
