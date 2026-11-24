<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cumulus - Logowanie do systemu</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v=3">
</head>
<body class="login">
    <div class="login-stage">
        @yield('content')
    </div>
</body>
</html>
