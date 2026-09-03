<?php

namespace App\Support\Synop;

class SynopGroups
{
    /** @param array<string, mixed> $ctx */
    public static function section1(string $g, array &$ctx): array
    {
        $f = $g[0] ?? '';
        if (str_starts_with($g, '00')) {
            return self::extraWind($g, $ctx);
        }
        if ($f === '1') {
            return self::tempGroup($g, $ctx, '1sTTT', 'Temperatura powietrza', 'temp', 'Temperatura powietrza');
        }
        if ($f === '2' && str_starts_with($g, '29')) {
            return self::humidityGroup($g);
        }
        if ($f === '2') {
            return self::dewGroup($g, $ctx);
        }
        if ($f === '3') {
            return self::pressureGroup($g, '3P0P0P0P0', 'Ciśnienie na stacji (QFE)');
        }
        if ($f === '4' && ($g[1] ?? '') === '8') {
            return SynopValue::item($g, '4a3hhh', 'Wysokość geopotencjału', 'Izobara 850 hPa, wysokość w geopotencjalnych dekametrach.');
        }
        if ($f === '4') {
            return self::pressureGroup($g, '4PPPP', 'Ciśnienie zredukowane do poziomu morza (QNH)');
        }
        if ($f === '5') {
            return self::tendencyGroup($g);
        }
        if ($f === '6') {
            return self::precipGroup($g);
        }
        if ($f === '7') {
            return self::weatherGroup($g, $ctx);
        }
        if ($f === '8') {
            return self::clouds8($g);
        }
        if ($f === '9') {
            $hh = substr($g, 1, 2);
            $mm = substr($g, 3, 2);

            return SynopValue::item($g, '9GGgg', 'Dokładny czas obserwacji', 'Godzina '.$hh.':'.$mm.' UTC.', [
                ['code' => 'GG='.$hh, 'text' => 'godzina UTC'],
                ['code' => 'gg='.$mm, 'text' => 'minuty'],
            ]);
        }

        return SynopValue::unknown($g, 1);
    }

    /** @param array<string, mixed> $ctx */
    public static function section3(string $g, array &$ctx): array
    {
        $f = $g[0] ?? '';
        if (str_starts_with($g, '911') && ctype_digit(substr($g, 3, 2))) {
            $ff = substr($g, 3, 2);
            $unit = SynopCodes::windUnit((string) $ctx['iw']);

            return SynopValue::item($g, '911ff', 'Poryw wiatru', 'Poryw '.$ff.' '.$unit.'.', [
                ['code' => '911', 'text' => 'grupa porywu w sekcji 3'],
                ['code' => 'ff='.$ff, 'text' => $ff.' '.$unit],
            ]);
        }
        if ($f === '1') {
            return self::tempGroup($g, $ctx, '1sTxTxTx', 'Temperatura maksymalna', null, 'Maksimum (zwykle z 12 godz.)');
        }
        if ($f === '2') {
            return self::tempGroup($g, $ctx, '2sTnTnTn', 'Temperatura minimalna', null, 'Minimum (zwykle z 12 godz.)');
        }
        if ($f === '4') {
            $sss = substr($g, 2, 3);

            return SynopValue::item($g, '4E\'sss', 'Pokrywa śnieżna', SynopValue::snowDepth($sss), [
                ['code' => 'E\'='.($g[1] ?? '/'), 'text' => 'stan gruntu ze śniegiem'],
                ['code' => 'sss='.$sss, 'text' => SynopValue::snowDepth($sss)],
            ]);
        }
        if (str_starts_with($g, '55')) {
            $sss = substr($g, 2, 3);

            return SynopValue::item($g, '55SSS', 'Usłonecznienie', ctype_digit($sss) ? 'Czas usłonecznienia '.(((int) $sss) / 10).' godz.' : 'Usłonecznienie niepodane.');
        }
        if ($f === '6') {
            return self::precipGroup($g);
        }
        if ($f === '7' && ctype_digit(substr($g, 1, 4))) {
            return SynopValue::item($g, '7R24R24R24R24', 'Opad 24-godzinny', SynopValue::precip24(substr($g, 1, 4)));
        }
        if ($f === '8') {
            return self::cloudLayer($g);
        }

        return SynopValue::unknown($g, 3);
    }

    /** @param array<string, mixed> $ctx */
    private static function extraWind(string $g, array $ctx): array
    {
        $fff = substr($g, 2, 3);
        $unit = SynopCodes::windUnit((string) $ctx['iw']);

        return SynopValue::item($g, '00fff', 'Prędkość wiatru > 99', ctype_digit($fff) ? 'Prędkość '.$fff.' '.$unit.'.' : 'Prędkość niepodana.', [
            ['code' => '00', 'text' => 'grupa dodatkowej prędkości (gdy ff=99)'],
            ['code' => 'fff='.$fff, 'text' => ctype_digit($fff) ? $fff.' '.$unit : 'brak danych'],
        ]);
    }

    /** @param array<string, mixed> $ctx */
    private static function tempGroup(string $g, array &$ctx, string $key, string $title, ?string $store, string $label): array
    {
        $value = SynopValue::signedTenths($g);
        if ($store !== null && $value !== null) {
            $ctx[$store] = $value;
        }
        $sign = $g[1] ?? '/';
        $ttt = substr($g, 2, 3);

        return SynopValue::item($g, $key, $title, $value === null ? $label.' niepodana.' : $label.' '.SynopValue::deg($value).'.', [
            ['code' => $g[0] ?? '1', 'text' => $title],
            ['code' => 's='.$sign, 'text' => $sign === '0' ? 'wartość dodatnia' : ($sign === '1' ? 'wartość ujemna' : 'znak nieznany')],
            ['code' => 'TTT='.$ttt, 'text' => $value === null ? 'brak danych' : SynopValue::deg($value)],
        ]);
    }

    private static function humidityGroup(string $g): array
    {
        $uu = substr($g, 2, 3);

        return SynopValue::item($g, '29UUU', 'Wilgotność względna', ctype_digit($uu) ? 'Wilgotność względna '.(int) $uu.'%.' : 'Wilgotność względna niepodana.', [
            ['code' => '29', 'text' => 'grupa wilgotności zamiast punktu rosy'],
            ['code' => 'UUU='.$uu, 'text' => ctype_digit($uu) ? (int) $uu.'%' : 'brak danych'],
        ]);
    }

    /** @param array<string, mixed> $ctx */
    private static function dewGroup(string $g, array &$ctx): array
    {
        $item = self::tempGroup($g, $ctx, '2sTdTdTd', 'Temperatura punktu rosy', 'dew', 'Punkt rosy');
        if ($ctx['temp'] !== null && isset($ctx['dew']) && is_float($ctx['dew'])) {
            $rh = SynopValue::humidity((float) $ctx['temp'], (float) $ctx['dew']);
            if ($rh !== null) {
                $item['meaning'] .= ' Wilgotność względna ok. '.$rh.'%.';
            }
        }

        return $item;
    }

    private static function pressureGroup(string $g, string $key, string $title): array
    {
        $pppp = substr($g, 1, 4);
        $hpa = SynopValue::pressure($g);

        return SynopValue::item($g, $key, $title, $hpa === null ? 'Ciśnienie niepodane.' : $title.': '.$hpa.' hPa.', [
            ['code' => 'PPPP='.$pppp, 'text' => $hpa === null ? 'brak danych' : $hpa.' hPa (kod < 5000 → dodać 1000)'],
        ]);
    }

    private static function tendencyGroup(string $g): array
    {
        $a = $g[1] ?? '/';
        $ppp = substr($g, 2, 3);
        $change = ctype_digit($ppp) ? ((int) $ppp) / 10 : null;

        return SynopValue::item($g, '5appp', 'Tendencja ciśnienia (3 godz.)', $change === null
            ? 'Tendencja niepodana.'
            : 'Ciśnienie '.SynopCodes::tendency($a).', zmiana '.$change.' hPa / 3 godz.', [
                ['code' => 'a='.$a, 'text' => SynopCodes::tendency($a)],
                ['code' => 'ppp='.$ppp, 'text' => $change === null ? 'brak danych' : $change.' hPa'],
            ]);
    }

    private static function precipGroup(string $g): array
    {
        $rrr = substr($g, 1, 3);
        $t = $g[4] ?? '/';

        return SynopValue::item($g, '6RRRt', 'Opad', 'Suma '.SynopCodes::precipMm($rrr).' za '.SynopCodes::precipHours($t).'.', [
            ['code' => 'RRR='.$rrr, 'text' => SynopCodes::precipMm($rrr).' (990 = ślad, 991–999 = dziesiąte mm)'],
            ['code' => 't='.$t, 'text' => 'okres: '.SynopCodes::precipHours($t)],
        ]);
    }

    /** @param array<string, mixed> $ctx */
    private static function weatherGroup(string $g, array $ctx): array
    {
        $ww = substr($g, 1, 2);
        $w1 = $g[3] ?? '/';
        $w2 = $g[4] ?? '/';
        $ix = (int) ($ctx['ix'] ?? 1);
        $txt = '';
        if (ctype_digit($ww)) {
            $txt = trim(strip_tags((string) WmoWeather::describe((int) $ww, $ix)['zjawiskoTXT']));
        }
        $meaning = ctype_digit($ww)
            ? ('Zjawisko bieżące ww='.$ww.($txt === '' ? '' : ' — '.$txt).'.')
            : 'Zjawisko bieżące niepodane.';

        return SynopValue::item($g, '7wwW1W2', 'Pogoda bieżąca i ubiegła', $meaning, [
            ['code' => 'ww='.$ww, 'text' => $txt === '' ? 'brak opisu / niepodane' : $txt],
            ['code' => 'W1='.$w1, 'text' => 'pogoda ubiegła (większa waga): '.SynopCodes::pastWeather($w1)],
            ['code' => 'W2='.$w2, 'text' => 'pogoda ubiegła (mniejsza waga): '.SynopCodes::pastWeather($w2)],
        ]);
    }

    private static function clouds8(string $g): array
    {
        $nh = $g[1] ?? '/';
        $cl = $g[2] ?? '/';
        $cm = $g[3] ?? '/';
        $ch = $g[4] ?? '/';

        return SynopValue::item($g, '8NhCLCMCH', 'Rodzaje chmur', SynopCodes::oktas($nh).'; niskie: '.SynopCodes::cloudLow($cl).'.', [
            ['code' => 'Nh='.$nh, 'text' => 'zachmurzenie CL lub CM: '.SynopCodes::oktas($nh)],
            ['code' => 'CL='.$cl, 'text' => SynopCodes::cloudLow($cl)],
            ['code' => 'CM='.$cm, 'text' => SynopCodes::cloudMid($cm)],
            ['code' => 'CH='.$ch, 'text' => SynopCodes::cloudHigh($ch)],
        ]);
    }

    private static function cloudLayer(string $g): array
    {
        $ns = $g[1] ?? '/';
        $c = $g[2] ?? '/';
        $hs = substr($g, 3, 2);

        return SynopValue::item($g, '8NsChshs', 'Warstwa chmur', 'Ilość '.SynopCodes::oktas($ns).', kod wysokości podstawy '.$hs.'.', [
            ['code' => 'Ns='.$ns, 'text' => SynopCodes::oktas($ns)],
            ['code' => 'C='.$c, 'text' => 'rodzaj chmur (kod '.$c.')'],
            ['code' => 'hshs='.$hs, 'text' => 'wysokość podstawy, kod '.$hs],
        ]);
    }
}
