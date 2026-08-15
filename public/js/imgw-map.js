var imgwLeafletMap = null;
var imgwMarkerLayer = null;
var imgwPlayTimer = null;
var imgwPlayDir = 0;
var imgwHourIndex = 0;
var imgwFrames = [];
var imgwMapFitted = false;

function destroyImgwLeaflet() {
    stopImgwPlay();
    imgwFrames = [];
    imgwHourIndex = 0;
    imgwMapFitted = false;
    imgwMarkerLayer = null;
    if (imgwLeafletMap) {
        imgwLeafletMap.remove();
        imgwLeafletMap = null;
    }
}

function initImgwLeaflet() {
    destroyImgwLeaflet();
    var el = document.getElementById('imgw-leaflet');
    var dataEl = document.getElementById('imgw-map-frames');
    if (!el || !dataEl || typeof L === 'undefined') {
        return;
    }
    try {
        imgwFrames = JSON.parse(dataEl.textContent || '[]');
    } catch (e) {
        imgwFrames = [];
    }
    if (!imgwFrames.length) {
        return;
    }
    imgwHourIndex = parseInt(el.getAttribute('data-current') || String(imgwFrames.length - 1), 10);
    if (imgwHourIndex < 0 || imgwHourIndex >= imgwFrames.length) {
        imgwHourIndex = imgwFrames.length - 1;
    }
    var bounds = L.latLngBounds([48.8, 13.8], [55.2, 24.4]);
    imgwLeafletMap = L.map(el, {
        zoom: 6.5,
        zoomSnap: 0.1,
        minZoom: 5.8,
        maxZoom: 11,
        zoomControl: true,
        maxBounds: bounds,
        maxBoundsViscosity: 1
    });
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        className: 'poland-mask'
    }).addTo(imgwLeafletMap);
    imgwMarkerLayer = L.layerGroup().addTo(imgwLeafletMap);

    var geoUrl = el.getAttribute('data-geojson') || '';
    renderImgwHour(imgwHourIndex, false);
    bindImgwPlay();

    function afterMask() {
        fitImgwMap(bounds);
    }
    if (geoUrl) {
        fetch(geoUrl)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                applyPolandMask(data, imgwLeafletMap);
                L.geoJSON(data, {
                    style: {
                        fillColor: 'transparent',
                        fillOpacity: 0,
                        weight: 1.2,
                        opacity: 0.9,
                        color: '#2c5282'
                    },
                    interactive: false
                }).addTo(imgwLeafletMap);
                afterMask();
            })
            .catch(afterMask);
    } else {
        afterMask();
    }
    setTimeout(function () { fitImgwMap(bounds); }, 80);
}

function renderImgwHour(index, allowFit) {
    if (!imgwLeafletMap || !imgwMarkerLayer || !imgwFrames[index]) {
        return;
    }
    imgwHourIndex = index;
    var frame = imgwFrames[index];
    var hourEl = document.getElementById('imgw-map-hour');
    if (hourEl) {
        hourEl.textContent = frame.hour || '';
    }
    imgwMarkerLayer.clearLayers();
    var night = Number(frame.night) === 1;
    (frame.points || []).forEach(function (p) {
        var marker = imgwPointMarker(p, night);
        if (marker) {
            imgwMarkerLayer.addLayer(marker);
        }
    });
    if (imgwMarkerLayer.bringToFront) {
        imgwMarkerLayer.bringToFront();
    }
    if (allowFit) {
        fitImgwMap();
    }
}

function imgwPointMarker(p, night) {
    if (p.lat == null || p.lon == null || p.lat === '' || p.lon === '') {
        return null;
    }
    var name = String(p.name || '');
    var missing = !!p.missing;
    var temp = missing ? 'BD' : (p.temp === '' || p.temp === null ? 'BD' : String(p.temp) + '°');
    var iconHtml = (!missing && p.icon)
        ? '<span class="imgw-pin-icon"><img src="' + p.icon + '" alt=""></span>'
        : '';
    var html = '<div class="imgw-pin' + (night ? ' is-night' : '') + (missing ? ' is-missing' : '') + '">' +
        '<div class="imgw-pin-badge">' +
        '<span class="imgw-pin-temp">' + temp + '</span>' +
        iconHtml +
        '</div>' +
        '<div class="imgw-pin-name">' + escapeHtml(name) + '</div>' +
        '</div>';
    var width = Math.max(78, Math.min(140, name.length * 7 + 52));
    return L.marker([p.lat, p.lon], {
        icon: L.divIcon({
            html: html,
            className: 'imgw-pin-marker',
            iconSize: [width, 72],
            iconAnchor: [width / 2, 36]
        }),
        riseOnHover: true,
        title: p.text ? name + ' — ' + p.text : name
    });
}

function fitImgwMap(fallbackBounds) {
    if (!imgwLeafletMap) {
        return;
    }
    imgwLeafletMap.invalidateSize();
    if (imgwMapFitted) {
        return;
    }
    var layers = imgwMarkerLayer ? imgwMarkerLayer.getLayers() : [];
    if (layers.length) {
        imgwLeafletMap.fitBounds(L.featureGroup(layers).getBounds().pad(0.1), { maxZoom: 8 });
        imgwMapFitted = true;
    } else if (fallbackBounds) {
        imgwLeafletMap.fitBounds(fallbackBounds);
        imgwMapFitted = true;
    }
}

function bindImgwPlay() {
    var root = document.getElementById('imgw-map-play');
    if (!root) {
        return;
    }
    root.querySelectorAll('.imgw-map-play-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var dir = parseInt(btn.getAttribute('data-dir'), 10) || 1;
            if (imgwPlayDir === dir) {
                stopImgwPlay();
                return;
            }
            stepImgwHour(dir);
            startImgwPlay(dir);
        });
    });
    var delay = document.getElementById('imgw-map-delay');
    if (delay) {
        delay.addEventListener('change', function () {
            if (imgwPlayDir) {
                startImgwPlay(imgwPlayDir);
            }
        });
    }
}

function startImgwPlay(dir) {
    stopImgwPlay(true);
    imgwPlayDir = dir;
    syncImgwPlayButtons();
    var delayEl = document.getElementById('imgw-map-delay');
    var ms = delayEl ? parseInt(delayEl.value, 10) : 3000;
    if (!ms || ms < 500) {
        ms = 3000;
    }
    imgwPlayTimer = setInterval(function () {
        stepImgwHour(imgwPlayDir);
    }, ms);
}

function stopImgwPlay(keepDir) {
    if (imgwPlayTimer) {
        clearInterval(imgwPlayTimer);
        imgwPlayTimer = null;
    }
    if (!keepDir) {
        imgwPlayDir = 0;
        syncImgwPlayButtons();
    }
}

function syncImgwPlayButtons() {
    var root = document.getElementById('imgw-map-play');
    if (!root) {
        return;
    }
    root.querySelectorAll('.imgw-map-play-btn').forEach(function (btn) {
        var dir = parseInt(btn.getAttribute('data-dir'), 10);
        btn.classList.toggle('is-active', imgwPlayDir === dir);
    });
}

function stepImgwHour(dir) {
    if (!imgwFrames.length) {
        return;
    }
    var next = imgwHourIndex + dir;
    if (next >= imgwFrames.length) {
        next = 0;
    } else if (next < 0) {
        next = imgwFrames.length - 1;
    }
    renderImgwHour(next, false);
}

function applyPolandMask(geoJsonData, mapInstance) {
    var outerBounds = [
        [-90, -180],
        [-90, 180],
        [90, 180],
        [90, -180],
        [-90, -180]
    ];
    var polandCoords = [];
    (geoJsonData.features || []).forEach(function (feature) {
        var geometry = feature.geometry;
        if (!geometry) {
            return;
        }
        if (geometry.type === 'Polygon') {
            polandCoords.push(geometry.coordinates[0]);
        } else if (geometry.type === 'MultiPolygon') {
            geometry.coordinates.forEach(function (polygon) {
                polandCoords.push(polygon[0]);
            });
        }
    });
    if (!polandCoords.length) {
        return;
    }
    L.geoJSON({
        type: 'Feature',
        geometry: {
            type: 'Polygon',
            coordinates: [outerBounds].concat(polandCoords)
        }
    }, {
        style: {
            fillColor: '#e8f4fc',
            fillOpacity: 0.9,
            stroke: false,
            interactive: false
        }
    }).addTo(mapInstance).bringToBack();
}

function escapeHtml(text) {
    return String(text).replace(/[&<>"']/g, function (ch) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
}
