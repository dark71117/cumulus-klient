@foreach($data['files'] as $file)
    @if(!isset($file['video']))
        <a href="{{ url('/klient/pobierz/tv/'.$file['fileName']) }}">{{ $file['a'] }}</a><br><br>
    @endif
@endforeach
@if(!empty($data['videoExist']))
    <div style="text-align:center;margin:40px 0;">
        Aktualna animacja z prognozą dla obszaru Polski (na 24h):
        <ul style="list-style-type:disc;margin-top:40px;">
            @foreach($data['files'] as $file)
                @if(isset($file['video']))
                    <li style="margin-bottom:40px;">{{ $file['desc'] }}<a href="{{ url('/klient/pobierz/tv/'.$file['fileName']) }}">{{ $file['a'] }}</a></li>
                @endif
            @endforeach
        </ul>
    </div>
@endif
