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
            if (['imgwTab', 'imgwTableNewTab', 'imgwMapTab', 'imgwMapNewTab', 'gddkiaRegionTab', 'gddkiaRoadTab', 'warningTab'].indexOf(menuPosition) !== -1) {
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
        stopLoadingContent();
        clearNavActive();
        $('#addonsTabs').removeClass('is-open');
        menuPosition = 'ipAdminTab';
        $('#ipAdmin').addClass('is-active');
        loadIpAdmin(klientUrl('/ipadmin'));
    });
    $('#content').on('submit', '.ipadmin-pane form', function (e) {
        e.preventDefault();
        loadIpAdmin(this.action, this.method || 'POST', $(this).serialize());
    });
    $('#content').on('click', '.ipadmin-pane a', function (e) {
        var href = this.getAttribute('href') || '';
        if (href.indexOf('/klient/ip') === -1) {
            return;
        }
        e.preventDefault();
        loadIpAdmin(href);
    });

    $('#mainTabs > div').click(function () {
        var id = $(this).attr('id');
        $('#ipAdmin').removeClass('is-active');
        if (id === 'addonsTab') {
            $('#addonsTabs').toggleClass('is-open');
            return;
        }
        stopLoadingContent();
        destroyImgwLeaflet();
        destroyImgwDataTable();
        contentDiv.removeClass('has-map');
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
        $('#ipAdmin').removeClass('is-active');
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
});

function clearNavActive() {
    $('#mainTabs > div.t1b').removeClass('t1b');
    $('#addonsTabs .t2b').removeClass('t2b').addClass('t2a');
}

function setActualTabs() {
    $.ajax({ cache: false, type: 'POST', url: klientUrl('/actualtabs'), dataType: 'json',
        success: function (tabs) {
            ['imgwTab', 'imgwTableNewTab', 'imgwMapTab', 'imgwMapNewTab', 'gddkiaRegionTab', 'gddkiaRoadTab'].forEach(function (key) {
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
    if (!$('#imgwTab').hasClass('hidden')) {
        $('#imgwTab').trigger('click');
    } else if (!$('#imgwMapTab').hasClass('hidden')) {
        $('#imgwMapTab').trigger('click');
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
            contentDiv.html(html);
            var hasLeaflet = contentDiv.find('#imgw-leaflet').length > 0;
            var hasClassic = contentDiv.find('.actualMapStage').length > 0;
            contentDiv.toggleClass('has-map', hasLeaflet || hasClassic);
            if (hasLeaflet) {
                initImgwLeaflet();
            } else if (hasClassic) {
                fitActualMap();
            } else if (contentDiv.find('.imgw-datatable').length) {
                initImgwDataTable();
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
function loadIpAdmin(url, method, data) {
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
            contentDiv.html('Nie udało się otworzyć administracji IP.');
        },
        success: function (html) {
            destroyImgwLeaflet();
            destroyImgwDataTable();
            contentDiv.removeClass('has-map');
            contentDiv.html(html);
            setContentDivHeight();
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
