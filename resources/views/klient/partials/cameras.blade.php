@if(empty($cameras))
    Brak kamer
@else
    @foreach($cameras as $camera)
        <p>
            {{ $camera->wojewodztwo }}/{{ $camera->region }}/{{ $camera->stacja }}
            @if($camera->nrDrogi) / Droga {{ $camera->nrDrogi }} @endif
            — <a href="{{ $camera->link }}" target="_blank">Kamera {{ $camera->kamera }}</a>
        </p>
    @endforeach
@endif
