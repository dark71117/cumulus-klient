<?php

namespace App\Services\Panel;

use App\Support\CustomerContext;
use App\Support\ForecastHtml;
use Illuminate\Support\Facades\DB;
use Throwable;

class ForecastService
{
    private array $types = [
        'forecast1Tab' => ['acl' => 'prognozaDzis', 'table' => 'z_prognozydzis'],
        'forecast2Tab' => ['acl' => 'prognozaJutro', 'table' => 'z_prognozyjutro'],
        'forecast3Tab' => ['acl' => 'prognozaDluga', 'table' => 'z_prognozydluga'],
        'forecast4Tab' => ['acl' => 'prognozaInna', 'table' => 'z_prognozyinna'],
    ];

    public function data(string $tab, int $rowsNumber): array
    {
        $tabKey = str_replace('archive', 'forecast', $tab);
        if (! isset($this->types[$tabKey])) {
            return ['forecasts' => [], 'cities' => null];
        }
        $customer = CustomerContext::get();
        $meta = $this->types[$tabKey];
        if ((int) ($customer[$meta['acl']] ?? 0) !== 1) {
            return ['forecasts' => [], 'cities' => null];
        }

        try {
            $query = DB::table($meta['table'].'klienci as pk')
                ->join($meta['table'].' as p', 'pk.idPrognozy', '=', 'p.id')
                ->where('pk.idKlienta', $customer['id'])
                ->where('p.robocza', 0)
                ->orderByDesc('pk.biezaca')
                ->orderByDesc('p.data')
                ->limit($rowsNumber)
                ->select('p.tresc', 'p.data', 'p.prognozaPL', 'pk.biezaca', 'p.id');
            if ((int) ($customer['prognozaTV'] ?? 0) === 1 && $rowsNumber === 1) {
                $query->where('p.data', '>=', date('Y-m-d'));
            }
            $rows = $query->get();
            $result = ['forecasts' => $rows, 'cities' => null];
            if ($rows->isNotEmpty() && (int) $rows[0]->prognozaPL === 1) {
                $result['cities'] = DB::table('z_uprawnieniaprognozamiasta as upm')
                    ->join('z_prognozamiasta as pm', 'upm.idMiasto', '=', 'pm.id')
                    ->where('upm.idKlienta', $customer['id'])
                    ->where('upm.aktywna', 1)
                    ->orderBy('upm.kolejnosc')
                    ->select('pm.miasto')
                    ->get();
            }

            return $result;
        } catch (Throwable $e) {
            report($e);

            return ['forecasts' => [], 'cities' => null];
        }
    }

    public function tabMarker(string $tab): ?object
    {
        if (! isset($this->types[$tab])) {
            return null;
        }
        $customer = CustomerContext::get();
        $meta = $this->types[$tab];
        if ((int) ($customer[$meta['acl']] ?? 0) !== 1) {
            return null;
        }
        try {
            $query = DB::table($meta['table'].'klienci as pk')
                ->join($meta['table'].' as p', 'pk.idPrognozy', '=', 'p.id')
                ->where('pk.idKlienta', $customer['id'])
                ->where('p.robocza', 0)
                ->where('p.biezaca', 1)
                ->orderByDesc('pk.biezaca')
                ->orderByDesc('p.data')
                ->select('p.tresc', 'p.data', 'p.prognozaPL', 'pk.biezaca', 'p.id');
            if ((int) ($customer['prognozaTV'] ?? 0) === 1) {
                $query->where('p.data', '>=', date('Y-m-d'));
            }

            return $query->first();
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    public function html(array $data, int $position): string
    {
        if (empty($data['forecasts']) || ! isset($data['forecasts'][$position])) {
            return '';
        }
        $forecast = $data['forecasts'][$position]->tresc ?? '';
        $content = ForecastHtml::cityForecast($forecast, $data, $position);

        return $content !== '' ? $content : ForecastHtml::forecastKeys($forecast);
    }
}
