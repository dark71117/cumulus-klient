<?php

namespace App\Services\Panel;

use App\Support\CustomerContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImgwService
{
    public function table(): array
    {
        $raw = $this->tableDb();
        if (! is_array($raw) || isset($raw['error'])) {
            return is_array($raw) && isset($raw['error'])
                ? $raw
                : ['actualHour' => '', 'pressure' => '', 'rows' => []];
        }
        if (empty($raw['rows'])) {
            return [
                'actualHour' => $raw['actualHour'] ?? '',
                'pressure' => $raw['pressure'] ?? '',
                'rows' => [],
            ];
        }

        $customer = CustomerContext::get();
        $actual = ! empty($raw['actualDateTime']) ? Carbon::parse($raw['actualDateTime']) : null;
        $result = [
            'actualHour' => $raw['actualHour'],
            'pressure' => $raw['pressure'] ?? '',
            'rows' => [],
        ];
        $regionRow = '';
        foreach ($raw['rows'] as $row) {
            $godzina = 0;
            if ($actual && ! empty($row->termin)) {
                $godzina = (int) $actual->diffInHours(Carbon::parse($row->termin));
            }
            $item = [
                'regionRow' => 0,
                'region' => $row->region,
                'imgwRow' => empty($row->zjawiskoKolor) ? 'imgwRow' : 'imgw'.$row->zjawiskoKolor,
                'imgwCity' => empty($row->zjawiskoKolor) ? ' class="imgwCity"' : '',
                'nazwaStacji' => $row->nazwaStacji,
                'godzina' => $godzina,
                'temp' => ($row->temp !== null && $row->temp != -99) ? number_format((float) $row->temp, 1, '.', '') : '-',
                'tempOdcz' => ($row->tempOdcz !== null && $row->tempOdcz != -99) ? number_format((float) $row->tempOdcz, 1, '.', '-') : '',
                'zachmurzenieTXT' => $row->zachmurzenieTXT,
                'zjawiskoTXT' => $row->zjawiskoTXT,
                'zjawiskoPoprzednie' => $row->zjawiskoPoprzednie,
                'widzialnosc' => $row->widzialnosc != -99 ? $row->widzialnosc : '-',
                'wiatr' => $row->wiatr,
            ];
            if ($regionRow != $row->region && (int) ($customer['wojDepesze'] ?? 0) === 1) {
                $item['regionRow'] = 1;
                $regionRow = $row->region;
            }
            $result['rows'][] = $item;
        }

        return $result;
    }

    public function map(): array
    {
        $raw = $this->mapDb();
        if (! is_array($raw) || isset($raw['error']) || empty($raw['rows'])) {
            return is_array($raw) && isset($raw['error'])
                ? $raw
                : ['actualHour' => '', 'partOfDay' => 'dzien', 'region' => 'Polska', 'rows' => []];
        }

        $pozX = 115;
        $pozY = 10;
        $result = [
            'actualHour' => $raw['actualHour'],
            'partOfDay' => $raw['partOfDay'],
            'region' => $raw['region'],
            'rows' => [],
        ];
        foreach ($raw['rows'] as $row) {
            $xx = $raw['region'] == 'Polska' ? $row->pozX : $row->pozWX;
            $yy = $raw['region'] == 'Polska' ? $row->pozY : $row->pozWY;
            if ($xx <= 0 || $yy <= 0) {
                continue;
            }
            $x = $pozX + $xx;
            $y = $pozY + $yy;
            $temp = ($row->temp !== null && $row->temp != -99) ? number_format((float) $row->temp, 0) : '';
            if ($temp === '-0') {
                $temp = 0;
            }
            $klasa = $temp >= 0 ? 'tempPositive' : 'tempNegative';
            $xKor = $temp >= 0 ? 5 : 14;
            if ($temp >= 10 || $temp <= -10) {
                $xKor += 16;
            }
            $item = [
                'city' => ['x' => $x, 'y' => $y, 'name' => $row->nazwaStacji],
                'temp' => ['x' => $x - $xKor, 'y' => $y - 30, 'value' => $temp, 'class' => $klasa],
                'icon' => [],
                'icon2' => [],
            ];
            $zjaw = explode(';', (string) $row->zjawiskoIkona);
            $ikona = $this->cloudIcon($zjaw[0] ?? 'N', $row->zachmurzenie);
            if ((int) $raw['night'] === 1 && $ikona) {
                $night = 'n'.$ikona;
                if (file_exists(public_path('images/ikony2/'.$night))) {
                    $ikona = $night;
                }
            }
            if ($ikona) {
                $item['icon'] = ['x' => $pozX + $xx + 10, 'y' => $pozY + $yy - 30, 'value' => $ikona];
            }
            if (count($zjaw) > 1) {
                $item['icon2'] = ['x' => $pozX + $xx + 40, 'y' => $pozY + $yy - 22, 'value' => 'w'.$zjaw[1].'.png'];
            }
            $result['rows'][$row->idStacji] = $item;
        }

        return $result;
    }

    private function tableDb(): array
    {
        $empty = ['actualHour' => '', 'pressure' => '', 'rows' => []];
        $customer = CustomerContext::get();
        if ((int) ($customer['IMGW'] ?? 0) !== 1) {
            return $empty;
        }
        try {
            $maxTermin = DB::table('z_depesze')->max('termin');
        } catch (Throwable $e) {
            report($e);

            return ['error' => 1];
        }
        if (empty($maxTermin)) {
            return $empty;
        }
        $actual = Carbon::parse($maxTermin);
        $result = [
            'actualHour' => $actual->format('G').':00',
            'actualDateTime' => $maxTermin,
            'pressure' => '',
            'rows' => [],
        ];
        try {
            $pressure = DB::table('z_uprawnieniadepesze as ud')
                ->join('z_listastacji as ls', 'ud.idStacji', '=', 'ls.idStacji')
                ->join('z_depesze as d', 'ls.idStacji', '=', 'd.idStacji')
                ->where('ud.cisnienie', 1)
                ->where('d.cisnienieTXT', '!=', '')
                ->where('ud.idKlienta', $customer['id'])
                ->where('ud.aktywna', 1)
                ->where('ls.aktywna', 1)
                ->orderBy('ud.lp')
                ->value('d.cisnienieTXT');
        } catch (Throwable $e) {
            report($e);

            return ['error' => 1];
        }
        if ($pressure) {
            $result['pressure'] = str_replace('!', "'", $pressure);
        }
        try {
            $since = $actual->copy()->subHours(2)->format('Y-m-d H:i:s');
            $result['rows'] = DB::table('z_uprawnieniadepesze as ud')
                ->join('z_listastacji as ls', 'ud.idStacji', '=', 'ls.idStacji')
                ->join('z_depesze as d', 'ls.idStacji', '=', 'd.idStacji')
                ->where('ud.idKlienta', $customer['id'])
                ->where('ud.aktywna', 1)
                ->where('ls.aktywna', 1)
                ->where('d.termin', '>=', $since)
                ->orderBy('ud.lp')
                ->select([
                    'ls.nazwaStacji', 'ls.region', 'd.temp', 'd.tempOdcz', 'd.zachmurzenieTXT',
                    'd.zjawiskoTXT', 'd.widzialnosc', 'd.wiatr', 'd.zjawiskoKolor', 'd.zjawisko',
                    'd.zjawiskoPoprzednie', 'd.termin',
                ])
                ->get();
        } catch (Throwable $e) {
            report($e);

            return ['error' => 1];
        }

        return $result;
    }

    private function mapDb(): array
    {
        $customer = CustomerContext::get();
        if ((int) ($customer['mapaWarunkow'] ?? 0) !== 1) {
            return [];
        }
        try {
            $maxTermin = DB::table('z_depesze')->max('termin');
            $counts = DB::table('z_uprawnieniadepesze as ud')
                ->join('z_listastacji as ls', 'ud.idstacji', '=', 'ls.idStacji')
                ->where('ud.aktywna', 1)
                ->where('ud.idKlienta', $customer['id'])
                ->selectRaw('COUNT(*) AS max, ls.region')
                ->groupBy('ls.region')
                ->orderByDesc('max')
                ->get();
            $rows = DB::table('z_uprawnieniadepesze as ud')
                ->join('z_depesze as d', 'ud.idStacji', '=', 'd.idStacji')
                ->join('z_listastacji as ls', 'd.idstacji', '=', 'ls.idStacji')
                ->where('ud.aktywna', 1)
                ->where('ud.idKlienta', $customer['id'])
                ->where('ls.aktywna', 1)
                ->orderBy('ud.lp')
                ->select(['ls.nazwaStacji', 'd.temp', 'ls.pozY', 'ls.pozX', 'ls.pozWY', 'ls.pozWX', 'd.zjawiskoIkona', 'd.zjawisko', 'd.zachmurzenie', 'd.zjawiskoTXT', 'd.idStacji'])
                ->get();
        } catch (Throwable $e) {
            report($e);

            return ['error' => 1];
        }
        if (empty($maxTermin) || $counts->isEmpty()) {
            return [];
        }
        $sun = date_sun_info(time(), (float) ($customer['geo_lat'] ?? 52), (float) ($customer['geo_lon'] ?? 19));
        $night = (time() <= $sun['sunrise'] || time() >= $sun['sunset']) ? 1 : 0;

        return [
            'actualHour' => Carbon::parse($maxTermin)->format('G').':00',
            'region' => $counts->count() === 1 ? $counts->first()->region : 'Polska',
            'night' => $night,
            'partOfDay' => $night ? 'noc' : 'dzien',
            'rows' => $rows,
        ];
    }

    private function cloudIcon(string $zjaw, $clouds): string
    {
        if ($zjaw !== 'N' && $zjaw !== '' && $zjaw != -99) {
            return 'w'.$zjaw.'.png';
        }
        $map = [0 => 'w01.png', 1 => 'w01.png', 2 => 'w01.png', 3 => 'w02.png', 4 => 'w02.png', 5 => 'w03.png', 6 => 'w03.png', 7 => 'w04.png', 8 => 'w05.png'];

        return $map[(int) $clouds] ?? '';
    }
}
