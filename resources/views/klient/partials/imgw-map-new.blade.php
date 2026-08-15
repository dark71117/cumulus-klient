@if(!empty($data['points']))
    <div class="actualHeader">
        <div><span class="normal">Warunki atmosferyczne o godzinie <span class="hour">{{ $data['actualHour'] }}</span></span></div>
    </div>
    <div id="imgw-leaflet" class="imgw-leaflet" data-night="{{ (int) ($data['night'] ?? 0) }}" data-geojson="{{ asset('data/wojewodztwa_pl.geojson') }}"></div>
    <script type="application/json" id="imgw-map-data">@json($data['points'], JSON_UNESCAPED_UNICODE)</script>
@else
    Brak danych
@endif
