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
    public function importLatest(): array
    {
        return $this->persist($this->client->latestPoland());
    }

    /**
     * @return array{fetched: int, saved: int, skipped: int}
     */
    public function importHours(int $hours): array
    {
        $to = Carbon::now('UTC')->startOfHour();
        $from = $to->copy()->subHours(max(1, $hours));

        return $this->persist($this->client->rangePoland($from, $to));
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
