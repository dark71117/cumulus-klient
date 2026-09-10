var imgwDtTables = [];

function destroyImgwDataTable() {
    stopImgwTablePlay();
    destroyImgwTableInstances();
    imgwTableFrames = [];
    imgwTableHourIndex = 0;
}

function destroyImgwTableInstances() {
    $(window).off('resize.imgwTable');
    imgwDtTables.forEach(function (api) {
        var node = api && api.table && api.table().node();
        if (node && $.fn.dataTable && $.fn.dataTable.isDataTable(node)) {
            api.destroy();
        }
    });
    imgwDtTables = [];
    $('.imgw-datatable').each(function () {
        if ($.fn.dataTable && $.fn.dataTable.isDataTable(this)) {
            $(this).DataTable().destroy();
        }
    });
    $('.imgw-table-new').not('.analiza-app').find('.imgw-dt-export').empty();
}

function imgwTableRoot() {
    return $('.imgw-table-new').not('.analiza-app');
}

function initImgwDataTable() {
    destroyImgwDataTable();
    var $root = imgwTableRoot();
    if (!$root.length || !$.fn.DataTable) {
        return;
    }
    imgwTableFrames = parseImgwTableFrames();
    imgwTableHourIndex = parseInt($root.attr('data-current') || String(imgwTableFrames.length - 1), 10);
    if (imgwTableHourIndex < 0 || imgwTableHourIndex >= imgwTableFrames.length) {
        imgwTableHourIndex = Math.max(0, imgwTableFrames.length - 1);
    }
    ensureImgwRegionSearch();
    bindImgwTableNav($root);
    bindImgwSharedSearch($root);
    bindImgwSharedFilters($root);
    bindImgwRegionSwitch($root);
    startImgwTableGrid($root);
    syncImgwTableHourUi();
}

function startImgwTableGrid($root) {
    destroyImgwTableInstances();
    var filename = 'imgw-warunki-' + new Date().toISOString().slice(0, 10);
    var title = function () {
        return 'Warunki atmosferyczne o godzinie ' + ($root.attr('data-hour') || '');
    };
    var exportOpts = {
        columns: ':visible',
        modifier: { search: 'applied', order: 'applied', page: 'all' },
        format: {
            header: function (data) {
                return String(data || '').replace(/\s+/g, ' ').trim();
            },
            body: function (data, row, column, node) {
                var custom = node.getAttribute('data-export');
                if (custom !== null && custom !== '') {
                    return custom;
                }
                return String(node.textContent || '').replace(/\s+/g, ' ').trim();
            }
        }
    };
    var $table = $root.find('#imgw-datatable');
    if (!$table.length) {
        return;
    }
    var api = $table.DataTable({
        autoWidth: false,
        scrollX: true,
        scrollY: '200px',
        scrollCollapse: false,
        pageLength: -1,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'max']],
        order: [],
        orderClasses: false,
        stripeClasses: [],
        layout: {
            topStart: null,
            topEnd: null,
            bottomStart: ['pageLength', 'info'],
            bottomEnd: 'paging'
        },
        language: {
            lengthMenu: 'Pokaż _MENU_',
            info: '_START_–_END_ z _TOTAL_ stacji',
            infoEmpty: 'Brak danych',
            infoFiltered: '(z _MAX_)',
            zeroRecords: 'Brak wyników dla bieżącego filtra',
            emptyTable: 'Brak danych',
            paginate: { first: 'Pierwsza', last: 'Ostatnia', next: '›', previous: '‹' }
        }
    });
    $(api.table().container()).addClass('imgw-dt');
    imgwDtTables.push(api);
    bindImgwSharedExport($root, title, filename, exportOpts);
    api.on('draw', function () {
        fitImgwTableScroll($root);
    });
    var layoutScroll = function () {
        fitImgwTableScroll($root);
        api.columns.adjust();
    };
    layoutScroll();
    requestAnimationFrame(layoutScroll);
    $(window).on('resize.imgwTable', layoutScroll);
}

function applyImgwTableFilters($root) {
    var search = $root.find('#imgw-dt-search').val() || '';
    imgwDtTables.forEach(function (api) {
        api.search(search);
    });
    $root.find('.imgw-dt-shared-filters .imgw-dt-colfilter').each(function () {
        var col = parseInt(this.getAttribute('data-col'), 10);
        var value = this.value;
        imgwDtTables.forEach(function (api) {
            api.column(col).search(value);
        });
    });
    imgwDtTables.forEach(function (api) {
        api.draw();
    });
}

function bindImgwSharedSearch($root) {
    $root.find('#imgw-dt-search').on('keyup search', function () {
        var value = this.value;
        imgwDtTables.forEach(function (api) {
            api.search(value).draw();
        });
    });
}

function bindImgwSharedFilters($root) {
    $root.find('.imgw-dt-shared-filters .imgw-dt-colfilter').on('keyup search change', function () {
        var col = parseInt(this.getAttribute('data-col'), 10);
        var value = this.value;
        imgwDtTables.forEach(function (api) {
            api.column(col).search(value).draw();
        });
    });
}

function bindImgwRegionSwitch($root) {
    $root.find('.imgw-dt-region-btn').on('click', function () {
        var region = this.getAttribute('data-region') || 'all';
        $root.attr('data-region', region);
        $root.find('.imgw-dt-region-btn').removeClass('is-active');
        $(this).addClass('is-active');
        applyImgwTableFilters($root);
        imgwDtTables.forEach(function (api) {
            api.columns.adjust();
        });
    });
}

function imgwRegionSearch(settings, _data, dataIndex) {
    if (!settings.nTable || settings.nTable.id !== 'imgw-datatable') {
        return true;
    }
    var root = imgwTableRoot()[0];
    var mode = root ? (root.getAttribute('data-region') || 'all') : 'all';
    if (mode === 'all') {
        return true;
    }
    var row = settings.aoData[dataIndex] && settings.aoData[dataIndex].nTr;
    var europe = row && row.getAttribute('data-europe') === '1';
    return mode === 'eu' ? europe : !europe;
}

function ensureImgwRegionSearch() {
    if (!$.fn.dataTable || !$.fn.dataTable.ext || !$.fn.dataTable.ext.search) {
        return;
    }
    var list = $.fn.dataTable.ext.search;
    if (list.indexOf(imgwRegionSearch) === -1) {
        list.push(imgwRegionSearch);
    }
}

function bindImgwSharedExport($root, title, filename, exportOpts) {
    if (!$.fn.dataTable.Buttons || !imgwDtTables.length) {
        return;
    }
    var host = imgwDtTables[0];
    new $.fn.dataTable.Buttons(host, {
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'XLS',
                title: title,
                filename: filename,
                exportOptions: exportOpts
            },
            {
                extend: 'pdfHtml5',
                text: 'PDF',
                title: title,
                filename: filename,
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: exportOpts,
                customize: function (doc) {
                    doc.defaultStyle.fontSize = 8;
                    doc.styles.tableHeader.fontSize = 9;
                    doc.styles.tableHeader.alignment = 'left';
                }
            }
        ]
    });
    host.buttons().container().appendTo($root.find('.imgw-dt-export'));
}

function fitImgwTableScroll($root) {
    var $body = $root.find('.imgw-table-stage-body');
    var $scroll = $root.find('.dt-scroll-body');
    if (!$body.length || !$scroll.length) {
        return;
    }
    var used = 0;
    $body.children().each(function () {
        if (!$(this).hasClass('dt-container')) {
            used += $(this).outerHeight(true) || 0;
        }
    });
    $root.find('.imgw-dt').children().each(function () {
        if (!$(this).find('.dt-scroll').length) {
            used += $(this).outerHeight(true) || 0;
        }
    });
    used += $root.find('.dt-scroll-head').outerHeight(true) || 0;
    var $dt = $root.find('.imgw-dt');
    used += (parseFloat($dt.css('padding-top')) || 0) + (parseFloat($dt.css('padding-bottom')) || 0);
    used += 2;
    var height = Math.floor($body.height() - used);
    if (height < 140) {
        height = 140;
    }
    $scroll.css({ height: height + 'px', maxHeight: height + 'px' });
}
