/* contao-maplibre: MapLibre GL JS + OpenFreeMap.
 *
 * - Laedt MapLibre GL JS/CSS bei Bedarf von unpkg (gepinnte Version aus der Konfiguration).
 * - Karte ist initial sichtbar, aber NICHT interaktiv, damit die Seite durch die Karte hindurch
 *   scrollt. Erst ein Klick/Tap aktiviert die Bewegungs-Handler (ausser "interactive: always").
 * - Unterstuetzt mehrere Karten pro Seite, Multi-Marker und Cluster-Marker.
 *
 * Konfiguration kommt pro Karte als JSON im Attribut data-maplibre des .maplibre-map-Containers.
 */
(function () {
  'use strict';

  var CSS_TPL = 'https://unpkg.com/maplibre-gl@{v}/dist/maplibre-gl.css';
  var JS_TPL = 'https://unpkg.com/maplibre-gl@{v}/dist/maplibre-gl.js';
  var DEFAULT_VERSION = '4.7.1';

  var loaderStarted = false;
  var queue = [];

  function ensureMaplibre(version, cb) {
    if (window.maplibregl) { cb(); return; }

    queue.push(cb);

    if (loaderStarted) { return; }
    loaderStarted = true;

    var v = version || DEFAULT_VERSION;

    var css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = CSS_TPL.replace('{v}', v);
    css.crossOrigin = 'anonymous';
    document.head.appendChild(css);

    var js = document.createElement('script');
    js.src = JS_TPL.replace('{v}', v);
    js.crossOrigin = 'anonymous';
    js.onload = function () {
      queue.forEach(function (fn) { fn(); });
      queue = [];
    };
    document.head.appendChild(js);
  }

  function parseConfig(el) {
    try {
      return JSON.parse(el.getAttribute('data-maplibre'));
    } catch (e) {
      return null;
    }
  }

  function buildPopup(m) {
    var wrap = document.createElement('div');
    wrap.className = 'maplibre-popup';

    if (m.title) {
      var title = document.createElement('strong');
      title.className = 'maplibre-popup__title';
      title.textContent = m.title;
      wrap.appendChild(title);
    }

    if (m.address) {
      var address = document.createElement('div');
      address.className = 'maplibre-popup__address';
      address.textContent = m.address;
      wrap.appendChild(address);
    }

    if (m.link) {
      var link = document.createElement('a');
      link.className = 'maplibre-popup__link';
      link.href = m.link;
      link.textContent = (window.MAPLIBRE_I18N && window.MAPLIBRE_I18N.more) || 'Mehr erfahren';
      wrap.appendChild(link);
    }

    return wrap;
  }

  function boundsOf(markers) {
    var b = new maplibregl.LngLatBounds();
    markers.forEach(function (m) { b.extend([m.lng, m.lat]); });
    return b;
  }

  function fitOrCenter(map, cfg) {
    var markers = cfg.markers;

    if (cfg.fitBounds && markers.length > 1) {
      map.fitBounds(boundsOf(markers), { padding: 48, maxZoom: 16, duration: 0 });
    } else if (markers.length === 1 && !cfg.center) {
      map.setCenter([markers[0].lng, markers[0].lat]);
      map.setZoom(cfg.zoom || 15);
    }
  }

  // Farbiger Tropfen-Pin (Markenfarbe) mit weißem Icon-Glyph (per CSS-Maske) – wie Google My Maps.
  // Die Pin-Spitze sitzt bei viewBox-Punkt (15,40) unten-mittig, daher anchor 'bottom'.
  function buildPinElement(color, iconUrl) {
    var wrap = document.createElement('div');
    wrap.className = 'maplibre-pin';
    wrap.style.setProperty('--pin-color', color);
    wrap.innerHTML = '<svg class="maplibre-pin__shape" viewBox="0 0 30 40" aria-hidden="true">'
      + '<path d="M15 0.5C7.3 0.5 1 6.8 1 14.5C1 24.5 15 39.5 15 39.5C15 39.5 29 24.5 29 14.5C29 6.8 22.7 0.5 15 0.5Z"/>'
      + '</svg><span class="maplibre-pin__icon"></span>';
    var glyph = wrap.querySelector('.maplibre-pin__icon');
    glyph.style.webkitMaskImage = 'url("' + iconUrl + '")';
    glyph.style.maskImage = 'url("' + iconUrl + '")';
    return wrap;
  }

  function addPins(map, cfg) {
    cfg.markers.forEach(function (m) {
      var marker;
      var popupOffset;

      if (m.icon) {
        marker = new maplibregl.Marker({ element: buildPinElement(m.color || cfg.markerColor, m.icon), anchor: 'bottom' });
        popupOffset = [0, -42];
      } else {
        marker = new maplibregl.Marker({ color: m.color || cfg.markerColor });
        popupOffset = 24;
      }

      marker.setLngLat([m.lng, m.lat]);

      if (m.title || m.address || m.link) {
        marker.setPopup(new maplibregl.Popup({ offset: popupOffset }).setDOMContent(buildPopup(m)));
      }

      marker.addTo(map);
    });
  }

  // Nummerierter Kreis-Marker für einen Cluster.
  function buildClusterElement(count, color) {
    var el = document.createElement('div');
    el.className = 'maplibre-cluster';
    el.style.setProperty('--cluster-color', color);
    el.textContent = String(count);
    return el;
  }

  // Cluster über HTML-Marker statt Circle-Layer: Einzelpunkte erhalten dieselben Icon-Pins wie im
  // Pin-Modus (konsistente Optik), Cluster werden als nummerierte Kreise dargestellt. Die GeoJSON-
  // Source übernimmt das Clustering; 'render' + querySourceFeatures synchronisieren die HTML-Marker.
  function addClustered(map, cfg) {
    var color = cfg.markerColor;

    var features = cfg.markers.map(function (m, i) {
      return {
        type: 'Feature',
        geometry: { type: 'Point', coordinates: [m.lng, m.lat] },
        properties: {
          pid: i,
          title: m.title || '',
          address: m.address || '',
          link: m.link || '',
          icon: m.icon || '',
          color: m.color || ''
        }
      };
    });

    map.addSource('maplibre-markers', {
      type: 'geojson',
      data: { type: 'FeatureCollection', features: features },
      cluster: true,
      clusterRadius: cfg.clusterRadius || 50,
      clusterMaxZoom: 16
    });

    // Unsichtbarer Layer, damit die Source getilet und per querySourceFeatures abfragbar ist.
    map.addLayer({
      id: 'maplibre-cluster-src',
      type: 'circle',
      source: 'maplibre-markers',
      paint: { 'circle-radius': 0, 'circle-color': 'rgba(0,0,0,0)' }
    });

    var markers = {};
    var onScreen = {};

    function makePointMarker(coords, p) {
      var m = { lng: coords[0], lat: coords[1], title: p.title, address: p.address, link: p.link, icon: p.icon, color: p.color };
      var marker;

      if (m.icon) {
        marker = new maplibregl.Marker({ element: buildPinElement(m.color || color, m.icon), anchor: 'bottom' });
      } else {
        marker = new maplibregl.Marker({ color: m.color || color });
      }

      marker.setLngLat(coords);

      if (m.title || m.address || m.link) {
        marker.setPopup(new maplibregl.Popup({ offset: m.icon ? [0, -42] : 24 }).setDOMContent(buildPopup(m)));
      }

      return marker;
    }

    function makeClusterMarker(coords, p) {
      var el = buildClusterElement(p.point_count_abbreviated || p.point_count, color);
      var clusterId = p.cluster_id;

      el.addEventListener('click', function () {
        map.getSource('maplibre-markers').getClusterExpansionZoom(clusterId).then(function (zoom) {
          map.easeTo({ center: coords, zoom: zoom });
        });
      });

      return new maplibregl.Marker({ element: el }).setLngLat(coords);
    }

    function updateMarkers() {
      var fresh = {};
      var feats = map.querySourceFeatures('maplibre-markers');

      for (var i = 0; i < feats.length; i++) {
        var coords = feats[i].geometry.coordinates;
        var p = feats[i].properties;
        var key = p.cluster ? ('c' + p.cluster_id) : ('p' + p.pid);

        if (fresh[key]) { continue; }

        if (!markers[key]) {
          markers[key] = p.cluster ? makeClusterMarker(coords, p) : makePointMarker(coords, p);
        }

        fresh[key] = markers[key];

        if (!onScreen[key]) { markers[key].addTo(map); }
      }

      for (var k in onScreen) {
        if (!fresh[k]) { onScreen[k].remove(); }
      }

      onScreen = fresh;
    }

    map.on('render', function () {
      if (map.isSourceLoaded('maplibre-markers')) { updateMarkers(); }
    });
    map.on('moveend', updateMarkers);
  }

  function initMap(el) {
    if (el.maplibreInitialized) { return; }
    el.maplibreInitialized = true;

    var cfg = parseConfig(el);
    if (!cfg || !cfg.markers || !cfg.markers.length) { return; }

    var clickToActivate = cfg.interactive !== 'always';

    var map = new maplibregl.Map({
      container: el,
      style: cfg.style,
      center: cfg.center || [0, 0],
      zoom: cfg.zoom || 13,
      attributionControl: true,
      scrollZoom: !clickToActivate,
      dragPan: !clickToActivate,
      dragRotate: false,
      touchZoomRotate: !clickToActivate,
      touchPitch: false,
      doubleClickZoom: !clickToActivate,
      keyboard: !clickToActivate,
      boxZoom: false
    });

    if (cfg.navigation) {
      map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');
    }

    // Ausschnitt (fitBounds/Center) erst anwenden, wenn der Container echte Breite hat – sonst
    // wuerde ein zunaechst 0px breiter Container (z. B. in einem versteckten Tab) einen falschen
    // Ausschnitt erzeugen.
    var viewApplied = false;

    function applyView() {
      if (viewApplied || 0 === el.clientWidth) { return; }
      viewApplied = true;
      fitOrCenter(map, cfg);
    }

    // Haelt die Karte an die Container-Groesse angepasst – robust gegen CSS-Ladereihenfolge,
    // responsive Layouts und nachtraegliches Einblenden (MapLibre trackt Container-Resize nicht selbst).
    if (window.ResizeObserver) {
      new ResizeObserver(function () {
        map.resize();
        applyView();
      }).observe(el);
    }

    // HTML-Marker und Kamera benoetigen den geladenen Style NICHT und werden sofort gesetzt – robust
    // auch dann, wenn das 'load'-Event verzoegert ist. Nur die Cluster-Layer (addSource/addLayer)
    // brauchen den fertigen Style.
    if (cfg.cluster && cfg.markers.length > 1) {
      if (map.isStyleLoaded()) {
        addClustered(map, cfg);
      } else {
        map.on('load', function () { addClustered(map, cfg); });
      }
    } else {
      addPins(map, cfg);
    }

    applyView();

    map.on('load', function () {
      map.resize();
      applyView();
    });

    if (!clickToActivate) {
      el.classList.add('maplibre-map--active');
      return;
    }

    var activate = function () {
      map.scrollZoom.enable();
      map.dragPan.enable();
      map.touchZoomRotate.enable();
      map.doubleClickZoom.enable();
      map.keyboard.enable();
      el.classList.add('maplibre-map--active');
    };

    el.addEventListener('click', activate, { once: true });
    el.addEventListener('touchstart', activate, { once: true, passive: true });
  }

  function initAll() {
    var els = document.querySelectorAll('.maplibre-map[data-maplibre]');
    if (!els.length) { return; }

    // Höhe per CSSOM statt Inline-style-Attribut im Template: eine CSP ohne 'unsafe-inline' in
    // style-src verwirft das Attribut (Karte 0px hoch). Vor dem Nachladen von MapLibre, damit
    // das Layout nicht springt.
    els.forEach(function (el) {
      var height = parseInt(el.dataset.height, 10);
      if (height > 0) { el.style.height = height + 'px'; }
    });

    var version = DEFAULT_VERSION;
    var first = parseConfig(els[0]);
    if (first && first.maplibreVersion) { version = first.maplibreVersion; }

    ensureMaplibre(version, function () {
      els.forEach(initMap);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }
})();
