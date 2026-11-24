@extends('layouts.login')

@section('content')
<div class="client-login">
    <form method="post" action="{{ url('/klient') }}" id="login-form">
        @csrf
        <div class="login-brand">
            <img src="{{ asset('images/logo_cumulus.png') }}" alt="Cumulus">
            <p class="company">Serwis pogodowy Biura Prognoz</p>
        </div>
        <div class="login-field">
            <label for="login">Login</label>
            <input id="login" type="text" name="login" value="{{ old('login') }}" autofocus autocomplete="username" required>
            @error('login')<div class="error" role="alert">{{ $message }}</div>@enderror
        </div>
        <div class="login-field">
            <label for="password">Hasło</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
            @error('password')<div class="error" role="alert">{{ $message }}</div>@enderror
        </div>
        <label class="remember">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Zapamiętaj hasło</span>
        </label>
        <button type="submit">Zaloguj</button>
    </form>
    <p class="ipAddress">Twój adres IP: {{ $ipAddress }}</p>
</div>
@endsection
