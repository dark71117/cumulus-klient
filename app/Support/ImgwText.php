<?php

namespace App\Support;

class ImgwText
{
    public static function decode(mixed $value): string
    {
        return html_entity_decode(trim((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
