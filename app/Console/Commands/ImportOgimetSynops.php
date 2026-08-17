<?php

namespace App\Console\Commands;

use App\Services\Synop\OgimetImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportOgimetSynops extends Command
{
    protected $signature = 'synop:import-ogimet
        {--hours=0 : Pobierz zakres z ostatnich N godzin zamiast samego latest}
        {--ensure-tables : Tylko utwórz tabele z_depesze_new / z_depesze_archiwum_new}';

    protected $description = 'Pobiera pełne depesze SYNOP z Ogimet (Polska) do tabel *_new';

    public function handle(OgimetImporter $importer): int
    {
        if ($this->option('ensure-tables')) {
            app(\App\Services\Synop\SynopStore::class)->ensureTables();
            $this->info('Tabele z_depesze_new i z_depesze_archiwum_new są gotowe.');

            return self::SUCCESS;
        }

        try {
            $hours = (int) $this->option('hours');
            $stats = $hours > 0 ? $importer->importHours($hours) : $importer->importLatest();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Ogimet [%s] %s lokalnie / %s UTC: pobrano %d, zapisano %d, pominięto %d.',
            $stats['source'] ?? 'ogimet',
            $stats['hourLocal'] ?? '-',
            $stats['hourUtc'] ?? '-',
            $stats['fetched'],
            $stats['saved'],
            $stats['skipped']
        ));

        return self::SUCCESS;
    }
}
