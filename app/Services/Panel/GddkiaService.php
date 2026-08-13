<?php

namespace App\Services\Panel;

use App\Support\CustomerContext;
use Illuminate\Support\Facades\DB;
use Throwable;

class GddkiaService
{
    public function blockade(): string
    {
        try {
            return (string) (DB::table('g_blokada')->value('tekst') ?? '');
        } catch (Throwable $e) {
            report($e);

            return '';
        }
    }

    public function counties(): array
    {
        $customer = CustomerContext::get();
        if ((int) ($customer['GDDKIAwoj'] ?? 0) !== 1) {
            return [];
        }
        try {
            $max = DB::table('g_depesze')->max('czas');
            $rows = DB::table('g_uprawnienia as u')
                ->join('g_depesze as d', 'u.id_stacja', '=', 'd.id_stacja')
                ->join('g_stacje as s', 'd.id_stacja', '=', 's.id')
                ->join('g_regiony as r', 's.id_region', '=', 'r.id')
                ->join('g_wojewodztwa as w', 'r.id_woj', '=', 'w.id')
                ->where('u.id_klient', $customer['id'])
                ->orderBy('w.opis')
                ->orderBy('s.opis')
                ->select([
                    'w.opis as wojewodztwo',
                    'r.opis as region',
                    's.opis as stacja',
                    's.nrDrogi',
                    's.id as stacjaId',
                    'd.czas',
                    DB::raw('ROUND(d.t2, 1) as t2'),
                    DB::raw('ROUND(d.t0, 1) as t0'),
                    'd.opad',
                    DB::raw('ROUND(d.wiatrP * 3.6, 0) as wiatr'),
                    DB::raw('ROUND(d.porywy * 3.6, 0) as porywy'),
                    'd.nawierzchnia',
                    'd.sliskosc',
                ])
                ->get();
        } catch (Throwable $e) {
            report($e);

            return ['error' => 1];
        }

        return ['maxTime' => $max, 'rows' => $rows];
    }

    public function roads(): array
    {
        $customer = CustomerContext::get();
        if ((int) ($customer['GDDKIAdrogi'] ?? 0) !== 1) {
            return [];
        }
        try {
            $max = DB::table('g_depesze')->max('czas');
            $rows = DB::table('g_uprawnieniad as u')
                ->join('g_depesze as d', 'u.id_stacja', '=', 'd.id_stacjaD')
                ->join('g_stacjed as s', 'd.id_stacjaD', '=', 's.stacja_id')
                ->where('u.id_klient', $customer['id'])
                ->whereColumn('u.droga', 's.droga')
                ->orderBy('s.droga')
                ->orderBy('s.stacja')
                ->select([
                    's.droga as nrDrogi',
                    's.woj as wojewodztwo',
                    's.region',
                    's.stacja as stacja',
                    's.stacja_id as stacjaId',
                    'd.czas',
                    DB::raw('ROUND(d.t2, 1) as t2'),
                    DB::raw('ROUND(d.t0, 1) as t0'),
                    'd.opad',
                    DB::raw('ROUND(d.wiatrP * 3.6, 0) as wiatr'),
                    'd.nawierzchnia',
                    'd.sliskosc',
                ])
                ->get();
        } catch (Throwable $e) {
            report($e);

            return ['error' => 1];
        }

        return ['maxTime' => $max, 'rows' => $rows, 'roads' => $rows->pluck('nrDrogi')->unique()->values()];
    }

    public function cameras(int $variant, int $number): array
    {
        $customer = CustomerContext::get();
        try {
            $max = DB::table('g_depesze')->max('czas');
            $since = $max ? date('Y-m-d H:i:s', strtotime($max) - 7200) : now()->subHours(2);
            $query = DB::table('g_uprawnienia as u')
                ->join('g_kamery as k', 'u.id_stacja', '=', 'k.id_stacja')
                ->join('g_stacje as s', 'k.id_stacja', '=', 's.id')
                ->join('g_regiony as r', 's.id_region', '=', 'r.id')
                ->join('g_wojewodztwa as w', 'r.id_woj', '=', 'w.id')
                ->where('u.id_klient', $customer['id'])
                ->where('k.czas', '>', $since)
                ->select('k.link', 'k.kamera', 's.opis as stacja', 'w.opis as wojewodztwo', 'r.opis as region', 's.nrDrogi');
            if ($variant === 1) {
                $query->where('w.id', $number);
            } elseif ($variant === 2) {
                $query->where('r.id', $number);
            } elseif ($variant === 3) {
                $query->where('s.id', $number);
            }

            return $query->get()->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }
}
