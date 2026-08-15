function destroyImgwDataTable() {
    var $t = $('#imgw-datatable');
    if ($t.length && $.fn.dataTable && $.fn.dataTable.isDataTable($t)) {
        $t.DataTable().destroy();
    }
}

function initImgwDataTable() {
    destroyImgwDataTable();
    var $table = $('#imgw-datatable');
    if (!$table.length || !$.fn.DataTable) {
        return;
    }
    var hour = $table.closest('.imgw-table-new').attr('data-hour') || '';
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

    $table.DataTable({
        autoWidth: false,
        pageLength: 50,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'Wszystkie']],
        order: [[0, 'asc'], [1, 'asc']],
        orderClasses: false,
        stripeClasses: [],
        layout: {
            topStart: {
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
            },
            topEnd: 'search',
            bottomStart: ['pageLength', 'info'],
            bottomEnd: 'paging'
        },
        language: {
            search: 'Szukaj:',
            searchPlaceholder: 'Miejscowość, zjawisko…',
            lengthMenu: 'Pokaż _MENU_',
            info: '_START_–_END_ z _TOTAL_ stacji',
            infoEmpty: 'Brak danych',
            infoFiltered: '(z _MAX_)',
            zeroRecords: 'Brak wyników dla bieżącego filtra',
            emptyTable: 'Brak danych',
            paginate: { first: 'Pierwsza', last: 'Ostatnia', next: '›', previous: '‹' }
        },
        initComplete: function () {
            var api = this.api();
            $(api.table().footer()).find('th').each(function (i) {
                var label = $(api.column(i).header()).text().replace(/\s+/g, ' ').trim();
                var $input = $('<input type="search" class="imgw-dt-colfilter">')
                    .attr('placeholder', label)
                    .attr('aria-label', 'Filtr: ' + label);
                $(this).empty().append($input);
                $input.on('click', function (e) {
                    e.stopPropagation();
                });
                $input.on('keyup change', function () {
                    if (api.column(i).search() !== this.value) {
                        api.column(i).search(this.value).draw();
                    }
                });
            });
        }
    });
}
