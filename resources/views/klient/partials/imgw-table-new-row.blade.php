@php
    $delayHours = (int) $row['godzina'];
    $delay = $delayHours === 1 ? ' (1 h)' : ($delayHours === 2 ? ' (2 h)' : '');
@endphp
<tr class="{{ $row['imgwRow'] }}">
    <td>{{ $row['region'] }}</td>
    <td @class([
            'imgwCity' => $row['imgwCity'] !== '' && $delayHours === 0,
            'imgw-delay-1' => $delayHours === 1,
            'imgw-delay-2' => $delayHours === 2,
        ])
        data-export="{{ $row['nazwaStacji'].$delay }}"
        @if($delayHours === 1) title="Dane sprzed godziny"
        @elseif($delayHours === 2) title="Dane sprzed dwóch godzin"
        @endif
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
