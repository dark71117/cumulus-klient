<?php

namespace App\Support;

class ForecastHtml
{
    public static function forecastKeys(string $w): string
    {
        $table = [
            0 => ['icon' => 'w00', 'desc' => 'Bezchmurnie'],
            1 => ['icon' => 'w01', 'desc' => 'Zachmurzenie małe'],
            2 => ['icon' => 'w02', 'desc' => 'Zachmurzenie umiarkowane'],
            3 => ['icon' => 'w03', 'desc' => 'Zachmurzenie duże'],
            33 => ['icon' => 'w33', 'desc' => 'Zachmurzenie pełne'],
            4 => ['icon' => 'w04', 'desc' => 'Deszcz przelotny'],
            5 => ['icon' => 'w05', 'desc' => 'Deszcz ciągły'],
            6 => ['icon' => 'w06', 'desc' => 'Burza i deszcz przelotny'],
            7 => ['icon' => 'w07', 'desc' => 'Burza i deszcz ciągły'],
            8 => ['icon' => 'w08', 'desc' => 'Deszcz ze śniegiem przelotny'],
            88 => ['icon' => 'w88', 'desc' => 'Deszcz ze śniegiem ciągły'],
            9 => ['icon' => 'w09', 'desc' => 'Gołoledź'],
            10 => ['icon' => 'w10', 'desc' => 'Śnieg przelotny'],
            11 => ['icon' => 'w11', 'desc' => 'Śnieg ciągły'],
            12 => ['icon' => 'w12', 'desc' => 'Mgła'],
        ];
        $off = 0;
        do {
            $pos1 = strpos($w, '{klucz ', $off);
            if ($pos1 === false) {
                break;
            }
            $dayKey = substr($w, $pos1, 9) === '{klucz d}';
            $pos2 = strpos($w, '>', $pos1);
            $pos3 = $pos2 !== false ? strpos($w, '</td>', $pos2) : false;
            if ($pos2 === false || $pos3 === false) {
                break;
            }
            $key = substr($w, $pos2 + 1, $pos3 - ($pos2 + 1));
            $key2 = '';
            if (str_contains($key, '/')) {
                [$key, $key2] = explode('/', $key, 2);
            }
            if ($key === '&nbsp;') {
                $key = -1;
            }
            $icon = $table[$key]['icon'] ?? '';
            $desc = $table[$key]['desc'] ?? '';
            $icon2 = $table[$key2]['icon'] ?? '';
            $desc2 = $table[$key2]['desc'] ?? '';
            if ($icon !== '') {
                if (! $dayKey && file_exists(public_path('images/ikony3/n'.$icon.'.png'))) {
                    $icon = 'n'.$icon;
                }
                $html = '<img src="'.asset('images/ikony3/'.$icon.'.png').'" title="'.$desc.'" />';
                if ($icon2 !== '') {
                    $html .= '<img style="width: 40px" src="'.asset('images/ikony3/'.$icon2.'.png').'" title="'.$desc2.'" />';
                }
                $w1 = str_replace($dayKey ? '{klucz d}' : '{klucz n}', '', substr($w, 0, $pos2 + 1));
                $w = $w1.$html.substr($w, $pos3);
            }
            $off = $pos3;
        } while ($pos1 !== false);

        return $w;
    }

    public static function cityForecast(string $forecast, array $data, int $position): string
    {
        $row = $data['forecasts'][$position] ?? null;
        $pl = is_object($row) ? ($row->prognozaPL ?? 0) : ($row['prognozaPL'] ?? 0);
        if ((int) $pl !== 1 || empty($data['cities'])) {
            return '';
        }
        $lines = explode("\r\n", $forecast);
        $header = '';
        $citiesList = [];
        $content = '';
        $point = true;
        $foundCity = '';
        foreach ($lines as $line) {
            if (str_starts_with($line, '<!-- :') && $foundCity === '') {
                $foundCity = '';
                foreach ($data['cities'] as $cityRow) {
                    $city = is_object($cityRow) ? $cityRow->miasto : $cityRow['miasto'];
                    if (mb_strtoupper($line) === '<!-- :'.mb_strtoupper($city).' -->') {
                        $point = true;
                        $foundCity = $city;
                        if ($header === '') {
                            $header = $content;
                            $content = '';
                        }
                        break;
                    }
                }
                if ($foundCity === '') {
                    $point = false;
                }
            }
            if (! $point && str_starts_with($line, '<!-- /')) {
                $point = true;
                $foundCity = '';
            }
            if ($point) {
                if ($foundCity !== '' && mb_strtoupper($line) === '<!-- /'.mb_strtoupper($foundCity).' -->') {
                    $citiesList[$foundCity] = $content;
                    $content = '';
                    $foundCity = '';
                }
                $content .= $line;
            }
        }
        $result = $header;
        foreach ($citiesList as $cityHtml) {
            if ($cityHtml !== '') {
                $result .= self::forecastKeys($cityHtml);
            }
        }

        return $result.self::forecastKeys($content);
    }
}
