@php
    $c = $customer ?? [];
    $flag = fn($k) => (int)($c[$k] ?? 0) === 1;
@endphp
<nav class="tabs_frame side-nav{{ ($c['nazwa'] ?? '') === 'admin' ? ' hidden' : '' }}" aria-label="Menu panelu">
    <p class="nav-section-label">Menu</p>
    <div class="tabs1" id="mainTabs">
        <div class="t1a nav-btn" id="forecastTab" data-tip="Prognozy tekstowe, graficzne i archiwum" title="Prognozy tekstowe, graficzne i archiwum">
            <span>Prognozy</span>
            <span class="circle" style="display:none;" title="Nowa prognoza"><img src="{{ asset('images/kulka.png') }}" alt=""></span>
        </div>
        <div class="t1a nav-btn" id="actualTab" data-tip="Bieżące warunki: tabela IMGW, mapa, GDDKiA, satelita i radar" title="Bieżące warunki: tabela IMGW, mapa, GDDKiA, satelita i radar">Aktualna pogoda</div>
        <div class="t1a nav-btn" id="calendarTab" data-tip="Imieniny, wschód i zachód słońca, atlas chmur" title="Imieniny, wschód i zachód słońca, atlas chmur">Kalendarium</div>
        <div class="t1a nav-btn{{ $flag('ostrzezeniaTXT') ? '' : ' hidden' }}" id="warningTab" data-tip="Aktualne ostrzeżenia meteorologiczne" title="Aktualne ostrzeżenia meteorologiczne">Ostrzeżenie</div>
    </div>
    <div class="tabs2 nav-sub hidden" id="forecastTabs">
        <div class="t2a nav-btn{{ $flag('prognozaDzis') ? '' : ' hidden' }}" id="forecast1Tab" data-tip="Najnowsza prognoza tekstowa" title="Najnowsza prognoza tekstowa"><div class="desc">Prognoza aktualna</div><div class="circle_small"><img src="{{ asset('images/kulka2.png') }}" alt=""></div></div>
        <div class="t2a nav-btn{{ $flag('prognozaJutro') ? '' : ' hidden' }}" id="forecast2Tab" data-tip="Druga prognoza tekstowa" title="Druga prognoza tekstowa"><div class="desc">Prognoza 2</div><div class="circle_small"><img src="{{ asset('images/kulka2.png') }}" alt=""></div></div>
        <div class="t2a nav-btn{{ $flag('prognozaDluga') ? '' : ' hidden' }}" id="forecast3Tab" data-tip="Prognoza długoterminowa" title="Prognoza długoterminowa"><div class="desc">Prognoza 3</div><div class="circle_small"><img src="{{ asset('images/kulka2.png') }}" alt=""></div></div>
        <div class="t2a nav-btn{{ $flag('prognozaInna') ? '' : ' hidden' }}" id="forecast4Tab" data-tip="Dodatkowa prognoza tekstowa" title="Dodatkowa prognoza tekstowa"><div class="desc">Prognoza 4</div><div class="circle_small"><img src="{{ asset('images/kulka2.png') }}" alt=""></div></div>
        <div class="t2a nav-btn{{ $flag('mapaPrognozy') ? '' : ' hidden' }}" id="forecast5Tab" data-tip="Mapy i wykresy MeteoMax" title="Mapy i wykresy MeteoMax"><div class="desc">Prognoza graficzna</div></div>
        <div class="t2a nav-btn{{ $flag('prognozaTV') ? '' : ' hidden' }}" id="animationTab" data-tip="Animacja satelitarna i film TV" title="Animacja satelitarna i film TV"><div class="desc">Animacja (film)</div></div>
        <div class="t2a nav-btn" id="archiveTab" data-tip="Wcześniejsze prognozy i ostrzeżenia" title="Wcześniejsze prognozy i ostrzeżenia"><div class="desc">Archiwum</div></div>
    </div>
    <div class="tabs2 nav-sub hidden" id="actualTabs">
        <div class="t2a nav-btn{{ $flag('IMGW') ? '' : ' hidden' }}" id="imgwTab" data-tip="Tabela synoptyczna IMGW" title="Tabela synoptyczna IMGW">TABELA</div>
        <div class="t2a nav-btn{{ $flag('mapaWarunkow') ? '' : ' hidden' }}" id="imgwMapTab" data-tip="Mapa aktualnych warunków" title="Mapa aktualnych warunków">MAPA</div>
        <div class="t2a nav-btn{{ $flag('GDDKIAwoj') ? '' : ' hidden' }}" id="gddkiaRegionTab" data-tip="Stacje GDDKiA pogrupowane według województw" title="Stacje GDDKiA pogrupowane według województw"><span>Dane z GDDKiA</span><span>wg województw</span></div>
        <div class="t2a nav-btn{{ $flag('GDDKIAdrogi') ? '' : ' hidden' }}" id="gddkiaRoadTab" data-tip="Stacje GDDKiA pogrupowane według dróg" title="Stacje GDDKiA pogrupowane według dróg"><span>Dane z GDDKiA</span><span>wg dróg</span></div>
        <div class="t2a nav-btn{{ $flag('zdjeciaSat') ? '' : ' hidden' }}" id="satPhotoTab" data-tip="Animacje satelitarne Europy i Polski" title="Animacje satelitarne Europy i Polski">Zdjęcia satelitarne</div>
        <div class="t2a nav-btn" id="radarTab" data-tip="Radar opadów i mapa wyładowań" title="Radar opadów i mapa wyładowań">Radar / Burze</div>
    </div>
    <div class="tabs2 nav-sub hidden" id="calendarTabs">
        <div class="t2a nav-btn" id="sunTab" data-tip="Imieniny oraz wschód i zachód słońca" title="Imieniny oraz wschód i zachód słońca">Kalendarium</div>
        <div class="t2a nav-btn" id="cloudsTab" data-tip="Otwiera atlas chmur w nowym oknie" title="Otwiera atlas chmur w nowym oknie">Atlas chmur</div>
        <div class="t2a nav-btn" id="theoryTab" data-tip="Otwiera kompendium meteorologii w nowym oknie" title="Otwiera kompendium meteorologii w nowym oknie">Teoria meteorologii</div>
    </div>
    <div class="tabs2 nav-sub hidden" id="archiveTabs">
        <div class="t5a nav-btn{{ $flag('prognozaDzis') ? '' : ' hidden' }}" id="archive1Tab" data-tip="Archiwum prognoz aktualnych" title="Archiwum prognoz aktualnych">{{ $flag('prognozaTV') ? 'Europa i Polska' : 'Prognozy aktualne' }}</div>
        <div class="t5a nav-btn{{ $flag('prognozaJutro') ? '' : ' hidden' }}" id="archive2Tab" data-tip="Archiwum prognoz na jutro" title="Archiwum prognoz na jutro">{{ $flag('prognozaTV') ? 'Województwo' : 'Prognozy na jutro' }}</div>
        <div class="t5a nav-btn{{ $flag('prognozaDluga') ? '' : ' hidden' }}" id="archive3Tab" data-tip="Archiwum prognoz długoterminowych" title="Archiwum prognoz długoterminowych">{{ $flag('prognozaTV') ? 'Tabele pogody' : 'Prognozy długoterminowe' }}</div>
        <div class="t5a nav-btn{{ $flag('prognozaInna') ? '' : ' hidden' }}" id="archive4Tab" data-tip="Archiwum prognoz dodatkowych" title="Archiwum prognoz dodatkowych">Prognozy dodatkowe</div>
        <div class="t5a nav-btn{{ $flag('ostrzezeniaTXT') ? '' : ' hidden' }}" id="archive5Tab" data-tip="Archiwum ostrzeżeń" title="Archiwum ostrzeżeń">Ostrzeżenia</div>
    </div>
    <div class="tabs2 nav-sub hidden" id="satTabs">
        <div class="t5a nav-btn" id="europeSatTab" data-tip="Animacja satelitarna Europy w podczerwieni" title="Animacja satelitarna Europy w podczerwieni">Zdjęcia Europy w podczerwieni</div>
        <div class="t5a nav-btn" id="polandSatTab" data-tip="Animacja satelitarna Polski w świetle widzialnym" title="Animacja satelitarna Polski w świetle widzialnym">Zdjęcia Polski w świetle widzialnym</div>
    </div>
</nav>
