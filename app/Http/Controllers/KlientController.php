<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\Auth\LoginService;
use App\Services\Panel\AnimationService;
use App\Services\Panel\CalendarService;
use App\Services\Panel\ForecastService;
use App\Services\Panel\GddkiaService;
use App\Services\Panel\ImgwService;
use App\Services\Panel\MenuTabsService;
use App\Services\Panel\MeteomaxService;
use App\Services\Panel\WarningService;
use App\Support\CustomerContext;
use App\Support\ImgwMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KlientController extends Controller
{
    public function index()
    {
        $clientsList = [];
        if (CustomerContext::isAdmin()) {
            $clientsList = Client::query()
                ->where('aktywny', 1)
                ->where('nazwa', '<>', 'admin')
                ->orderBy('nazwa')
                ->pluck('nazwa', 'id')
                ->all();
            app(LoginService::class)->impersonateSoleClient();
        }

        return view('klient.index', [
            'customer' => CustomerContext::get(),
            'clientsList' => $clientsList,
            'adminBar' => CustomerContext::isAdmin(),
        ]);
    }

    public function setCustomer(Request $request, LoginService $login): JsonResponse
    {
        abort_unless(CustomerContext::isAdmin(), 403);
        $login->impersonate((int) $request->input('id', 0));

        return response()->json(['ok' => true]);
    }

    public function weatherTabs(MenuTabsService $tabs): JsonResponse
    {
        return response()->json($tabs->weatherTabs());
    }

    public function actualTabs(MenuTabsService $tabs): JsonResponse
    {
        return response()->json($tabs->actualTabs());
    }

    public function tabStatus(MenuTabsService $tabs): JsonResponse
    {
        return response()->json($tabs->tabStatus());
    }

    public function content(Request $request): Response
    {
        $tab = (string) $request->input('tab', '');
        $position = (int) $request->input('position', 0);
        $customer = auth()->user();
        if ($customer && $customer->nazwa !== 'admin' && config('cumulus.save_usage')) {
            logger()->info('Klient ID: '.$customer->id.'; Zakładka: '.$tab);
        }

        $html = $this->renderTab($tab, $position);

        return response($html);
    }

    public function saveMapLimit(Request $request): JsonResponse
    {
        $id = CustomerContext::id();
        abort_unless($id > 0, 403);
        $limit = ImgwMap::playbackLimit(['mapaOkresy' => (int) $request->input('limit', ImgwMap::PLAYBACK_DEFAULT)]);
        Client::ensureMapaOkresyColumn();
        $client = Client::query()->findOrFail($id);
        $client->mapaOkresy = $limit;
        $client->save();
        CustomerContext::put($client);

        return response()->json(['ok' => true, 'limit' => $limit]);
    }

    public function downloadTv(string $filename, AnimationService $animation): BinaryFileResponse
    {
        abort_unless((int) (CustomerContext::get()['prognozaTV'] ?? 0) === 1, 403);
        $path = $animation->path($filename);
        abort_unless($path, 404);

        return response()->download($path, basename($filename));
    }

    public function cameras(int $variant, int $number, GddkiaService $gddkia, int $region = 0)
    {
        $blockade = $gddkia->blockade();
        if ($blockade !== '') {
            return view('klient.partials.gddkia-blockade', ['text' => $blockade]);
        }

        return view('klient.partials.cameras', ['cameras' => $gddkia->cameras($variant, $number)]);
    }

    public function mmMaps(MeteomaxService $mm): Response
    {
        return response($mm->maps());
    }

    public function mmRegion(Request $request, MeteomaxService $mm, ?int $id = null, ?int $b = null, ?int $c = null): Response
    {
        $id = $id ?? (int) $request->query('id', 0);
        $b = $b ?? (int) $request->query('b', 0);
        $c = $c ?? (int) $request->query('c', 0);

        return response($mm->region($id, $b, $c));
    }

    public function mmChart(MeteomaxService $mm, int $id, int $product = 0, int $print = 0): Response
    {
        return response($mm->chart($id, $product, $print));
    }

    public function mmPng(MeteomaxService $mm, int $id): Response
    {
        return response($mm->png($id))->header('Content-Type', 'image/png');
    }

    private function renderTab(string $tab, int $position): string
    {
        return match (true) {
            in_array($tab, ['forecast1Tab', 'forecast2Tab', 'forecast3Tab', 'forecast4Tab', 'archive1Tab', 'archive2Tab', 'archive3Tab', 'archive4Tab'], true)
                => $this->forecastHtml($tab, $position),
            in_array($tab, ['warningTab', 'archive5Tab'], true) => $this->warningHtml($tab, $position),
            $tab === 'animationTab' => view('klient.partials.animation', ['data' => app(AnimationService::class)->files()])->render(),
            $tab === 'sunTab' => view('klient.partials.calendar', app(CalendarService::class)->data())->render(),
            $tab === 'imgwTab' => $this->imgwHtml(),
            $tab === 'imgwTableNewTab' => $this->imgwTableNewHtml(),
            $tab === 'imgwTableNew2Tab' => $this->imgwTableNewHtml(true),
            $tab === 'imgwMapTab' => $this->imgwMapHtml(),
            $tab === 'imgwMapNewTab' => $this->imgwMapNewHtml(),
            $tab === 'imgwMapNew2Tab' => $this->imgwMapNewHtml(true),
            $tab === 'gddkiaRegionTab' => $this->gddkiaCountiesHtml(),
            $tab === 'gddkiaRoadTab' => $this->gddkiaRoadsHtml(),
            $tab === 'forecast5Tab' => $this->meteomaxHtml(),
            default => '',
        };
    }

    private function forecastHtml(string $tab, int $position): string
    {
        $rowsNumber = str_contains($tab, 'archive') ? 10 : 1;
        $service = app(ForecastService::class);
        $data = $service->data($tab, $rowsNumber);
        if ($rowsNumber === 1 && ! empty($data['forecasts'][0])) {
            session([$tab => $data['forecasts'][0]->id]);
        }

        return view('klient.partials.forecast', [
            'data' => $data,
            'rowsNumber' => $rowsNumber,
            'position' => $position,
            'tab' => $tab,
            'html' => $service->html($data, $position),
        ])->render();
    }

    private function warningHtml(string $tab, int $position): string
    {
        $rowsNumber = str_contains($tab, 'archive') ? 10 : 1;
        $data = app(WarningService::class)->data($rowsNumber);
        if ($rowsNumber === 1 && ! empty($data[0])) {
            session(['warningTab' => $data[0]->id]);
        }

        return view('klient.partials.warning', [
            'data' => $data,
            'rowsNumber' => $rowsNumber,
            'position' => $position,
            'tab' => $tab,
        ])->render();
    }

    private function imgwHtml(): string
    {
        $data = app(ImgwService::class)->table();
        if (isset($data['error'])) {
            return view('klient.partials.critical')->render();
        }

        return view('klient.partials.imgw', ['data' => $data])->render();
    }

    private function imgwTableNewHtml(bool $ogimet = false): string
    {
        $service = $ogimet ? ImgwService::fromOgimet() : app(ImgwService::class);
        $data = $service->table(true);
        if (isset($data['error'])) {
            return view('klient.partials.critical')->render();
        }

        return view('klient.partials.imgw-table-new', ['data' => $data])->render();
    }

    private function imgwMapHtml(): string
    {
        $data = app(ImgwService::class)->map();
        if (isset($data['error'])) {
            return view('klient.partials.critical')->render();
        }

        return view('klient.partials.imgw-map', ['data' => $data])->render();
    }

    private function imgwMapNewHtml(bool $ogimet = false): string
    {
        $service = $ogimet ? ImgwService::fromOgimet() : app(ImgwService::class);
        $data = $service->mapLeaflet();
        if (isset($data['error'])) {
            return view('klient.partials.critical')->render();
        }

        return view('klient.partials.imgw-map-new', ['data' => $data])->render();
    }

    private function gddkiaCountiesHtml(): string
    {
        $gddkia = app(GddkiaService::class);
        $blockade = $gddkia->blockade();
        if ($blockade !== '') {
            return view('klient.partials.gddkia-blockade', ['text' => $blockade])->render();
        }
        $data = $gddkia->counties();
        if (isset($data['error'])) {
            return view('klient.partials.critical')->render();
        }

        return view('klient.partials.gddkia-counties', ['data' => $data])->render();
    }

    private function gddkiaRoadsHtml(): string
    {
        $gddkia = app(GddkiaService::class);
        $blockade = $gddkia->blockade();
        if ($blockade !== '') {
            return view('klient.partials.gddkia-blockade', ['text' => $blockade])->render();
        }
        $data = $gddkia->roads();
        if (isset($data['error']) || empty($data['roads'])) {
            return empty($data) ? '' : view('klient.partials.critical')->render();
        }

        return view('klient.partials.gddkia-roads', ['data' => $data])->render();
    }

    private function meteomaxHtml(): string
    {
        if (! config('cumulus.meteomax_active')) {
            return view('klient.partials.meteomax-link')->render();
        }

        return app(MeteomaxService::class)->maps();
    }
}
