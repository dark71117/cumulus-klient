<?php

namespace App\Services\Panel;

use App\Support\AnalysisTable;
use App\Support\CustomerContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AnalysisService
{
    public function __construct(
        private string $depeszeTable = 'z_depesze_new',
        private string $archiveTable = 'z_depesze_archiwum_new',
    ) {}

    public static function fromSource(string $source): self
    {
        return $source === 'imgw'
            ? new self('z_depesze', 'z_depesze_archiwum')
            : new self('z_depesze_new', 'z_depesze_archiwum_new');
    }

    /**
     * @return array{source: string, latest: string, termin: string, prev: ?string, next: ?string, rows: list<array<string, mixed>>, stations: list<array{id: int, name: string}>}
     */
    public function hour(?string $termin = null): array
    {
        $empty = ['latest' => '', 'termin' => '', 'prev' => null, 'next' => null, 'rows' => [], 'stations' => $this->stations()];
        $customer = CustomerContext::get();
        if ((int) ($customer['IMGW'] ?? 0) !== 1) {
            return $empty;
        }
        try {
            $latest = $this->latestTermin();
        } catch (Throwable $e) {
            report($e);

            return array_merge($empty, ['error' => 1]);
        }
        if ($latest === '') {
            return $empty;
        }
        $at = $this->parseHour($termin) ?? Carbon::parse($latest);
        $wanted = $at->format('Y-m-d H:i:s');
        try {
            $raw = $this->hourRows($wanted);
            $rows = [];
            foreach ($raw as $row) {
                $rows[] = AnalysisTable::item($row);
            }

            return [
                'latest' => $latest,
                'termin' => $wanted,
                'prev' => $this->neighborTermin($wanted, false),
                'next' => $this->neighborTermin($wanted, true),
                'rows' => $rows,
                'stations' => $this->stations(),
            ];
        } catch (Throwable $e) {
            report($e);

            return array_merge($empty, ['error' => 1]);
        }
    }

    /**
     * @return array{from: string, to: string, station: array{id: int, name: string}|null, stats: list<array<string, mixed>>, error?: string}
     */
    public function stats(int $stationId, string $from, string $to): array
    {
        $fromAt = $this->parseHour($from);
        $toAt = $this->parseHour($to);
        if (! $fromAt || ! $toAt || $toAt->lt($fromAt)) {
            return ['from' => $from, 'to' => $to, 'station' => null, 'stats' => [], 'error' => 'Niepoprawny zakres dat.'];
        }
        if ($fromAt->diffInDays($toAt) > AnalysisTable::MAX_RANGE_DAYS) {
            return [
                'from' => $fromAt->format('Y-m-d H:i:s'),
                'to' => $toAt->format('Y-m-d H:i:s'),
                'station' => null,
                'stats' => [],
                'error' => 'Maksymalny zakres to '.AnalysisTable::MAX_RANGE_DAYS.' dni.',
            ];
        }
        $station = collect($this->stations())->firstWhere('id', $stationId);
        if (! $station) {
            return ['from' => $fromAt->format('Y-m-d H:i:s'), 'to' => $toAt->format('Y-m-d H:i:s'), 'station' => null, 'stats' => [], 'error' => 'Nieznana stacja.'];
        }
        $fields = AnalysisTable::numericFields($this->archiveTable);
        if ($fields === []) {
            $fields = AnalysisTable::numericFields($this->depeszeTable);
        }
        try {
            $rows = $this->rangeRows($stationId, $fromAt->format('Y-m-d H:i:s'), $toAt->format('Y-m-d H:i:s'));
        } catch (Throwable $e) {
            report($e);

            return ['from' => $fromAt->format('Y-m-d H:i:s'), 'to' => $toAt->format('Y-m-d H:i:s'), 'station' => $station, 'stats' => [], 'error' => 1];
        }
        $stats = [];
        foreach ($fields as $column => $label) {
            $values = [];
            foreach ($rows as $row) {
                $number = AnalysisTable::parseNumeric($row->{$column} ?? null);
                if ($number !== null) {
                    $values[] = $number;
                }
            }
            $count = count($values);
            $stats[] = [
                'key' => $column,
                'label' => $label,
                'min' => $count ? AnalysisTable::formatStat(min($values)) : '/',
                'max' => $count ? AnalysisTable::formatStat(max($values)) : '/',
                'avg' => $count ? AnalysisTable::formatStat(array_sum($values) / $count) : '/',
                'count' => $count,
            ];
        }

        return [
            'from' => $fromAt->format('Y-m-d H:i:s'),
            'to' => $toAt->format('Y-m-d H:i:s'),
            'station' => $station,
            'stats' => $stats,
        ];
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function stations(): array
    {
        $customer = CustomerContext::get();
        if ((int) ($customer['IMGW'] ?? 0) !== 1) {
            return [];
        }
        try {
            return DB::table('z_uprawnieniadepesze as ud')
                ->join('z_listastacji as ls', 'ud.idStacji', '=', 'ls.idStacji')
                ->where('ud.aktywna', 1)
                ->where('ud.idKlienta', $customer['id'])
                ->where('ls.aktywna', 1)
                ->orderBy('ud.lp')
                ->get(['ls.idStacji as id', 'ls.nazwaStacji as name'])
                ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
                ->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    private function latestTermin(): string
    {
        $current = Schema::hasTable($this->depeszeTable) ? DB::table($this->depeszeTable)->max('termin') : null;
        if ($current) {
            return (string) $current;
        }
        $archive = Schema::hasTable($this->archiveTable) ? DB::table($this->archiveTable)->max('termin') : null;

        return $archive ? (string) $archive : '';
    }

    private function neighborTermin(string $termin, bool $next): ?string
    {
        $query = $this->baseQuery($this->preferredTable())
            ->select('d.termin')
            ->distinct();
        if ($next) {
            $value = $query->where('d.termin', '>', $termin)->orderBy('d.termin')->value('d.termin');
        } else {
            $value = $query->where('d.termin', '<', $termin)->orderByDesc('d.termin')->value('d.termin');
        }

        return $value ? (string) $value : null;
    }

    private function hourRows(string $termin)
    {
        $rows = $this->rowsAt($this->preferredTable(), $termin);
        if ($rows->isEmpty() && $this->preferredTable() !== $this->depeszeTable) {
            $rows = $this->rowsAt($this->depeszeTable, $termin);
        }

        return $rows;
    }

    private function rangeRows(int $stationId, string $from, string $to)
    {
        $table = $this->preferredTable();
        $select = $this->selectColumns($table);

        return $this->baseQuery($table)
            ->where('d.idStacji', $stationId)
            ->whereBetween('d.termin', [$from, $to])
            ->orderBy('d.termin')
            ->select($select)
            ->get();
    }

    private function rowsAt(string $table, string $termin)
    {
        return $this->baseQuery($table)
            ->where('d.termin', $termin)
            ->orderBy('ud.lp')
            ->select($this->selectColumns($table))
            ->get();
    }

    private function baseQuery(string $table)
    {
        $customer = CustomerContext::get();

        return DB::table('z_uprawnieniadepesze as ud')
            ->join($table.' as d', 'ud.idStacji', '=', 'd.idStacji')
            ->join('z_listastacji as ls', 'd.idStacji', '=', 'ls.idStacji')
            ->where('ud.aktywna', 1)
            ->where('ud.idKlienta', $customer['id'])
            ->where('ls.aktywna', 1);
    }

    /** @return list<string> */
    private function selectColumns(string $table): array
    {
        $wanted = [
            'idStacji', 'termin', 'temp', 'tempOdcz', 'zachmurzenie', 'zachmurzenieTXT',
            'zachmurzeniePodstawa', 'widzialnosc', 'wiatr', 'wiatrP', 'wiatrK', 'wiatrMS',
            'porywy', 'wilgotnosc', 'cisnienie', 'cisnienieStacja', 'cisnienieMorze',
            'zjawisko', 'zjawiskoTXT', 'zjawiskoKolor', 'wysokoscOpadu', 'okresOpadu',
            'synop', 'synop_raw', 'metar', 'zrodlo',
        ];
        $cols = ['ls.nazwaStacji', 'ls.region'];
        foreach ($wanted as $column) {
            if (Schema::hasColumn($table, $column)) {
                $cols[] = 'd.'.$column;
            }
        }

        return $cols;
    }

    private function preferredTable(): string
    {
        return Schema::hasTable($this->archiveTable) ? $this->archiveTable : $this->depeszeTable;
    }

    private function parseHour(?string $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $value = str_replace('T', ' ', $value);
        try {
            return Carbon::parse($value)->startOfHour();
        } catch (Throwable $e) {
            return null;
        }
    }
}
