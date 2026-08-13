var refreshOffset = 3;
var menuPosition = 0;
var loading = 0;
var firstTab = { weatherTabs: '', actualTabs: '' };
var autoLoading = 0;
var ajaxRequest;
var tabStatus = { forecast1Tab: 0, forecast2Tab: 0, forecast3Tab: 0, forecast4Tab: 0, warningTab: 0 };
var loadingDiv, ajaxLoaderDiv, contentDiv, contentDivHeight;

function showSubNav() {
    var ids = Array.prototype.slice.call(arguments);
    $('.tabs_frame > .tabs2').removeClass('is-open');
    ids.forEach(function (id) {
        if (id) $('#' + id).addClass('is-open');
    });
}

function klientUrl(path) {
    return (window.klientBase || '/klient') + path;
}

$(document).ready(function () {
    loadingDiv = $('#loading');
    ajaxLoaderDiv = $('#ajax_loader');
    contentDiv = $('#content');
    $(document).everyTime(60000, function () {
        setWeatherTabs();
        getTabStatus();
    });
    $(document).everyTime(50000, function () {
        var m = new Date().getMinutes();
        if ([3, 13, 23, 33, 43, 53].indexOf(m) !== -1) {
            autoLoading = 1;
            if (['forecast1Tab', 'forecast2Tab', 'forecast3Tab', 'forecast4Tab', 'imgwTab', 'imgwMapTab',
                'gddkiaRegionTab', 'gddkiaRoadTab', 'satPhotoTab', 'europeSatTab', 'polandSatTab', 'warningTab'].indexOf(menuPosition) !== -1) {
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
        $('#mainTabs > div.t1b').removeClass('t1b');
        showSubNav();
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
        contentDiv.html('');
        stopLoadingContent();
        $('#ipAdmin').removeClass('is-active');
        showSubNav();
        $('#mainTabs > div.t1b').removeClass('t1b');
        $(this).addClass('t1b');
        setContentDivHeight(135);
        switch ($(this).attr('id')) {
            case 'forecastTab':
                showSubNav('forecastTabs');
                $('#forecastTabs > div.t2b').removeClass('t2b').addClass('t2a');
                $('#' + firstTab.weatherTabs).removeClass('t2a').addClass('t2b');
                tabStatus[firstTab.weatherTabs] = 0;
                menuPosition = firstTab.weatherTabs;
                break;
            case 'actualTab':
                showSubNav('actualTabs');
                $('#actualTabs > div.t2b').removeClass('t2b').addClass('t2a');
                $('#' + firstTab.actualTabs).removeClass('t2a').addClass('t2b');
                menuPosition = firstTab.actualTabs;
                break;
            case 'calendarTab':
                showSubNav('calendarTabs');
                $('#sunTab').removeClass('t2a').addClass('t2b');
                menuPosition = 'sunTab';
                break;
            case 'warningTab':
                setContentDivHeight(101);
                menuPosition = 'warningTab';
                $(this).removeClass('t1c').addClass('t1a').addClass('t1b');
                tabStatus.warningTab = 0;
                break;
        }
        loadContent(menuPosition);
        setTabStatus();
    });

    $('#forecastTabs > div').click(function () {
        stopLoadingContent();
        $('#forecastTabs > div.t2b').removeClass('t2b').addClass('t2a');
        $(this).addClass('t2b');
        menuPosition = $(this).attr('id');
        if (menuPosition === 'archiveTab') {
            showSubNav('forecastTabs', 'archiveTabs');
            setContentDivHeight(169);
        } else {
            showSubNav('forecastTabs');
            setContentDivHeight(135);
        }
        loadContent(menuPosition);
        setTabStatus();
    });
    $('#archiveTabs > div').click(function () {
        stopLoadingContent();
        $('#archiveTabs > div.t5b').removeClass('t5b').addClass('t5a');
        $(this).removeClass('t5a').addClass('t5b');
        menuPosition = $(this).attr('id');
        loadContent(menuPosition);
    });
    $('#actualTabs > div').click(function () {
        stopLoadingContent();
        $('#actualTabs > div.t2b').removeClass('t2b').addClass('t2a');
        $(this).addClass('t2b');
        menuPosition = $(this).attr('id');
        if (menuPosition === 'satPhotoTab') {
            showSubNav('actualTabs', 'satTabs');
            contentDiv.html('');
            setContentDivHeight(169);
        } else if (menuPosition === 'radarTab') {
            showSubNav('actualTabs');
            contentDiv.html('<br><br>Przejdź do strony z aktualnym obrazem radarowym.<br><a href="https://www.rainviewer.com/" target="_blank">https://www.rainviewer.com/</a><br><br>Przejdź do strony z aktualnymi wyładowaniami.<br><a href="https://www.blitzortung.org/en/live_lightning_maps.php?map=15" target="_blank">https://www.blitzortung.org</a>');
            setContentDivHeight(135);
        } else {
            showSubNav('actualTabs');
            loadContent(menuPosition);
            setContentDivHeight(menuPosition === 'imgwMapTab' ? 59 : 135);
        }
    });
    $('#calendarTabs > div').click(function () {
        stopLoadingContent();
        $('#calendarTabs > div.t2b').removeClass('t2b').addClass('t2a');
        $(this).addClass('t2b');
        if ($(this).attr('id') === 'sunTab') {
            menuPosition = 'sunTab';
            loadContent(menuPosition);
        } else if ($(this).attr('id') === 'cloudsTab') {
            window.open('http://www.cumulus.nazwa.pl/atlas/');
        } else {
            window.open('http://www.cumulus.nazwa.pl/teoria/');
        }
    });
    $('#satTabs > div').click(function () {
        stopLoadingContent();
        $('#satTabs > div.t5b').removeClass('t5b').addClass('t5a');
        $(this).removeClass('t5a').addClass('t5b');
        var src = $(this).attr('id') === 'europeSatTab'
            ? 'https://api.sat24.com/animated/PL/infraPolair/3/Coordinated%20Universal%20Time/567700'
            : 'https://api.sat24.com/animated/PL/visual/3/Coordinated%20Universal%20Time/4368611';
        contentDiv.html('<br><br><a href="https://www.sat24.com/pl" target="sat24"><img id="satImage" src="' + src + '" width="845" height="615"></a>');
    });
    $(window).resize(function () {
        if (contentDivHeight > 0) setContentDivHeight(contentDivHeight);
    });
    setWeatherTabs();
    setActualTabs();
    getTabStatus();
});

function setWeatherTabs() {
    $.ajax({ cache: false, type: 'POST', url: klientUrl('/weathertabs'), dataType: 'json',
        success: function (tabs) {
            firstTab.weatherTabs = '';
            var counter = 1;
            for (var key in tabs) {
                if (tabs[key].title) {
                    $('#' + key + ' > div.desc').html('Prognoza ' + tabs[key].title);
                }
                if (tabs[key].active === 1) {
                    $('#' + key).show();
                    if (!firstTab.weatherTabs) firstTab.weatherTabs = key;
                    counter++;
                } else {
                    $('#' + key).hide();
                }
            }
            if (!firstTab.weatherTabs) firstTab.weatherTabs = 'archiveTab';
        }
    });
}

function setActualTabs() {
    $.ajax({ cache: false, type: 'POST', url: klientUrl('/actualtabs'), dataType: 'json',
        success: function (tabs) {
            firstTab.actualTabs = '';
            for (var key in tabs) {
                if (tabs[key].active === 1) {
                    $('#' + key).show();
                    if (!firstTab.actualTabs) firstTab.actualTabs = key;
                } else {
                    $('#' + key).hide();
                }
            }
            if (!firstTab.actualTabs) firstTab.actualTabs = 'radarTab';
            openActualWeatherOnStart();
        }
    });
}

function openActualWeatherOnStart() {
    if ($('.tabs_frame').hasClass('hidden') || $('#actualTab').hasClass('t1b')) {
        return;
    }
    $('#actualTab').trigger('click');
}

function setTabStatus() {
    var forecastTab = 0;
    for (var key in tabStatus) {
        if (key === 'warningTab') {
            if (tabStatus[key] === 1) $('#warningTab').show().removeClass('t1a').addClass('t1c');
            else if (tabStatus[key] === 0) $('#warningTab').show().removeClass('t1c').addClass('t1a');
            else $('#warningTab').hide();
        } else if (tabStatus[key] == 1) {
            $('#' + key + ' .circle_small').show();
            forecastTab = 1;
        } else {
            $('#' + key + ' .circle_small').hide();
        }
    }
    if (forecastTab === 1) $('#forecastTab .circle').css('display', 'inline-block');
    else $('#forecastTab .circle').hide();
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
        success: function (html) { contentDiv.html(html); },
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
