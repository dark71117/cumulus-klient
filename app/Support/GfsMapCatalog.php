<?php

namespace App\Support;

class GfsMapCatalog
{
    public const REGIONS = ['Polska', 'Europa'];

    public const WEEKDAYS = ['N', 'PO', 'WT', 'ŚR', 'CZ', 'P', 'SO'];

    public static function weekday(int $phpWeekday): string
    {
        return self::WEEKDAYS[$phpWeekday] ?? '';
    }

    public static function isRegion(string $region): bool
    {
        return in_array($region, self::REGIONS, true);
    }

    public static function filesFor(string $product): array
    {
        foreach (self::tabs() as $tab) {
            foreach ($tab['groups'] as $group) {
                foreach ($group['items'] as $item) {
                    if ($item['id'] === $product) {
                        return $item['files'];
                    }
                }
            }
        }

        return [];
    }

    public static function tabs(): array
    {
        return [
            [
                'id' => 'synoptyk',
                'label' => 'Synoptyk',
                'groups' => [
                    ['title' => 'Synoptyk', 'items' => [
                        self::item('precip6h', 'Opad za 6h', ['precip6h_2', 'precip6h']),
                        self::item('synop3', 'Synop 3', ['synop3']),
                        self::item('synop2', 'Synop 2', ['synop2']),
                        self::item('synop1', 'Synop 1', ['synop1']),
                        self::item('gridPL', 'Siatka', ['gridPL']),
                    ]],
                    ['title' => 'Temperatura', 'items' => [
                        self::item('tmax2m', 'Temp. Max.', ['tmax2m_2', 'tmax2m']),
                        self::item('tmin2m', 'Temp. Min.', ['tmin2m_2', 'tmin2m']),
                        self::item('tmp2m', 'Temp.', ['tmp2m_2', 'tmp2m']),
                    ]],
                    ['title' => 'Wiatr', 'items' => [
                        self::item('wind10m', 'Wiatr', ['wind10m_2', 'wind10m']),
                        self::item('windGust', 'Porywy', ['windGust_2', 'windGust']),
                        self::item('wind2Gust', 'Wiatr + Porywy', ['wind2Gust_2', 'wind2Gust']),
                    ]],
                    ['title' => 'Zagrożenia', 'items' => [
                        self::item('storm', 'Sztorm', ['storm_2', 'storm']),
                        self::item('lifted', 'Burze', ['lifted']),
                        self::item('vis10m', 'Widzialność', ['vis10m']),
                    ]],
                    ['title' => 'TB + temp', 'items' => [
                        self::item('tmpHGT925', '925 temp + HGT', ['tmpHGT925']),
                        self::item('tmpHGT850', '850 temp + HGT', ['tmpHGT850']),
                        self::item('tmpWind700', '700 temp + wiatr', ['tmpWind700']),
                        self::item('tmpWind500', '500 temp + wiatr', ['tmpWind500']),
                    ]],
                    ['title' => 'TB + RH', 'items' => [
                        self::item('rhHGT925', '925 RH + HGT', ['rhHGT925']),
                        self::item('rhHGT850', '850 RH + HGT', ['rhHGT850']),
                        self::item('rhHGT700', '700 RH + HGT', ['rhHGT700']),
                        self::item('rhHGT500', '500 RH + HGT', ['rhHGT500']),
                    ]],
                ],
            ],
            [
                'id' => 'ziemia',
                'label' => 'Mapy Ziemia',
                'groups' => [
                    ['title' => 'Temp. na 2m', 'items' => [
                        self::item('tmp2m', 'Temp.', ['tmp2m_2', 'tmp2m']),
                        self::item('tmax2m', 'Temp. Max.', ['tmax2m_2', 'tmax2m']),
                        self::item('tmin2m', 'Temp. Min.', ['tmin2m_2', 'tmin2m']),
                    ]],
                    ['title' => 'Wiatr', 'items' => [
                        self::item('wind10m', 'Wiatr 10m', ['wind10m_2', 'wind10m']),
                        self::item('windGust', 'Porywy 10m', ['windGust_2', 'windGust']),
                        self::item('storm', 'Sztorm', ['storm_2', 'storm']),
                    ]],
                    ['title' => 'Opady', 'items' => [
                        self::item('precipType', 'Opad 1h/3h/12h', ['precipType']),
                        self::item('precip6h', 'Opad za 6h', ['precip6h_2', 'precip6h']),
                        self::item('precip12h', 'Opad za 12h', ['precip12h']),
                        self::item('precip24h', 'Opad za 24h', ['precip24h']),
                    ]],
                    ['title' => 'Ciśnienie', 'items' => [
                        self::item('mslpSfc', 'Ciśnienie n.p.m.', ['mslpSfc']),
                        self::item('mslpch', 'Tendencja za 3h', ['mslpch']),
                    ]],
                    ['title' => 'Zachmurzenie', 'items' => [
                        self::item('cloudsHigh', 'Wysokie', ['cloudsHigh']),
                        self::item('cloudsMid', 'Średnie', ['cloudsMid']),
                        self::item('cloudsLow', 'Niskie', ['cloudsLow']),
                        self::item('cloudsAll', 'Całkowite', ['cloudsAll']),
                    ]],
                    ['title' => 'Chmury', 'items' => [
                        self::item('clouds_base', 'Podstawa chmur', ['clouds_base']),
                        self::item('cloudsKonw', 'Zachm. konwek.', ['cloudsKonw']),
                        self::item('rh2m', 'Wilgotność 2m', ['rh2m']),
                        self::item('vis10m_2', 'Widzialność', ['vis10m_2', 'vis10m']),
                    ]],
                ],
            ],
            [
                'id' => 'gorne',
                'label' => 'Mapy górne',
                'groups' => [
                    self::level('500', 'hgt500', 'rh500', 'tmp500', 'wind500'),
                    self::level('700', 'hgt700', 'rh700', 'tmp700', 'wind700'),
                    self::level('850', 'hgt850', 'rh850', 'tmp850', 'wind850'),
                    self::level('925', 'hgt925', 'rh925', 'tmp925', 'wind925'),
                ],
            ],
            [
                'id' => 'sat24',
                'label' => 'Sat24',
                'groups' => [],
            ],
        ];
    }

    private static function item(string $id, string $label, array $files): array
    {
        return ['id' => $id, 'label' => $label, 'files' => $files];
    }

    private static function level(string $level, string $hgt, string $rh, string $tmp, string $wind): array
    {
        return ['title' => 'Poziom '.$level, 'items' => [
            self::item($wind, 'Wiatr', [$wind]),
            self::item($tmp, 'Temp.', [$tmp]),
            self::item($rh, 'Wilgotność', [$rh]),
            self::item($hgt, 'Wysokość geop.', [$hgt]),
        ]];
    }
}
