<div class="ipadmin-pane">
    <h2>Strona administracyjna - ograniczenia dostępu wg adresów IP</h2>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ url('/klient/ipadmin') }}">
        @csrf
        <div>Wybierz klienta:</div>
        <select name="IpAdmin[id]">
            <option value="0"></option>
            @foreach($users as $id => $name)
                <option value="{{ $id }}" @selected($customerId == $id)>{{ $name }}</option>
            @endforeach
        </select>
        <button class="button" type="submit">Wybierz</button>
    </form>
    @if($customer)
        <div class="selected">
            <div>Wybrany klient: <span class="bold">{{ $customer->nazwa }}</span></div>
            <a class="button" href="{{ url('/klient/ipjournal/'.$customer->id) }}">Pokaż dziennik adresów IP</a>
        </div>
        <hr>
        <form method="post" action="{{ url('/klient/ipadmin/active') }}">
            @csrf
            <input type="hidden" name="IpAdmin[id]" value="{{ $customer->id }}">
            <div><label><input type="radio" name="IpAdmin[aktywny]" value="1" @checked($customer->aktywny == 1)> Klient aktywny</label></div>
            <div><label><input type="radio" name="IpAdmin[aktywny]" value="0" @checked($customer->aktywny == 0)> Klient nieaktywny</label></div>
            <div>Nowe hasło: <input type="text" name="IpAdmin[haslo]"></div>
            <button class="button" type="submit">Zapisz</button>
        </form>
        <hr>
        <p>Jeśli tabela jest pusta to klient może logować się z dowolnego adresu IP. Wpisany zakres to zakres <strong>zabroniony</strong>.</p>
        <form method="post" action="{{ url('/klient/ipadmin/ip') }}">
            @csrf
            <input type="hidden" name="id_klient" value="{{ $customer->id }}">
            <label>Opis <input name="opis" required></label>
            <label>IP od <input name="ip1" required></label>
            <label>IP do <input name="ip2" required></label>
            <button class="button" type="submit">Dodaj</button>
        </form>
        @if($ipAddresses->isNotEmpty())
            <table class="actualTable">
                <tr><th>Opis</th><th>Od</th><th>Do</th><th></th></tr>
                @foreach($ipAddresses as $ip)
                    <tr>
                        <td>{{ $ip->opis }}</td>
                        <td>{{ $ip->ip1 }}</td>
                        <td>{{ $ip->ip2 }}</td>
                        <td><a href="{{ url('/klient/ipdelete/'.$ip->id) }}">usuń</a></td>
                    </tr>
                @endforeach
            </table>
        @endif
    @endif
</div>
