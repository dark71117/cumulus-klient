<?php

namespace App\Services\Panel;

class MeteomaxService
{
    public function maps(): string
    {
        $html = $this->run('cumulus_maps.php');
        if ($this->isFailedOutput($html)) {
            return view('klient.partials.meteomax-link')->render();
        }

        return $html;
    }

    public function region(int $id, int $b, int $c): string
    {
        return $this->run('cumulus_region.php', sprintf('-i%d -b%d -c%d', $id, $b, $c));
    }

    public function chart(int $id, int $product, int $print): string
    {
        return $this->run('cumulus_chart.php', sprintf('-i%d -d%d -p%d', $id, $print, $product));
    }

    public function png(int $id): string
    {
        return $this->run('cumulus_png.php', sprintf('-i%d', $id));
    }

    public function mapsScriptPath(): ?string
    {
        return $this->scriptPath('cumulus_maps.php');
    }

    private function run(string $script, string $args = ''): string
    {
        if (! config('cumulus.meteomax_active')) {
            return '';
        }
        $file = $this->scriptPath($script);
        if ($file === null) {
            return '';
        }

        $command = [$this->phpBinary(), '-f', $file];
        if ($args !== '') {
            $command[] = '--';
            foreach (preg_split('/\s+/', trim($args)) ?: [] as $part) {
                if ($part !== '') {
                    $command[] = $part;
                }
            }
        }

        $pipes = [];
        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, dirname($file), $this->cliEnvironment());
        if (! is_resource($process)) {
            return '';
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $stdout;
    }

    private function scriptPath(string $script): ?string
    {
        $dir = $this->worksDir();
        if ($dir === null) {
            return null;
        }
        $file = $dir.DIRECTORY_SEPARATOR.$script;

        return is_file($file) ? $file : null;
    }

    private function worksDir(): ?string
    {
        $htdocs = dirname(base_path());
        $suffix = DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_weathermax'.DIRECTORY_SEPARATOR.'works';
        $candidates = [
            config('cumulus.meteomax_path'),
            $htdocs.DIRECTORY_SEPARATOR.'meteomaxv2'.$suffix,
            $htdocs.DIRECTORY_SEPARATOR.'meteomaxv3'.$suffix,
            $htdocs.DIRECTORY_SEPARATOR.'meteomax'.$suffix,
            '/home/meteomax/public_html/components/com_weathermax/works',
        ];
        foreach ($candidates as $dir) {
            if (! $dir) {
                continue;
            }
            $dir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $dir), DIRECTORY_SEPARATOR);
            if (is_file($dir.DIRECTORY_SEPARATOR.'cumulus_maps.php')) {
                return $dir;
            }
        }

        return null;
    }

    private function phpBinary(): string
    {
        $configured = (string) config('cumulus.php_path');
        $candidates = [
            $configured,
            rtrim($configured, '/\\').DIRECTORY_SEPARATOR.'php',
            rtrim($configured, '/\\').DIRECTORY_SEPARATOR.'php.exe',
            PHP_BINARY,
        ];
        foreach ($candidates as $bin) {
            if (is_string($bin) && $bin !== '' && is_file($bin)) {
                return $bin;
            }
        }

        return PHP_BINARY;
    }

    private function cliEnvironment(): array
    {
        $skip = [
            'REQUEST_METHOD', 'QUERY_STRING', 'CONTENT_TYPE', 'CONTENT_LENGTH',
            'PATH_INFO', 'REQUEST_URI', 'SCRIPT_NAME', 'PHP_SELF', 'GATEWAY_INTERFACE',
            'SERVER_PROTOCOL', 'REMOTE_ADDR', 'REMOTE_PORT', 'DOCUMENT_ROOT',
        ];
        $env = [];
        foreach (getenv() ?: [] as $key => $value) {
            $upper = strtoupper((string) $key);
            if (in_array($upper, $skip, true) || str_starts_with($upper, 'HTTP_')) {
                continue;
            }
            $env[$key] = $value;
        }

        return $env;
    }

    private function isFailedOutput(string $html): bool
    {
        $trim = trim(preg_replace('/^(Deprecated|PHP Deprecated|PHPERR):.*$/mi', '', $html) ?? $html);

        return $trim === '' || strcasecmp($trim, 'Error') === 0;
    }
}
