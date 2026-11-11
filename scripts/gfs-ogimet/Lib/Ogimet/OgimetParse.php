<?php

/**
 * Parsery odpowiedzi Ogimet (CSV CGI i txt ultimos), bez Laravela.
 */
declare(strict_types=1);

class OgimetParse
{
    /**
     * @return list<array{raw: string, stationId: int, observedAtUtc: DateTimeImmutable, windUnit: int}>
     */
    public static function parseGetsynopCsv(string $body): array
    {
        $out = [];
        foreach (preg_split("/\r\n|\n|\r/", $body) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (! preg_match('/^(\d{5}),(\d{4}),(\d{2}),(\d{2}),(\d{2}),(\d{2}),\s*AAXX\s+(\d{5})\s+(.+)$/i', $line, $m)) {
                continue;
            }
            $observed = new DateTimeImmutable(
                sprintf('%s-%s-%s %s:%s:00', $m[2], $m[3], $m[4], $m[5], $m[6]),
                new DateTimeZone('UTC')
            );
            $out[] = [
                'raw' => rtrim($m[8], '= '),
                'stationId' => (int) $m[1],
                'observedAtUtc' => $observed,
                'windUnit' => (int) substr($m[7], 4, 1),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{raw: string, stationId: int, observedAtUtc: DateTimeImmutable, windUnit: int}>
     */
    public static function parseUltimosTxt(string $body): array
    {
        $out = [];
        foreach (preg_split("/\r\n|\n|\r/", self::joinWrappedLines($body)) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (! preg_match('/^(20\d{10})\s+\S+\s+(\d{5})\s+AAXX\s+(\d{5})\s+(.+)$/i', $line, $m)) {
                continue;
            }
            $observed = DateTimeImmutable::createFromFormat('YmdHi', $m[1], new DateTimeZone('UTC'));
            if ($observed === false) {
                continue;
            }
            $out[] = [
                'raw' => trim($m[4]),
                'stationId' => (int) $m[2],
                'observedAtUtc' => $observed,
                'windUnit' => (int) substr($m[3], 4, 1),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{raw: string, stationId: int, observedAtUtc: DateTimeImmutable, windUnit: int}>
     */
    public static function parseAny(string $body): array
    {
        $parsed = self::parseGetsynopCsv($body);
        if ($parsed !== []) {
            return $parsed;
        }

        return self::parseUltimosTxt($body);
    }

    /**
     * @param  list<array{raw: string, stationId: int, observedAtUtc: DateTimeImmutable, windUnit: int}>  $items
     * @return list<array{raw: string, stationId: int, observedAtUtc: DateTimeImmutable, windUnit: int}>
     */
    public static function filterHour(array $items, DateTimeImmutable $fromUtc): array
    {
        $wanted = $fromUtc->setTime((int) $fromUtc->format('G'), 0)->format('Y-m-d H:00:00');

        return array_values(array_filter(
            $items,
            fn (array $item) => $item['observedAtUtc']->setTime((int) $item['observedAtUtc']->format('G'), 0)->format('Y-m-d H:00:00') === $wanted
        ));
    }

    private static function joinWrappedLines(string $body): string
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
}
