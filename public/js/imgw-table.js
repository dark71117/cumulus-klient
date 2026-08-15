var imgwDtTables = [];
var imgwDtSyncingOrder = false;

function destroyImgwDataTable() {
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
}

function initImgwDataTable() {
    destroyImgwDataTable();
    var $root = $('.imgw-table-new');
    if (!$root.length || !$.fn.DataTable) {
        return;
    }
    var hour = $root.attr('data-hour') || '';
    var title = 'Warunki atmosferyczne o godzinie ' + hour;
    var filename = 'imgw-warunki-' + new Date().toISOString().slice(0, 10);
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
    var dtOpts = {
        autoWidth: false,
        pageLength: 100,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'max']],
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
    };

    ['imgw-datatable-pl', 'imgw-datatable-eu'].forEach(function (id) {
        var $table = $('#' + id);
        if ($table.length) {
            imgwDtTables.push($table.DataTable(dtOpts));
        }
    });
    if (!imgwDtTables.length) {
        return;
    }

    bindImgwSharedSearch($root);
    bindImgwSharedFilters($root);
    bindImgwSharedOrder();
    bindImgwSharedExport($root, title, filename, exportOpts);
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

function bindImgwSharedOrder() {
    imgwDtTables.forEach(function (api, idx) {
        api.on('order.dt', function () {
            if (imgwDtSyncingOrder) {
                return;
            }
            imgwDtSyncingOrder = true;
            var order = api.order();
            imgwDtTables.forEach(function (other, otherIdx) {
                if (otherIdx !== idx) {
                    other.order(order).draw(false);
                }
            });
            imgwDtSyncingOrder = false;
        });
    });
}

function imgwMergedExportData(exportOpts) {
    var header = imgwDtTables[0].buttons.exportData(exportOpts).header;
    var body = [];
    var labels = { 'imgw-datatable-pl': 'Polska', 'imgw-datatable-eu': 'Europa' };
    imgwDtTables.forEach(function (api) {
        var exported = api.buttons.exportData(exportOpts);
        if (!exported.body.length) {
            return;
        }
        var id = api.table().node().id;
        var section = ['— ' + (labels[id] || id) + ' —'];
        while (section.length < exported.header.length) {
            section.push('');
        }
        body.push(section);
        Array.prototype.push.apply(body, exported.body);
    });
    return { header: header, body: body };
}

function imgwApplyMergedData(data, exportOpts) {
    var merged = imgwMergedExportData(exportOpts);
    data.header = merged.header;
    data.body.splice(0, data.body.length);
    Array.prototype.push.apply(data.body, merged.body);
}

function bindImgwSharedExport($root, title, filename, exportOpts) {
    if (!$.fn.dataTable.Buttons) {
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
                exportOptions: exportOpts,
                customizeData: function (data) {
                    imgwApplyMergedData(data, exportOpts);
                }
            },
            {
                extend: 'pdfHtml5',
                text: 'PDF',
                title: title,
                filename: filename,
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: exportOpts,
                customizeData: function (data) {
                    imgwApplyMergedData(data, exportOpts);
                },
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
