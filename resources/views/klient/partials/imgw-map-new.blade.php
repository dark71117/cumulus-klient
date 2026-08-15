@if(!empty($data['points']) || !empty($data['frames']))
    <div class="imgw-map-new">
        <div class="imgw-dt-head imgw-map-header">
            <div>
                <span class="imgw-dt-title">Warunki atmosferyczne o godzinie <span class="hour" id="imgw-map-hour">{{ $data['actualHour'] }}</span></span>
                <span class="imgw-dt-sub">(co 1 godzinę)</span>
            </div>
            <div class="imgw-map-play" id="imgw-map-play">
                <div class="imgw-map-play-title">Automatyczne przewijanie</div>
                <div class="imgw-map-play-row">
                    <button type="button" class="imgw-map-play-btn" data-dir="-1" title="Wstecz" data-tip="Cofnij godzinę / przewijaj wstecz">«</button>
                    <button type="button" class="imgw-map-play-btn" data-dir="1" title="W przód" data-tip="Do przodu / przewijaj w przód">»</button>
                    <label class="imgw-map-play-delay">Zwłoka
                        <select id="imgw-map-delay" title="Odstęp między godzinami">
                            <option value="1000">1 s</option>
                            <option value="2000">2 s</option>
                            <option value="3000" selected>3 s</option>
                            <option value="5000">5 s</option>
                            <option value="10000">10 s</option>
                        </select>
                    </label>
                </div>
            </div>
        </div>
        <div id="imgw-leaflet" class="imgw-leaflet" data-night="{{ (int) ($data['night'] ?? 0) }}" data-geojson="{{ asset('data/wojewodztwa_pl.geojson') }}" data-current="{{ (int) ($data['current'] ?? 0) }}"></div>
        <script type="application/json" id="imgw-map-frames">@json($data['frames'] ?? [], JSON_UNESCAPED_UNICODE)</script>
    </div>
@else
    Brak danych
@endif
