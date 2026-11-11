<div class="gfs-app" id="gfs-app">
    <div class="gfs-rail" role="tablist" aria-label="Rodzaj map">
        @foreach($tabs as $tab)
            <button type="button" class="gfs-rail-btn{{ $tab['id'] === 'synoptyk' ? ' is-on' : '' }}" data-tab="{{ $tab['id'] }}">{{ $tab['label'] }}</button>
        @endforeach
    </div>
    <div class="gfs-products">
        @foreach($tabs as $tab)
            <div class="gfs-product-tab" data-tab="{{ $tab['id'] }}" @if($tab['id'] !== 'synoptyk') hidden @endif>
                @if($tab['id'] === 'sat24')
                    <p class="gfs-empty">Brak zdjęć Sat24.</p>
                @endif
                @foreach($tab['groups'] as $group)
                    <fieldset class="gfs-group">
                        <legend>{{ $group['title'] }}</legend>
                        @foreach($group['items'] as $item)
                            <button type="button" class="gfs-param{{ $item['id'] === 'synop3' ? ' is-on' : '' }}" data-id="{{ $item['id'] }}">{{ $item['label'] }}</button>
                        @endforeach
                    </fieldset>
                @endforeach
            </div>
        @endforeach
    </div>
    <div class="gfs-stage">
        <div class="gfs-caption">
            <strong>Mapa synoptyka v.3</strong>
            <div class="gfs-valid-date"></div>
            <div class="gfs-valid-time"></div>
        </div>
        <div class="gfs-panes"></div>
        <label class="gfs-zoom">
            <span>[x/8]</span>
            <input type="range" min="0" max="8" value="0" step="1" aria-label="Przybliżenie">
        </label>
    </div>
    <aside class="gfs-side">
        <div class="gfs-stan">Stan: 0</div>
        <select class="gfs-run" aria-label="Termin prognozy"></select>
        <div class="gfs-regions">
            <button type="button" class="is-on" data-region="Polska">Polska</button>
            <button type="button" data-region="Europa">Europa</button>
        </div>
        <select class="gfs-layout" aria-label="Liczba map">
            <option value="1">Jedna mapa</option>
            <option value="2">Dwie mapy</option>
            <option value="4">Cztery mapy</option>
        </select>
        <div class="gfs-days"></div>
        <div class="gfs-hours"></div>
        <div class="gfs-step">
            <button type="button" data-dir="-1" title="Poprzednia godzina">&lt;&lt;</button>
            <button type="button" data-dir="1" title="Następna godzina">&gt;&gt;</button>
        </div>
        <button type="button" class="gfs-screen" title="Rozciągnij mapy na cały ekran">PEŁNY EKRAN</button>
        <div class="gfs-status">
            <div>STATUS meteomax.pl</div>
            <div class="gfs-status-line">GFS: —</div>
        </div>
    </aside>
    <script type="application/json" id="gfs-boot">@json($boot)</script>
</div>
