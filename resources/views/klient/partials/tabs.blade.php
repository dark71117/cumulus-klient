@php
    $c = $customer ?? [];
    $flag = fn($k) => (int)($c[$k] ?? 0) === 1;
@endphp
<nav class="tabs_frame side-nav{{ ($c['nazwa'] ?? '') === 'admin' ? ' hidden' : '' }}" aria-label="Menu panelu">
    <p class="nav-section-label">Menu</p>
    <div class="tabs1" id="mainTabs">
        <div class="t1a nav-btn{{ $flag('IMGW') ? '' : ' hidden' }}" id="imgwTab" data-tip="Tabela synoptyczna IMGW" title="Tabela synoptyczna IMGW">Tabela</div>
        <div class="t1a nav-btn{{ $flag('IMGW') ? '' : ' hidden' }}" id="imgwTableNewTab" data-tip="Tabela IMGW z sortowaniem, filtrowaniem i eksportem" title="Tabela IMGW z sortowaniem, filtrowaniem i eksportem">Tabela NEW</div>
        <div class="t1a nav-btn{{ $flag('mapaWarunkow') ? '' : ' hidden' }}" id="imgwMapTab" data-tip="Klasyczna mapa bitmapowa IMGW" title="Klasyczna mapa bitmapowa IMGW">Mapa</div>
        <div class="t1a nav-btn{{ $flag('mapaWarunkow') ? '' : ' hidden' }}" id="imgwMapNewTab" data-tip="Nowa mapa OpenStreetMap z danymi IMGW" title="Nowa mapa OpenStreetMap z danymi IMGW">Mapa NEW</div>
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
