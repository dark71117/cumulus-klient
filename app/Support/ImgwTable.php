<?php

namespace App\Support;

use Carbon\Carbon;

class ImgwTable
{
    public const DELAY_HOURS = 2;

    public static function item(object $row, ?Carbon $actual): array
    {
        $godzina = 0;
        if ($actual && ! empty($row->termin)) {
            $godzina = (int) floor(abs(
                $actual->getTimestamp() - Carbon::parse($row->termin)->getTimestamp()
            ) / 3600);
        }

        return [
            'regionRow' => 0,
            'region' => $row->region,
            'europe' => ImgwRegion::isEurope((string) $row->region) ? 1 : 0,
            'imgwRow' => empty($row->zjawiskoKolor) ? 'imgwRow' : 'imgw'.$row->zjawiskoKolor,
            'imgwCity' => empty($row->zjawiskoKolor) ? ' class="imgwCity"' : '',
            'nazwaStacji' => $row->nazwaStacji,
            'godzina' => $godzina,
            'temp' => ($row->temp !== null && $row->temp != -99) ? number_format((float) $row->temp, 1, '.', '') : '-',
            'tempOdcz' => ($row->tempOdcz !== null && $row->tempOdcz != -99) ? number_format((float) $row->tempOdcz, 1, '.', '-') : '',
            'zachmurzenieTXT' => ImgwText::decode($row->zachmurzenieTXT ?? ''),
            'zjawiskoTXT' => ImgwText::markup($row->zjawiskoTXT ?? ''),
            'zjawiskoPoprzednie' => ImgwText::decode($row->zjawiskoPoprzednie ?? ''),
            'widzialnosc' => ($row->widzialnosc ?? '') != -99 ? ImgwText::decode($row->widzialnosc ?? '') : '-',
            'wiatr' => ImgwText::decode($row->wiatr ?? ''),
        ];
    }

    public static function frames(iterable $latestRows, iterable $historyRows, string $maxTermin, int $limit): array
    {
        $byStation = ImgwMap::rowsByStation($historyRows, $latestRows);
        $order = [];
        foreach ([$latestRows, $historyRows] as $set) {
            foreach ($set as $row) {
                $id = (int) $row->idStacji;
                if ($id > 0 && ! isset($order[$id])) {
                    $order[$id] = $id;
                }
            }
        }
        $frames = [];
        foreach (ImgwMap::hourKeys($byStation, $maxTermin, $limit) as $termin) {
            $at = Carbon::parse($termin);
            $rows = [];
            foreach ($order as $id) {
                $match = self::rowNearHour($byStation[$id] ?? [], $termin);
                if ($match) {
                    $rows[] = self::item($match, $at);
                }
            }
            $frames[] = [
                'hour' => $at->format('G').':00',
                'date' => $at->format('d.m.Y'),
                'termin' => $at->format('Y-m-d H:i:s'),
                'rows' => $rows,
            ];
        }

        return $frames;
    }

    public static function rowNearHour(array $rows, string $hourKey, int $maxDelay = self::DELAY_HOURS): ?object
    {
        $hourTs = Carbon::parse($hourKey)->startOfHour()->getTimestamp();
        $best = null;
        $bestDelay = null;
        foreach ($rows as $row) {
            if (empty($row->termin)) {
                continue;
            }
            $terminTs = Carbon::parse($row->termin)->startOfHour()->getTimestamp();
            $delay = (int) floor(($hourTs - $terminTs) / 3600);
            if ($delay < 0 || $delay > $maxDelay) {
                continue;
            }
            if ($bestDelay === null || $delay < $bestDelay) {
                $best = $row;
                $bestDelay = $delay;
            }
        }

        return $best;
    }
}
