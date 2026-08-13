@if(!empty($data['forecasts']) && isset($data['forecasts'][$position]))
    @if($rowsNumber > 1)
        <div class="archiveDates">
            <div class="header">Archiwum:</div>
            @foreach($data['forecasts'] as $i => $row)
                <div class="position {{ $position == $i ? 'current' : '' }}" data-position="{{ $i }}">{{ $row->data }}</div>
            @endforeach
        </div>
        <script>
            $(".archiveDates div.position").click(function () {
                loadContent(@json($tab), $(this).data('position'));
            });
        </script>
    @endif
    <div class="align_left">{!! $html !!}</div>
@else
    Na chwilę obecną brak aktualnej informacji.<br> Informacje, poprzednie (wcześniejsze) dostępne są w archiwum.
@endif
