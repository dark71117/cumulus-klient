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
    <div class="imgw-table-new" data-hour="{{ $data['actualHour'] }}">
        <div class="imgw-dt-head">
            <div>
                <span class="imgw-dt-title">Warunki atmosferyczne o godzinie <span class="hour">{{ $data['actualHour'] }}</span></span>
                <span class="imgw-dt-sub">(co 1 godzinę)</span>
            </div>
            <div class="imgw-dt-legend">
                <span><span class="imgw-dt-swatch imgw-delay-1"></span> nazwa miasta — dane sprzed godziny</span>
                <span><span class="imgw-dt-swatch imgw-delay-2"></span> nazwa miasta — dane sprzed dwóch godzin</span>
            </div>
        </div>
        @if(!empty($data['pressure']))
            <div class="pressure imgw-dt-pressure">{!! $data['pressure'] !!}</div>
        @endif
        <div class="imgw-dt-toolbar">
            <div class="imgw-dt-export"></div>
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
@else
    Brak danych
@endif
