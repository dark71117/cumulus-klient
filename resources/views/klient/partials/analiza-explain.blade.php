@php
    $meta = collect([
        $station !== '' ? $station.($stationId !== '' ? ' ('.$stationId.')' : '') : ($stationId !== '' ? $stationId : ''),
        $termin !== '' ? \Carbon\Carbon::parse($termin)->format('d.m.Y, H:i') : '',
    ])->filter()->implode(' · ');
@endphp
@if($meta !== '')
    <p class="analiza-explain-meta">{{ $meta }}</p>
@endif
<p class="analiza-explain-raw"><code>{{ $raw }}</code></p>
<p class="analiza-explain-hint">Każdy człon depeszy FM-12 ma zwykle 5 znaków. Kliknij grupę, żeby podświetlić opis.</p>
<p class="analiza-explain-chips" role="list">
    @foreach($groups as $i => $group)
        <button type="button" class="analiza-explain-chip" data-i="{{ $i }}" role="listitem" title="{{ $group['title'] }}">{{ $group['token'] }}</button>
    @endforeach
</p>
<ol class="analiza-explain-list">
    @foreach($groups as $i => $group)
        <li class="analiza-explain-item" data-i="{{ $i }}" id="analiza-explain-g{{ $i }}">
            <code class="analiza-explain-token">{{ $group['token'] }}</code>
            <div class="analiza-explain-copy">
                <div class="analiza-explain-title">
                    <span>{{ $group['title'] }}</span>
                    <code>{{ $group['key'] }}</code>
                </div>
                <p>{{ $group['meaning'] }}</p>
                @if(!empty($group['bits']))
                    <ul class="analiza-explain-bits">
                        @foreach($group['bits'] as $bit)
                            <li><code>{{ $bit['code'] }}</code> {{ $bit['text'] }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </li>
    @endforeach
</ol>
