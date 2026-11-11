<?php

namespace App\Services\Panel;

use App\Support\GfsMapCatalog;
use DateTimeImmutable;
use DateTimeZone;

class GfsMapService
{
    public function root(): string
    {
        $configured = (string) config('cumulus.gfs_maps_path', '/home/meteomax-data/maps');
        if ($configured !== '' && is_dir($configured)) {
            return $configured;
        }
        $local = 'D:/cumulus/PROGRAMY/Pogoda/GFS_0.25/daneKoncowe/mapy';
        if (is_dir($local)) {
            return $local;
        }

        return $configured;
    }

    public function runs(): array
    {
        $root = $this->root();
        if ($root === '' || ! is_dir($root)) {
            return [];
        }
        $runs = [];
        foreach (scandir($root) ?: [] as $name) {
            if (preg_match('/^\d{4}\.\d{2}\.\d{2}\.\d{2}$/', $name) && is_dir($root.DIRECTORY_SEPARATOR.$name)) {
                $runs[] = $name;
            }
        }
        rsort($runs);

        return $runs;
    }

    public function frames(string $run, string $region, string $product, string $tab = ''): ?array
    {
        if (! $this->validRun($run) || ! GfsMapCatalog::isRegion($region) || GfsMapCatalog::filesFor($product) === []) {
            return null;
        }
        $stamps = $this->stamps($run, $region, $product);
        $hours = array_keys($stamps);
        $coverage = $this->coverage($run, $region, $product, $tab);

        return [
            'hours' => $hours,
            'days' => $this->timeline($run, $hours),
            'count' => count($hours),
            'stamps' => (object) $stamps,
            'runs' => $this->runs(),
            'coverage' => [
                'total' => $coverage['total'],
                'hours' => (object) $coverage['hours'],
                'dates' => (object) $coverage['dates'],
            ],
        ];
    }

    public function imagePath(string $run, string $region, string $product, string $hour): ?string
    {
        if (! preg_match('/^\d{3}$/', $hour)) {
            return null;
        }
        $dir = $this->regionDir($run, $region);
        $prefixes = GfsMapCatalog::filesFor($product);
        if ($dir === null || $prefixes === []) {
            return null;
        }
        foreach ($prefixes as $prefix) {
            if (! preg_match('/^[A-Za-z0-9_]+$/', $prefix)) {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$prefix.'.'.$hour.'.png';
            if (! is_file($path)) {
                continue;
            }
            $real = realpath($path);
            if ($real !== false && $this->inside($real, $dir)) {
                return $real;
            }
        }

        return null;
    }

    public function hours(string $run, string $region, string $product): array
    {
        return array_keys($this->stamps($run, $region, $product));
    }

    public function stamps(string $run, string $region, string $product): array
    {
        $dir = $this->regionDir($run, $region);
        $prefixes = GfsMapCatalog::filesFor($product);
        if ($dir === null || $prefixes === []) {
            return [];
        }
        $wanted = array_flip($prefixes);
        $found = [];
        foreach (scandir($dir) ?: [] as $file) {
            if (! preg_match('/^([A-Za-z0-9_]+)\.(\d{3})\.png$/', $file, $match)) {
                continue;
            }
            if (! isset($wanted[$match[1]])) {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            clearstatcache(true, $path);
            $mtime = filemtime($path);
            if ($mtime === false) {
                continue;
            }
            if (! isset($found[$match[2]]) || $mtime > $found[$match[2]]) {
                $found[$match[2]] = $mtime;
            }
        }
        ksort($found);

        return $found;
    }

    public function coverage(string $run, string $region, string $product, string $tab = ''): array
    {
        $tabId = GfsMapCatalog::productsForTab($tab) !== [] ? $tab : (GfsMapCatalog::tabIdFor($product) ?? '');
        $products = GfsMapCatalog::productsForTab($tabId);
        $prefixToProduct = [];
        foreach ($products as $item) {
            foreach ($item['files'] as $prefix) {
                $prefixToProduct[$prefix] = $item['id'];
            }
        }
        $byHour = [];
        $dir = $this->regionDir($run, $region);
        if ($dir !== null) {
            foreach (scandir($dir) ?: [] as $file) {
                if (! preg_match('/^([A-Za-z0-9_]+)\.(\d{3})\.png$/', $file, $match)) {
                    continue;
                }
                $id = $prefixToProduct[$match[1]] ?? null;
                if ($id === null) {
                    continue;
                }
                $byHour[$match[2]][$id] = true;
            }
        }
        $hourCounts = [];
        foreach ($byHour as $lead => $ids) {
            $hourCounts[$lead] = count($ids);
        }
        ksort($hourCounts);
        $start = DateTimeImmutable::createFromFormat('!Y.m.d.H', $run, new DateTimeZone('UTC'));
        $byDate = [];
        if ($start !== false) {
            foreach ($byHour as $lead => $ids) {
                $key = $start->modify('+'.((int) $lead).' hours')->format('d.m.Y');
                foreach (array_keys($ids) as $id) {
                    $byDate[$key][$id] = true;
                }
            }
        }
        $dateCounts = [];
        foreach ($byDate as $date => $ids) {
            $dateCounts[$date] = count($ids);
        }

        return [
            'total' => count($products),
            'hours' => $hourCounts,
            'dates' => $dateCounts,
        ];
    }

    public function timeline(string $run, array $hours): array
    {
        $start = DateTimeImmutable::createFromFormat('!Y.m.d.H', $run, new DateTimeZone('UTC'));
        if ($start === false) {
            return [];
        }
        $days = [];
        foreach ($hours as $hour) {
            $lead = (int) $hour;
            $valid = $start->modify('+'.$lead.' hours');
            $key = $valid->format('Y-m-d');
            if (! isset($days[$key])) {
                $days[$key] = [
                    'date' => $valid->format('d.m.Y'),
                    'label' => GfsMapCatalog::weekday((int) $valid->format('w')),
                    'clocks' => [],
                ];
            }
            $days[$key]['clocks'][] = [
                'clock' => $valid->format('H'),
                'lead' => sprintf('%03d', $lead),
            ];
        }

        return array_values($days);
    }

    private function validRun(string $run): bool
    {
        return (bool) preg_match('/^\d{4}\.\d{2}\.\d{2}\.\d{2}$/', $run);
    }

    private function regionDir(string $run, string $region): ?string
    {
        if (! $this->validRun($run) || ! GfsMapCatalog::isRegion($region)) {
            return null;
        }
        $root = $this->root();
        $realRoot = $root !== '' ? realpath($root) : false;
        if ($realRoot === false) {
            return null;
        }
        $dir = realpath($realRoot.DIRECTORY_SEPARATOR.$run.DIRECTORY_SEPARATOR.$region);
        if ($dir === false || ! $this->inside($dir, $realRoot)) {
            return null;
        }

        return $dir;
    }

    private function inside(string $path, string $root): bool
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $root = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with(strtolower($path), strtolower($root));
    }
}
