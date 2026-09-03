var analizaRequest = null;

function destroyAnaliza() {
    if (analizaRequest) {
        analizaRequest.abort();
        analizaRequest = null;
    }
    closeAnalizaExplain();
    $(document).off('keydown.analizaExplain');
    destroyAnalizaTables();
}

function destroyAnalizaTables() {
    ['analiza-datatable', 'analiza-stats-table'].forEach(function (id) {
        var $table = $('#' + id);
        if ($table.length && $.fn.dataTable && $.fn.dataTable.isDataTable($table)) {
            $table.DataTable().destroy();
        }
    });
    $('.analiza-app .imgw-dt-export').empty();
}

function initAnaliza() {
    var $root = $('.analiza-app');
    if (!$root.length) {
        return;
    }
    bindAnaliza($root);
    startAnalizaTable($root);
}

function bindAnaliza($root) {
    $root.find('#analiza-source').off('change.analiza').on('change.analiza', function () {
        $root.attr('data-source', this.value);
        loadAnalizaHour($root, $root.attr('data-termin') || '');
    });
    $root.find('#analiza-mode').off('change.analiza').on('change.analiza', function () {
        var mode = this.value;
        $root.attr('data-mode', mode);
        $root.find('.analiza-hour-controls').prop('hidden', mode !== 'hour');
        $root.find('.analiza-stats-controls').prop('hidden', mode !== 'stats');
        if (mode === 'hour') {
            loadAnalizaHour($root, $root.attr('data-termin') || '');
        } else {
            $root.find('#analiza-body').html('<p class="analiza-empty">Wybierz stację i zakres, potem kliknij „Licz min / max / średnia”.</p>');
            destroyAnalizaTables();
        }
    });
    $root.find('#analiza-prev').off('click.analiza').on('click.analiza', function () {
        var prev = $root.attr('data-prev');
        if (prev) {
            loadAnalizaHour($root, prev);
        }
    });
    $root.find('#analiza-next').off('click.analiza').on('click.analiza', function () {
        var next = $root.attr('data-next');
        if (next) {
            loadAnalizaHour($root, next);
        }
    });
    $root.find('#analiza-latest').off('click.analiza').on('click.analiza', function () {
        var latest = $root.attr('data-latest') || this.getAttribute('data-termin');
        if (latest) {
            loadAnalizaHour($root, latest);
        }
    });
    $root.find('#analiza-termin').off('change.analiza').on('change.analiza', function () {
        if (this.value) {
            loadAnalizaHour($root, this.value);
        }
    });
    $root.find('#analiza-show-desc').off('change.analiza').on('change.analiza', function () {
        $root.toggleClass('analiza-hide-desc', !this.checked);
        var api = analizaTableApi();
        if (api) {
            api.columns('.analiza-desc').visible(this.checked);
        }
    });
    $root.find('#analiza-stats-run').off('click.analiza').on('click.analiza', function () {
        loadAnalizaStats($root);
    });
    $root.off('click.analizaExplain').on('click.analizaExplain', '.analiza-synop-open', function () {
        openAnalizaExplain($(this));
    });
    $root.off('click.analizaModal').on('click.analizaModal', '[data-analiza-close]', function () {
        closeAnalizaExplain();
    });
    $root.off('click.analizaChip').on('click.analizaChip', '.analiza-explain-chip', function () {
        highlightAnalizaExplain($(this).attr('data-i'));
    });
    $(document).off('keydown.analizaExplain').on('keydown.analizaExplain', function (e) {
        if (e.key === 'Escape') {
            closeAnalizaExplain();
        }
    });
}

function loadAnalizaHour($root, termin) {
    postAnaliza($root, {
        mode: 'hour',
        source: $root.find('#analiza-source').val() || 'ogimet',
        termin: termin
    }, function (res) {
        applyAnalizaMeta($root, res);
        $root.find('#analiza-body').html(res.html);
        startAnalizaTable($root);
    });
}

function loadAnalizaStats($root) {
    postAnaliza($root, {
        mode: 'stats',
        source: $root.find('#analiza-source').val() || 'ogimet',
        station: $root.find('#analiza-station').val() || '',
        from: $root.find('#analiza-from').val() || '',
        to: $root.find('#analiza-to').val() || ''
    }, function (res) {
        $root.find('#analiza-body').html(res.html);
        startAnalizaTable($root);
    });
}

function applyAnalizaMeta($root, res) {
    var meta = res.meta || {};
    $root.attr('data-termin', meta.termin || '');
    $root.attr('data-prev', meta.prev || '');
    $root.attr('data-next', meta.next || '');
    $root.attr('data-latest', meta.latest || '');
    $root.find('#analiza-prev').prop('disabled', !meta.prev);
    $root.find('#analiza-next').prop('disabled', !meta.next);
    $root.find('#analiza-latest').prop('disabled', !meta.latest);
    if (meta.termin) {
        $root.find('#analiza-termin').val(meta.termin.replace(' ', 'T').slice(0, 16));
        var d = meta.termin.replace('T', ' ').slice(0, 16).split(/[- :]/);
        if (d.length >= 5) {
            $root.find('#analiza-hour-label').text(d[2] + '.' + d[1] + '.' + d[0] + ', ' + d[3] + ':' + d[4]);
        }
    }
}

function postAnaliza($root, data, onOk) {
    if (analizaRequest) {
        analizaRequest.abort();
    }
    $root.addClass('is-loading');
    analizaRequest = $.ajax({
        cache: false,
        type: 'POST',
        url: klientUrl('/analiza'),
        data: data,
        dataType: 'json',
        error: function (xhr, status) {
            if (status === 'abort') {
                return;
            }
            $root.find('#analiza-body').html('<p class="analiza-empty">Nie udało się wczytać analizy.</p>');
        },
        success: function (res) {
            if (!res || !res.ok) {
                $root.find('#analiza-body').html('<p class="analiza-empty">Nie udało się wczytać analizy.</p>');
                return;
            }
            onOk(res);
        },
        complete: function () {
            $root.removeClass('is-loading');
            analizaRequest = null;
        }
    });
}

function analizaTableApi() {
    var $table = $('#analiza-datatable');
    if ($table.length && $.fn.dataTable && $.fn.dataTable.isDataTable($table)) {
        return $table.DataTable();
    }
    return null;
}

function startAnalizaTable($root) {
    destroyAnalizaTables();
    var $table = $root.find('#analiza-datatable, #analiza-stats-table').first();
    if (!$table.length || !$.fn.DataTable) {
        return;
    }
    var showDesc = $root.find('#analiza-show-desc').is(':checked');
    $root.toggleClass('analiza-hide-desc', !showDesc);
    var api = $table.DataTable({
        autoWidth: false,
        scrollX: true,
        pageLength: 100,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'max']],
        order: [],
        stripeClasses: [],
        layout: {
            topStart: null,
            topEnd: null,
            bottomStart: ['pageLength', 'info'],
            bottomEnd: 'paging'
        },
        language: {
            lengthMenu: 'Pokaż _MENU_',
            info: '_START_–_END_ z _TOTAL_',
            infoEmpty: 'Brak danych',
            infoFiltered: '(z _MAX_)',
            zeroRecords: 'Brak wyników',
            emptyTable: 'Brak danych',
            paginate: { first: 'Pierwsza', last: 'Ostatnia', next: '›', previous: '‹' }
        }
    });
    if ($table.attr('id') === 'analiza-datatable') {
        api.columns('.analiza-desc').visible(showDesc);
        $root.find('#analiza-search').on('keyup search', function () {
            api.search(this.value).draw();
        });
    }
    if ($.fn.dataTable.Buttons) {
        new $.fn.dataTable.Buttons(api, {
            buttons: [
                { extend: 'excelHtml5', text: 'XLS', title: 'analiza-synop', filename: 'analiza-synop' },
                { extend: 'pdfHtml5', text: 'PDF', title: 'analiza-synop', filename: 'analiza-synop', orientation: 'landscape', pageSize: 'A4' }
            ]
        });
        api.buttons().container().appendTo($root.find('.imgw-dt-export'));
    }
}

function openAnalizaExplain($btn) {
    var $modal = $('#analiza-explain-modal');
    var synop = $btn.attr('data-synop') || '';
    if (!$modal.length || synop === '') {
        return;
    }
    $modal.find('#analiza-explain-body').html('<p class="analiza-empty">Rozbijam depeszę…</p>');
    $modal.prop('hidden', false);
    $.ajax({
        cache: false,
        type: 'POST',
        url: klientUrl('/analiza'),
        data: {
            mode: 'explain',
            synop: synop,
            station: $btn.attr('data-station') || '',
            station_id: $btn.attr('data-id') || '',
            termin: $btn.attr('data-termin') || ''
        },
        dataType: 'json',
        error: function () {
            $modal.find('#analiza-explain-body').html('<p class="analiza-empty">Nie udało się rozbić depeszy.</p>');
        },
        success: function (res) {
            if (!res || !res.ok) {
                $modal.find('#analiza-explain-body').html('<p class="analiza-empty">Nie udało się rozbić depeszy.</p>');
                return;
            }
            $modal.find('#analiza-explain-body').html(res.html);
        }
    });
}

function closeAnalizaExplain() {
    var $modal = $('#analiza-explain-modal');
    if ($modal.length) {
        $modal.prop('hidden', true);
        $modal.find('#analiza-explain-body').empty();
    }
}

function highlightAnalizaExplain(i) {
    var $modal = $('#analiza-explain-modal');
    $modal.find('.analiza-explain-chip, .analiza-explain-item').removeClass('is-active');
    $modal.find('.analiza-explain-chip[data-i="' + i + '"], .analiza-explain-item[data-i="' + i + '"]').addClass('is-active');
    var el = $modal.find('.analiza-explain-item[data-i="' + i + '"]')[0];
    if (el && el.scrollIntoView) {
        el.scrollIntoView({ block: 'nearest' });
    }
}
