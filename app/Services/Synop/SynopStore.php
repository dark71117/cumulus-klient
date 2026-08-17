<?php

namespace App\Services\Synop;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SynopStore
{
    public function __construct(
        private string $currentTable = 'z_depesze_new',
        private string $archiveTable = 'z_depesze_archiwum_new',
    ) {}

    public function ensureTables(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            if (Schema::hasTable('z_depesze') && ! Schema::hasTable($this->currentTable)) {
                DB::statement('CREATE TABLE '.$this->currentTable.' LIKE z_depesze');
            }
            if (Schema::hasTable('z_depesze_archiwum') && ! Schema::hasTable($this->archiveTable)) {
                DB::statement('CREATE TABLE '.$this->archiveTable.' LIKE z_depesze_archiwum');
            }

            return;
        }
        if (! Schema::hasTable($this->currentTable) && Schema::hasTable('z_depesze')) {
            DB::statement('CREATE TABLE '.$this->currentTable.' AS SELECT * FROM z_depesze WHERE 0');
        }
        if (! Schema::hasTable($this->archiveTable) && Schema::hasTable('z_depesze_archiwum')) {
            DB::statement('CREATE TABLE '.$this->archiveTable.' AS SELECT * FROM z_depesze_archiwum WHERE 0');
        }
    }

    /**
     * @param  array<string, mixed>  $record
     */
    public function save(array $record): bool
    {
        if (empty($record['idStacji']) || empty($record['termin'])) {
            return false;
        }
        $now = now();
        $record['aktualizacja'] = $now;
        $current = $this->payload($this->currentTable, $record);
        $existing = DB::table($this->currentTable)->where('idStacji', $record['idStacji'])->first();
        if ($existing) {
            if (empty($existing->termin) || $record['termin'] >= $existing->termin) {
                DB::table($this->currentTable)->where('id', $existing->id)->update($current);
            }
        } else {
            DB::table($this->currentTable)->insert($current);
        }

        $archive = $this->payload($this->archiveTable, $record);
        DB::table($this->archiveTable)
            ->where('idStacji', $record['idStacji'])
            ->where('termin', $record['termin'])
            ->delete();
        DB::table($this->archiveTable)->insert($archive);

        return true;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function payload(string $table, array $record): array
    {
        $out = [];
        foreach ($record as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $out[$column] = $value;
            }
        }

        return $out;
    }
}
