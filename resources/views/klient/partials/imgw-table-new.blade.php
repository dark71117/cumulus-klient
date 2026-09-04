@php
    $rowsPl = [];
    $rowsEu = [];
    foreach ($data['rows'] as $row) {
        if (\App\Support\ImgwRegion::isEurope((string) $row['region'])) {
            $rowsEu[] = $row;
        } else {
            $rowsPl[] = $row;
        }
    }
@endphp
@if(!empty($data['rows']))
    <div class="imgw-table-new" data-hour="{{ $data['actualHour'] }}" data-current="{{ (int) ($data['current'] ?? 0) }}">
        @include('klient.partials.imgw-hour-nav', ['prefix' => 'imgw-table', 'data' => $data])
        @if(!empty($data['pressure']))
            <div class="pressure imgw-dt-pressure">{!! $data['pressure'] !!}</div>
        @endif
        <div class="imgw-dt-toolbar">
            <div class="imgw-dt-export"></div>
            <div class="imgw-dt-legend">
                <span><span class="imgw-dt-swatch imgw-delay-1"></span> dane sprzed godziny</span>
                <span><span class="imgw-dt-swatch imgw-delay-2"></span> dane sprzed dwóch godzin</span>
            </div>
            <label class="imgw-dt-search-label">Szukaj:
                <input type="search" id="imgw-dt-search" placeholder="Miejscowość, zjawisko…">
            </label>
        </div>
        <div class="imgw-dt-shared-filters" aria-label="Filtry kolumn">
            <input type="search" class="imgw-dt-colfilter" data-col="0" placeholder="Województwo / region">
            <input type="search" class="imgw-dt-colfilter" data-col="1" placeholder="Miejscowość">
            <input type="search" class="imgw-dt-colfilter" data-col="2" placeholder="Temp.">
            <input type="search" class="imgw-dt-colfilter" data-col="3" placeholder="Temp. odcz.">
            <input type="search" class="imgw-dt-colfilter" data-col="4" placeholder="Zachmurzenie">
            <input type="search" class="imgw-dt-colfilter" data-col="5" placeholder="Zjawisko">
            <input type="search" class="imgw-dt-colfilter" data-col="6" placeholder="Widoczność">
            <input type="search" class="imgw-dt-colfilter" data-col="7" placeholder="Wiatr">
        </div>
        <div class="imgw-table-stage">
            <button type="button" class="imgw-map-step imgw-table-step imgw-map-step-prev" data-dir="-1" title="Cofnij o 1 godzinę" data-tip="Tryb ręczny: cofnij o 1 godzinę">‹</button>
            <div class="imgw-table-stage-body">
                @if(!empty($rowsPl))
                    @include('klient.partials.imgw-table-new-section', [
                        'id' => 'imgw-datatable-pl',
                        'title' => 'Polska',
                        'regionLabel' => 'Województwo',
                        'rows' => $rowsPl,
                    ])
                @endif
                @if(!empty($rowsEu))
                    @include('klient.partials.imgw-table-new-section', [
                        'id' => 'imgw-datatable-eu',
                        'title' => 'Europa',
                        'regionLabel' => 'Region',
                        'rows' => $rowsEu,
                    ])
                @endif
            </div>
            <button type="button" class="imgw-map-step imgw-table-step imgw-map-step-next" data-dir="1" title="Do przodu o 1 godzinę" data-tip="Tryb ręczny: do przodu o 1 godzinę">›</button>
        </div>
        <script type="application/json" id="imgw-table-frames">@json($data['frames'] ?? [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)</script>
    </div>
@else
    Brak danych
@endif
