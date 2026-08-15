@if(!empty($data['points']) || !empty($data['frames']))
    <div class="imgw-map-new">
        <div class="imgw-dt-head imgw-map-header">
            <div class="imgw-map-jump">
                <div class="imgw-map-jump-title">Skok do godziny</div>
                <div class="imgw-map-jump-row">
                    <select id="imgw-map-jump" title="Wybierz godzinę" data-tip="Tryb ręczny: skok do wybranej godziny z bazy">
                        @foreach(array_reverse($data['frames'] ?? [], true) as $idx => $frame)
                            <option value="{{ $idx }}" @selected((int) $idx === (int) ($data['current'] ?? 0))>{{ trim(($frame['date'] ?? '').', '.($frame['hour'] ?? ''), ', ') }}</option>
                        @endforeach
                    </select>
                    <label class="imgw-map-limit">Wstecz
                        <select id="imgw-map-limit" title="Ile godzin wstecz" data-tip="Ile godzin wstecz wczytać z bazy. Zapisuje się w profilu firmy.">
                            @foreach(\App\Support\ImgwMap::PLAYBACK_CHOICES as $n)
                                <option value="{{ $n }}" @selected((int) $n === (int) ($data['limit'] ?? 24))>{{ $n }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>
            <div class="imgw-map-title">
                <span class="imgw-dt-title">Warunki atmosferyczne o godzinie <span class="hour" id="imgw-map-hour">{{ $data['actualHour'] }}{{ !empty($data['actualDate']) ? ', '.$data['actualDate'] : '' }}</span></span>
                <span class="imgw-dt-sub">(co 1 godzinę)</span>
            </div>
            <div class="imgw-map-play" id="imgw-map-play">
                <div class="imgw-map-play-title">Automatyczne przewijanie</div>
                <div class="imgw-map-play-row">
                    <button type="button" class="imgw-map-play-btn" data-dir="-1" title="Automat wstecz" data-tip="Tryb automatyczny: przewijaj wstecz">«</button>
                    <button type="button" class="imgw-map-play-btn imgw-map-pause" id="imgw-map-pause" title="Pauza" data-tip="Zatrzymaj automatyczne przewijanie" disabled><span class="imgw-map-pause-icon" aria-hidden="true"></span></button>
                    <button type="button" class="imgw-map-play-btn" data-dir="1" title="Automat w przód" data-tip="Tryb automatyczny: przewijaj w przód">»</button>
                    <label class="imgw-map-play-delay">Zwłoka
                        <select id="imgw-map-delay" title="Odstęp między godzinami" data-tip="Odstęp między godzinami w trybie automatycznym">
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
