@if(!empty($data['rows']) && count($data['rows']))
    <p>Dane GDDKiA wg dróg @if(!empty($data['maxTime'])) ({{ $data['maxTime'] }}) @endif</p>
    <table class="actualTable">
        <thead>
            <tr class="header">
                <th>Droga</th><th>Województwo</th><th>Region</th><th>Stacja</th>
                <th>T2 [°C]</th><th>T0 [°C]</th><th>Opad</th><th>Wiatr [km/h]</th>
            </tr>
        </thead>
        <tbody>
        @foreach($data['rows'] as $row)
            <tr>
                <td>{{ $row->nrDrogi }}</td>
                <td>{{ $row->wojewodztwo }}</td>
                <td>{{ $row->region }}</td>
                <td>{{ $row->stacja }}</td>
                <td>{{ $row->t2 }}</td>
                <td>{{ $row->t0 }}</td>
                <td>{{ $row->opad }}</td>
                <td>{{ $row->wiatr }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    Brak danych
@endif
