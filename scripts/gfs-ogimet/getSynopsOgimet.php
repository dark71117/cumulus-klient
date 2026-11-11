<?php

/**
 * Import SYNOP z Ogimet do z_depesze_new / z_depesze_archiwum_new.
 * Do crona u grzes, obok getSynops.pl (curl + PDO, bez Laravela):
 *
 * 25 * * * * /bin/php84 /home/grzes/gfs/scripts/getSynopsOgimet.php >> /home/grzes/gfs/logs/getSynopsOgimet.log 2>&1
 */
declare(strict_types=1);

putenv('TZ=Europe/Warsaw');
date_default_timezone_set('Europe/Warsaw');

$lib = __DIR__.'/Lib/Ogimet';
require_once $lib.'/WmoWeather.php';
require_once $lib.'/SynopDecoder.php';
require_once $lib.'/OgimetParse.php';

$hours = 0;
$ensureOnly = false;
foreach (array_slice($argv ?? [], 1) as $arg) {
    if ($arg === '--ensure-tables') {
        $ensureOnly = true;
    } elseif (str_starts_with($arg, '--hours=')) {
        $hours = (int) substr($arg, 8);
    }
}

try {
    $pdo = ogimetPdo();
    ogimetEnsureTables($pdo);
    if ($ensureOnly) {
        echo date('Y-m-d H:i:s')." Tabele z_depesze_new i z_depesze_archiwum_new są gotowe.\n";
        exit(0);
    }

    $warsaw = new DateTimeZone('Europe/Warsaw');
    $utc = new DateTimeZone('UTC');
    $localHour = (new DateTimeImmutable('now', $warsaw))->setTime((int) date('G'), 0, 0);

    if ($hours > 0) {
        $fromUtc = $localHour->sub(new DateInterval('PT'.$hours.'H'))->setTimezone($utc);
        $toUtc = new DateTimeImmutable('now', $utc);
        $items = OgimetParse::parseAny(ogimetCurl(ogimetRangeUrl($fromUtc, $toUtc)));
        $source = 'ogimet-range';
    } else {
        $fromUtc = $localHour->setTimezone($utc);
        $toUtc = $fromUtc->add(new DateInterval('PT1H'));
        $items = OgimetParse::filterHour(
            OgimetParse::parseAny(ogimetCurl(ogimetRangeUrl($fromUtc, $toUtc))),
            $fromUtc
        );
        $source = 'ogimet-hour';
        if ($items === []) {
            $items = OgimetParse::parseAny(ogimetCurl(
                'https://www.ogimet.com/ultimos_synops2.php?lang=en&estado=Pola&fmt=txt&Send=Send'
            ));
            $source = 'ogimet-latest';
        }
    }

    $decoder = new App\Services\Synop\SynopDecoder();
    $saved = 0;
    $skipped = 0;
    foreach ($items as $item) {
        $record = $decoder->decode($item['raw'], $item['observedAtUtc'], $item['windUnit']);
        if ($record === null || (int) $record['idStacji'] !== (int) $item['stationId']) {
            $skipped++;
            continue;
        }
        ogimetSave($pdo, $record);
        $saved++;
    }

    echo sprintf(
        "%s Ogimet [%s] %s lokalnie / %s UTC: pobrano %d, zapisano %d, pominięto %d.\n",
        date('Y-m-d H:i:s'),
        $source,
        $localHour->format('Y-m-d H:i'),
        $localHour->setTimezone($utc)->format('Y-m-d H:i'),
        count($items),
        $saved,
        $skipped
    );
} catch (Throwable $e) {
    fwrite(STDERR, date('Y-m-d H:i:s').' BŁĄD: '.$e->getMessage()."\n");
    exit(1);
}

function ogimetRangeUrl(DateTimeImmutable $fromUtc, DateTimeImmutable $toUtc): string
{
    return sprintf(
        'https://www.ogimet.com/cgi-bin/getsynop?begin=%s&end=%s&state=Pol',
        $fromUtc->format('YmdHi'),
        $toUtc->format('YmdHi')
    );
}

function ogimetCurl(string $url): string
{
    $bin = is_executable('/usr/bin/curl') ? '/usr/bin/curl' : 'curl';
    $cmd = sprintf(
        '%s -sSf --max-time 90 -A %s %s',
        $bin,
        escapeshellarg('CumulusKlient/1.0 (getSynopsOgimet)'),
        escapeshellarg($url)
    );
    $out = [];
    $code = 0;
    exec($cmd.' 2>&1', $out, $code);
    $body = implode("\n", $out);
    if ($code !== 0) {
        throw new RuntimeException('curl exit '.$code.' dla '.$url.($body !== '' ? ': '.$body : ''));
    }

    return $body;
}

function ogimetPdo(): PDO
{
    $file = ogimetDbFile();
    $db = require $file;
    if (! is_array($db) || empty($db['host']) || empty($db['db'])) {
        throw new RuntimeException('Niepoprawna konfiguracja bazy: '.$file);
    }

    return new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'],
            $db['port'] ?? 3306,
            $db['db'],
            $db['charset'] ?? 'utf8'
        ),
        (string) $db['username'],
        (string) ($db['password'] ?? ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

function ogimetDbFile(): string
{
    $fromEnv = getenv('OGIMET_DB_CONFIG');
    $candidates = array_filter([
        is_string($fromEnv) && $fromEnv !== '' ? $fromEnv : null,
        __DIR__.'/customers.new/src/configs/db.php',
        '/home/grzes/gfs/scripts/customers.new/src/configs/db.php',
        dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'grzes'.DIRECTORY_SEPARATOR.'gfs'.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'customers.new'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'db.php',
    ]);
    foreach ($candidates as $file) {
        if (is_readable($file)) {
            return $file;
        }
    }

    throw new RuntimeException('Brak db.php (szukano obok skryptu i w /home/grzes/gfs/scripts/customers.new/src/configs/db.php)');
}

function ogimetEnsureTables(PDO $pdo): void
{
    ogimetEnsureTable($pdo, 'z_depesze_new', 'z_depesze');
    ogimetEnsureTable($pdo, 'z_depesze_archiwum_new', 'z_depesze_archiwum');
}

function ogimetTableExists(PDO $pdo, string $table): bool
{
    $st = $pdo->prepare('SHOW TABLES LIKE ?');
    $st->execute([$table]);

    return $st->fetchColumn() !== false;
}

function ogimetEnsureTable(PDO $pdo, string $table, string $like): void
{
    if (ogimetTableExists($pdo, $table)) {
        return;
    }
    try {
        $pdo->exec('CREATE TABLE `'.$table.'` LIKE `'.$like.'`');
    } catch (PDOException $e) {
        throw new RuntimeException(
            "Brak tabeli {$table} i user MySQL nie może jej utworzyć. ".
            "Raz, jako root/meteomax: cd /home/cumulus_klient && /bin/php84 artisan synop:import-ogimet --ensure-tables"
        );
    }
}

function ogimetSave(PDO $pdo, array $record): void
{
    $record['aktualizacja'] = (new DateTimeImmutable('now', new DateTimeZone('Europe/Warsaw')))->format('Y-m-d H:i:s');
    ogimetUpsert($pdo, 'z_depesze_new', $record, true);
    $pdo->prepare('DELETE FROM z_depesze_archiwum_new WHERE idStacji = ? AND termin = ?')
        ->execute([$record['idStacji'], $record['termin']]);
    ogimetUpsert($pdo, 'z_depesze_archiwum_new', $record, false);
}

function ogimetUpsert(PDO $pdo, string $table, array $record, bool $skipOlder): void
{
    $cols = ogimetColumns($pdo, $table);
    $data = array_intersect_key($record, array_flip($cols));
    if ($data === []) {
        return;
    }
    if ($skipOlder) {
        $st = $pdo->prepare("SELECT id, termin FROM {$table} WHERE idStacji = ? LIMIT 1");
        $st->execute([$record['idStacji']]);
        $existing = $st->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            if (! empty($existing['termin']) && ($record['termin'] ?? '') < $existing['termin']) {
                return;
            }
            $sets = implode(', ', array_map(fn ($c) => "`{$c}` = ?", array_keys($data)));
            $pdo->prepare("UPDATE {$table} SET {$sets} WHERE id = ?")->execute([...array_values($data), $existing['id']]);

            return;
        }
    }
    $names = implode(', ', array_map(fn ($c) => "`{$c}`", array_keys($data)));
    $qs = implode(', ', array_fill(0, count($data), '?'));
    $pdo->prepare("INSERT INTO {$table} ({$names}) VALUES ({$qs})")->execute(array_values($data));
}

function ogimetColumns(PDO $pdo, string $table): array
{
    static $cache = [];
    if (! isset($cache[$table])) {
        $cache[$table] = $pdo->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_COLUMN);
    }

    return $cache[$table];
}
