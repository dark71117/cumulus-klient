@if(!empty($rows))
    <div class="imgw-dt-toolbar">
        <div class="imgw-dt-export"></div>
        <label class="imgw-dt-search-label">Szukaj:
            <input type="search" id="analiza-search" placeholder="Stacja, depesza…">
        </label>
    </div>
    <table id="analiza-datatable" class="imgw-datatable display nowrap" style="width:100%">
        <thead>
            <tr>
                <th>Województwo</th>
                <th>Stacja</th>
                <th>iii</th>
                <th>N</th>
                <th>h</th>
                <th>VV</th>
                <th>ddd/ff</th>
                <th>T</th>
                <th>uu</th>
                <th>QNH</th>
                <th>QFE</th>
                <th class="analiza-desc">Zjawisko</th>
                <th class="analiza-desc">Zachmurzenie</th>
                <th class="analiza-desc">Wiatr</th>
                <th>Czas</th>
                <th>Depesza</th>
            </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            <tr class="{{ $row['imgwRow'] }}">
                <td>{{ $row['region'] }}</td>
                <td>{{ $row['nazwaStacji'] }}</td>
                <td>{{ $row['idStacji'] }}</td>
                <td>{{ $row['n'] }}</td>
                <td>{{ $row['h'] }}</td>
                <td>{{ $row['vv'] }}</td>
                <td>{{ $row['windAaxx'] }}</td>
                <td>{{ $row['temp'] }}</td>
                <td>{{ $row['uu'] }}</td>
                <td>{{ $row['qnh'] }}</td>
                <td>{{ $row['qfe'] }}</td>
                <td class="analiza-desc">{{ $row['zjawiskoTXT'] }}</td>
                <td class="analiza-desc">{{ $row['zachmurzenieTXT'] }}</td>
                <td class="analiza-desc">{{ $row['wiatrTXT'] }}</td>
                <td>{{ $row['czas'] }}</td>
                <td class="analiza-synop" title="{{ $row['synopRaw'] }}">{{ $row['synopRaw'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <p class="analiza-empty">Brak danych dla wybranej godziny.</p>
@endif
