@extends('layouts.login')

@section('content')
<div class="client-login">
    <form method="post" action="{{ url('/klient') }}" id="login-form">
        @csrf
        <div class="row">
            <div class="company">SERWIS POGODOWY BIURA PROGNOZ</div>
        </div>
        <div class="row">
            <img src="{{ asset('images/logo_cumulus.png') }}" alt="Cumulus">
        </div>
        <div class="row">
            <div class="col-lg-6">
                <input type="text" name="login" value="{{ old('login') }}" autofocus placeholder="Login">
                @error('login')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="col-lg-6">
                <input type="password" name="password" placeholder="Hasło">
                @error('password')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <label><input type="checkbox" name="remember" value="1"> Zapamiętaj hasło</label>
            </div>
            <div class="col-lg-6">
                <button type="submit" class="btn-3d">Zaloguj</button>
            </div>
        </div>
    </form>
    <div class="row">
        <div class="ipAddress">Twój adres IP: {{ $ipAddress }}</div>
    </div>
</div>
@endsection
