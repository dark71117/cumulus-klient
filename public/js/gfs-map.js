function initGfsMap() {
    var root = document.getElementById('gfs-app');
    var bootNode = document.getElementById('gfs-boot');
    if (!root || !bootNode || root.dataset.ready === '1') return;
    root.dataset.ready = '1';
    var boot = JSON.parse(bootNode.textContent);
    var state = {
        run: boot.run || '',
        lead: asLead(boot.lead || ''),
        startup: true,
        layout: 1,
        active: 0,
        panes: [{
            region: boot.region || 'Polska',
            product: boot.product || 'synop3',
            hours: leadList(boot.frames && boot.frames.hours),
            days: (boot.frames && boot.frames.days) || [],
            count: (boot.frames && boot.frames.count) || 0,
            stamps: stampsOf(boot.frames && boot.frames.stamps),
            coverage: coverageOf(boot.frames && boot.frames.coverage),
            fresh: {},
            zoom: 0,
            panX: 0,
            panY: 0
        }]
    };

    var runSelect = root.querySelector('.gfs-run');
    (boot.runs || []).forEach(function (run) {
        var option = document.createElement('option');
        option.value = run;
        option.textContent = run;
        if (run === state.run) option.selected = true;
        runSelect.appendChild(option);
    });
    if (!boot.runs || !boot.runs.length) {
        var empty = document.createElement('option');
        empty.textContent = 'brak przebiegów';
        runSelect.appendChild(empty);
    }

    function pane(index) { return state.panes[index]; }
    function active() { return state.panes[state.active]; }

    function asLead(value) {
        var n = parseInt(value, 10);
        if (isNaN(n)) return '';
        return String(n).padStart(3, '0');
    }

    function leadList(list) {
        return (list || []).map(asLead).filter(Boolean);
    }

    function shortDate(date) {
        var bits = String(date || '').split('.');
        return bits.length >= 2 ? bits[0] + '-' + bits[1] : '';
    }

    function ensurePanes(count) {
        while (state.panes.length < count) {
            var copy = Object.assign({}, state.panes[0]);
            copy.zoom = 0;
            copy.panX = 0;
            copy.panY = 0;
            copy.stamps = null;
            copy.coverage = coverageOf(null);
            copy.fresh = {};
            state.panes.push(copy);
        }
        state.layout = count;
        if (state.active >= count) state.active = 0;
        renderPanes();
        for (var i = 0; i < count; i++) loadFrames(i, true);
    }

    function renderPanes() {
        var box = root.querySelector('.gfs-panes');
        box.dataset.n = String(state.layout);
        box.innerHTML = '';
        for (var i = 0; i < state.layout; i++) {
            var el = document.createElement('div');
            el.className = 'gfs-pane' + (i === state.active ? ' is-active' : '');
            el.dataset.index = String(i);
            el.innerHTML = '<div class="gfs-view"><img alt=""></div><div class="gfs-missing" hidden>brak mapy</div>';
            box.appendChild(el);
            bindPane(el, i);
            paint(i);
        }
        root.querySelector('.gfs-zoom input').value = String(active().zoom || 0);
        markProduct();
        markRegion();
    }

    function bindPane(el, index) {
        el.addEventListener('mousedown', function (event) {
            if (event.button !== 0) return;
            state.active = index;
            root.querySelectorAll('.gfs-pane').forEach(function (node, n) {
                node.classList.toggle('is-active', n === index);
            });
            markProduct();
            markRegion();
            renderClock();
            root.querySelector('.gfs-zoom input').value = String(pane(index).zoom || 0);
            var startX = event.clientX;
            var startY = event.clientY;
            var originX = pane(index).panX;
            var originY = pane(index).panY;
            function move(ev) {
                if ((pane(index).zoom || 0) === 0) return;
                pane(index).panX = originX + (ev.clientX - startX);
                pane(index).panY = originY + (ev.clientY - startY);
                place(index);
            }
            function up() {
                window.removeEventListener('mousemove', move);
                window.removeEventListener('mouseup', up);
            }
            window.addEventListener('mousemove', move);
            window.addEventListener('mouseup', up);
        });
    }

    function imageUrl(index) {
        var item = pane(index);
        var url = boot.imageBase + '/' + state.run + '/' + item.region + '/' + item.product + '/' + state.lead;
        var stamp = item.stamps && item.stamps[state.lead];
        return stamp ? url + '?t=' + stamp : url;
    }

    function paint(index) {
        var el = root.querySelectorAll('.gfs-pane')[index];
        if (!el) return;
        var img = el.querySelector('img');
        var missing = el.querySelector('.gfs-missing');
        var item = pane(index);
        var has = item.hours.indexOf(state.lead) !== -1 && state.run && state.lead;
        if (!has) {
            img.removeAttribute('src');
            missing.hidden = false;
            return;
        }
        missing.hidden = true;
        var url = imageUrl(index);
        if (img.dataset.url !== url) {
            img.dataset.url = url;
            img.onload = function () {
                if (img.dataset.url !== url) return;
                missing.hidden = true;
                place(index);
            };
            img.onerror = function () {
                if (img.dataset.url !== url) return;
                missing.hidden = false;
            };
            img.src = url;
        } else {
            place(index);
        }
    }

    function place(index) {
        var el = root.querySelectorAll('.gfs-pane')[index];
        if (!el) return;
        var view = el.querySelector('.gfs-view');
        var img = el.querySelector('img');
        var item = pane(index);
        if (!img.naturalWidth || !view.clientWidth) return;
        var zoom = item.zoom || 0;
        var base = Math.min(view.clientWidth / img.naturalWidth, view.clientHeight / img.naturalHeight);
        var scale = base * (zoom === 0 ? 1 : (1 + zoom * 0.85));
        var width = img.naturalWidth * scale;
        var height = img.naturalHeight * scale;
        if (zoom === 0 || item.panX === null || item.panX === undefined) {
            item.panX = (view.clientWidth - width) / 2;
            item.panY = (view.clientHeight - height) / 2;
        }
        img.style.width = width + 'px';
        img.style.height = height + 'px';
        img.style.transform = 'translate(' + item.panX + 'px,' + item.panY + 'px)';
    }

    function stampsOf(raw) {
        if (!raw || Array.isArray(raw)) return {};
        return raw;
    }

    function coverageOf(raw) {
        raw = raw || {};
        return {
            total: raw.total || 0,
            hours: stampsOf(raw.hours),
            dates: stampsOf(raw.dates)
        };
    }

    function coverageKey(item) {
        var cov = item.coverage || coverageOf(null);
        var hours = cov.hours || {};
        var dates = cov.dates || {};
        var hourPart = Object.keys(hours).sort().map(function (key) { return key + ':' + hours[key]; }).join(',');
        var datePart = Object.keys(dates).sort().map(function (key) { return key + ':' + dates[key]; }).join(',');
        return cov.total + '|' + hourPart + '|' + datePart;
    }

    function clockSignature() {
        var item = active();
        var freshKeys = Object.keys(item.fresh || {}).sort();
        return state.lead + '|' + (item.hours || []).join(',') + '|' + freshKeys.join(',') + '|' + coverageKey(item);
    }

    function currentTab() {
        var button = root.querySelector('.gfs-rail-btn.is-on');
        return button ? button.dataset.tab : 'synoptyk';
    }

    function describeMaps(count, fresh) {
        var total = active().coverage && active().coverage.total;
        var parts = [];
        if (total && count) parts.push(count + ' z ' + total + ' map' + (count >= total ? ', komplet' : ''));
        if (fresh) parts.push('nowa mapa');
        return parts.join(' — ');
    }

    function daySum(day) {
        var products = active().coverage && active().coverage.total;
        var hours = active().coverage && active().coverage.hours;
        if (!products || !hours || !day.clocks.length) return null;
        var got = 0;
        day.clocks.forEach(function (item) { got += hours[item.lead] || 0; });
        return { got: got, max: day.clocks.length * products };
    }

    function pad2(hour) {
        return String(hour).padStart(2, '0');
    }

    function hourGroups(clocks) {
        var byClock = {};
        clocks.forEach(function (item) { byClock[item.clock] = item.lead; });
        var present = Object.keys(byClock).map(function (clock) { return parseInt(clock, 10); });
        present.sort(function (a, b) { return a - b; });
        if (present.length < 2) {
            return present.map(function (hour) {
                return { start: hour, end: hour, lead: byClock[pad2(hour)], span: 1 };
            });
        }
        var step = 24;
        for (var i = 1; i < present.length; i++) step = Math.min(step, present[i] - present[i - 1]);
        if (step < 3) {
            var singles = [];
            for (var hour = 0; hour < 24; hour++) {
                singles.push({ start: hour, end: hour, lead: byClock[pad2(hour)] || '', span: 1 });
            }
            return singles;
        }
        return present.map(function (hour) {
            var start = hour - step + 1;
            if (start < 0) start += 24;
            return { start: start, end: hour, lead: byClock[pad2(hour)], span: step };
        });
    }

    function groupLabel(group) {
        var start = pad2(group.start);
        var end = pad2(group.end);
        return start === end ? start : start + '-' + end;
    }

    function fillState(count) {
        var total = active().coverage && active().coverage.total;
        if (!total || !count) return '';
        return count >= total ? 'complete' : 'partial';
    }

    function markFill(button, stateName) {
        if (stateName === 'complete') button.classList.add('is-complete');
        else if (stateName === 'partial') button.classList.add('is-partial');
    }

    function setTip(button, text) {
        if (!text) return;
        button.title = text;
        button.setAttribute('data-tip', text);
    }

    function utcNow() {
        var now = new Date();
        var pad = function (n) { return String(n).padStart(2, '0'); };
        return {
            date: pad(now.getUTCDate()) + '.' + pad(now.getUTCMonth() + 1) + '.' + now.getUTCFullYear(),
            clock: pad(now.getUTCHours())
        };
    }

    function watchingNow() {
        var current = slot();
        if (!current || !current.day) return false;
        var now = utcNow();
        if (current.day.date !== now.date) return false;
        if (current.clock === now.clock) return true;
        var clocks = current.day.clocks.map(function (item) { return item.clock; });
        if (clocks.indexOf(now.clock) !== -1) return false;
        var edge = '';
        clocks.forEach(function (clock) {
            if (clock <= now.clock && clock > edge) edge = clock;
        });
        return current.clock === edge;
    }

    function newestRun() {
        for (var i = 0; i < runSelect.options.length; i++) {
            if (/^\d{4}\.\d{2}\.\d{2}\.\d{2}$/.test(runSelect.options[i].value)) return runSelect.options[i].value;
        }
        return '';
    }

    function followLive() {
        return watchingNow() || (state.startup && state.run === newestRun());
    }

    var liveTimer = null;
    var liveGen = 0;

    function armLive() {
        liveGen += 1;
        if (liveTimer) clearTimeout(liveTimer);
        liveTimer = null;
        if (!followLive() || !root.isConnected) return;
        var gen = liveGen;
        liveTimer = setTimeout(function () {
            liveTimer = null;
            if (gen !== liveGen || !root.isConnected || !followLive()) return;
            var left = state.layout;
            for (var i = 0; i < state.layout; i++) {
                loadFrames(i, true, true, function () {
                    if (gen !== liveGen) return;
                    if (--left === 0) armLive();
                });
            }
        }, 10000);
    }

    function syncRuns(runs) {
        if (!runs || !runs.length) return;
        var current = Array.prototype.map.call(runSelect.options, function (option) { return option.value; }).join('|');
        if (current === runs.join('|')) return;
        var selected = state.run;
        runSelect.innerHTML = '';
        runs.forEach(function (run) {
            var option = document.createElement('option');
            option.value = run;
            option.textContent = run;
            if (run === selected) option.selected = true;
            runSelect.appendChild(option);
        });
    }

    function applyFrames(index, data, keepLead, baseline) {
        var item = pane(index);
        var stamps = stampsOf(data.stamps);
        item.hours = leadList(data.hours);
        item.days = data.days || [];
        item.count = data.count || 0;
        if (!item.fresh) item.fresh = {};
        if (!baseline) {
            item.fresh = {};
        } else {
            Object.keys(stamps).forEach(function (lead) {
                if (!baseline[lead] || baseline[lead] !== stamps[lead]) item.fresh[lead] = true;
            });
        }
        item.stamps = stamps;
        item.coverage = coverageOf(data.coverage);
        if (index === state.active) {
            state.lead = asLead(state.lead);
            if (keepLead && item.hours.indexOf(state.lead) === -1) {
                state.lead = nearest(item.hours, state.lead);
            }
            if (!keepLead) state.lead = item.hours[0] || '';
            if (item.fresh[state.lead]) delete item.fresh[state.lead];
            syncRuns(data.runs);
            if (clockSignature() !== item.clockKey) renderClock();
        }
        paint(index);
    }

    function loadFrames(index, keepLead, live, done) {
        var item = pane(index);
        if (!state.run) {
            if (done) done();
            return;
        }
        var baseline = live ? Object.assign({}, item.stamps || {}) : null;
        item.req = (item.req || 0) + 1;
        var req = item.req;
        $.getJSON(boot.framesBase + '/' + state.run + '/' + item.region + '/' + item.product + '?tab=' + encodeURIComponent(currentTab()))
            .done(function (data) {
                if (!root.isConnected || item.req !== req) return;
                applyFrames(index, data, keepLead, baseline);
            })
            .always(function () {
                if (done) done();
                else if (!live && index === state.active && root.isConnected) armLive();
            });
    }

    function nearest(hours, lead) {
        if (!hours.length) return '';
        if (hours.indexOf(lead) !== -1) return lead;
        var target = parseInt(lead, 10);
        var best = hours[0];
        var bestDiff = 9999;
        hours.forEach(function (hour) {
            var diff = Math.abs(parseInt(hour, 10) - target);
            if (diff < bestDiff) { best = hour; bestDiff = diff; }
        });
        return best;
    }

    function slot() {
        var days = active().days || [];
        for (var i = 0; i < days.length; i++) {
            for (var j = 0; j < days[i].clocks.length; j++) {
                if (asLead(days[i].clocks[j].lead) === asLead(state.lead)) {
                    return { day: days[i], clock: days[i].clocks[j].clock };
                }
            }
        }
        return null;
    }

    function renderClock() {
        var current = slot();
        var daysBox = root.querySelector('.gfs-days');
        var hoursBox = root.querySelector('.gfs-hours');
        daysBox.innerHTML = '';
        hoursBox.innerHTML = '';
        (active().days || []).forEach(function (day) {
            var button = document.createElement('button');
            button.type = 'button';
            var name = document.createElement('span');
            name.className = 'gfs-day-name';
            name.textContent = day.label;
            var when = document.createElement('span');
            when.className = 'gfs-day-date';
            when.textContent = shortDate(day.date);
            button.appendChild(name);
            button.appendChild(when);
            var dayFresh = day.clocks.some(function (item) { return active().fresh && active().fresh[item.lead]; });
            var sum = daySum(day);
            var dayText = sum ? (sum.got + ' z ' + sum.max + ' map' + (sum.got >= sum.max ? ', komplet' : '')) : '';
            if (dayFresh && dayText) dayText += ' — nowa mapa';
            var dayTip = [day.date, dayText].filter(Boolean).join(' — ');
            setTip(button, dayTip);
            markFill(button, !sum ? '' : (sum.got >= sum.max ? 'complete' : 'partial'));
            if (dayFresh) button.classList.add('is-fresh');
            if (current && current.day.date === day.date) button.classList.add('is-on');
            button.addEventListener('click', function () {
                stopPlay();
                pickDay(day);
            });
            daysBox.appendChild(button);
        });
        var groups = hourGroups(current ? current.day.clocks : []);
        groups.forEach(function (group) {
            var button = document.createElement('button');
            button.type = 'button';
            button.textContent = groupLabel(group);
            var lead = group.lead;
            if (!lead) button.disabled = true;
            if (group.span > 1) {
                button.classList.add('is-range');
                button.style.flexGrow = String(group.span);
            }
            var hourFresh = !!(lead && active().fresh && active().fresh[lead]);
            var hourCount = lead && active().coverage ? active().coverage.hours[lead] : 0;
            setTip(button, describeMaps(hourCount, hourFresh));
            markFill(button, fillState(hourCount));
            if (hourFresh) button.classList.add('is-fresh');
            if (current && parseInt(current.clock, 10) === group.end) button.classList.add('is-on');
            button.addEventListener('click', function () {
                if (!lead) return;
                stopPlay();
                setLead(lead);
            });
            hoursBox.appendChild(button);
        });
        root.querySelector('.gfs-valid-date').textContent = current ? current.day.date : '';
        root.querySelector('.gfs-valid-time').textContent = current ? (current.clock + ':00Z (+' + state.lead + ')') : '';
        root.querySelector('.gfs-stan').textContent = 'Stan: ' + (active().count || 0);
        var status = root.querySelector('.gfs-status-line');
        status.textContent = active().count ? ('GFS: ' + state.run) : 'GFS: brak map';
        active().clockKey = clockSignature();
    }

    function pickDay(day) {
        var same = day.clocks.filter(function (item) { return slot() && item.clock === slot().clock; })[0];
        setLead((same || day.clocks[0]).lead);
    }

    function setLead(lead) {
        state.startup = false;
        state.lead = asLead(lead);
        var item = active();
        if (item.fresh) delete item.fresh[lead];
        renderClock();
        for (var i = 0; i < state.layout; i++) paint(i);
        armLive();
    }

    function step(dir) {
        var hours = active().hours;
        var index = hours.indexOf(state.lead);
        var next = index + dir;
        if (next < 0 || next >= hours.length) return false;
        setLead(hours[next]);
        return true;
    }

    var playDelays = [1, 2, 3, 5, 10];
    var playKey = 'cumulus_gfs_autoplay_delay_sec';
    var playTimer = null;
    var playDir = 0;

    function readDelay() {
        try {
            var sec = parseInt(localStorage.getItem(playKey), 10);
            if (playDelays.indexOf(sec) !== -1) return sec;
        } catch (e) {}
        return 3;
    }

    function writeDelay(sec) {
        try { localStorage.setItem(playKey, String(sec)); } catch (e) {}
    }

    function delayMs() {
        var select = root.querySelector('.gfs-delay-select');
        var sec = select ? parseInt(select.value, 10) : 3;
        return (playDelays.indexOf(sec) === -1 ? 3 : sec) * 1000;
    }

    function syncPlayButtons() {
        root.querySelectorAll('.gfs-auto').forEach(function (button) {
            var dir = parseInt(button.dataset.auto, 10);
            var on = playDir === dir;
            button.classList.toggle('is-playing', on);
            button.textContent = on ? 'II' : (dir < 0 ? '◀' : '▶');
            var label = on
                ? 'Zatrzymaj przewijanie'
                : (dir < 0 ? 'Przewijaj automatycznie wstecz' : 'Przewijaj automatycznie w przód');
            button.title = label;
            button.setAttribute('aria-label', label);
        });
    }

    function stopPlay() {
        if (playTimer) {
            clearInterval(playTimer);
            playTimer = null;
        }
        playDir = 0;
        syncPlayButtons();
    }

    function tickPlay() {
        if (!document.body.contains(root)) {
            stopPlay();
            return;
        }
        var hours = active().hours || [];
        if (hours.indexOf(state.lead) < 0 || !step(playDir)) stopPlay();
    }

    function startPlay(dir) {
        if (playDir === dir) {
            stopPlay();
            return;
        }
        if (playTimer) {
            clearInterval(playTimer);
            playTimer = null;
        }
        playDir = 0;
        if (!step(dir)) {
            syncPlayButtons();
            return;
        }
        playDir = dir;
        syncPlayButtons();
        playTimer = setInterval(tickPlay, delayMs());
    }

    if (window.__gfsStopPlay) window.__gfsStopPlay();
    window.__gfsStopPlay = stopPlay;

    function markProduct() {
        root.querySelectorAll('.gfs-param').forEach(function (button) {
            button.classList.toggle('is-on', button.dataset.id === active().product);
        });
    }

    function markRegion() {
        root.querySelectorAll('.gfs-regions button').forEach(function (button) {
            button.classList.toggle('is-on', button.dataset.region === active().region);
        });
    }

    root.querySelectorAll('.gfs-rail-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            root.querySelectorAll('.gfs-rail-btn').forEach(function (node) {
                node.classList.toggle('is-on', node === button);
            });
            root.querySelectorAll('.gfs-product-tab').forEach(function (node) {
                node.hidden = node.dataset.tab !== button.dataset.tab;
            });
        });
    });
    root.querySelectorAll('.gfs-param').forEach(function (button) {
        button.addEventListener('click', function () {
            active().product = button.dataset.id;
            markProduct();
            loadFrames(state.active, true);
        });
    });
    root.querySelectorAll('.gfs-regions button').forEach(function (button) {
        button.addEventListener('click', function () {
            active().region = button.dataset.region;
            markRegion();
            loadFrames(state.active, true);
        });
    });
    runSelect.addEventListener('change', function () {
        state.startup = false;
        state.run = runSelect.value;
        for (var i = 0; i < state.layout; i++) loadFrames(i, true);
    });
    root.querySelector('.gfs-layout').addEventListener('change', function (event) {
        ensurePanes(parseInt(event.target.value, 10));
    });
    root.querySelector('.gfs-zoom input').addEventListener('input', function (event) {
        var item = active();
        item.zoom = parseInt(event.target.value, 10) || 0;
        item.panX = null;
        place(state.active);
    });
    var delaySelect = root.querySelector('.gfs-delay-select');
    if (delaySelect) {
        delaySelect.value = String(readDelay());
        delaySelect.addEventListener('change', function () {
            var sec = parseInt(delaySelect.value, 10);
            if (playDelays.indexOf(sec) === -1) sec = 3;
            delaySelect.value = String(sec);
            writeDelay(sec);
            if (!playDir) return;
            if (playTimer) clearInterval(playTimer);
            playTimer = setInterval(tickPlay, sec * 1000);
        });
    }
    root.querySelectorAll('.gfs-auto').forEach(function (button) {
        button.addEventListener('click', function () {
            startPlay(parseInt(button.dataset.auto, 10));
        });
    });
    root.querySelectorAll('.gfs-step button[data-dir]').forEach(function (button) {
        button.addEventListener('click', function () {
            stopPlay();
            step(parseInt(button.dataset.dir, 10));
        });
    });
    var screenBtn = root.querySelector('.gfs-screen');
    function setScreen(full) {
        root.classList.toggle('is-full', full);
        screenBtn.textContent = full ? 'POWRÓT' : 'PEŁNY EKRAN';
        screenBtn.title = full ? 'Wróć do widoku panelu' : 'Rozciągnij mapy na cały ekran';
        window.requestAnimationFrame(function () {
            for (var i = 0; i < state.layout; i++) place(i);
        });
    }
    screenBtn.addEventListener('click', function () {
        setScreen(!root.classList.contains('is-full'));
    });
    $(document).off('keydown.gfs').on('keydown.gfs', function (event) {
        if (!document.getElementById('gfs-app')) return;
        var tag = event.target && event.target.tagName;
        if (tag === 'SELECT' || tag === 'INPUT' || tag === 'TEXTAREA') return;
        if (event.key === 'Escape' && root.classList.contains('is-full')) setScreen(false);
        if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') stopPlay();
        if (event.key === 'ArrowRight') step(1);
        if (event.key === 'ArrowLeft') step(-1);
    });
    $(window).off('resize.gfs').on('resize.gfs', function () {
        if (!document.getElementById('gfs-app')) return;
        for (var i = 0; i < state.layout; i++) place(i);
    });
    $(document).off('visibilitychange.gfs').on('visibilitychange.gfs', function () {
        if (document.hidden) stopPlay();
    });

    renderPanes();
    renderClock();
    armLive();
}
