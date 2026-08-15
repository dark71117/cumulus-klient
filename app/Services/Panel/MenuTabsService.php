<?php

namespace App\Services\Panel;

use App\Support\CustomerContext;
use Illuminate\Support\Facades\DB;
use Throwable;

class MenuTabsService
{
    private array $weatherTypes = [
        ['acl' => 'prognozaDzis', 'table' => 'z_prognozydzis', 'selector' => 'forecast1Tab'],
        ['acl' => 'prognozaJutro', 'table' => 'z_prognozyjutro', 'selector' => 'forecast2Tab'],
        ['acl' => 'prognozaDluga', 'table' => 'z_prognozydluga', 'selector' => 'forecast3Tab'],
        ['acl' => 'prognozaInna', 'table' => 'z_prognozyinna', 'selector' => 'forecast4Tab'],
    ];

    public function weatherTabs(): array
    {
        $customer = CustomerContext::get();
        $result = [
            'forecast1Tab' => ['title' => '', 'active' => 0],
            'forecast2Tab' => ['title' => '', 'active' => 0],
            'forecast3Tab' => ['title' => '', 'active' => 0],
            'forecast4Tab' => ['title' => '', 'active' => 0],
            'forecast5Tab' => ['title' => '', 'active' => 0],
            'animationTab' => ['title' => '', 'active' => 0],
        ];

        foreach ($this->weatherTypes as $weather) {
            if ((int) ($customer[$weather['acl']] ?? 0) !== 1) {
                continue;
            }
            try {
                $query = DB::table($weather['table'].'klienci as pk')
                    ->join($weather['table'].' as p', 'pk.idprognozy', '=', 'p.id')
                    ->where('pk.idklienta', $customer['id'] ?? 0)
                    ->where('pk.biezaca', 1)
                    ->orderByDesc('pk.idprognozy');
                if ((int) ($customer['prognozaTV'] ?? 0) === 1) {
                    $query->where('p.data', '>=', date('Y-m-d'));
                }
                $row = $query->select('p.temat')->first();
                if ($row) {
                    $result[$weather['selector']] = ['title' => $row->temat, 'active' => 1];
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        if ((int) ($customer['mapaPrognozy'] ?? 0) === 1) {
            $result['forecast5Tab']['active'] = 1;
        }
        if (! empty($customer['prognozaTV'])) {
            $files = app(AnimationService::class)->files();
            if (! empty($files['files'])) {
                $result['animationTab']['active'] = 1;
            }
        }

        return $result;
    }

    public function actualTabs(): array
    {
        $customer = CustomerContext::get();
        $types = [
            'imgwTab' => 'IMGW',
            'imgwTableNewTab' => 'IMGW',
            'imgwMapTab' => 'mapaWarunkow',
            'imgwMapNewTab' => 'mapaWarunkow',
            'gddkiaRegionTab' => 'GDDKIAwoj',
            'gddkiaRoadTab' => 'GDDKIAdrogi',
        ];
        $result = [];
        foreach ($types as $selector => $acl) {
            $result[$selector] = ['active' => (int) ($customer[$acl] ?? 0) === 1 ? 1 : 0];
        }

        return $result;
    }

    public function tabStatus(): array
    {
        $forecast = app(ForecastService::class);
        $result = [
            'forecast1Tab' => 0,
            'forecast2Tab' => 0,
            'forecast3Tab' => 0,
            'forecast4Tab' => 0,
        ];
        foreach (array_keys($result) as $tab) {
            $data = $forecast->tabMarker($tab);
            if ($data && ($data->id ?? null) != session($tab, 0)) {
                $result[$tab] = 1;
            }
        }

        $warning = app(WarningService::class)->tabMarker();
        $result['warningTab'] = -1;
        if ($warning) {
            $result['warningTab'] = ($warning->id ?? null) != session('warningTab', 0) ? 1 : 0;
        }

        return $result;
    }
}
