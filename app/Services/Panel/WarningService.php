<?php

namespace App\Services\Panel;

use App\Support\CustomerContext;
use Illuminate\Support\Facades\DB;
use Throwable;

class WarningService
{
    public function data(int $rowsNumber): array
    {
        $customer = CustomerContext::get();
        if ((int) ($customer['ostrzezeniaTXT'] ?? 0) !== 1) {
            return [];
        }

        try {
            return DB::table('z_ostrzezeniaklienci as ok')
                ->join('z_ostrzezenia as o', 'ok.idPrognozy', '=', 'o.id')
                ->where('ok.idKlienta', $customer['id'])
                ->orderByDesc('ok.biezaca')
                ->orderByDesc('ok.idPrognozy')
                ->limit($rowsNumber)
                ->select('o.tresc', 'o.data', 'ok.biezaca', 'o.id')
                ->get()
                ->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    public function tabMarker(): ?object
    {
        $customer = CustomerContext::get();
        if ((int) ($customer['ostrzezeniaTXT'] ?? 0) !== 1) {
            return null;
        }

        try {
            return DB::table('z_ostrzezeniaklienci as ok')
                ->join('z_ostrzezenia as o', 'ok.idPrognozy', '=', 'o.id')
                ->where('ok.idKlienta', $customer['id'])
                ->where('o.biezaca', 1)
                ->select('o.tresc', 'o.data', 'ok.biezaca', 'o.id')
                ->first();
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
