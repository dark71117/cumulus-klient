@php
    $delayHours = (int) $row['godzina'];
    $delay = $delayHours === 1 ? ' (1 h)' : ($delayHours === 2 ? ' (2 h)' : '');
@endphp
<tr class="{{ $row['imgwRow'] }}">
    <td data-export="{{ $row['region'] }}">
        @if($delayHours === 1)
            <span class="imgw-dt-swatch imgw-delay-1" title="Dane sprzed godziny"></span>
        @elseif($delayHours === 2)
            <span class="imgw-dt-swatch imgw-delay-2" title="Dane sprzed dwóch godzin"></span>
        @else
            <span class="imgw-dt-swatch imgw-delay-none" aria-hidden="true"></span>
        @endif
        {{ $row['region'] }}
    </td>
    <td @class(['imgwCity' => $row['imgwCity'] !== '' && $delayHours === 0])
        data-export="{{ $row['nazwaStacji'].$delay }}"
    >
        {{ $row['nazwaStacji'] }}
    </td>
    <td data-order="{{ $row['temp'] === '-' ? -999 : $row['temp'] }}">{{ $row['temp'] }}</td>
    <td data-order="{{ $row['tempOdcz'] === '' ? -999 : $row['tempOdcz'] }}">{{ $row['tempOdcz'] }}</td>
    <td>{{ $row['zachmurzenieTXT'] }}</td>
    <td data-export="{{ strip_tags($row['zjawiskoTXT']) }}">{!! $row['zjawiskoTXT'] !!}</td>
    <td>{{ $row['widzialnosc'] }}</td>
    <td>{{ $row['wiatr'] }}</td>
</tr>
