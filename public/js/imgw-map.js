var imgwLeafletMap = null;

function destroyImgwLeaflet() {
    if (imgwLeafletMap) {
        imgwLeafletMap.remove();
        imgwLeafletMap = null;
    }
}

function initImgwLeaflet() {
    destroyImgwLeaflet();
    var el = document.getElementById('imgw-leaflet');
    var dataEl = document.getElementById('imgw-map-data');
    if (!el || !dataEl || typeof L === 'undefined') {
        return;
    }
    var points = [];
    try {
        points = JSON.parse(dataEl.textContent || '[]');
    } catch (e) {
        return;
    }
    if (!points.length) {
        return;
    }
    var night = el.getAttribute('data-night') === '1';
    var geoUrl = el.getAttribute('data-geojson') || '';
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

    var group = [];
    points.forEach(function (p) {
        if (!p.lat || !p.lon) {
            return;
        }
        var name = String(p.name || '');
        var temp = p.temp === '' || p.temp === null ? '–' : String(p.temp);
        var iconHtml = p.icon
            ? '<span class="imgw-pin-icon"><img src="' + p.icon + '" alt=""></span>'
            : '';
        var html = '<div class="imgw-pin' + (night ? ' is-night' : '') + '">' +
            '<div class="imgw-pin-badge">' +
            '<span class="imgw-pin-temp">' + temp + '°</span>' +
            iconHtml +
            '</div>' +
            '<div class="imgw-pin-name">' + escapeHtml(name) + '</div>' +
            '</div>';
        var width = Math.max(54, Math.min(120, name.length * 7 + 18));
        var marker = L.marker([p.lat, p.lon], {
            icon: L.divIcon({
                html: html,
                className: 'imgw-pin-marker',
                iconSize: [width, 52],
                iconAnchor: [width / 2, 26]
            }),
            riseOnHover: true,
            title: p.text ? name + ' — ' + p.text : name
        });
        marker.addTo(imgwLeafletMap);
        group.push(marker);
    });

    function fitMap() {
        if (!imgwLeafletMap) {
            return;
        }
        imgwLeafletMap.invalidateSize();
        if (group.length) {
            imgwLeafletMap.fitBounds(L.featureGroup(group).getBounds().pad(0.1), { maxZoom: 8 });
        } else {
            imgwLeafletMap.fitBounds(bounds);
        }
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
                fitMap();
            })
            .catch(function () {
                fitMap();
            });
    } else {
        fitMap();
    }

    setTimeout(fitMap, 80);
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
