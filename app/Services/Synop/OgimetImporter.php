<?php

namespace App\Services\Synop;

use Carbon\Carbon;

class OgimetImporter
{
    public function __construct(
        private OgimetClient $client,
        private SynopDecoder $decoder,
        private SynopStore $store,
    ) {}

    /**
     * @return array{fetched: int, saved: int, skipped: int}
     */
    /**
     * @return array{fetched: int, saved: int, skipped: int, hourLocal: string, hourUtc: string, source: string}
     */
    public function importLatest(): array
    {
        $local = Carbon::now(config('app.timezone'))->startOfHour();
        $utc = $local->copy()->timezone('UTC');
        $items = $this->client->currentHourPoland($local);
        $source = 'ogimet-hour';
        if ($items === []) {
            $items = $this->client->latestPoland();
            $source = 'ogimet-latest';
        }
        $stats = $this->persist($items);
        $stats['hourLocal'] = $local->format('Y-m-d H:i');
        $stats['hourUtc'] = $utc->format('Y-m-d H:i');
        $stats['source'] = $source;

        return $stats;
    }

    /**
     * @return array{fetched: int, saved: int, skipped: int}
     */
    public function importHours(int $hours): array
    {
        $to = Carbon::now('UTC')->startOfHour();
        $from = $to->copy()->subHours(max(1, $hours));

        $stats = $this->persist($this->client->rangePoland($from, $to));
        $stats['hourLocal'] = $from->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i');
        $stats['hourUtc'] = $from->format('Y-m-d H:i');
        $stats['source'] = 'ogimet-range';

        return $stats;
    }

    /**
     * @param  list<array{raw: string, stationId: int, observedAtUtc: Carbon, windUnit: int}>  $items
     * @return array{fetched: int, saved: int, skipped: int}
     */
    public function persist(array $items): array
    {
        $this->store->ensureTables();
        $saved = 0;
        $skipped = 0;
        foreach ($items as $item) {
            $record = $this->decoder->decode($item['raw'], $item['observedAtUtc'], $item['windUnit']);
            if ($record === null || (int) $record['idStacji'] !== (int) $item['stationId']) {
                $skipped++;
                continue;
            }
            if ($this->store->save($record)) {
                $saved++;
            } else {
                $skipped++;
            }
        }

        return ['fetched' => count($items), 'saved' => $saved, 'skipped' => $skipped];
    }
}
