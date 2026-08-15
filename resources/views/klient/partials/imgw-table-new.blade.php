@if(!empty($data['rows']))
    <div class="imgw-table-new" data-hour="{{ $data['actualHour'] }}">
        <div class="imgw-dt-head">
            <div>
                <span class="imgw-dt-title">Warunki atmosferyczne o godzinie <span class="hour">{{ $data['actualHour'] }}</span></span>
                <span class="imgw-dt-sub">(co 1 godzinę)</span>
            </div>
            <div class="imgw-dt-legend">
                <span><span class="circleBase yellowBackColor"></span> godzinę wcześniej</span>
                <span><span class="circleBase redBackColor"></span> dwie godziny temu</span>
            </div>
        </div>
        @if(!empty($data['pressure']))
            <div class="pressure imgw-dt-pressure">{!! $data['pressure'] !!}</div>
        @endif
        <table id="imgw-datatable" class="imgw-datatable display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>Województwo</th>
                    <th>Miejscowość</th>
                    <th>Temp. [°C]</th>
                    <th>Temp. odcz. [°C]</th>
                    <th>Zachmurzenie</th>
                    <th>Zjawisko</th>
                    <th>Widoczność [km]</th>
                    <th>Wiatr/Porywy [km/h]</th>
                </tr>
            </thead>
            <tbody>
            @foreach($data['rows'] as $row)
                @php
                    $delay = $row['godzina'] == 1 ? ' (1 h)' : ($row['godzina'] == 2 ? ' (2 h)' : '');
                @endphp
                <tr class="{{ $row['imgwRow'] }}">
                    <td>{{ $row['region'] }}</td>
                    <td{!! $row['imgwCity'] !!} data-export="{{ $row['nazwaStacji'].$delay }}">
                        {{ $row['nazwaStacji'] }}
                        @if($row['godzina'] == 1)
                            <span class="circleBase yellowBackColor" title="Opóźnienie 1h"></span>
                        @elseif($row['godzina'] == 2)
                            <span class="circleBase redBackColor" title="Opóźnienie 2h"></span>
                        @endif
                    </td>
                    <td data-order="{{ $row['temp'] === '-' ? -999 : $row['temp'] }}">{{ $row['temp'] }}</td>
                    <td data-order="{{ $row['tempOdcz'] === '' ? -999 : $row['tempOdcz'] }}">{{ $row['tempOdcz'] }}</td>
                    <td>{{ $row['zachmurzenieTXT'] }}</td>
                    <td>{{ $row['zjawiskoTXT'] }}</td>
                    <td>{{ $row['widzialnosc'] }}</td>
                    <td>{{ $row['wiatr'] }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th>
                </tr>
            </tfoot>
        </table>
    </div>
@else
    Brak danych
@endif
