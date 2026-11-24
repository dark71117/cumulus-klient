<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\PanelUser;
use App\Services\Auth\UserAccountService;
use App\Support\PanelAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserAdminController extends Controller
{
    public function __construct(private UserAccountService $accounts)
    {
        PanelUser::ensureTable();
        Client::ensureAccountColumns();
    }

    public function index(Request $request): View
    {
        abort_unless(PanelAccess::canManageUsers(), 403);

        return $this->page($request);
    }

    public function save(Request $request): View|RedirectResponse
    {
        abort_unless(PanelAccess::canManageUsers(), 403);
        $clientId = PanelAccess::managedClientId();
        abort_unless($clientId > 0, 403);
        if ($request->input('rodzaj') === 'firma') {
            return $this->saveFirm($request, $clientId);
        }
        $existing = $this->findEditable((int) $request->input('id', 0));
        $validator = Validator::make($request->all(), [
            'login' => [
                'required', 'string', 'min:2', 'max:40', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('z_uzytkownicy', 'login')->ignore($existing?->id),
                Rule::unique('z_klienci', 'login'),
            ],
            'nazwa' => ['nullable', 'string', 'max:100'],
            'poziom' => ['required', 'integer', Rule::in(array_keys(PanelAccess::LEVELS))],
            'haslo' => [$existing ? 'nullable' : 'required', 'string', 'min:4', 'max:100'],
        ], [
            'login.unique' => 'Taki login jest już zajęty.',
            'login.regex' => 'Login może zawierać litery, cyfry oraz znaki . _ -',
            'haslo.required' => 'Podaj hasło nowego konta.',
            'haslo.min' => 'Hasło musi mieć co najmniej 4 znaki.',
        ]);
        $validator->after(function ($validator) use ($request): void {
            if (! PanelAccess::canAssign((int) $request->input('poziom'))) {
                $validator->errors()->add('poziom', 'Nie możesz nadać tego poziomu.');
            }
        });
        if ($validator->fails()) {
            $request->flash();

            return $this->failed($request, $validator, $existing);
        }

        $this->accounts->save($clientId, [
            'login' => $request->input('login'),
            'nazwa' => $request->input('nazwa', ''),
            'poziom' => (int) $request->input('poziom'),
            'aktywny' => $request->boolean('aktywny'),
            'haslo' => (string) $request->input('haslo', ''),
            'modules' => $this->modules($request),
        ], $existing);

        return $this->saved($request, 'Zapisano konto.');
    }

    private function saveFirm(Request $request, int $clientId): View|RedirectResponse
    {
        $client = Client::query()->findOrFail($clientId);
        abort_unless(PanelAccess::canEditFirm($client), 403);
        $validator = Validator::make($request->all(), [
            'login' => [
                'required', 'string', 'min:2', 'max:40', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('z_klienci', 'login')->ignore($client->id),
                Rule::unique('z_uzytkownicy', 'login'),
            ],
            'poziom' => ['required', 'integer', Rule::in(array_keys(PanelAccess::LEVELS))],
            'haslo' => ['nullable', 'string', 'min:4', 'max:100'],
        ], [
            'login.unique' => 'Taki login jest już zajęty.',
            'login.regex' => 'Login może zawierać litery, cyfry oraz znaki . _ -',
            'haslo.min' => 'Hasło musi mieć co najmniej 4 znaki.',
        ]);
        $validator->after(function ($validator) use ($request): void {
            if (! PanelAccess::canAssign((int) $request->input('poziom'))) {
                $validator->errors()->add('poziom', 'Nie możesz nadać tego poziomu.');
            }
        });
        if ($validator->fails()) {
            $request->flash();

            return $this->failed($request, $validator, null);
        }

        $firm = [
            'login' => $request->input('login'),
            'poziom' => PanelAccess::LEVEL_USER,
            'haslo' => (string) $request->input('haslo', ''),
            'modules' => $this->modules($request),
        ];
        if ($request->has('aktywny')) {
            $firm['aktywny'] = $request->boolean('aktywny');
        }
        $this->accounts->saveFirm($client, $firm);

        return $this->saved($request, 'Zapisano konto wspólne.');
    }

    public function destroy(Request $request): View|RedirectResponse
    {
        abort_unless(PanelAccess::canManageUsers(), 403);
        $user = PanelUser::query()->find((int) $request->input('id', 0));
        abort_unless($user && PanelAccess::canDelete($user), 403);
        $user->delete();

        return $this->saved($request, 'Usunięto konto.');
    }

    public function profile(Request $request): View
    {
        abort_unless(PanelAccess::isPanelUser(), 403);

        return $this->profileView($request);
    }

    public function updateProfile(Request $request): View|RedirectResponse
    {
        abort_unless(PanelAccess::isPanelUser(), 403);
        $user = PanelUser::query()->find(PanelAccess::id());
        abort_unless($user, 403);
        $validator = Validator::make($request->all(), [
            'login' => [
                'required', 'string', 'min:2', 'max:40', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('z_uzytkownicy', 'login')->ignore($user->id),
                Rule::unique('z_klienci', 'login'),
            ],
            'haslo' => ['nullable', 'string', 'min:4', 'max:100', 'confirmed'],
            'haslo_confirmation' => ['nullable', 'string'],
            'obecne' => ['required', 'string'],
        ], [
            'login.unique' => 'Taki login jest już zajęty.',
            'login.regex' => 'Login może zawierać litery, cyfry oraz znaki . _ -',
            'haslo.min' => 'Hasło musi mieć co najmniej 4 znaki.',
            'haslo.confirmed' => 'Powtórzone hasło nie jest takie samo.',
            'obecne.required' => 'Podaj obecne hasło.',
        ]);
        $validator->after(function ($validator) use ($request, $user): void {
            if (! $user->passwordMatches((string) $request->input('obecne', ''))) {
                $validator->errors()->add('obecne', 'Obecne hasło jest nieprawidłowe.');
            }
        });
        if ($validator->fails()) {
            $request->flash();
            if ($request->ajax()) {
                return $this->profileView($request)->withErrors($validator);
            }

            return redirect()->route('klient.profile')->withErrors($validator)->withInput();
        }

        $this->accounts->changeCredentials(
            $user,
            (string) $request->input('login'),
            (string) $request->input('haslo', '')
        );

        if ($request->ajax()) {
            session()->now('success', 'Zapisano login i hasło.');

            return $this->profileView($request);
        }

        return redirect()->route('klient.profile')->with('success', 'Zapisano login i hasło.');
    }

    private function page(Request $request, ?PanelUser $editing = null): View
    {
        PanelUser::ensureTable();
        $clientId = PanelAccess::managedClientId();
        $client = $clientId > 0 ? Client::query()->find($clientId) : null;
        if ($client && (int) ($client->poziom_konta ?? PanelAccess::LEVEL_USER) !== PanelAccess::LEVEL_USER) {
            $client->poziom_konta = PanelAccess::LEVEL_USER;
            $client->save();
        }
        if ($editing === null && $client && $request->filled('edycja')) {
            $editing = $this->findEditable((int) $request->query('edycja'));
        }
        $users = $client
            ? PanelUser::query()->where('id_klient', $client->id)->orderBy('login')->get()
            : collect();
        $view = $request->ajax() ? 'klient.partials.users' : 'klient.users';

        return view($view, [
            'client' => $client,
            'users' => $users,
            'editing' => $editing,
            'levels' => PanelAccess::assignableLevels(),
            'modules' => PanelAccess::MODULES,
        ]);
    }

    private function profileView(Request $request): View
    {
        $user = PanelUser::query()->find(PanelAccess::id());
        $view = $request->ajax() ? 'klient.partials.profile' : 'klient.profile';

        return view($view, ['account' => $user]);
    }

    private function findEditable(int $id): ?PanelUser
    {
        if ($id <= 0) {
            return null;
        }
        $user = PanelUser::query()
            ->where('id', $id)
            ->where('id_klient', PanelAccess::managedClientId())
            ->first();
        abort_unless($user && PanelAccess::canEdit($user), 403);

        return $user;
    }

    private function modules(Request $request): array
    {
        $modules = [];
        foreach (array_keys(PanelAccess::MODULES) as $module) {
            $modules[$module] = $request->boolean('mod_'.$module);
        }

        return $modules;
    }

    private function saved(Request $request, string $message): View|RedirectResponse
    {
        if ($request->ajax()) {
            session()->now('success', $message);

            return $this->page($request);
        }

        return redirect()->route('klient.users')->with('success', $message);
    }

    private function failed(Request $request, $validator, ?PanelUser $editing): View|RedirectResponse
    {
        if ($request->ajax()) {
            return $this->page($request, $editing)->withErrors($validator);
        }

        return redirect()->route('klient.users')->withErrors($validator)->withInput();
    }
}
