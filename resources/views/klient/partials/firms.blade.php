@php
    $blank = [
        'id' => 0,
        'nazwa' => '',
        'login' => '',
        'mod_tabela' => 1,
        'mod_mapa' => 1,
        'mod_gfs' => 1,
        'mod_analiza' => 1,
        'mod_dodatki' => 1,
        'aktywny' => 1,
    ];
    $openForm = $errors->any() && old('nazwa') !== null;
    $formState = null;
    if ($openForm) {
        $formState = [
            'id' => (int) old('id', 0),
            'nazwa' => old('nazwa', ''),
            'login' => old('login', ''),
            'mod_tabela' => (string) old('mod_tabela') === '1' ? 1 : 0,
            'mod_mapa' => (string) old('mod_mapa') === '1' ? 1 : 0,
            'mod_gfs' => (string) old('mod_gfs') === '1' ? 1 : 0,
            'mod_analiza' => (string) old('mod_analiza') === '1' ? 1 : 0,
            'mod_dodatki' => (string) old('mod_dodatki') === '1' ? 1 : 0,
            'aktywny' => (string) old('aktywny') === '0' ? 0 : 1,
        ];
    }
@endphp
<div class="users-app firms-app" @if($currentName !== '') data-company-id="{{ $currentId }}" data-company-name="{{ $currentName }}" @endif>
    <div class="users-card">
        <div class="users-card-head">
            <h2>{{ $full ? 'FIRMY' : 'FIRMA' }}</h2>
            <span>{{ $full ? 'najpierw firma, potem konta' : 'nazwa na żółtym pasku' }}</span>
        </div>
        <div class="users-card-body">
            @if(session('success'))<div class="users-alert is-ok">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="users-alert is-bad">{{ $errors->first() }}</div>@endif
            <div class="users-toolbar">
                <div>
                    <div class="users-kicker">Lista firm</div>
                    <input id="firms-search" type="search" placeholder="Szukaj: nazwa, login" autocomplete="off">
                </div>
                @if($full)
                    <button type="button" class="users-btn users-btn-primary" data-firm-edit="{{ json_encode($blank, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}">+ Dodaj firmę</button>
                @endif
            </div>
            <p class="users-note">Najpierw firma: nazwa, moduły i czy jest aktywna. Potem przycisk Konta — tam dodajesz użytkowników. Każdy dostaje własny login, hasło i najwyżej te moduły, które ma jego firma.</p>
            <div class="users-table-wrap">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Firma</th>
                            <th>Login wspólny</th>
                            <th>Moduły</th>
                            <th>Konta</th>
                            <th>Aktywna</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($firms as $firm)
                            @php
                                $row = [
                                    'id' => (int) $firm->id,
                                    'nazwa' => (string) $firm->nazwa,
                                    'login' => (string) $firm->login,
                                    'mod_tabela' => (int) ($firm->mod_tabela ?? 1),
                                    'mod_mapa' => (int) ($firm->mod_mapa ?? 1),
                                    'mod_gfs' => (int) ($firm->mod_gfs ?? 1),
                                    'mod_analiza' => (int) ($firm->mod_analiza ?? 1),
                                    'mod_dodatki' => (int) ($firm->mod_dodatki ?? 1),
                                    'aktywny' => (int) $firm->aktywny === 1 ? 1 : 0,
                                ];
                                $labels = collect($modules)->filter(fn ($label, $key) => (int) ($firm->{'mod_'.$key} ?? 1) === 1)->implode(', ');
                            @endphp
                            <tr>
                                <td><strong>{{ $firm->nazwa }}</strong></td>
                                <td>{{ $firm->login }}</td>
                                <td class="users-mods">{{ $labels !== '' ? $labels : 'brak' }}</td>
                                <td>{{ $firm->panel_users_count }}</td>
                                <td><span class="users-badge {{ (int) $firm->aktywny === 1 ? 'is-on' : 'is-off' }}">{{ (int) $firm->aktywny === 1 ? 'tak' : 'nie' }}</span></td>
                                <td class="users-actions">
                                    <button type="button" class="users-btn users-btn-muted" data-firm-users="{{ $firm->id }}">Konta</button>
                                    <button type="button" class="users-icon users-icon-edit" title="Edytuj {{ $firm->nazwa }}" data-firm-edit="{{ json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">Nie ma jeszcze żadnej firmy.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="users-modal{{ $openForm ? ' is-open' : '' }}" id="firms-modal" @if($openForm) data-open="1" data-form="{{ json_encode($formState, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}" @endif>
        <div class="users-modal-card" role="dialog" aria-modal="true" aria-labelledby="firms-modal-title">
            <div class="users-modal-head">
                <h2 id="firms-modal-title">NOWA FIRMA</h2>
                <button type="button" class="users-modal-close" data-firm-close aria-label="Zamknij">&times;</button>
            </div>
            <form method="post" action="{{ url('/klient/firmy') }}">
                @csrf
                <input type="hidden" name="id" value="0">
                <div class="users-modal-body">
                    <div class="users-form-grid">
                        <label>Nazwa firmy
                            <input name="nazwa" required autocomplete="off">
                        </label>
                        <label>Login wspólny
                            <input name="login" required autocomplete="off" @unless($full) readonly @endunless>
                        </label>
                        @if($full)
                            <label>Hasło konta wspólnego
                                <input type="password" name="haslo" autocomplete="new-password" placeholder="Puste zostawia dotychczasowe">
                            </label>
                            <label>Aktywna
                                <select name="aktywny">
                                    <option value="1">tak</option>
                                    <option value="0">nie</option>
                                </select>
                            </label>
                        @endif
                    </div>
                    <div class="users-modules">
                        @foreach($modules as $key => $label)
                            <label class="users-check"><input type="checkbox" name="mod_{{ $key }}" value="1" checked @unless($full) disabled @endunless> {{ $label }}</label>
                        @endforeach
                    </div>
                    <p class="users-note">Wyłączony moduł znika u wszystkich kont tej firmy. Włączenie tabeli, mapy albo analizy odsłania też dotychczasową treść klienta.</p>
                </div>
                <div class="users-modal-foot">
                    <button class="users-btn users-btn-primary" type="submit">Zapisz firmę</button>
                    <button class="users-btn users-btn-muted" type="button" data-firm-close>Anuluj</button>
                </div>
            </form>
        </div>
    </div>
</div>
