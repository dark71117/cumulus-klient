<?php

namespace App\Services\Panel;

class AnimationService
{
    public function files(): array
    {
        $folder = $this->folder();
        $day = date('d');
        $founded = [];
        $videoExist = false;
        if (! is_dir($folder)) {
            return ['files' => [], 'videoExist' => false];
        }
        $files = scandir($folder) ?: [];
        foreach (config('cumulus.tv_files') as $type => $tvFile) {
            foreach ($files as $file) {
                if (str_contains($file, $tvFile['file'].$day) && date('Y-m-d', filemtime($folder.$file)) === date('Y-m-d')) {
                    $tvFile['a'] .= $day;
                    $tvFile['fileName'] = $file;
                    $founded[$type] = $tvFile;
                    if (isset($tvFile['video'])) {
                        $videoExist = true;
                    }
                    break;
                }
            }
        }

        return ['files' => $founded, 'videoExist' => $videoExist];
    }

    public function path(string $filename): ?string
    {
        $full = $this->folder().basename($filename);
        if (! is_file($full)) {
            return null;
        }

        return $full;
    }

    private function folder(): string
    {
        $suffix = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) config('cumulus.tv_files_dir'));
        $htdocs = dirname(base_path());
        $candidates = [
            config('cumulus.web_folder'),
            $htdocs.DIRECTORY_SEPARATOR.'cumulus.wroc.pl'.DIRECTORY_SEPARATOR.'domains'.DIRECTORY_SEPARATOR.'test3.cumulus.wroc.pl'.DIRECTORY_SEPARATOR.'public_html',
            public_path(),
        ];
        $fallback = rtrim(public_path(), '/\\').$suffix.DIRECTORY_SEPARATOR;
        foreach ($candidates as $base) {
            if (! $base) {
                continue;
            }
            $dir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $base), DIRECTORY_SEPARATOR).$suffix;
            if (! is_dir($dir)) {
                continue;
            }
            $files = array_diff(scandir($dir) ?: [], ['.', '..']);
            if ($files !== []) {
                return $dir.DIRECTORY_SEPARATOR;
            }
            $fallback = $dir.DIRECTORY_SEPARATOR;
        }

        return $fallback;
    }
}
