<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\PanelUser;
use App\Services\Auth\UserAccountService;
use App\Support\CustomerContext;
use App\Support\PanelAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FirmAdminController extends Controller
{
    public function __construct(private UserAccountService $accounts)
    {
        Client::ensureAccountColumns();
        PanelUser::ensureTable();
    }

    public function index(Request $request): View
    {
        abort_unless(PanelAccess::canManageCompany(), 403);

        return $this->page($request);
    }

    public function save(Request $request): View|RedirectResponse
    {
        abort_unless(PanelAccess::canManageCompany(), 403);
        $existing = $this->find((int) $request->input('id', 0));
        $validator = Validator::make($request->all(), [
            'nazwa' => ['required', 'string', 'min:2', 'max:100', 'not_in:admin'],
            'login' => [
                'required', 'string', 'min:2', 'max:40', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('z_klienci', 'login')->ignore($existing?->id),
                Rule::unique('z_uzytkownicy', 'login'),
            ],
            'haslo' => [$existing ? 'nullable' : 'required', 'string', 'min:4', 'max:100'],
        ], [
            'nazwa.required' => 'Podaj nazwę firmy.',
            'nazwa.not_in' => 'Ta nazwa jest zarezerwowana.',
            'login.unique' => 'Taki login jest już zajęty.',
            'login.regex' => 'Login może zawierać litery, cyfry oraz znaki . _ -',
            'haslo.required' => 'Podaj hasło konta wspólnego.',
            'haslo.min' => 'Hasło musi mieć co najmniej 4 znaki.',
        ]);
        if ($validator->fails()) {
            $request->flash();

            return $this->page($request)->withErrors($validator);
        }

        $payload = [
            'nazwa' => $request->input('nazwa'),
            'login' => $request->input('login'),
            'haslo' => (string) $request->input('haslo', ''),
            'modules' => $this->modules($request),
        ];
        if ($request->has('aktywny')) {
            $payload['aktywny'] = $request->boolean('aktywny');
        }
        $this->accounts->saveCompany($existing, $payload);

        if ($request->ajax()) {
            session()->now('success', 'Zapisano firmę.');

            return $this->page($request);
        }

        return redirect()->route('klient.firms')->with('success', 'Zapisano firmę.');
    }

    private function page(Request $request): View
    {
        $firms = Client::query()
            ->where('nazwa', '<>', 'admin')
            ->withCount('panelUsers')
            ->orderBy('nazwa')
            ->get();
        $current = CustomerContext::get();
        $currentName = ($current['nazwa'] ?? '') !== 'admin' ? (string) ($current['nazwa'] ?? '') : '';
        $view = $request->ajax() ? 'klient.partials.firms' : 'klient.firms';

        return view($view, [
            'firms' => $firms,
            'modules' => PanelAccess::MODULES,
            'currentId' => (int) ($current['id'] ?? 0),
            'currentName' => $currentName,
            'full' => true,
        ]);
    }

    private function find(int $id): ?Client
    {
        if ($id <= 0) {
            return null;
        }
        $client = Client::query()->where('id', $id)->where('nazwa', '<>', 'admin')->first();
        abort_unless($client, 404);

        return $client;
    }

    private function modules(Request $request): array
    {
        $modules = [];
        foreach (array_keys(PanelAccess::MODULES) as $module) {
            $modules[$module] = $request->boolean('mod_'.$module);
        }

        return $modules;
    }

}
