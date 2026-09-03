<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cumulus - serwis pogodowy</title>
    <link rel="stylesheet" href="{{ asset('css/client.css') }}?v=8">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}?v=20">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.3/css/buttons.dataTables.min.css">
</head>
<body class="layout-app">
@php
    $barCustomer = is_array($customer ?? null) ? $customer : \App\Support\CustomerContext::get();
    $showAdminBar = $adminBar ?? \App\Support\CustomerContext::isAdmin();
    $clientsList = $clientsList ?? [];
@endphp
<form id="logout" method="post" action="{{ url('/klient/logout') }}">@csrf</form>
<div class="app-shell">
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <img src="{{ asset('images/logo_cumulus.png') }}" alt="Cumulus">
            <div>
                <strong>Cumulus</strong>
                <span>panel klienta</span>
            </div>
        </div>
        <div class="sidebar-exclusive" title="Treści tej strony są przeznaczone wyłącznie dla wybranego klienta">
            Serwis pogodowy Biura Prognoz Cumulus, wyłączność strony dla
            <span class="blue">{{ $barCustomer['nazwa'] ?? '' }}</span>
        </div>
        <div class="sidebar-nav">
            @yield('nav')
        </div>
        <div class="sidebar-footer">
            @if($showAdminBar)
                <div class="admin sidebar-admin sidebar-admin-island">
                    <div class="admin-label">Tryb administracyjny</div>
                    <label class="admin-select-label" for="customers">Klient</label>
                    <select id="customers" title="Wybierz firmę, której panel chcesz podejrzeć">
                        <option value="0"></option>
                        @foreach($clientsList as $id => $name)
                            <option value="{{ $id }}" @selected(($barCustomer['id'] ?? 0) == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <input type="button" class="button" id="loadPage" value="Załaduj stronę" title="Wczytaj panel wybranego klienta" data-tip="Wczytaj panel wybranego klienta">
                    <input type="button" class="button" id="ipAdmin" value="Administracja IP" title="Ograniczenia logowania według adresów IP" data-tip="Ograniczenia logowania według adresów IP">
                </div>
            @endif
            <button type="submit" form="logout" class="btn-3d btn-logout" id="logoutBtn" title="Zakończ sesję i wróć do logowania" data-tip="Zakończ sesję i wróć do logowania">Wyloguj</button>
        </div>
    </aside>
    <div class="app-main">
        <div class="app-stage">
            <div class="loading" id="loading">Aktualizacja</div>
            <div class="wrap">
                <div class="container">
                    @yield('content')
                </div>
            </div>
        </div>
        <footer class="app-footer">
            <button type="button" class="footer-link cookies" id="cookiesBtn" title="Informacja o plikach cookies" data-tip="Informacja o plikach cookies">Cookies</button>
            <span>© 2002-{{ date('Y') }} Cumulus</span>
            <a href="https://www.cumulus.wroc.pl" title="Strona Biura Prognoz Cumulus"><img src="{{ asset('images/logo_cumulus.png') }}" alt="Cumulus"></a>
        </footer>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    window.klientBase = @json(url('/klient'));
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.datatables.net/2.3.2/js/dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.3/js/buttons.html5.min.js"></script>
<script src="{{ asset('js/timer.js') }}"></script>
<script src="{{ asset('js/imgw-map.js') }}?v=10"></script>
<script src="{{ asset('js/imgw-table-hour.js') }}?v=2"></script>
<script src="{{ asset('js/imgw-table.js') }}?v=6"></script>
<script src="{{ asset('js/analiza.js') }}?v=1"></script>
<script src="{{ asset('js/client.js') }}?v=16"></script>
</body>
</html>
