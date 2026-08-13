<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cumulus - Logowanie do systemu</title>
    <link rel="stylesheet" href="{{ asset('css/client.css') }}?v=3">
</head>
<body>
    <div class="wrap">
        <div class="container">
            @yield('content')
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</body>
</html>
