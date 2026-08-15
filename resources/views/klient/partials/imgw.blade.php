@if(!empty($data['rows']))
    <div class="actualHeader">
        <div><span class="normal">Warunki atmosferyczne o godzinie <span class="hour">{{ $data['actualHour'] }}</span></span><span class="small"> (co 1 godzinę)</span></div>
        <div><span class="leftHour"><div class="circleBase yellowBackColor"></div> - godzinę wcześniej</span><span class="rightHour"><div class="circleBase redBackColor"></div> - dwie godziny temu</span></div>
        <div class="spacer"></div>
    </div>
    <div class="pressure">{!! $data['pressure'] !!}</div>
    <table class="actualTable imgw">
        <thead>
            <tr class="header">
                <th class="width120">Miejscowość</th>
                <th class="width60">Temp.<br>[°C]</th>
                <th class="width60">Temp. odcz.<br>[°C]</th>
                <th class="width130">Zachmurzenie</th>
                <th>Zjawisko</th>
                <th class="width110">Widoczność [km]</th>
                <th class="width130">Wiatr/Porywy<br>[km/h]</th>
            </tr>
        </thead>
        <tbody>
        @foreach($data['rows'] as $row)
            @if($row['regionRow'] == 1)
                <tr class="imgwRegion"><td colspan="7">{{ $row['region'] }}</td></tr>
            @endif
            <tr class="{{ $row['imgwRow'] }}">
                <td{!! $row['imgwCity'] !!}>
                    {{ $row['nazwaStacji'] }}
                    @if($row['godzina'] == 1)
                        <div class="circleBase yellowBackColor pull-right" title="Opóźnienie 1h"></div>
                    @elseif($row['godzina'] == 2)
                        <div class="circleBase redBackColor pull-right" title="Opóźnienie 2h"></div>
                    @endif
                </td>
                <td>{{ $row['temp'] }}</td>
                <td>{{ $row['tempOdcz'] }}</td>
                <td>{{ $row['zachmurzenieTXT'] }}</td>
                <td>{!! $row['zjawiskoTXT'] !!}</td>
                <td>{{ $row['widzialnosc'] }}</td>
                <td>{{ $row['wiatr'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    Brak danych
@endif
