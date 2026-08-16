@if(!empty($data['points']) || !empty($data['frames']))
    <div class="imgw-map-new">
        @include('klient.partials.imgw-hour-nav', ['prefix' => 'imgw-map', 'data' => $data])
        <div class="imgw-map-stage">
            <button type="button" class="imgw-map-step imgw-map-step-prev" data-dir="-1" title="Cofnij o 1 godzinę" data-tip="Tryb ręczny: cofnij o 1 godzinę">‹</button>
            <div id="imgw-leaflet" class="imgw-leaflet" data-night="{{ (int) ($data['night'] ?? 0) }}" data-geojson="{{ asset('data/wojewodztwa_pl.geojson') }}" data-current="{{ (int) ($data['current'] ?? 0) }}"></div>
            <button type="button" class="imgw-map-step imgw-map-step-next" data-dir="1" title="Do przodu o 1 godzinę" data-tip="Tryb ręczny: do przodu o 1 godzinę">›</button>
        </div>
        <script type="application/json" id="imgw-map-frames">@json($data['frames'] ?? [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)</script>
    </div>
@else
    Brak danych
@endif
