<div class="calendar">
    <div class="names">
        <div class="t_row">
            <div class="align_left">Imieniny</div>
            <div>DZISIAJ - {{ $astro['today']['day'] ?? '' }} {{ $astro['today']['month'] ?? '' }}<br><br>{{ $names['today'] ?? '' }}</div>
            <div>JUTRO - {{ $astro['yesterday']['day'] ?? '' }} {{ $astro['yesterday']['month'] ?? '' }}<br><br>{{ $names['yesterday'] ?? '' }}</div>
        </div>
    </div>
    <div class="times">
        <div class="t_row">
            <div class="align_left">Dzień {{ $astro['today']['year'] ?? '' }} roku<br>Do Nowego roku pozostało</div>
            <div>{{ $astro['today']['dayOfYear'] ?? '' }}<br>{{ $astro['today']['toNewYear'] ?? '' }}</div>
            <div>{{ $astro['yesterday']['dayOfYear'] ?? '' }}<br>{{ $astro['yesterday']['toNewYear'] ?? '' }}</div>
        </div>
        <div class="t_row">
            <div class="align_left">Długość dnia<br>Dłuższy od najkrótszego o<br>Krótszy od najdłuższego</div>
            <div>{{ $astro['today']['dayLength'] ?? '' }}<br>{{ $astro['today']['dayLonger'] ?? '' }}<br>{{ $astro['today']['dayShorter'] ?? '' }}</div>
            <div>{{ $astro['yesterday']['dayLength'] ?? '' }}<br>{{ $astro['yesterday']['dayLonger'] ?? '' }}<br>{{ $astro['yesterday']['dayShorter'] ?? '' }}</div>
        </div>
        <div class="t_row">
            <div class="align_left">Słońce<br>wschód / zachód</div>
            <div><br>{{ $astro['today']['sunSet'] ?? '' }} / {{ $astro['today']['sunRise'] ?? '' }}</div>
            <div><br>{{ $astro['yesterday']['sunSet'] ?? '' }} / {{ $astro['yesterday']['sunRise'] ?? '' }}</div>
        </div>
        <div class="t_row">
            <div class="align_left">Księżyc<br>{{ $astro['today']['moonInfo'] ?? '' }}</div>
            <div>
                @if(!empty($astro['today']['moonIcon']))
                    <img src="{{ asset('images/ksiezyc/'.$astro['today']['moonIcon'].'.gif') }}" title="Faza księżyca w dniu dzisiejszym" alt="">
                @endif
                <br>{{ $astro['today']['moonDays'] ?? '' }}
            </div>
            <div>
                @if(!empty($astro['yesterday']['moonIcon']))
                    <img src="{{ asset('images/ksiezyc/'.$astro['yesterday']['moonIcon'].'.gif') }}" title="Faza księżyca w dniu jutrzejszym" alt="">
                @endif
                <br>{{ $astro['yesterday']['moonDays'] ?? '' }}
            </div>
        </div>
    </div>
</div>
