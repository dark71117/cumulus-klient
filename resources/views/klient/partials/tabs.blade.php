@php
    $c = $customer ?? [];
    $flag = fn($k) => (int)($c[$k] ?? 0) === 1;
@endphp
<nav class="tabs_frame side-nav{{ ($c['nazwa'] ?? '') === 'admin' ? ' hidden' : '' }}" aria-label="Menu panelu">
    <p class="nav-section-label">Menu</p>
    <div class="tabs1" id="mainTabs">
        <div class="t1a nav-btn{{ $flag('IMGW') ? '' : ' hidden' }}" id="imgwTableNew2Tab" data-tip="Tabela IMGW z depesz Ogimet" title="Tabela IMGW z depesz Ogimet">Tabela</div>
        <div class="t1a nav-btn{{ $flag('mapaWarunkow') ? '' : ' hidden' }}" id="imgwMapNew2Tab" data-tip="Mapa IMGW z depesz Ogimet" title="Mapa IMGW z depesz Ogimet">Mapa</div>
        <div class="t1a nav-btn{{ $flag('IMGW') ? '' : ' hidden' }}" id="analizaTab" data-tip="Przegląd depesz SYNOP, skok do daty i statystyki stacji" title="Przegląd depesz SYNOP, skok do daty i statystyki stacji">Analiza</div>
        <div class="t1a nav-btn" id="gfsMapTab" data-tip="Mapa synoptyka v.3, te same mapy GFS co w programie na biurku" title="Mapa synoptyka v.3, te same mapy GFS co w programie na biurku">Mapy GFS</div>
        <div class="t1a nav-btn" id="addonsTab" data-tip="Pozostałe narzędzia panelu" title="Pozostałe narzędzia panelu">Dodatki</div>
    </div>
    <div class="tabs2 nav-sub" id="addonsTabs">
        <a class="t2a nav-btn nav-ext" id="meteomaxPlTab" href="https://meteomax.pl" target="_blank" rel="noopener noreferrer" data-tip="Otwiera meteomax.pl w nowej karcie" title="Otwiera meteomax.pl w nowej karcie">meteomax.pl</a>
        <a class="t2a nav-btn nav-ext" id="meteomaxEuTab" href="https://meteomax.eu" target="_blank" rel="noopener noreferrer" data-tip="Otwiera meteomax.eu w nowej karcie" title="Otwiera meteomax.eu w nowej karcie">meteomax.eu</a>
        <div class="t2a nav-btn" id="calendarTab" data-tip="Imieniny, wschód i zachód słońca" title="Imieniny, wschód i zachód słońca">Kalendarium</div>
        <div class="t2a nav-btn{{ $flag('ostrzezeniaTXT') ? '' : ' hidden' }}" id="warningTab" data-tip="Aktualne ostrzeżenia meteorologiczne" title="Aktualne ostrzeżenia meteorologiczne">Ostrzeżenie</div>
        <div class="t2a nav-btn{{ $flag('GDDKIAwoj') ? '' : ' hidden' }}" id="gddkiaRegionTab" data-tip="Stacje GDDKiA pogrupowane według województw" title="Stacje GDDKiA pogrupowane według województw"><span>Dane z GDDKiA</span><span>wg województw</span></div>
        <div class="t2a nav-btn{{ $flag('GDDKIAdrogi') ? '' : ' hidden' }}" id="gddkiaRoadTab" data-tip="Stacje GDDKiA pogrupowane według dróg" title="Stacje GDDKiA pogrupowane według dróg"><span>Dane z GDDKiA</span><span>wg dróg</span></div>
    </div>
</nav>
