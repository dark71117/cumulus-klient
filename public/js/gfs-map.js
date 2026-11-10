function initGfsMap() {
    var root = document.getElementById('gfs-app');
    var bootNode = document.getElementById('gfs-boot');
    if (!root || !bootNode || root.dataset.ready === '1') return;
    root.dataset.ready = '1';
    var boot = JSON.parse(bootNode.textContent);
    var state = {
        run: boot.run || '',
        lead: boot.lead || '',
        layout: 1,
        active: 0,
        panes: [{
            region: boot.region || 'Polska',
            product: boot.product || 'synop3',
            hours: (boot.frames && boot.frames.hours) || [],
            days: (boot.frames && boot.frames.days) || [],
            count: (boot.frames && boot.frames.count) || 0,
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

    function ensurePanes(count) {
        while (state.panes.length < count) {
            var copy = Object.assign({}, state.panes[0]);
            copy.zoom = 0;
            copy.panX = 0;
            copy.panY = 0;
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
        return boot.imageBase + '/' + state.run + '/' + item.region + '/' + item.product + '/' + state.lead;
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
            img.onload = function () { place(index); };
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

    function loadFrames(index, keepLead) {
        var item = pane(index);
        if (!state.run) return;
        $.getJSON(boot.framesBase + '/' + state.run + '/' + item.region + '/' + item.product)
            .done(function (data) {
                item.hours = data.hours || [];
                item.days = data.days || [];
                item.count = data.count || 0;
                if (index === state.active) {
                    if (keepLead && item.hours.indexOf(state.lead) === -1) {
                        state.lead = nearest(item.hours, state.lead);
                    }
                    if (!keepLead) state.lead = item.hours[0] || '';
                    renderClock();
                }
                paint(index);
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
                if (days[i].clocks[j].lead === state.lead) {
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
            button.textContent = day.label;
            button.title = day.date;
            if (current && current.day.date === day.date) button.className = 'is-on';
            button.addEventListener('click', function () { pickDay(day); });
            daysBox.appendChild(button);
        });
        var clocks = {};
        if (current) {
            current.day.clocks.forEach(function (item) { clocks[item.clock] = item.lead; });
        }
        for (var hour = 0; hour < 24; hour++) {
            var label = String(hour).padStart(2, '0');
            var button = document.createElement('button');
            button.type = 'button';
            button.textContent = label;
            if (!clocks[label]) button.disabled = true;
            if (current && current.clock === label) button.className = 'is-on';
            button.addEventListener('click', function (event) {
                var key = event.currentTarget.textContent;
                if (clocks[key]) setLead(clocks[key]);
            });
            hoursBox.appendChild(button);
        }
        root.querySelector('.gfs-valid-date').textContent = current ? current.day.date : '';
        root.querySelector('.gfs-valid-time').textContent = current ? (current.clock + ':00Z (+' + state.lead + ')') : '';
        root.querySelector('.gfs-stan').textContent = 'Stan: ' + (active().count || 0);
        var status = root.querySelector('.gfs-status-line');
        status.textContent = active().count ? ('GFS: ' + state.run) : 'GFS: brak map';
    }

    function pickDay(day) {
        var same = day.clocks.filter(function (item) { return slot() && item.clock === slot().clock; })[0];
        setLead((same || day.clocks[0]).lead);
    }

    function setLead(lead) {
        state.lead = lead;
        renderClock();
        for (var i = 0; i < state.layout; i++) paint(i);
    }

    function step(dir) {
        var hours = active().hours;
        var index = hours.indexOf(state.lead);
        var next = index + dir;
        if (next < 0 || next >= hours.length) return;
        setLead(hours[next]);
    }

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
    root.querySelectorAll('.gfs-step button').forEach(function (button) {
        button.addEventListener('click', function () { step(parseInt(button.dataset.dir, 10)); });
    });
    $(document).off('keydown.gfs').on('keydown.gfs', function (event) {
        if (!document.getElementById('gfs-app')) return;
        var tag = event.target && event.target.tagName;
        if (tag === 'SELECT' || tag === 'INPUT' || tag === 'TEXTAREA') return;
        if (event.key === 'ArrowRight') step(1);
        if (event.key === 'ArrowLeft') step(-1);
    });
    $(window).off('resize.gfs').on('resize.gfs', function () {
        if (!document.getElementById('gfs-app')) return;
        for (var i = 0; i < state.layout; i++) place(i);
    });

    renderPanes();
    renderClock();
}
