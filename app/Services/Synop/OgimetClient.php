<?php

namespace App\Services\Synop;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OgimetClient
{
    /**
     * @return list<array{raw: string, stationId: int, observedAtUtc: Carbon, windUnit: int}>
     */
    public function latestPoland(): array
    {
        $url = config('cumulus.ogimet_latest_url');
        $body = $this->get($url);

        return $this->parseUltimosTxt($body);
    }

    /**
     * Depesze z bieżącej godziny lokalnej (jak getSynops.pl), nie „ostatnie co Ogimet ma”.
     *
     * @return list<array{raw: string, stationId: int, observedAtUtc: Carbon, windUnit: int}>
     */
    public function currentHourPoland(?Carbon $localHour = null): array
    {
        $local = ($localHour ?? Carbon::now(config('app.timezone')))->startOfHour();
        $fromUtc = $local->copy()->timezone('UTC');
        $toUtc = $fromUtc->copy()->addHour();
        $wanted = $fromUtc->format('Y-m-d H:00:00');

        return array_values(array_filter(
            $this->rangePoland($fromUtc, $toUtc),
            fn (array $item) => $item['observedAtUtc']->copy()->startOfHour()->format('Y-m-d H:i:s') === $wanted
        ));
    }

    /**
     * @return list<array{raw: string, stationId: int, observedAtUtc: Carbon, windUnit: int}>
     */
    public function rangePoland(Carbon $fromUtc, Carbon $toUtc): array
    {
        $url = sprintf(
            '%s?begin=%s&end=%s&state=Pol',
            rtrim((string) config('cumulus.ogimet_range_url'), '?'),
            $fromUtc->format('YmdHi'),
            $toUtc->format('YmdHi')
        );
        $body = $this->get($url);
        $parsed = $this->parseUltimosTxt($body);
        if ($parsed !== []) {
            return $parsed;
        }

        return $this->parseGetsynop($body);
    }

    /** @return list<array{raw: string, stationId: int, observedAtUtc: Carbon, windUnit: int}> */
    public function parseUltimosTxt(string $body): array
    {
        $joined = $this->joinWrappedLines($body);
        $out = [];
        foreach (preg_split("/\r\n|\n|\r/", $joined) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (! preg_match('/^(20\d{10})\s+\S+\s+(\d{5})\s+AAXX\s+(\d{5})\s+(.+)$/i', $line, $m)) {
                continue;
            }
            $observed = Carbon::createFromFormat('YmdHi', $m[1], 'UTC');
            $windUnit = (int) substr($m[3], 4, 1);
            $raw = trim($m[4]);
            $out[] = [
                'raw' => $raw,
                'stationId' => (int) $m[2],
                'observedAtUtc' => $observed,
                'windUnit' => $windUnit,
            ];
        }

        return $out;
    }

    /** @return list<array{raw: string, stationId: int, observedAtUtc: Carbon, windUnit: int}> */
    public function parseGetsynop(string $body): array
    {
        $out = [];
        $header = null;
        $buffer = '';
        foreach (preg_split("/\r\n|\n|\r/", $body) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (preg_match('/^AAXX\s+(\d{5})/i', $line, $m)) {
                $header = $m[1];
                continue;
            }
            $buffer = $buffer === '' ? $line : $buffer.' '.$line;
            if (! str_contains($line, '=')) {
                continue;
            }
            if ($header === null || ! preg_match('/^(\d{5})\b/', $buffer, $id)) {
                $buffer = '';
                continue;
            }
            $day = (int) substr($header, 0, 2);
            $hour = (int) substr($header, 2, 2);
            $observed = Carbon::now('UTC')->startOfHour();
            if ($observed->day !== $day) {
                $observed->subDay();
            }
            $observed->setTime($hour, 0);
            $out[] = [
                'raw' => rtrim($buffer, '='),
                'stationId' => (int) $id[1],
                'observedAtUtc' => $observed,
                'windUnit' => (int) substr($header, 4, 1),
            ];
            $buffer = '';
        }

        return $out;
    }

    private function joinWrappedLines(string $body): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $body) ?: [];
        $joined = [];
        foreach ($lines as $line) {
            if ($line !== '' && preg_match('/^\s+/', $line) && $joined !== []) {
                $joined[array_key_last($joined)] .= ' '.trim($line);
                continue;
            }
            $joined[] = $line;
        }

        return implode("\n", $joined);
    }

    private function get(string $url): string
    {
        $response = Http::timeout((int) config('cumulus.ogimet_timeout', 90))
            ->withHeaders(['User-Agent' => 'CumulusKlient/1.0 (ogimet synop import)'])
            ->get($url);
        if (! $response->successful()) {
            throw new RuntimeException('Ogimet HTTP '.$response->status().' dla '.$url);
        }

        return (string) $response->body();
    }
}
