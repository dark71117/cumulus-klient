<?php

namespace App\Support\Synop;

class SynopCodes
{
    public static function ir(string $d): string
    {
        return match ($d) {
            '0' => 'opad w sekcji 1 (6RRRt), w sekcji 3 pominięty',
            '1' => 'opad podany w sekcji 1 (grupa 6RRRt)',
            '2' => 'opad podany w sekcji 3 (grupa 6RRRt)',
            '3' => 'opad pominięty — nie wystąpił',
            '4' => 'opad pominięty — nie mierzono',
            default => 'wskaźnik opadu nieznany',
        };
    }

    public static function ix(string $d): string
    {
        return match ($d) {
            '1' => 'stacja załogowa, grupa 7wwW1W2 podana (ww WMO 4677)',
            '2' => 'stacja załogowa, grupa 7 pominięta (brak zjawiska)',
            '3' => 'stacja załogowa, grupa 7 pominięta (brak danych)',
            '4' => 'automat, grupa 7 podana (ww WMO 4677)',
            '5' => 'automat, grupa 7 pominięta (brak zjawiska)',
            '6' => 'automat, grupa 7 pominięta (brak danych)',
            '7' => 'automat, grupa 7 podana (wawa WMO 4680)',
            default => 'typ stacji / wskaźnik pogody nieznany',
        };
    }

    public static function cloudBase(string $h): string
    {
        return match ($h) {
            '0' => '0–50 m',
            '1' => '50–100 m',
            '2' => '100–200 m',
            '3' => '200–300 m',
            '4' => '300–600 m',
            '5' => '600–1000 m',
            '6' => '1000–1500 m',
            '7' => '1500–2000 m',
            '8' => '2000–2500 m',
            '9' => '> 2500 m lub brak chmur',
            default => 'podstawa chmur nieobserwowana',
        };
    }

    public static function oktas(string $n): string
    {
        return match ($n) {
            '0' => '0 oktan — niebo bez chmur',
            '1' => '1 okta lub mniej',
            '2' => '2 oktany (ok. 1/4 nieba)',
            '3' => '3 oktany',
            '4' => '4 oktany (połowa nieba)',
            '5' => '5 oktan',
            '6' => '6 oktan (ok. 3/4 nieba)',
            '7' => '7 oktan lub więcej, ale nie 8',
            '8' => '8 oktan — całkowite zachmurzenie',
            '9' => 'niebo niewidoczne',
            default => 'zachmurzenie nieobserwowane',
        };
    }

    public static function iw(string $d): string
    {
        return match ($d) {
            '0' => 'wiatr w m/s, szacowany',
            '1' => 'wiatr w m/s, z wiatromierza',
            '3' => 'wiatr w węzłach, szacowany',
            '4' => 'wiatr w węzłach, z wiatromierza',
            default => 'jednostka wiatru nieznana',
        };
    }

    public static function windUnit(string $iw): string
    {
        return in_array($iw, ['3', '4'], true) ? 'kt' : 'm/s';
    }

    public static function tendency(string $a): string
    {
        return match ($a) {
            '0' => 'wzrosło, potem spadło',
            '1' => 'wzrosło, potem stałe / wolniej rośnie',
            '2' => 'rośnie',
            '3' => 'spadło lub stałe, potem rośnie / szybciej rośnie',
            '4' => 'stałe',
            '5' => 'spadło, potem wzrosło',
            '6' => 'spada, potem stałe / wolniej spada',
            '7' => 'spada',
            '8' => 'wzrosło lub stałe, potem spada / szybciej spada',
            default => 'charakterystyka tendencji nieznana',
        };
    }

    public static function precipHours(string $t): string
    {
        return match ($t) {
            '1' => '6 godz.',
            '2' => '12 godz.',
            '3' => '18 godz.',
            '4' => '24 godz.',
            '5' => '1 godz.',
            '6' => '2 godz.',
            '7' => '3 godz.',
            '8' => '9 godz.',
            '9' => '15 godz.',
            default => 'okres opadu nieznany',
        };
    }

    public static function pastWeather(string $w): string
    {
        return match ($w) {
            '0' => 'chmury ≤ połowy nieba przez cały okres',
            '1' => 'chmury raz > połowy, raz ≤ połowy nieba',
            '2' => 'chmury > połowy nieba przez cały okres',
            '3' => 'burza piaskowa / pyłowa lub zamieć',
            '4' => 'mgła, mgła lodowa lub gęste zmętnienie',
            '5' => 'mżawka',
            '6' => 'deszcz',
            '7' => 'śnieg lub deszcz ze śniegiem',
            '8' => 'przelotne opady',
            '9' => 'burza z opadem lub bez',
            default => 'pogoda ubiegła niepodana',
        };
    }

    public static function cloudLow(string $c): string
    {
        return match ($c) {
            '0' => 'brak chmur CL',
            '1' => 'Cu humilis / fractus (ładna pogoda)',
            '2' => 'Cu mediocris / congestus',
            '3' => 'Cb calvus',
            '4' => 'Sc ze spłaszczonego Cu',
            '5' => 'Sc (nie ze spłaszczonego Cu)',
            '6' => 'St lub St fractus (ładna pogoda)',
            '7' => 'St/Cu fractus pogody niepogodnej',
            '8' => 'Cu i Sc o podstawach na różnych wysokościach',
            '9' => 'Cb capillatus',
            default => 'chmury niskie niewidoczne',
        };
    }

    public static function cloudMid(string $c): string
    {
        return match ($c) {
            '0' => 'brak chmur CM',
            '1' => 'As przezroczysty',
            '2' => 'As nieprzezroczysty lub Ns',
            '3' => 'Ac przezroczysty',
            '4' => 'łaty Ac (soczewkowate)',
            '5' => 'pasma Ac przezroczystego',
            '6' => 'Ac ze spłaszczonego Cu',
            '7' => 'Ac z As lub Ns, albo podwójna warstwa Ac',
            '8' => 'Ac kłębiasty (castellanus / floccus)',
            '9' => 'chaotyczne Ac na wielu poziomach',
            default => 'chmury średnie niewidoczne',
        };
    }

    public static function cloudHigh(string $c): string
    {
        return match ($c) {
            '0' => 'brak chmur CH',
            '1' => 'Ci nitkowate (nie gęstniejące)',
            '2' => 'Ci gęste, kłębiaste lub kowadło Cb',
            '3' => 'Ci gęste, zwykle z Cb',
            '4' => 'Ci haczykowate, gęstniejące',
            '5' => 'Ci i Cs, powłoka nie zakrywa całego nieba',
            '6' => 'Ci i Cs, powłoka zakrywa całe niebo',
            '7' => 'całkowita powłoka Cs',
            '8' => 'Cs nie przykrywające całego nieba',
            '9' => 'Cc, ewentualnie z Ci lub Cs',
            default => 'chmury wysokie niewidoczne',
        };
    }

    public static function visibility(string $vv): string
    {
        if ($vv === '' || str_contains($vv, '/')) {
            return 'widzialność niepodana';
        }
        $code = (int) $vv;
        $km = match (true) {
            $code <= 50 => $code / 10,
            $code >= 56 && $code <= 80 => $code - 50,
            $code >= 81 && $code <= 88 => 35 + ($code - 81) * 5,
            $code === 89 => 70,
            $code === 90 => 0.04,
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
            return 'widzialność nieznana (kod '.$vv.')';
        }
        if ($code === 89 || $code === 99) {
            return 'widzialność ≥ '.((int) $km).' km';
        }
        if ($code === 90) {
            return 'widzialność < 0,05 km';
        }
        if ($km < 1) {
            return 'widzialność '.rtrim(rtrim(sprintf('%0.2f', $km), '0'), '.').' km';
        }

        return 'widzialność '.(fmod($km, 1.0) === 0.0 ? (string) (int) $km : sprintf('%0.1f', $km)).' km';
    }

    public static function precipMm(string $rrr): string
    {
        if ($rrr === '' || str_contains($rrr, '/')) {
            return 'suma opadu niepodana';
        }
        $code = (int) $rrr;
        if ($code === 990) {
            return 'ślad opadu (< 0,1 mm)';
        }
        if ($code >= 991 && $code <= 999) {
            return sprintf('%0.1f mm', ($code - 990) / 10);
        }

        return $code.' mm';
    }

    public static function dirName(int $deg): string
    {
        return match (true) {
            $deg <= 22, $deg >= 338 => 'północny',
            $deg <= 68 => 'północno-wschodni',
            $deg <= 112 => 'wschodni',
            $deg <= 158 => 'południowo-wschodni',
            $deg <= 202 => 'południowy',
            $deg <= 248 => 'południowo-zachodni',
            $deg <= 291 => 'zachodni',
            default => 'północno-zachodni',
        };
    }
}
