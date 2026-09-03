@php
    $source = $source ?? 'ogimet';
    $mode = $mode ?? 'hour';
    $termin = $data['termin'] ?? '';
    $dtValue = $termin !== '' ? str_replace(' ', 'T', substr($termin, 0, 16)) : '';
    $latestDt = !empty($data['latest']) ? str_replace(' ', 'T', substr($data['latest'], 0, 16)) : '';
    $fromValue = $termin !== '' ? \Carbon\Carbon::parse($termin)->subDays(7)->format('Y-m-d\TH:i') : '';
    $toValue = $dtValue;
@endphp
<div class="analiza-app imgw-table-new"
     data-source="{{ $source }}"
     data-mode="{{ $mode }}"
     data-termin="{{ $termin }}"
     data-prev="{{ $data['prev'] ?? '' }}"
     data-next="{{ $data['next'] ?? '' }}"
     data-latest="{{ $data['latest'] ?? '' }}">
    <div class="imgw-dt-head imgw-map-header analiza-head">
        <div class="analiza-title">
            <span class="imgw-dt-title">Analiza depesz SYNOP</span>
            <span class="imgw-dt-sub" id="analiza-hour-label">
                @if($termin !== '')
                    {{ \Carbon\Carbon::parse($termin)->format('d.m.Y, H:i') }}
                @endif
            </span>
        </div>
        <div class="analiza-toolbar">
            <label>Źródło
                <select id="analiza-source" title="Źródło depesz">
                    <option value="ogimet" @selected($source === 'ogimet')>Ogimet</option>
                    <option value="imgw" @selected($source === 'imgw')>IMGW (stare)</option>
                </select>
            </label>
            <label>Tryb
                <select id="analiza-mode" title="Przegląd godziny albo statystyki zakresu">
                    <option value="hour" @selected($mode === 'hour')>Przegląd godziny</option>
                    <option value="stats" @selected($mode === 'stats')>Statystyki zakresu</option>
                </select>
            </label>
        </div>
    </div>

    <div class="analiza-controls analiza-hour-controls" @if($mode !== 'hour') hidden @endif>
        <button type="button" class="analiza-nav" id="analiza-prev" title="Poprzednia godzina z danymi" @disabled(empty($data['prev']))>‹</button>
        <label>Skok do
            <input type="datetime-local" id="analiza-termin" step="3600" value="{{ $dtValue }}">
        </label>
        <button type="button" class="analiza-nav" id="analiza-next" title="Następna godzina z danymi" @disabled(empty($data['next']))>›</button>
        <button type="button" class="analiza-now" id="analiza-latest" data-termin="{{ $data['latest'] ?? '' }}" @disabled($latestDt === '')>Aktualne</button>
        <label class="analiza-desc-toggle">
            <input type="checkbox" id="analiza-show-desc" checked>
            Opisy (jak Tabela NEW2)
        </label>
    </div>

    <div class="analiza-controls analiza-stats-controls" @if($mode !== 'stats') hidden @endif>
        <label>Stacja
            <select id="analiza-station">
                @foreach($data['stations'] ?? [] as $station)
                    <option value="{{ $station['id'] }}">{{ $station['name'] }} ({{ $station['id'] }})</option>
                @endforeach
            </select>
        </label>
        <label>Od
            <input type="datetime-local" id="analiza-from" step="3600" value="{{ $fromValue }}">
        </label>
        <label>Do
            <input type="datetime-local" id="analiza-to" step="3600" value="{{ $toValue }}">
        </label>
        <button type="button" class="analiza-now" id="analiza-stats-run">Licz min / max / średnia</button>
    </div>

    <div class="analiza-body" id="analiza-body">
        @if($mode === 'stats')
            @include('klient.partials.analiza-stats', ['stats' => $stats ?? []])
        @else
            @include('klient.partials.analiza-hour', ['rows' => $data['rows'] ?? []])
        @endif
    </div>

    <div class="analiza-modal" id="analiza-explain-modal" hidden>
        <div class="analiza-modal-backdrop" data-analiza-close></div>
        <div class="analiza-modal-panel" role="dialog" aria-modal="true" aria-labelledby="analiza-explain-title">
            <div class="analiza-modal-head">
                <h2 id="analiza-explain-title">Rozbiór depeszy SYNOP</h2>
                <button type="button" class="analiza-modal-close" data-analiza-close aria-label="Zamknij">×</button>
            </div>
            <div class="analiza-modal-body" id="analiza-explain-body"></div>
        </div>
    </div>
</div>
