<div class="ipadmin-pane">
    <div>Dla klienta: <span class="bold">{{ $customer->nazwa }}</span></div>
    <a class="button" href="{{ url('/klient/ipadmin') }}">Ograniczenia dostępu</a>
    <table class="actualTable">
        <tr><th>IP</th><th>Host</th><th>Czas</th><th>Licznik</th></tr>
        @foreach($journal as $row)
            <tr>
                <td>{{ $row->ip }}</td>
                <td>{{ $row->host }}</td>
                <td>{{ $row->czas }}</td>
                <td>{{ $row->licznik }}</td>
            </tr>
        @endforeach
    </table>
</div>
