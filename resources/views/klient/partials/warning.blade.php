@if(!empty($data))
    @if($rowsNumber > 1)
        <div class="archiveDates">
            <div class="header">Archiwum:</div>
            @foreach($data as $i => $row)
                <div class="position {{ $position == $i ? 'current' : '' }}" data-position="{{ $i }}">{{ $row->data }}</div>
            @endforeach
        </div>
        <script>
            $(".archiveDates div.position").click(function () {
                loadContent(@json($tab), $(this).data('position'));
            });
        </script>
    @endif
    <div class="align_left">{!! $data[$position]->tresc ?? '' !!}</div>
@else
    Aktualnie nie ma informacji w tym dziale.
@endif
