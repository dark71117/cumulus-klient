@php
    $meta = collect([
        $station !== '' ? $station.($stationId !== '' ? ' ('.$stationId.')' : '') : ($stationId !== '' ? $stationId : ''),
        $termin !== '' ? \Carbon\Carbon::parse($termin)->format('d.m.Y, H:i') : '',
    ])->filter()->implode(' · ');
@endphp
@if($meta !== '')
    <p class="analiza-explain-meta">{{ $meta }}</p>
@endif
<p class="analiza-explain-raw"><code>{{ $raw }}</code></p>
<p class="analiza-explain-hint">
    To nie jest depesza SYNOP (FM-12). W tej godzinie stacja nie nadała SYNOP
    (albo Ogimet go nie ma), więc temperatury, wiatr i ciśnienie w tabeli pochodzą
    z depeszy <strong>METAR</strong> lotniska. Rozbiór członów 5-znakowych dotyczy tylko oryginalnego SYNOP.
</p>
