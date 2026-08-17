<?php

namespace App\Support\Synop;

class WmoWeather
{
    public static function describe(int $code, int $wxInd = 1): array
    {
        $table = $wxInd > 4 ? self::wmo4680() : self::wmo4677();
        $row = $table[$code] ?? $table[-99];

        return [
            'zjawiskoTXT' => $row[0],
            'zjawiskoKolor' => $row[1],
            'zjawiskoIkona' => $row[2],
            'zjawiskoPoprzednie' => $row[3],
            'zjawiskoSynop' => trim(strip_tags($row[0])),
        ];
    }

    public static function clouds(int $oktas): string
    {
        return match (true) {
            $oktas < 0 => '',
            $oktas <= 1 => 'bezchmurnie',
            $oktas <= 3 => 'małe',
            $oktas <= 6 => 'umiarkowane',
            $oktas === 7 => 'duże',
            $oktas === 8 => 'pełne',
            $oktas === 9 => 'niebo niewidoczne',
            default => '',
        };
    }

    public static function pressure(float $station, float $sea, int $tendency): string
    {
        if ($tendency < 0 || $tendency > 8 || $station <= -99 || $sea <= -99) {
            return '';
        }
        $tendencyDesc = $tendency <= 3 ? 'rośnie' : ($tendency === 4 ? 'jest stałe' : 'spada');
        $pressTxt = $sea < 1008 ? 'niskie' : ($sea <= 1018 ? 'średnie' : 'wysokie');
        $mmHg = $station * 0.7500615613;

        return sprintf(
            "<span class=!cisnienie!>Ciśnienie <span class=!cisnienieBlue!>%s</span> wynosi <span class=!cisnienieBlue!>%0.1f hPa</span> czyli <span class=!cisnienieBlue!>%0.1f mmHg</span> i <span class=!cisnienieBlue!>%s</span>.</span>",
            $pressTxt,
            $station,
            $mmHg,
            $tendencyDesc
        );
    }

    public static function windChill(float $temp, float $windKmh): float
    {
        if ($temp <= -99) {
            return -99.0;
        }
        $result = $temp;
        if ($temp < 20 && $windKmh >= 5) {
            $result = 13.12 + 0.6215 * $temp - 11.37 * ($windKmh ** 0.16) + 0.3965 * $temp * ($windKmh ** 0.16);
            if ($result > $temp) {
                $result = $temp;
            }
        }

        return round($result, 1);
    }

    /** @return array<int, array{0:string,1:string,2:string,3:string}> */
    private static function wmo4677(): array
    {
        $b = fn (string $w) => '<span class="pogrubione">'.$w.'</span>';
        $n = ['', '', 'N', ''];

        return [
            -99 => $n, 0 => $n, 2 => $n, 6 => $n, 7 => $n,
            1 => ['chmury zanikają', '', 'N', ''],
            3 => ['chmur przybywa', '', 'N', ''],
            4 => ['dymy', '', 'N', ''],
            5 => ['zmętnienie', '', 'N', ''],
            8 => [$b('wiry').' pyłowe', '', 'N', ''],
            9 => [$b('wichura').' pyłowa', '', 'N', ''],
            10 => ['zamglenie', '', 'N', ''],
            11 => [$b('mgła').' w płatach', 'mgla', 'N;06', ''],
            12 => ['cienka warstwa '.$b('mgły'), 'mgla', 'N;06', ''],
            13 => [$b('błyskawica').' w polu widzenia', 'burza', '62', ''],
            14 => [$b('opad').' w polu widzenia', 'deszcz', 'N', ''],
            15 => [$b('opad').' w polu widzenia', 'deszcz', 'N', ''],
            16 => [$b('opad').' w polu widzenia', 'deszcz', 'N', ''],
            17 => [$b('burza').' bez opadu', 'burza', '62', ''],
            18 => [$b('nawałnica'), 'burza', '76', ''],
            19 => [$b('trąba').' powietrzna', 'burza', 'N', ''],
            20 => ['po '.$b('mżawce'), '', 'N', 'po_deszczu'],
            21 => ['po '.$b('deszczu'), '', 'N', 'po_deszczu'],
            22 => ['po '.$b('śniegu'), '', 'N', 'po_sniegu'],
            23 => ['po '.$b('deszczu ze śniegiem'), '', 'N', 'po_sniegu'],
            24 => ['po '.$b('opadzie marznącym'), '', 'N', 'po_deszczu'],
            25 => ['po przelotnym '.$b('deszczu'), '', 'N', 'po_deszczu'],
            26 => ['po przelotnym '.$b('śniegu'), '', 'N', 'po_sniegu'],
            27 => ['po przelotnym '.$b('gradzie'), '', 'N', 'po_burzy'],
            28 => ['po '.$b('mgle'), '', 'N', 'po_mgle'],
            29 => ['po '.$b('burzy'), '', 'N', 'po_burzy'],
            30 => ['słaba/umiarkowana '.$b('wichura').' pyłowa', '', 'N', ''],
            31 => ['słaba/umiarkowana '.$b('wichura').' pyłowa', '', 'N', ''],
            32 => ['słaba/umiarkowana '.$b('wichura').' pyłowa', '', 'N', ''],
            33 => ['silna '.$b('wichura').' pyłowa', '', 'N', ''],
            34 => ['silna '.$b('wichura').' pyłowa', '', 'N', ''],
            35 => ['silna '.$b('wichura').' pyłowa', '', 'N', ''],
            36 => ['słaba/umiarkowana '.$b('zamieć').' śnieżna', '', 'N', ''],
            37 => ['silna '.$b('zamieć').' śnieżna', '', 'N', ''],
            38 => ['słaba/umiarkowana '.$b('zamieć').' śnieżna', '', 'N', ''],
            39 => ['silna '.$b('zamieć').' śnieżna', '', 'N', ''],
            40 => [$b('mgła').' w polu widzenia', 'mgla', '06', ''],
            41 => [$b('mgła').' w płatach', 'mgla', 'N;06', ''],
            42 => [$b('mgła').' staje się rzadsza, niebo widoczne', 'mgla', '06', ''],
            43 => [$b('mgła').' staje się rzadsza, niebo niewidoczne', 'mgla', '06', ''],
            44 => [$b('mgła').', niebo widoczne', 'mgla', '06', ''],
            45 => [$b('mgła').', niebo niewidoczne', 'mgla', '06', ''],
            46 => [$b('mgła').' gęstnieje, niebo widoczne', 'mgla', '06', ''],
            47 => [$b('mgła').' gęstnieje, niebo niewidoczne', 'mgla', '06', ''],
            48 => [$b('mgła').' osadzająca szadź, niebo widoczne', 'mgla', '06;07', ''],
            49 => [$b('mgła').' osadzająca szadź, niebo niewidoczne', 'mgla', '06;07', ''],
            50 => ['słaba '.$b('mżawka').' (z przerwami)', 'deszcz', '15', ''],
            51 => ['słaba '.$b('mżawka').' (ciągła)', 'deszcz', '15', ''],
            52 => ['umiarkowana '.$b('mżawka').' (z przerwami)', 'deszcz', '16', ''],
            53 => ['umiarkowana '.$b('mżawka').' (ciągła)', 'deszcz', '16', ''],
            54 => ['intensywna '.$b('mżawka').' (z przerwami)', 'deszcz', '16', ''],
            55 => ['intensywna '.$b('mżawka').' (ciągła)', 'deszcz', '16', ''],
            56 => ['słaba, '.$b('marznąca mżawka'), 'deszcz', '15;07', ''],
            57 => ['umiarkowana/silna, '.$b('marznąca mżawka'), 'deszcz', '15;07', ''],
            58 => ['słaba '.$b('mżawka z deszczem'), 'deszcz', '19', ''],
            59 => ['umiarkowana/silna '.$b('mżawka z deszczem'), 'deszcz', '19', ''],
            60 => ['słaby '.$b('deszcz').' (z przerwami)', 'deszcz', '25', ''],
            61 => ['słaby '.$b('deszcz').' (ciągły)', 'deszcz', '25', ''],
            62 => ['umiarkowany '.$b('deszcz').' z przerwami', 'deszcz', '26', ''],
            63 => ['umiarkowany '.$b('deszcz').' (ciągły)', 'deszcz', '26', ''],
            64 => ['silny '.$b('deszcz').' z przerwami', 'deszcz', '26', ''],
            65 => ['silny '.$b('deszcz').' (ciągły)', 'deszcz', '26', ''],
            66 => ['słaby, '.$b('marznący deszcz'), 'deszcz', '25;07', ''],
            67 => ['umiarkowany/silny, '.$b('marznący deszcz'), 'deszcz', '26;07', ''],
            68 => ['słaby '.$b('deszcz ze śniegiem'), 'deszcz', '33', ''],
            69 => ['umiarkowany/silny '.$b('deszcz ze śniegiem'), 'deszcz', '33', ''],
            70 => ['słaby '.$b('śnieg').' z przerwami', 'snieg', '45', ''],
            71 => ['słaby '.$b('śnieg').' (ciągły)', 'snieg', '45', ''],
            72 => ['umiarkowany '.$b('śnieg').' z przerwami', 'snieg', '46', ''],
            73 => ['umiarkowany '.$b('śnieg').' (ciągły)', 'snieg', '46', ''],
            74 => ['silny '.$b('śnieg').' z przerwami', 'snieg', '46', ''],
            75 => ['silny '.$b('śnieg').' (ciągły)', 'snieg', '46', ''],
            76 => [$b('pył').' diamentowy', 'snieg', '45', ''],
            77 => [$b('śnieg').' ziarnisty', 'snieg', '45', ''],
            78 => ['oddzielne '.$b('gwiazdki śniegu'), 'snieg', '45', ''],
            79 => [$b('ziarna lodowe'), 'snieg', '45', ''],
            80 => ['słaby, przelotny '.$b('deszcz'), 'deszcz', '21', ''],
            81 => ['umiarkowany/silny, przelotny '.$b('deszcz'), 'deszcz', '22', ''],
            82 => ['gwałtowny, przelotny '.$b('deszcz'), 'deszcz', '22', ''],
            83 => ['słaby, przelotny '.$b('deszcz ze śniegiem'), 'deszcz', '31', ''],
            84 => ['umiarkowany/silny, przelotny '.$b('deszcz ze śniegiem'), 'deszcz', '31', ''],
            85 => ['słaby, przelotny '.$b('śnieg'), 'snieg', '41', ''],
            86 => ['umiarkowany/silny, przelotny '.$b('śnieg'), 'snieg', '42', ''],
            87 => ['słabe, przelotne '.$b('krupy śnieżne lub lodowe'), 'snieg', '45', ''],
            88 => ['umiarkowane/silne, przelotne '.$b('krupy śnieżne'), 'snieg', '46', ''],
            89 => ['słaby, przelotny '.$b('grad'), 'burza', '51', ''],
            90 => ['umiarkowany/silny, przelotny '.$b('grad'), 'burza', '52', ''],
            91 => ['po burzy, pada lekki '.$b('deszcz'), 'deszcz', '21', ''],
            92 => ['po burzy, pada umiarkowany/silny '.$b('deszcz'), 'deszcz', '22', ''],
            93 => ['po burzy, pada lekki '.$b('grad'), 'burza', '51', ''],
            94 => ['po burzy, pada umiarkowany/silny '.$b('grad'), 'burza', '52', ''],
            95 => ['słaba/umiarkowana '.$b('burza'), 'burza', '71', ''],
            96 => ['słaba/umiarkowana '.$b('burza z gradem'), 'burza', '83', ''],
            97 => ['silna '.$b('burza'), 'burza', '74', ''],
            98 => ['silna '.$b('burza').' z wichurą pyłową', 'burza', '74', ''],
            99 => ['silna '.$b('burza z gradem'), 'burza', '84', ''],
        ];
    }

    /** @return array<int, array{0:string,1:string,2:string,3:string}> */
    private static function wmo4680(): array
    {
        $empty = ['', '', '', ''];
        $n = ['', '', 'N', ''];
        $b = fn (string $w) => '<span class="pogrubione">'.$w.'</span>';

        return [
            -99 => ['', '', 'N', ''], 0 => $empty, 6 => $empty, 7 => $empty, 8 => $empty, 9 => $empty,
            13 => $empty, 14 => $empty, 15 => $empty, 16 => $empty, 17 => $empty, 19 => $empty,
            36 => $empty, 37 => $empty, 38 => $empty, 39 => $empty, 49 => $empty, 59 => $empty,
            69 => $empty, 79 => $empty, 88 => $empty, 97 => $empty, 98 => $empty,
            1 => ['chmury zanikają', '', 'N', ''],
            2 => ['stan nieba bez zmian', '', 'N', ''],
            3 => ['chmur przybywa', '', 'N', ''],
            4 => ['zmętnienie, dymy', '', 'N', ''],
            5 => ['zmętnienie, dymy', 'mgla', '06', ''],
            10 => ['zamglenie', '', 'N', ''],
            11 => ['pył diamentowy', '', 'N', ''],
            12 => [$b('błyskawica').' w polu widzenia', '', 'N', ''],
            18 => [$b('nawałnica'), 'burza', '76', ''],
            20 => ['po '.$b('mgle'), '', 'N', 'po_mgle'],
            21 => ['po '.$b('opadzie'), '', 'N', 'po_deszczu'],
            22 => ['po słabym '.$b('opadzie'), '', 'N', 'po_deszczu'],
            23 => ['po '.$b('deszczu'), '', 'N', 'po_deszczu'],
            24 => ['po '.$b('śniegu'), '', 'N', 'po_sniegu'],
            25 => ['po '.$b('opadzie marznącym'), '', 'N', 'po_deszczu'],
            26 => ['po '.$b('burzy'), '', 'N', 'po_burzy'],
            27 => [$b('zamieć').' śnieżna lub '.$b('wichura').' pyłowa', '', 'N', ''],
            28 => [$b('zamieć').' śnieżna lub '.$b('wichura').' pyłowa', '', 'N', ''],
            29 => [$b('zamieć').' śnieżna lub '.$b('wichura').' pyłowa', 'mgla', 'N', ''],
            30 => [$b('mgła'), 'mgla', '06', ''],
            31 => [$b('mgła').' w płatach', 'mgla', '06', ''],
            32 => [$b('mgła').' staje się rzadsza', 'mgla', '06', ''],
            33 => [$b('mgła').' bez zmian widzialności', 'mgla', '06', ''],
            34 => [$b('mgła').' gęstnieje', 'mgla', '06', ''],
            35 => [$b('mgła').' osadzająca szadź', 'mgla', '06;07', ''],
            40 => [$b('opad'), 'deszcz', '25', ''],
            41 => ['słaby/umiarkowany '.$b('opad'), 'deszcz', '25', ''],
            42 => ['silny '.$b('opad'), 'deszcz', '26', ''],
            43 => ['słaby/umiarkowany '.$b('opad').' (ciekły)', 'deszcz', '25', ''],
            44 => ['silny '.$b('opad').' (ciekły)', 'deszcz', '26', ''],
            45 => ['słaby/umiarkowany '.$b('opad').' (stały)', 'snieg', '45', ''],
            46 => ['silny '.$b('opad').' (stały)', 'snieg', '46', ''],
            47 => ['słaby/umiarkowany, '.$b('marznący opad'), 'deszcz', '25;07', ''],
            48 => ['silny, '.$b('marznący opad'), 'deszcz', '26;07', ''],
            50 => [$b('mżawka'), 'deszcz', '15', ''],
            51 => ['słaba '.$b('mżawka'), 'deszcz', '15', ''],
            52 => ['umiarkowana '.$b('mżawka'), 'deszcz', '16', ''],
            53 => ['silna '.$b('mżawka'), 'deszcz', '16', ''],
            54 => ['słaba, '.$b('marznąca mżawka'), 'deszcz', '15;07', ''],
            55 => ['umiarkowana, '.$b('marznąca mżawka'), 'deszcz', '16;07', ''],
            56 => ['silna, '.$b('marznąca mżawka'), 'deszcz', '16;07', ''],
            57 => ['słaba '.$b('mżawka z deszczem'), 'deszcz', '19', ''],
            58 => ['umiarkowana/silna '.$b('mżawka z deszczem'), 'deszcz', '19', ''],
            60 => [$b('deszcz'), 'deszcz', '25', ''],
            61 => ['słaby '.$b('deszcz'), 'deszcz', '25', ''],
            62 => ['umiarkowany '.$b('deszcz'), 'deszcz', '26', ''],
            63 => ['silny '.$b('deszcz'), 'deszcz', '26', ''],
            64 => ['słaby, '.$b('marznący deszcz'), 'deszcz', '25;07', ''],
            65 => ['umiarkowany, '.$b('marznący deszcz'), 'deszcz', '26;07', ''],
            66 => ['silny, '.$b('marznący deszcz'), 'deszcz', '26;07', ''],
            67 => ['słaby '.$b('deszcz ze śniegiem'), 'deszcz', '33', ''],
            68 => ['umiarkowany/silny '.$b('deszcz ze śniegiem'), 'deszcz', '33', ''],
            70 => [$b('śnieg'), 'snieg', '45', ''],
            71 => ['słaby '.$b('śnieg'), 'snieg', '45', ''],
            72 => ['umiarkowany '.$b('śnieg'), 'snieg', '46', ''],
            73 => ['silny '.$b('śnieg'), 'snieg', '46', ''],
            74 => ['słabe '.$b('ziarna lodowe'), 'snieg', '45', ''],
            75 => ['umiarkowane '.$b('ziarna lodowe'), 'snieg', '46', ''],
            76 => ['silne '.$b('ziarna lodowe'), 'snieg', '46', ''],
            77 => [$b('śnieg ziarnisty'), 'snieg', '45', ''],
            78 => [$b('kryształki lodowe'), 'snieg', '45', ''],
            80 => ['przelotny '.$b('opad'), 'deszcz', '21', ''],
            81 => ['słaby, przelotny '.$b('deszcz'), 'deszcz', '21', ''],
            82 => ['umiarkowany, przelotny '.$b('deszcz'), 'deszcz', '22', ''],
            83 => ['silny, przelotny '.$b('deszcz'), 'deszcz', '22', ''],
            84 => ['gwałtowny, przelotny '.$b('deszcz'), 'deszcz', '22', ''],
            85 => ['słaby, przelotny '.$b('śnieg'), 'snieg', '41', ''],
            86 => ['umiarkowany, przelotny '.$b('śnieg'), 'snieg', '42', ''],
            87 => ['silny, przelotny '.$b('śnieg'), 'snieg', '42', ''],
            89 => [$b('grad'), 'burza', '51', ''],
            90 => [$b('burza'), 'burza', '62', ''],
            91 => ['słaba/umiarkowana '.$b('burza').' bez opadu', 'burza', '62', ''],
            92 => ['słaba/umiarkowana '.$b('burza').' z opadem', 'burza', '71', ''],
            93 => ['słaba/umiarkowana '.$b('burza z gradem'), 'burza', '83', ''],
            94 => ['silna '.$b('burza').' bez opadu', 'burza', '71', ''],
            95 => ['silna '.$b('burza').' z opadem', 'burza', '74', ''],
            96 => ['silna '.$b('burza z gradem'), 'burza', '84', ''],
            99 => [$b('trąba').' powietrzna', 'burza', 'N', ''],
        ];
    }
}
