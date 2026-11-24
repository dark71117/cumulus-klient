@php
    $level = (int) ($account->poziom ?? 1);
    $levelClass = [1 => 'is-user', 2 => 'is-admin', 3 => 'is-super'];
    $openProfile = $errors->any() || ! session('success');
@endphp
<div class="users-app">
    <div class="users-card">
        <div class="users-card-head">
            <h2>MÓJ PROFIL</h2>
            <span>{{ $account->login ?? '' }}</span>
        </div>
        <div class="users-card-body">
            @if(session('success'))<div class="users-alert is-ok">{{ session('success') }}</div>@endif
            <p class="users-note">Login i hasło zmieniasz w oknie. Moduły i poziom ustawia administrator.</p>
            <div class="users-profile-meta">
                <div>
                    <span>Login</span>
                    <strong>{{ $account->login ?? '' }}</strong>
                </div>
                <div>
                    <span>Nazwa</span>
                    <strong>{{ ($account->nazwa ?? '') !== '' ? $account->nazwa : '—' }}</strong>
                </div>
                <div>
                    <span>Poziom</span>
                    <strong class="users-badge {{ $levelClass[$level] ?? 'is-user' }}">{{ $account?->levelLabel() }}</strong>
                </div>
                <div>
                    <span>Moduły</span>
                    <strong>{{ $account?->moduleLabels() }}</strong>
                </div>
            </div>
            <button type="button" class="users-btn users-btn-primary" data-profile-open>Zmień login i hasło</button>
        </div>
    </div>
    <div class="users-modal{{ $openProfile ? ' is-open' : '' }}" id="users-modal">
        <div class="users-modal-card is-profile" role="dialog" aria-modal="true" aria-labelledby="profile-modal-title">
            <div class="users-modal-head">
                <h2 id="profile-modal-title">LOGIN I HASŁO</h2>
                <button type="button" class="users-modal-close" data-user-close aria-label="Zamknij">&times;</button>
            </div>
            <form method="post" action="{{ url('/klient/profil') }}">
                @csrf
                <div class="users-modal-body">
                    @if($errors->any())<div class="users-alert is-bad">{{ $errors->first() }}</div>@endif
                    <div class="users-form-grid">
                        <label>Login
                            <input name="login" value="{{ old('login', $account->login ?? '') }}" required autocomplete="username">
                        </label>
                        <label>Obecne hasło
                            <input type="password" name="obecne" required autocomplete="current-password">
                        </label>
                        <label>Nowe hasło
                            <input type="password" name="haslo" autocomplete="new-password" placeholder="Puste zostawia dotychczasowe">
                        </label>
                        <label>Powtórz nowe hasło
                            <input type="password" name="haslo_confirmation" autocomplete="new-password">
                        </label>
                    </div>
                    <p class="users-note">Moduły i poziom zostają bez zmian.</p>
                </div>
                <div class="users-modal-foot">
                    <button class="users-btn users-btn-primary" type="submit">Zapisz</button>
                    <button class="users-btn users-btn-muted" type="button" data-user-close>Anuluj</button>
                </div>
            </form>
        </div>
    </div>
</div>
