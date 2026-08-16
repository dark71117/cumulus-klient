var imgwTableFrames = [];
var imgwTableHourIndex = 0;
var imgwTablePlayTimer = null;
var imgwTablePlayDir = 0;
var imgwTablePlayToken = 0;

function parseImgwTableFrames() {
    var dataEl = document.getElementById('imgw-table-frames');
    if (!dataEl) {
        return [];
    }
    try {
        var frames = JSON.parse(dataEl.textContent || '[]');
        return Array.isArray(frames) ? frames : [];
    } catch (e) {
        return [];
    }
}

function renderImgwTableHour(index) {
    var $root = $('.imgw-table-new');
    if (!$root.length || !imgwTableFrames[index]) {
        return;
    }
    imgwTableHourIndex = index;
    var frame = imgwTableFrames[index];
    $root.attr('data-hour', frame.hour || '');
    if (imgwDtTables.length) {
        updateImgwTableRows(frame.rows || []);
    } else {
        fillImgwTableBodies(frame.rows || []);
        startImgwTableGrid($root);
    }
    applyImgwTableFilters($root);
    syncImgwTableHourUi();
}

function updateImgwTableRows(rows) {
    var pl = [];
    var eu = [];
    rows.forEach(function (row) {
        if (Number(row.europe) === 1) {
            eu.push(row);
        } else {
            pl.push(row);
        }
    });
    imgwDtTables.forEach(function (api) {
        var id = api.table().node().id;
        var subset = id === 'imgw-datatable-eu' ? eu : pl;
        var nodes = subset.map(function (row) {
            return $(imgwTableRowHtml(row)).get(0);
        }).filter(Boolean);
        api.clear();
        if (nodes.length) {
            api.rows.add(nodes);
        }
    });
}

function fillImgwTableBodies(rows) {
    var pl = [];
    var eu = [];
    rows.forEach(function (row) {
        if (Number(row.europe) === 1) {
            eu.push(row);
        } else {
            pl.push(row);
        }
    });
    var $pl = $('#imgw-datatable-pl tbody');
    var $eu = $('#imgw-datatable-eu tbody');
    if ($pl.length) {
        $pl.html(pl.map(imgwTableRowHtml).join(''));
    }
    if ($eu.length) {
        $eu.html(eu.map(imgwTableRowHtml).join(''));
    }
}

function imgwTableRowHtml(row) {
    var delayHours = parseInt(row.godzina, 10) || 0;
    var delay = delayHours === 1 ? ' (1 h)' : (delayHours === 2 ? ' (2 h)' : '');
    var cityClass = [];
    if (row.imgwCity && delayHours === 0) {
        cityClass.push('imgwCity');
    }
    if (delayHours === 1) {
        cityClass.push('imgw-delay-1');
    }
    if (delayHours === 2) {
        cityClass.push('imgw-delay-2');
    }
    var title = delayHours === 1 ? ' title="Dane sprzed godziny"' : (delayHours === 2 ? ' title="Dane sprzed dwóch godzin"' : '');
    var cityAttr = cityClass.length ? ' class="' + cityClass.join(' ') + '"' : '';
    var temp = row.temp == null ? '-' : String(row.temp);
    var tempOdcz = row.tempOdcz == null ? '' : String(row.tempOdcz);
    var zjawisko = row.zjawiskoTXT || '';
    return '<tr class="' + imgwEsc(row.imgwRow || 'imgwRow') + '">' +
        '<td>' + imgwEsc(row.region) + '</td>' +
        '<td' + cityAttr + ' data-export="' + imgwEsc(String(row.nazwaStacji || '') + delay) + '"' + title + '>' + imgwEsc(row.nazwaStacji) + '</td>' +
        '<td data-order="' + imgwEsc(temp === '-' ? '-999' : temp) + '">' + imgwEsc(temp) + '</td>' +
        '<td data-order="' + imgwEsc(tempOdcz === '' ? '-999' : tempOdcz) + '">' + imgwEsc(tempOdcz) + '</td>' +
        '<td>' + imgwEsc(row.zachmurzenieTXT) + '</td>' +
        '<td data-export="' + imgwEsc(imgwPlain(zjawisko)) + '">' + zjawisko + '</td>' +
        '<td>' + imgwEsc(row.widzialnosc) + '</td>' +
        '<td>' + imgwEsc(row.wiatr) + '</td>' +
        '</tr>';
}

function imgwEsc(text) {
    return String(text == null ? '' : text).replace(/[&<>"']/g, function (ch) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
}

function imgwPlain(html) {
    return String(html || '').replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim();
}

function bindImgwTableNav($root) {
    var jump = document.getElementById('imgw-table-jump');
    if (jump) {
        jump.addEventListener('change', function () {
            var idx = parseInt(jump.value, 10);
            if (isNaN(idx) || !imgwTableFrames[idx]) {
                return;
            }
            stopImgwTablePlay();
            renderImgwTableHour(idx);
        });
    }
    var play = document.getElementById('imgw-table-play');
    if (play) {
        play.querySelectorAll('.imgw-map-play-btn[data-dir]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var dir = parseInt(btn.getAttribute('data-dir'), 10) || 1;
                if (imgwTablePlayDir === dir) {
                    stopImgwTablePlay();
                    return;
                }
                stepImgwTableHour(dir, true);
                startImgwTablePlay(dir);
            });
        });
    }
    var pause = document.getElementById('imgw-table-pause');
    if (pause) {
        pause.addEventListener('click', function () {
            stopImgwTablePlay();
        });
    }
    var delay = document.getElementById('imgw-table-delay');
    if (delay) {
        delay.addEventListener('change', function () {
            if (imgwTablePlayDir) {
                startImgwTablePlay(imgwTablePlayDir);
            }
        });
    }
    $root.find('.imgw-table-step').on('click', function () {
        if (this.disabled) {
            return;
        }
        stopImgwTablePlay();
        stepImgwTableHour(parseInt(this.getAttribute('data-dir'), 10) || 1, false);
    });
}

function imgwTablePlayDelayMs() {
    var delayEl = document.getElementById('imgw-table-delay');
    var ms = delayEl ? parseInt(delayEl.value, 10) : 3000;
    if (!ms || ms < 500) {
        ms = 3000;
    }
    return ms;
}

function startImgwTablePlay(dir) {
    stopImgwTablePlay(true);
    imgwTablePlayDir = dir;
    syncImgwTablePlayButtons();
    queueImgwTablePlay();
}

function queueImgwTablePlay() {
    if (!imgwTablePlayDir) {
        return;
    }
    var token = ++imgwTablePlayToken;
    if (imgwTablePlayTimer) {
        clearTimeout(imgwTablePlayTimer);
        imgwTablePlayTimer = null;
    }
    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            if (token !== imgwTablePlayToken || !imgwTablePlayDir) {
                return;
            }
            imgwTablePlayTimer = setTimeout(function () {
                imgwTablePlayTimer = null;
                if (token !== imgwTablePlayToken || !imgwTablePlayDir) {
                    return;
                }
                stepImgwTableHour(imgwTablePlayDir, true);
                queueImgwTablePlay();
            }, imgwTablePlayDelayMs());
        });
    });
}

function stopImgwTablePlay(keepDir) {
    imgwTablePlayToken += 1;
    if (imgwTablePlayTimer) {
        clearTimeout(imgwTablePlayTimer);
        imgwTablePlayTimer = null;
    }
    if (!keepDir) {
        imgwTablePlayDir = 0;
        syncImgwTablePlayButtons();
    }
}

function stepImgwTableHour(dir, wrap) {
    if (!imgwTableFrames.length) {
        return;
    }
    var next = imgwTableHourIndex + dir;
    if (next >= imgwTableFrames.length) {
        next = wrap ? 0 : imgwTableFrames.length - 1;
    } else if (next < 0) {
        next = wrap ? imgwTableFrames.length - 1 : 0;
    }
    renderImgwTableHour(next);
}

function syncImgwTableHourUi() {
    if (!imgwTableFrames.length) {
        document.querySelectorAll('.imgw-table-step').forEach(function (btn) {
            btn.disabled = true;
        });
        return;
    }
    var frame = imgwTableFrames[imgwTableHourIndex] || {};
    var hourEl = document.getElementById('imgw-table-hour');
    if (hourEl) {
        hourEl.textContent = (frame.hour || '') + (frame.date ? ', ' + frame.date : '');
    }
    var jump = document.getElementById('imgw-table-jump');
    if (jump) {
        jump.value = String(imgwTableHourIndex);
    }
    document.querySelectorAll('.imgw-table-step').forEach(function (btn) {
        var dir = parseInt(btn.getAttribute('data-dir'), 10) || 1;
        var next = imgwTableHourIndex + dir;
        btn.disabled = next < 0 || next >= imgwTableFrames.length;
    });
    syncImgwTablePlayButtons();
}

function syncImgwTablePlayButtons() {
    var root = document.getElementById('imgw-table-play');
    if (!root) {
        return;
    }
    root.querySelectorAll('.imgw-map-play-btn[data-dir]').forEach(function (btn) {
        var dir = parseInt(btn.getAttribute('data-dir'), 10);
        btn.classList.toggle('is-active', imgwTablePlayDir === dir);
    });
    var pause = document.getElementById('imgw-table-pause');
    if (pause) {
        pause.classList.toggle('is-active', imgwTablePlayDir !== 0);
        pause.disabled = imgwTablePlayDir === 0;
    }
}
