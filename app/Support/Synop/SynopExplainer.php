<?php

namespace App\Support\Synop;

class SynopExplainer
{
    /**
     * @return array{raw: string, groups: list<array{token: string, key: string, title: string, meaning: string, bits: list<array{code: string, text: string}>}>}
     */
    public function explain(string $raw): array
    {
        $raw = strtoupper(trim(preg_replace('/\s+/', ' ', $raw) ?? $raw));
        $ended = str_ends_with($raw, '=');
        $body = rtrim($raw, "=\n\r ");
        $tokens = $body === '' ? [] : array_values(array_filter(explode(' ', $body), fn ($t) => $t !== ''));
        if ($ended) {
            $tokens[] = '=';
        }
        $groups = [];
        $ctx = ['section' => 1, 'iw' => '1', 'ix' => '1', 'temp' => null];
        $i = 0;
        $n = count($tokens);
        if ($i < $n && in_array($tokens[$i], ['AAXX', 'BBXX'], true)) {
            $groups[] = $this->header($tokens[$i++]);
            if ($i < $n && preg_match('/^\d{5}$/', $tokens[$i])) {
                $groups[] = $this->yygg($tokens[$i], $ctx);
                $i++;
            }
        }
        if ($i < $n && preg_match('/^\d{5}$/', $tokens[$i])) {
            $groups[] = $this->station($tokens[$i++]);
        }
        if ($i < $n && $this->isFixed($tokens[$i])) {
            $groups[] = $this->irixhVV($tokens[$i++], $ctx);
        }
        if ($i < $n && $this->isFixed($tokens[$i])) {
            $groups[] = $this->nddff($tokens[$i++], $ctx);
        }
        for (; $i < $n; $i++) {
            $groups[] = $this->next($tokens[$i], $ctx);
        }

        return ['raw' => $ended ? $body.'=' : $body, 'groups' => $groups];
    }

    /** @param array<string, mixed> $ctx */
    private function next(string $g, array &$ctx): array
    {
        if ($g === '=') {
            return SynopValue::item($g, '=', 'Koniec depeszy', 'Znak końca komunikatu SYNOP.');
        }
        if ($g === 'NIL') {
            return SynopValue::item($g, 'NIL', 'Brak obserwacji', 'Stacja nie nadała depeszy w tym terminie.');
        }
        if ($g === '333' || $g === '555') {
            $ctx['section'] = (int) $g[0];

            return SynopValue::item($g, $g, 'Znacznik sekcji '.$g[0], $g === '333'
                ? 'Dalej dane klimatologiczne (temperatury ekstremalne, porywy, warstwy chmur).'
                : 'Dalej grupy narodowe / dodatkowe.');
        }

        return $ctx['section'] === 3
            ? SynopGroups::section3($g, $ctx)
            : SynopGroups::section1($g, $ctx);
    }

    private function header(string $g): array
    {
        return SynopValue::item($g, 'MiMiMjMj', 'Nagłówek depeszy', $g === 'AAXX'
            ? 'Depesza lądowa SYNOP (FM-12).'
            : 'Depesza morska SHIP (FM-13).');
    }

    /** @param array<string, mixed> $ctx */
    private function yygg(string $g, array &$ctx): array
    {
        $ctx['iw'] = $g[4] ?? '1';

        return SynopValue::item($g, 'YYGGiw', 'Termin i jednostka wiatru', 'Dzień '.(int) substr($g, 0, 2).'. miesiąca, godz. '.substr($g, 2, 2).' UTC. '.SynopCodes::iw($ctx['iw']).'.', [
            ['code' => 'YY='.substr($g, 0, 2), 'text' => 'dzień miesiąca (UTC)'],
            ['code' => 'GG='.substr($g, 2, 2), 'text' => 'godzina UTC'],
            ['code' => 'iw='.$ctx['iw'], 'text' => SynopCodes::iw($ctx['iw'])],
        ]);
    }

    private function station(string $g): array
    {
        return SynopValue::item($g, 'IIiii', 'Indeks stacji WMO', 'Numer stacji synoptycznej '.$g.'.', [
            ['code' => 'II='.substr($g, 0, 2), 'text' => 'blok / obszar'],
            ['code' => 'iii='.substr($g, 2, 3), 'text' => 'numer stacji'],
        ]);
    }

    /** @param array<string, mixed> $ctx */
    private function irixhVV(string $g, array &$ctx): array
    {
        $ir = $g[0] ?? '/';
        $ix = $g[1] ?? '/';
        $h = $g[2] ?? '/';
        $vv = substr($g, 3, 2);
        $ctx['ix'] = $ix;

        return SynopValue::item($g, 'irixhVV', 'Opad, typ stacji, podstawa chmur, widzialność', SynopCodes::visibility($vv).'; podstawa chmur: '.SynopCodes::cloudBase($h).'.', [
            ['code' => 'ir='.$ir, 'text' => SynopCodes::ir($ir)],
            ['code' => 'ix='.$ix, 'text' => SynopCodes::ix($ix)],
            ['code' => 'h='.$h, 'text' => 'podstawa chmur '.SynopCodes::cloudBase($h)],
            ['code' => 'VV='.$vv, 'text' => SynopCodes::visibility($vv)],
        ]);
    }

    /** @param array<string, mixed> $ctx */
    private function nddff(string $g, array &$ctx): array
    {
        $n = $g[0] ?? '/';
        $dd = substr($g, 1, 2);
        $ff = substr($g, 3, 2);
        $unit = SynopCodes::windUnit((string) $ctx['iw']);

        return SynopValue::item($g, 'Nddff', 'Zachmurzenie całkowite i wiatr', SynopCodes::oktas($n).'. '.SynopValue::windText($dd, $ff, $unit), [
            ['code' => 'N='.$n, 'text' => SynopCodes::oktas($n)],
            ['code' => 'dd='.$dd, 'text' => SynopValue::dirText($dd)],
            ['code' => 'ff='.$ff, 'text' => SynopValue::speedText($ff, $unit)],
        ]);
    }

    private function isFixed(string $g): bool
    {
        return $g !== '=' && $g !== '333' && $g !== '555' && $g !== 'NIL';
    }
}
