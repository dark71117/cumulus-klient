var menuPosition = 0;
var loading = 0;
var autoLoading = 0;
var ajaxRequest;
var tabStatus = { forecast1Tab: 0, forecast2Tab: 0, forecast3Tab: 0, forecast4Tab: 0, warningTab: 0 };
var loadingDiv, ajaxLoaderDiv, contentDiv, contentDivHeight;

function klientUrl(path) {
    return (window.klientBase || '/klient') + path;
}

$(document).ready(function () {
    loadingDiv = $('#loading');
    ajaxLoaderDiv = $('#ajax_loader');
    contentDiv = $('#content');
    $(document).everyTime(60000, getTabStatus);
    $(document).everyTime(50000, function () {
        var m = new Date().getMinutes();
        if ([3, 13, 23, 33, 43, 53].indexOf(m) !== -1) {
            autoLoading = 1;
            if (['imgwTableNew2Tab', 'analizaTab', 'imgwMapNew2Tab', 'gddkiaRegionTab', 'gddkiaRoadTab', 'warningTab'].indexOf(menuPosition) !== -1) {
                loadContent(menuPosition);
            }
        }
    });
    $('.print.button').hide();
    if ($('.tabs_frame').hasClass('hidden')) {
        $('.main_frame').hide();
    } else {
        $('.main_frame').show();
    }
    setContentDivHeight(100);
    $('#cookiesBtn').click(function () {
        window.open('https://www.cumulus.wroc.pl/site/cookies', '_blank');
    });
    $(document).on('mouseenter', '[data-tip]', function () {
        var $el = $(this);
        $('.app-tooltip').remove();
        if (this.title) {
            $el.data('nativeTitle', this.title);
            this.title = '';
        }
        var tip = $('<div class="app-tooltip" role="tooltip"></div>').text($el.attr('data-tip')).appendTo('body');
        var rect = this.getBoundingClientRect();
        var top = rect.top + rect.height / 2;
        var left = rect.right + 10;
        if (left + 270 > window.innerWidth) {
            left = Math.max(8, rect.left - 10 - tip.outerWidth());
        }
        if (top < 16) top = 16;
        if (top > window.innerHeight - 16) top = window.innerHeight - 16;
        tip.css({ top: top, left: left });
        $el.data('tipEl', tip);
    }).on('mouseleave', '[data-tip]', function () {
        var $el = $(this);
        var tip = $el.data('tipEl');
        if (tip) tip.remove();
        $('.app-tooltip').remove();
        if ($el.data('nativeTitle')) {
            this.title = $el.data('nativeTitle');
        }
    });
    $('input#loadPage').click(function () {
        var value = $('#customers').val();
        if (!value || value === '0') {
            alert('Musisz wybrać klienta.');
            return;
        }
        $.post(klientUrl('/setcustomer'), { id: value }, function () {
            window.location.replace(klientUrl(''));
        });
    });
    $('input#ipAdmin').click(function () {
        openAdminPane(klientUrl('/ipadmin'), '#ipAdmin');
    });
    $('input#firmsAdmin').click(function () {
        openAdminPane(klientUrl('/firmy'), '#firmsAdmin');
    });
    $('input#usersAdmin').click(function () {
        openAdminPane(klientUrl('/uzytkownicy'), '#usersAdmin');
    });
    $('#myProfile').click(function () {
        openAdminPane(klientUrl('/profil'), '#myProfile');
    });
    $('#content').on('change', '#imgw-map-limit, #imgw-table-limit', function () {
        var limit = parseInt(this.value, 10);
        $.post(klientUrl('/maplimit'), { limit: limit }, function () {
            loadContent(menuPosition);
        });
    });
    $('#content').on('submit', '.ipadmin-pane form, .users-app form', function (e) {
        e.preventDefault();
        var msg = $(this).attr('data-confirm');
        if (msg && !window.confirm(msg)) {
            return;
        }
        var errorText = $(this).closest('.firms-app').length
            ? 'Nie udało się zapisać firmy.'
            : ($(this).closest('.users-app').length
                ? ($(this).find('[name=obecne]').length ? 'Nie udało się zapisać profilu.' : 'Nie udało się zapisać konta.')
                : 'Nie udało się otworzyć administracji IP.');
        loadAdminPane(this.action, this.method || 'POST', $(this).serialize(), errorText);
    });
    $('#content').on('click', '[data-user-edit]', function () {
        fillUsersModal(JSON.parse($(this).attr('data-user-edit')));
    });
    $('#content').on('click', '[data-profile-open]', function () {
        $('#users-modal').addClass('is-open');
        $('#users-modal [name=login]').trigger('focus');
    });
    $('#content').on('click', '[data-firm-users]', function () {
        openAdminPane(klientUrl('/uzytkownicy?firma=' + $(this).attr('data-firm-users')), '#usersAdmin');
    });
    $('#content').on('click', '[data-firm-edit]', function () {
        fillFirmsModal(JSON.parse($(this).attr('data-firm-edit')));
    });
    $('#content').on('click', '[data-firm-close]', function () {
        $('#firms-modal').removeClass('is-open');
    });
    $('#content').on('click', '#firms-modal', function (e) {
        if (e.target === this) {
            $(this).removeClass('is-open');
        }
    });
    $('#content').on('input', '#firms-search', function () {
        var query = $(this).val().toLowerCase();
        $('.firms-app .users-table tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(query) !== -1);
        });
    });
    $('#content').on('click', '[data-user-close]', function () {
        $('#users-modal').removeClass('is-open');
    });
    $('#content').on('click', '#users-modal', function (e) {
        if (e.target === this) {
            $(this).removeClass('is-open');
        }
    });
    $('#content').on('input', '#users-search', function () {
        var query = $(this).val().toLowerCase();
        $('.users-table tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(query) !== -1);
        });
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $('#users-modal, #firms-modal').removeClass('is-open');
        }
    });
    $('#content').on('click', '.ipadmin-pane a', function (e) {
        var href = this.getAttribute('href') || '';
        if (href.indexOf('/klient/ip') === -1 && href.indexOf('/klient/uzytkownicy') === -1 && href.indexOf('/klient/profil') === -1) {
            return;
        }
        e.preventDefault();
        loadAdminPane(href, 'GET', null, 'Nie udało się otworzyć strony.');
    });

    $('#mainTabs > div').click(function () {
        var id = $(this).attr('id');
        $('#ipAdmin, #firmsAdmin, #usersAdmin, #myProfile').removeClass('is-active');
        if (id === 'addonsTab') {
            $('#addonsTabs').toggleClass('is-open');
            return;
        }
        stopLoadingContent();
        destroyImgwLeaflet();
        destroyImgwDataTable();
        destroyAnaliza();
        contentDiv.removeClass('has-map has-analiza has-imgw-table has-gfs');
        contentDiv.html('');
        $('#addonsTabs').removeClass('is-open');
        clearNavActive();
        $(this).addClass('t1b');
        menuPosition = id;
        loadContent(menuPosition);
        setContentDivHeight();
        setTabStatus();
    });

    $('#addonsTabs').on('click', '.nav-btn:not(.nav-ext)', function () {
        var id = $(this).attr('id');
        stopLoadingContent();
        $('#ipAdmin, #firmsAdmin, #usersAdmin, #myProfile').removeClass('is-active');
        $('#mainTabs > div.t1b').removeClass('t1b');
        $('#addonsTab').addClass('t1b');
        $('#addonsTabs').addClass('is-open');
        $('#addonsTabs .t2b').removeClass('t2b').addClass('t2a');
        $(this).addClass('t2b');
        if (id === 'calendarTab') {
            menuPosition = 'sunTab';
        } else {
            menuPosition = id;
        }
        if (id === 'warningTab') {
            $(this).removeClass('t1c');
            tabStatus.warningTab = 0;
            setTabStatus();
        }
        loadContent(menuPosition);
        setContentDivHeight();
    });

    $(window).resize(function () {
        if (contentDivHeight > 0) setContentDivHeight(contentDivHeight);
        if (imgwLeafletMap) {
            imgwLeafletMap.invalidateSize();
        }
        fitActualMap();
    });
    setActualTabs();
    getTabStatus();
    openTableOnStart();
    openUsersModalFromPane();
});

function clearNavActive() {
    $('#mainTabs > div.t1b').removeClass('t1b');
    $('#addonsTabs .t2b').removeClass('t2b').addClass('t2a');
}

function setActualTabs() {
    $.ajax({ cache: false, type: 'POST', url: klientUrl('/actualtabs'), dataType: 'json',
        success: function (tabs) {
            ['imgwTableNew2Tab', 'analizaTab', 'imgwMapNew2Tab', 'gddkiaRegionTab', 'gddkiaRoadTab'].forEach(function (key) {
                if (!tabs[key]) return;
                if (tabs[key].active === 1) {
                    $('#' + key).removeClass('hidden');
                } else {
                    $('#' + key).addClass('hidden');
                }
            });
        }
    });
}

function openTableOnStart() {
    if ($('.tabs_frame').hasClass('hidden')) {
        return;
    }
    if (!$('#imgwTableNew2Tab').hasClass('hidden')) {
        $('#imgwTableNew2Tab').trigger('click');
        return;
    }
    var next = ['#imgwMapNew2Tab', '#analizaTab', '#gfsMapTab'];
    for (var i = 0; i < next.length; i++) {
        var $tab = $(next[i]);
        if ($tab.length && !$tab.hasClass('hidden')) {
            $tab.trigger('click');
            return;
        }
    }
}

function setTabStatus() {
    var $warning = $('#warningTab');
    $('#addonsTab').toggleClass('has-warning', tabStatus.warningTab === 1);
    if (tabStatus.warningTab === 1) {
        $warning.removeClass('hidden t2a t2b').addClass('t1c');
    } else if (tabStatus.warningTab === 0) {
        $warning.removeClass('hidden t1c');
        if (!$warning.hasClass('t2b')) {
            $warning.addClass('t2a');
        }
    } else {
        $warning.addClass('hidden');
    }
}

function getTabStatus() {
    $.ajax({ cache: false, type: 'POST', url: klientUrl('/tabstatus'), dataType: 'json',
        success: function (tabs) {
            tabStatus = tabs;
            setTabStatus();
        }
    });
}

function loadContent(tab, position) {
    if (!tab || loading === 1) return;
    showLoading();
    var data = { tab: tab };
    if (position !== undefined) data.position = position;
    ajaxRequest = $.ajax({
        cache: false, type: 'POST', url: klientUrl('/content'), data: data,
        error: function (xhr, status) {
            var hint = status === 'timeout' ? 'Przekroczono czas oczekiwania.' : 'Kod: ' + (xhr && xhr.status ? xhr.status : '');
            contentDiv.html('Podczas ładowania strony wystąpił błąd. ' + hint + '<br>Spróbuj ponownie klikając wybraną zakładkę.');
        },
        success: function (html) {
            destroyImgwLeaflet();
            destroyImgwDataTable();
            destroyAnaliza();
            contentDiv.html(html);
            var hasLeaflet = contentDiv.find('#imgw-leaflet').length > 0;
            var hasClassic = contentDiv.find('.actualMapStage').length > 0;
            var hasAnaliza = contentDiv.find('.analiza-app').length > 0;
            var hasImgwTable = contentDiv.find('.imgw-table-new').not('.analiza-app').length > 0;
            var hasGfs = contentDiv.find('.gfs-app').length > 0;
            contentDiv.toggleClass('has-map', hasLeaflet || hasClassic);
            contentDiv.toggleClass('has-analiza', hasAnaliza);
            contentDiv.toggleClass('has-imgw-table', hasImgwTable);
            contentDiv.toggleClass('has-gfs', hasGfs);
            if (hasAnaliza || hasImgwTable) {
                setContentDivHeight();
            }
            if (hasLeaflet) {
                initImgwLeaflet();
            } else if (hasClassic) {
                fitActualMap();
            } else if (hasAnaliza) {
                initAnaliza();
            } else if (hasImgwTable) {
                initImgwDataTable();
            } else if (hasGfs) {
                initGfsMap();
            }
        },
        complete: hideLoading
    });
}

function stopLoadingContent() {
    if (loading === 1 && ajaxRequest) {
        ajaxRequest.abort();
        hideLoading();
    }
}
function showLoading() {
    loading = 1;
    if (autoLoading === 0) {
        contentDiv.html(ajaxLoaderDiv);
        contentDiv.find(ajaxLoaderDiv).show();
    } else loadingDiv.show();
}
function hideLoading() {
    loading = 0;
    loadingDiv.hide();
}
function openAdminPane(url, activeId) {
    stopLoadingContent();
    clearNavActive();
    $('#addonsTabs').removeClass('is-open');
    menuPosition = activeId;
    $('#ipAdmin, #firmsAdmin, #usersAdmin, #myProfile').removeClass('is-active');
    $(activeId).addClass('is-active');
    loadAdminPane(url, 'GET', null, 'Nie udało się otworzyć strony.');
}

function fillUsersModal(data) {
    var $modal = $('#users-modal');
    if (!$modal.length || !data) {
        return;
    }
    var firm = data.rodzaj === 'firma';
    $modal.find('[name=rodzaj]').val(firm ? 'firma' : 'user');
    $modal.find('[name=id]').val(data.id || 0);
    $modal.find('[name=login]').val(data.login || '');
    $modal.find('[name=nazwa]').val(data.nazwa || '');
    $modal.find('[name=poziom]').val(firm ? '1' : String(data.poziom || 1));
    $modal.find('[name=haslo]').val('').prop('required', !firm && !data.id);
    $modal.find('[name=aktywny]').val(String(data.aktywny) === '0' ? '0' : '1');
    ['tabela', 'mapa', 'gfs', 'analiza', 'dodatki'].forEach(function (key) {
        $modal.find('[name=mod_' + key + ']').prop('checked', String(data['mod_' + key]) === '1');
    });
    $modal.find('.users-field-nazwa, .users-field-level').toggleClass('is-hidden', firm);
    $modal.find('.users-field-active').removeClass('is-hidden');
    $modal.find('.users-field-modules').toggleClass('is-hidden', firm);
    var title = firm ? 'EDYCJA KONTA WSPÓLNEGO' : (data.id ? 'EDYCJA UŻYTKOWNIKA ' + (data.login || '') : 'DODAJ UŻYTKOWNIKA');
    $('#users-modal-title').text(title);
    $modal.addClass('is-open');
    $modal.find('[name=login]').trigger('focus');
}

function fillFirmsModal(data) {
    var $modal = $('#firms-modal');
    if (!$modal.length || !data) {
        return;
    }
    $modal.find('[name=id]').val(data.id || 0);
    $modal.find('[name=nazwa]').val(data.nazwa || '');
    $modal.find('[name=login]').val(data.login || '');
    $modal.find('[name=haslo]').val('').prop('required', !data.id);
    $modal.find('[name=aktywny]').val(String(data.aktywny) === '0' ? '0' : '1');
    ['tabela', 'mapa', 'gfs', 'analiza', 'dodatki'].forEach(function (key) {
        $modal.find('[name=mod_' + key + ']').prop('checked', String(data['mod_' + key]) !== '0');
    });
    $('#firms-modal-title').text(data.id ? 'EDYCJA FIRMY' : 'NOWA FIRMA');
    $modal.addClass('is-open');
    $modal.find('[name=nazwa]').trigger('focus');
}

function openFirmsModalFromPane() {
    var $modal = $('#firms-modal');
    if ($modal.attr('data-open') === '1' && $modal.attr('data-form')) {
        fillFirmsModal(JSON.parse($modal.attr('data-form')));
    }
}

function openUsersModalFromPane() {
    var $modal = $('#users-modal');
    if ($modal.attr('data-open') === '1' && $modal.attr('data-form')) {
        fillUsersModal(JSON.parse($modal.attr('data-form')));
    }
}

function loadIpAdmin(url, method, data) {
    loadAdminPane(url, method, data, 'Nie udało się otworzyć administracji IP.');
}

function loadAdminPane(url, method, data, errorText) {
    if (loading === 1) {
        stopLoadingContent();
    }
    showLoading();
    ajaxRequest = $.ajax({
        cache: false,
        url: url,
        type: (method || 'GET').toUpperCase(),
        data: data || undefined,
        headers: { Accept: 'text/html' },
        error: function (xhr, status) {
            if (status === 'abort') {
                return;
            }
            contentDiv.html(errorText || 'Nie udało się otworzyć strony.');
        },
        success: function (html) {
            destroyImgwLeaflet();
            destroyImgwDataTable();
            destroyAnaliza();
            contentDiv.removeClass('has-map has-analiza has-imgw-table has-gfs');
            contentDiv.html(html);
            setContentDivHeight();
            openUsersModalFromPane();
            openFirmsModalFromPane();
            var companyName = contentDiv.find('[data-company-name]').attr('data-company-name');
            var companyId = contentDiv.find('[data-company-id]').attr('data-company-id');
            if (companyName) {
                $('.sidebar-exclusive .blue').text(companyName);
                if (companyId) {
                    $('#customers option[value="' + companyId + '"]').text(companyName);
                }
            }
            if ($('#users-modal.is-open [name=obecne]').length) {
                $('#users-modal [name=login]').trigger('focus');
            }
        },
        complete: hideLoading
    });
}
function setContentDivHeight(offset) {
    contentDivHeight = offset || 1;
    if ($('body').hasClass('layout-app') && $('.app-stage').length) {
        var stageHeight = $('.app-stage').height();
        if (stageHeight > 0 && contentDiv.length) {
            contentDiv.height(stageHeight);
            return;
        }
    }
    contentDiv.height($(window).height() - (offset || 100));
}

function fitActualMap() {
    var $map = contentDiv.find('.actualMap');
    var $stage = contentDiv.find('.actualMapStage');
    if (!$map.length || !$stage.length) {
        return;
    }
    var $img = $map.find('img.map');
    var apply = function () {
        var nw = ($img[0] && $img[0].naturalWidth) || $img.width();
        var nh = ($img[0] && $img[0].naturalHeight) || $img.height();
        if (!nw || !nh) {
            return;
        }
        var padTop = 10;
        var boxW = nw;
        var boxH = nh + padTop;
        $map.css({ width: boxW + 'px', height: boxH + 'px' });
        var scale = Math.min($stage.width() / boxW, $stage.height() / boxH);
        if (!isFinite(scale) || scale <= 0) {
            scale = 1;
        }
        $map.css({
            transform: 'scale(' + scale + ')',
            transformOrigin: 'top left',
            marginRight: (boxW * (scale - 1)) + 'px',
            marginBottom: (boxH * (scale - 1)) + 'px'
        });
    };
    var whenReady = function () {
        if ($img.length && $img[0].complete && $img[0].naturalWidth) {
            apply();
        } else {
            $img.off('load.fitMap').on('load.fitMap', apply);
        }
    };
    if (window.requestAnimationFrame) {
        requestAnimationFrame(whenReady);
    } else {
        whenReady();
    }
}
