@if(!empty($data['rows']))
    <div class="actualHeader">
        <div><span class="normal">Warunki atmosferyczne o godzinie <span class="hour">{{ $data['actualHour'] }}</span></span></div>
    </div>
    <div class="actualMap">
        <img class="map" src="{{ asset('images/mapy/'.$data['partOfDay'].'/'.$data['region'].'.png') }}" alt="">
        @foreach($data['rows'] as $row)
            <span class="city" style="left: {{ $row['city']['x'] }}px; top: {{ $row['city']['y'] }}px;">{{ $row['city']['name'] }}</span>
            <span class="{{ $row['temp']['class'] }}" style="left: {{ $row['temp']['x'] }}px; top: {{ $row['temp']['y'] }}px;">{{ $row['temp']['value'] }}</span>
            @if(!empty($row['icon']))
                <img class="icon" style="left: {{ $row['icon']['x'] }}px; top: {{ $row['icon']['y'] }}px;" src="{{ asset('images/ikony2/'.$row['icon']['value']) }}" alt="">
            @endif
        @endforeach
    </div>
@else
    Brak danych
@endif
