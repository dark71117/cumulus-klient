@php
    $blank = [
        'rodzaj' => 'user',
        'id' => 0,
        'login' => '',
        'nazwa' => '',
        'poziom' => 1,
        'aktywny' => 1,
        'mod_tabela' => 1,
        'mod_mapa' => 1,
        'mod_gfs' => 1,
        'mod_analiza' => 1,
        'mod_dodatki' => 1,
    ];
    $firm = null;
    if ($client) {
        $firm = [
            'rodzaj' => 'firma',
            'id' => (int) $client->id,
            'login' => (string) $client->login,
            'nazwa' => (string) $client->nazwa,
            'poziom' => (int) ($client->poziom_konta ?? 1),
            'aktywny' => 1,
            'mod_tabela' => (int) ($client->mod_tabela ?? 1),
            'mod_mapa' => (int) ($client->mod_mapa ?? 1),
            'mod_gfs' => (int) ($client->mod_gfs ?? 1),
            'mod_analiza' => (int) ($client->mod_analiza ?? 1),
            'mod_dodatki' => (int) ($client->mod_dodatki ?? 1),
        ];
    }
    $openForm = $errors->any() && old('login') !== null;
    $formState = null;
    if ($openForm) {
        $formState = [
            'rodzaj' => old('rodzaj', 'user'),
            'id' => (int) old('id', 0),
            'login' => old('login', ''),
            'nazwa' => old('nazwa', ''),
            'poziom' => (int) old('poziom', 1),
            'aktywny' => (string) old('aktywny') === '1' ? 1 : 0,
            'mod_tabela' => (string) old('mod_tabela') === '1' ? 1 : 0,
            'mod_mapa' => (string) old('mod_mapa') === '1' ? 1 : 0,
            'mod_gfs' => (string) old('mod_gfs') === '1' ? 1 : 0,
            'mod_analiza' => (string) old('mod_analiza') === '1' ? 1 : 0,
            'mod_dodatki' => (string) old('mod_dodatki') === '1' ? 1 : 0,
        ];
    } elseif ($editing) {
        $openForm = true;
        $formState = [
            'rodzaj' => 'user',
            'id' => (int) $editing->id,
            'login' => (string) $editing->login,
            'nazwa' => (string) $editing->nazwa,
            'poziom' => (int) $editing->poziom,
            'aktywny' => (int) $editing->aktywny === 1 ? 1 : 0,
            'mod_tabela' => (int) $editing->mod_tabela,
            'mod_mapa' => (int) $editing->mod_mapa,
            'mod_gfs' => (int) $editing->mod_gfs,
            'mod_analiza' => (int) $editing->mod_analiza,
            'mod_dodatki' => (int) $editing->mod_dodatki,
        ];
    }
    $levelClass = [1 => 'is-user', 2 => 'is-admin', 3 => 'is-super'];
@endphp
<div class="users-app">
    @if(! $client)
        <div class="users-card">
            <div class="users-card-head"><h2>UŻYTKOWNICY</h2></div>
            <div class="users-card-body">
                <p>Wybierz klienta w trybie administracyjnym i załaduj stronę, żeby zarządzać jego kontami.</p>
            </div>
        </div>
    @else
        <div class="users-card">
            <div class="users-card-head">
                <h2>UŻYTKOWNICY</h2>
                <span>{{ $client->nazwa }}</span>
            </div>
            <div class="users-card-body">
                @if(session('success'))<div class="users-alert is-ok">{{ session('success') }}</div>@endif
                @if($errors->any())<div class="users-alert is-bad">{{ $errors->first() }}</div>@endif
                <div class="users-toolbar">
                    <div>
                        <div class="users-kicker">Lista kont</div>
                        <input id="users-search" type="search" placeholder="Szukaj: login, nazwa, poziom" autocomplete="off">
                    </div>
                    <button type="button" class="users-btn users-btn-primary" data-user-edit="{{ json_encode($blank, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}">+ Dodaj użytkownika</button>
                </div>
                <p class="users-note">Konto wspólne to dotychczasowy login firmy. Domyślnie jest zwykłym użytkownikiem, więc samo hasło firmowe nie daje już zarządzania kontami. Edycja — także własnego konta — otwiera się w oknie.</p>
                <div class="users-table-wrap">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Login</th>
                                <th>Nazwa</th>
                                <th>Poziom</th>
                                <th>Moduły</th>
                                <th>Aktywne</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>{{ $firm['login'] }}</strong> <span class="users-tag">wspólne</span></td>
                                <td>{{ $firm['nazwa'] }}</td>
                                <td><span class="users-badge {{ $levelClass[$firm['poziom']] ?? 'is-user' }}">{{ $levels[$firm['poziom']] ?? \App\Support\PanelAccess::LEVELS[$firm['poziom']] ?? $firm['poziom'] }}</span></td>
                                <td class="users-mods">{{ collect($modules)->filter(fn ($label, $key) => (int) $firm['mod_'.$key] === 1)->implode(', ') ?: 'brak' }}</td>
                                <td><span class="users-badge is-on">tak</span></td>
                                <td class="users-actions">
                                    @if(\App\Support\PanelAccess::canEditFirm($client))
                                        <button type="button" class="users-icon users-icon-edit" title="Edytuj konto wspólne" data-user-edit="{{ json_encode($firm, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @foreach($users as $user)
                                @php
                                    $row = [
                                        'rodzaj' => 'user',
                                        'id' => (int) $user->id,
                                        'login' => (string) $user->login,
                                        'nazwa' => (string) $user->nazwa,
                                        'poziom' => (int) $user->poziom,
                                        'aktywny' => (int) $user->aktywny === 1 ? 1 : 0,
                                        'mod_tabela' => (int) $user->mod_tabela,
                                        'mod_mapa' => (int) $user->mod_mapa,
                                        'mod_gfs' => (int) $user->mod_gfs,
                                        'mod_analiza' => (int) $user->mod_analiza,
                                        'mod_dodatki' => (int) $user->mod_dodatki,
                                    ];
                                    $mine = \App\Support\PanelAccess::id() === (int) $user->id;
                                @endphp
                                <tr>
                                    <td><strong>{{ $user->login }}</strong>@if($mine) <span class="users-tag">to Ty</span>@endif</td>
                                    <td>{{ $user->nazwa !== '' ? $user->nazwa : '—' }}</td>
                                    <td><span class="users-badge {{ $levelClass[(int) $user->poziom] ?? 'is-user' }}">{{ $user->levelLabel() }}</span></td>
                                    <td class="users-mods">{{ $user->moduleLabels() }}</td>
                                    <td><span class="users-badge {{ (int) $user->aktywny === 1 ? 'is-on' : 'is-off' }}">{{ (int) $user->aktywny === 1 ? 'tak' : 'nie' }}</span></td>
                                    <td class="users-actions">
                                        @if(\App\Support\PanelAccess::canEdit($user))
                                            <button type="button" class="users-icon users-icon-edit" title="Edytuj {{ $user->login }}" data-user-edit="{{ json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                            </button>
                                        @endif
                                        @if(\App\Support\PanelAccess::canDelete($user))
                                            <form method="post" action="{{ url('/klient/uzytkownicy/usun') }}" data-confirm="Usunąć konto {{ $user->login }}?">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $user->id }}">
                                                <button class="users-icon users-icon-delete" type="submit" title="Usuń {{ $user->login }}">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 7h12l-1 14H7L6 7zm3-3h6l1 2H8l1-2zM4 6h16v2H4z"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="users-modal{{ $openForm ? ' is-open' : '' }}" id="users-modal" @if($openForm) data-open="1" data-form="{{ json_encode($formState, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}" @endif>
            <div class="users-modal-card" role="dialog" aria-modal="true" aria-labelledby="users-modal-title">
                <div class="users-modal-head">
                    <h2 id="users-modal-title">DODAJ UŻYTKOWNIKA</h2>
                    <button type="button" class="users-modal-close" data-user-close aria-label="Zamknij">&times;</button>
                </div>
                <form method="post" action="{{ url('/klient/uzytkownicy') }}">
                    @csrf
                    <input type="hidden" name="rodzaj" value="user">
                    <input type="hidden" name="id" value="0">
                    <div class="users-modal-body">
                        <div class="users-form-grid">
                            <label>Login
                                <input name="login" required autocomplete="off">
                            </label>
                            <label class="users-field-nazwa">Nazwa
                                <input name="nazwa" autocomplete="off">
                            </label>
                            <label>Poziom
                                <select name="poziom">
                                    @foreach($levels as $level => $label)
                                        <option value="{{ $level }}">{{ $level }} — {{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Hasło
                                <input type="password" name="haslo" autocomplete="new-password" placeholder="Puste zostawia dotychczasowe">
                            </label>
                            <label class="users-check users-field-active"><input type="checkbox" name="aktywny" value="1" checked> Konto aktywne</label>
                        </div>
                        <div class="users-modules">
                            @foreach($modules as $key => $label)
                                <label class="users-check"><input type="checkbox" name="mod_{{ $key }}" value="1" checked> {{ $label }}</label>
                            @endforeach
                        </div>
                        <p class="users-note">Odznaczony moduł znika z lewego menu. Nie pokaże się też, gdy klient nie ma tej treści.</p>
                    </div>
                    <div class="users-modal-foot">
                        <button class="users-btn users-btn-primary" type="submit">Zapisz użytkownika</button>
                        <button class="users-btn users-btn-muted" type="button" data-user-close>Anuluj</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
