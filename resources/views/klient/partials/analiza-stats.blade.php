@if(!empty($stats['error']) && is_string($stats['error']))
    <p class="analiza-empty">{{ $stats['error'] }}</p>
@elseif(!empty($stats['stats']))
    <p class="analiza-stats-meta">
        {{ $stats['station']['name'] ?? '' }}
        ({{ $stats['station']['id'] ?? '' }})
        · {{ \Carbon\Carbon::parse($stats['from'])->format('d.m.Y H:i') }}
        – {{ \Carbon\Carbon::parse($stats['to'])->format('d.m.Y H:i') }}
    </p>
    <table id="analiza-stats-table" class="imgw-datatable display nowrap" style="width:100%">
        <thead>
            <tr>
                <th>Parametr</th>
                <th>Min</th>
                <th>Max</th>
                <th>Średnia</th>
                <th>Liczba obserwacji</th>
            </tr>
        </thead>
        <tbody>
        @foreach($stats['stats'] as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td>{{ $row['min'] }}</td>
                <td>{{ $row['max'] }}</td>
                <td>{{ $row['avg'] }}</td>
                <td>{{ $row['count'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <p class="analiza-empty">Wybierz stację i zakres, potem kliknij „Licz min / max / średnia”.</p>
@endif
