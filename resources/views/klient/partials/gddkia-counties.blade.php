@if(!empty($data['rows']) && count($data['rows']))
    <p>Dane GDDKiA wg województw @if(!empty($data['maxTime'])) ({{ $data['maxTime'] }}) @endif</p>
    <table class="actualTable">
        <thead>
            <tr class="header">
                <th>Województwo</th><th>Region</th><th>Stacja</th><th>Droga</th>
                <th>T2 [°C]</th><th>T0 [°C]</th><th>Opad</th><th>Wiatr [km/h]</th><th>Nawierzchnia</th>
            </tr>
        </thead>
        <tbody>
        @foreach($data['rows'] as $row)
            <tr>
                <td>{{ $row->wojewodztwo }}</td>
                <td>{{ $row->region }}</td>
                <td>{{ $row->stacja }}
                    <a href="{{ url('/klient/kamery/3/'.$row->stacjaId.'/0') }}" target="_blank">kamery</a>
                </td>
                <td>{{ $row->nrDrogi }}</td>
                <td>{{ $row->t2 }}</td>
                <td>{{ $row->t0 }}</td>
                <td>{{ $row->opad }}</td>
                <td>{{ $row->wiatr }}@if(!empty($row->porywy)) / {{ $row->porywy }}@endif</td>
                <td>{{ $row->nawierzchnia }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    Brak danych
@endif
