<?php

namespace App\Support;

class ImgwText
{
    public static function decode(mixed $value): string
    {
        return html_entity_decode(trim((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function plain(mixed $value): string
    {
        return trim(strip_tags(self::decode($value)));
    }

    public static function cloudCover(mixed $value): string
    {
        $text = mb_strtolower(self::plain($value), 'UTF-8');
        if ($text === '') {
            return '';
        }
        if (str_contains($text, 'zachmurzenie') || str_contains($text, 'bezchmurn') || str_contains($text, 'niebo')) {
            return $text;
        }

        return 'zachmurzenie '.$text;
    }

    public static function markup(mixed $value): string
    {
        $text = self::decode($value);
        $parts = [];
        $text = preg_replace_callback(
            '/<span\s+class="pogrubione">(.*?)<\/span>/isu',
            function (array $match) use (&$parts): string {
                $key = '___IMGW_BOLD_'.count($parts).'___';
                $parts[$key] = '<span class="pogrubione">'.e($match[1]).'</span>';

                return $key;
            },
            $text
        ) ?? $text;

        return strtr(e(strip_tags($text)), $parts);
    }
}
