<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class AnalysisTable
{
    public const MISSING = -99;
    public const MAX_RANGE_DAYS = 93;

    /**
     * @return array<string, string>
     */
    public static function numericFields(string $table): array
    {
        $fields = [
            'temp' => 'Temperatura [°C]',
            'tempOdcz' => 'Temperatura odczuwalna [°C]',
            'zachmurzenie' => 'Zachmurzenie N [okta]',
            'zachmurzeniePodstawa' => 'Podstawa chmur h [m]',
            'widzialnosc' => 'Widzialność VV [km]',
            'wiatrK' => 'Kierunek wiatru [°]',
            'wiatrMS' => 'Prędkość wiatru [m/s]',
            'wiatrP' => 'Prędkość wiatru [km/h]',
            'porywy' => 'Porywy [km/h]',
            'wilgotnosc' => 'Wilgotność uu [%]',
            'cisnienieMorze' => 'QNH [hPa]',
            'cisnienieStacja' => 'QFE [hPa]',
            'wysokoscOpadu' => 'Opad [mm]',
        ];
        foreach (array_keys($fields) as $column) {
            if (! Schema::hasColumn($table, $column)) {
                unset($fields[$column]);
            }
        }

        return $fields;
    }

    public static function item(object $row): array
    {
        $termin = ! empty($row->termin) ? Carbon::parse($row->termin) : null;
        $code = self::code($row->zjawisko ?? null);
        $desc = ImgwText::plain($row->zjawiskoTXT ?? '');
        $source = self::sourceMessage($row);

        return [
            'region' => (string) ($row->region ?? ''),
            'nazwaStacji' => (string) ($row->nazwaStacji ?? ''),
            'idStacji' => (int) ($row->idStacji ?? 0),
            'n' => self::code($row->zachmurzenie ?? null),
            'h' => self::cloudBase($row->zachmurzeniePodstawa ?? null),
            'vv' => self::visibility($row->widzialnosc ?? null),
            'windAaxx' => self::windAaxx($row),
            'temp' => self::decimal($row->temp ?? null),
            'uu' => self::code($row->wilgotnosc ?? null),
            'qnh' => self::decimal($row->cisnienieMorze ?? $row->cisnienie ?? null),
            'qfe' => self::decimal($row->cisnienieStacja ?? null),
            'zjawisko' => $code,
            'zjawiskoTXT' => $desc === '' ? '' : ($code === '/' ? $desc : $code.' '.$desc),
            'zachmurzenieTXT' => ImgwText::decode($row->zachmurzenieTXT ?? ''),
            'wiatrTXT' => ImgwText::decode($row->wiatr ?? ''),
            'czas' => $termin ? $termin->format('H:i') : '',
            'termin' => $termin ? $termin->format('Y-m-d H:i:s') : '',
            'synopRaw' => $source['text'],
            'sourceKind' => $source['kind'],
            'imgwRow' => empty($row->zjawiskoKolor) ? 'imgwRow' : 'imgw'.$row->zjawiskoKolor,
        ];
    }

    public static function parseNumeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            $number = (float) $value;

            return self::isMissing($number) ? null : $number;
        }
        $text = html_entity_decode(trim((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($text === '' || $text === '/' || $text === '-') {
            return null;
        }
        if (preg_match('/(-?\d+(?:[.,]\d+)?)/', str_replace(',', '.', $text), $m)) {
            $number = (float) $m[1];

            return self::isMissing($number) ? null : $number;
        }

        return null;
    }

    public static function formatStat(?float $value, int $decimals = 1): string
    {
        if ($value === null) {
            return '/';
        }

        return number_format($value, $decimals, '.', '');
    }

    public static function isMetar(string $raw): bool
    {
        $raw = strtoupper(trim($raw));

        return (bool) preg_match('/^(?:METAR|SPECI)\s+[A-Z]{4}\b/', $raw)
            || (bool) preg_match('/^[A-Z]{4}\s+\d{6}Z\b/', $raw);
    }

    /**
     * @return array{text: string, kind: string}
     */
    private static function sourceMessage(object $row): array
    {
        $raw = trim((string) ($row->synop_raw ?? ''));
        if ($raw !== '') {
            return ['text' => $raw, 'kind' => 'synop'];
        }
        $metar = trim((string) ($row->metar ?? ''));
        if ($metar !== '') {
            return ['text' => $metar, 'kind' => 'metar'];
        }
        $synop = trim((string) ($row->synop ?? ''));

        return ['text' => $synop, 'kind' => $synop === '' ? '' : 'synop'];
    }

    private static function windAaxx(object $row): string
    {
        $dir = self::parseNumeric($row->wiatrK ?? null);
        $ms = self::parseNumeric($row->wiatrMS ?? null);
        if ($ms === null && isset($row->wiatrP)) {
            $kmh = self::parseNumeric($row->wiatrP);
            $ms = $kmh === null ? null : $kmh / 3.6;
        }
        $gustKmh = self::parseNumeric($row->porywy ?? null);
        if ($dir === null && ($ms === null || $ms <= 0)) {
            $txt = mb_strtolower(ImgwText::plain($row->wiatr ?? ''), 'UTF-8');
            if ($txt === 'cisza') {
                return '000/0';
            }

            return '/';
        }
        $ff = (int) round($ms ?? 0);
        $ddd = $dir === null ? '///' : sprintf('%03d', (int) $dir);
        $out = $ddd.'/'.$ff;
        if ($gustKmh !== null && $gustKmh > 0) {
            $gustMs = (int) round($gustKmh / 3.6);
            if ($gustMs > $ff) {
                $out .= '*'.$gustMs;
            }
        }

        return $out;
    }

    private static function cloudBase(mixed $value): string
    {
        if (is_string($value) && str_starts_with($value, '>')) {
            return $value;
        }

        return self::code($value);
    }

    private static function visibility(mixed $value): string
    {
        $text = ImgwText::decode($value ?? '');
        if ($text === '' || $text === '-' || self::isMissing($value)) {
            return '/';
        }

        return $text;
    }

    private static function code(mixed $value): string
    {
        if ($value === null || $value === '' || self::isMissing($value)) {
            return '/';
        }

        return (string) (int) $value;
    }

    private static function decimal(mixed $value, int $decimals = 1): string
    {
        if ($value === null || $value === '' || self::isMissing($value)) {
            return '/';
        }

        return number_format((float) $value, $decimals, '.', '');
    }

    private static function isMissing(mixed $value): bool
    {
        return is_numeric($value) && (float) $value == self::MISSING;
    }
}
