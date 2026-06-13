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

  function addPins(map, cfg) {
    cfg.markers.forEach(function (m) {
      var marker = new maplibregl.Marker({ color: m.color || cfg.markerColor })
        .setLngLat([m.lng, m.lat]);

      if (m.title || m.address || m.link) {
        marker.setPopup(new maplibregl.Popup({ offset: 24 }).setDOMContent(buildPopup(m)));
      }

      marker.addTo(map);
    });
  }

  function addClustered(map, cfg) {
    var features = cfg.markers.map(function (m) {
      return {
        type: 'Feature',
        geometry: { type: 'Point', coordinates: [m.lng, m.lat] },
        properties: { title: m.title || '', address: m.address || '', link: m.link || '' }
      };
    });

    var color = cfg.markerColor;

    map.addSource('maplibre-markers', {
      type: 'geojson',
      data: { type: 'FeatureCollection', features: features },
      cluster: true,
      clusterRadius: cfg.clusterRadius || 50,
      clusterMaxZoom: 16
    });

    map.addLayer({
      id: 'clusters',
      type: 'circle',
      source: 'maplibre-markers',
      filter: ['has', 'point_count'],
      paint: {
        'circle-color': color,
        'circle-opacity': 0.9,
        'circle-radius': ['step', ['get', 'point_count'], 16, 10, 22, 30, 30],
        'circle-stroke-width': 3,
        'circle-stroke-color': '#ffffff'
      }
    });

    map.addLayer({
      id: 'cluster-count',
      type: 'symbol',
      source: 'maplibre-markers',
      filter: ['has', 'point_count'],
      layout: {
        'text-field': ['get', 'point_count_abbreviated'],
        'text-font': ['Noto Sans Regular'],
        'text-size': 13
      },
      paint: { 'text-color': '#ffffff' }
    });

    map.addLayer({
      id: 'unclustered',
      type: 'circle',
      source: 'maplibre-markers',
      filter: ['!', ['has', 'point_count']],
      paint: {
        'circle-color': color,
        'circle-radius': 8,
        'circle-stroke-width': 3,
        'circle-stroke-color': '#ffffff'
      }
    });

    map.on('click', 'clusters', function (e) {
      var features = map.queryRenderedFeatures(e.point, { layers: ['clusters'] });
      var clusterId = features[0].properties.cluster_id;
      var source = map.getSource('maplibre-markers');

      source.getClusterExpansionZoom(clusterId).then(function (zoom) {
        map.easeTo({ center: features[0].geometry.coordinates, zoom: zoom });
      });
    });

    map.on('click', 'unclustered', function (e) {
      var feature = e.features[0];
      new maplibregl.Popup({ offset: 12 })
        .setLngLat(feature.geometry.coordinates.slice())
        .setDOMContent(buildPopup(feature.properties))
        .addTo(map);
    });

    ['clusters', 'unclustered'].forEach(function (layer) {
      map.on('mouseenter', layer, function () { map.getCanvas().style.cursor = 'pointer'; });
      map.on('mouseleave', layer, function () { map.getCanvas().style.cursor = ''; });
    });
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
