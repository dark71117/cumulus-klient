<div class="ipadmin-pane users-pane">
    <h2>Mój profil</h2>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
    <p class="users-note">Tu zmieniasz tylko swój login i hasło. Moduły i poziom ustawia administrator.</p>
    <form method="post" action="{{ url('/klient/profil') }}">
        @csrf
        <label>Login <input name="login" value="{{ old('login', $account->login ?? '') }}" required autocomplete="username"></label>
        <label>Nowe hasło (puste zostawia dotychczasowe)
            <input type="password" name="haslo" autocomplete="new-password">
        </label>
        <label>Powtórz nowe hasło
            <input type="password" name="haslo_confirmation" autocomplete="new-password">
        </label>
        <label>Obecne hasło
            <input type="password" name="obecne" required autocomplete="current-password">
        </label>
        <button class="button" type="submit">Zapisz</button>
    </form>
</div>
