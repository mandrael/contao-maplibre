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

  // Zweite Schicht gegen javascript:/data:/vbscript:-Links: Marker::sanitizeLink (PHP) bereinigt bereits
  // die eigenen Standorte/Ad-hoc-Marker, aber die Karte akzeptiert (per API) auch fremde JSON-Quellen.
  function isSafeLink(link) {
    link = String(link).trim();
    // Browser lesen "\" wie "/" und streichen Steuerzeichen, beides führt sonst auf fremde Domains.
    if (/[\\\u0000-\u001F\u007F]/.test(link)) { return false; }
    if (link.indexOf('//') === 0) { return false; }
    if (/^(https?:|mailto:|tel:|\/(?!\/)|#|\?)/i.test(link)) { return true; }

    var idx = link.search(/[\/?#:]/);
    return idx === -1 || link.charAt(idx) !== ':';
  }

  function buildPopup(m, cfg) {
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

    if (m.description) {
      var description = document.createElement('div');
      description.className = 'maplibre-popup__description';
      description.textContent = m.description;
      wrap.appendChild(description);
    }

    if (m.link && isSafeLink(m.link)) {
      var link = document.createElement('a');
      link.className = 'maplibre-popup__link';
      // Contao setzt <base href> auf die Startseite: "#anker" würde dorthin springen statt auf diese Seite.
      link.href = m.link.charAt(0) === '#' ? window.location.pathname + window.location.search + m.link : m.link;
      link.textContent = (window.MAPLIBRE_I18N && window.MAPLIBRE_I18N.more) || 'Mehr erfahren';
      // Fremde Seiten im neuen Tab, damit die Karte offen bleibt; Anker und eigene Seiten im selben Tab.
      if (/^https?:/i.test(m.link)) { link.target = '_blank'; link.rel = 'noopener'; }
      wrap.appendChild(link);
    }

    if (cfg && cfg.gmapsLink) {
      // Mit Name und Adresse öffnet Google die Ortskarte; ohne Adresse genügen die Koordinaten.
      var query = m.address ? (m.title ? m.title + ', ' : '') + m.address : m.lat + ',' + m.lng;
      var gmaps = document.createElement('a');
      gmaps.className = 'maplibre-popup__gmaps';
      gmaps.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(query);
      gmaps.target = '_blank';
      gmaps.rel = 'noopener';
      gmaps.textContent = cfg.gmapsLabel || 'In Google Maps öffnen';
      wrap.appendChild(gmaps);
    }

    return wrap;
  }

  function hasPopup(m, cfg) {
    return !!(m.title || m.address || m.description || m.link || (cfg && cfg.gmapsLink));
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
      map.setZoom(typeof cfg.zoom === 'number' ? cfg.zoom : 15);
    } else if (markers.length > 1 && !cfg.fitBounds && !cfg.center) {
      // Kein fitBounds und kein fester Mittelpunkt: auf die Mitte der Marker-Bounds zentrieren.
      map.setCenter(boundsOf(markers).getCenter());
      map.setZoom(typeof cfg.zoom === 'number' ? cfg.zoom : 13);
    }
  }

  function setMask(el, iconUrl) {
    // encodeURI kodiert die URL; " und \ zusätzlich maskieren, damit ein Dateiname nicht aus url("...") ausbricht.
    var safeUrl = encodeURI(iconUrl).replace(/\\/g, '%5C').replace(/"/g, '%22');
    el.style.webkitMaskImage = 'url("' + safeUrl + '")';
    el.style.maskImage = 'url("' + safeUrl + '")';
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
    setMask(wrap.querySelector('.maplibre-pin__icon'), iconUrl);
    return wrap;
  }

  // Gibt eine Filterfunktion zurück: hidden = { Kategorie: true }, Marker ohne Kategorie bleiben sichtbar.
  function addPins(map, cfg) {
    var entries = [];

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

      if (hasPopup(m, cfg)) {
        marker.setPopup(new maplibregl.Popup({ offset: popupOffset }).setDOMContent(buildPopup(m, cfg)));
      }

      marker.addTo(map);
      entries.push({ marker: marker, category: m.category || '', shown: true });
    });

    return function (hidden) {
      entries.forEach(function (e) {
        var show = !(e.category && hidden[e.category]);
        if (show === e.shown) { return; }
        e.shown = show;
        if (show) { e.marker.addTo(map); } else { e.marker.remove(); }
      });
    };
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
          color: m.color || '',
          description: m.description || '',
          category: m.category || ''
        }
      };
    });

    function collection(hidden) {
      return {
        type: 'FeatureCollection',
        features: features.filter(function (f) {
          return !(f.properties.category && hidden[f.properties.category]);
        })
      };
    }

    map.addSource('maplibre-markers', {
      type: 'geojson',
      data: collection({}),
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
      var m = { lng: coords[0], lat: coords[1], title: p.title, address: p.address, link: p.link, icon: p.icon, color: p.color, description: p.description, category: p.category };
      var marker;

      if (m.icon) {
        marker = new maplibregl.Marker({ element: buildPinElement(m.color || color, m.icon), anchor: 'bottom' });
      } else {
        marker = new maplibregl.Marker({ color: m.color || color });
      }

      marker.setLngLat(coords);

      if (hasPopup(m, cfg)) {
        marker.setPopup(new maplibregl.Popup({ offset: m.icon ? [0, -42] : 24 }).setDOMContent(buildPopup(m, cfg)));
      }

      return marker;
    }

    function makeClusterMarker(coords, p) {
      var el = buildClusterElement(p.point_count_abbreviated || p.point_count, color);
      var clusterId = p.cluster_id;

      el.addEventListener('click', function () {
        map.getSource('maplibre-markers').getClusterExpansionZoom(clusterId).then(function (zoom) {
          map.easeTo({ center: coords, zoom: zoom });
        }).catch(function () {});
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

    return function (hidden) {
      // Cluster-IDs gelten nur für den jeweiligen Datensatz: alte HTML-Marker verwerfen, 'render' baut neu auf.
      for (var k in onScreen) { onScreen[k].remove(); }
      markers = {};
      onScreen = {};
      map.getSource('maplibre-markers').setData(collection(hidden));
    };
  }

  // Kategorien-Legende als MapLibre-Control. Auf schmalen Karten startet sie eingeklappt.
  function buildLegend(cfg, onChange, collapsed) {
    var cats = [];
    // Ohne Prototyp: eine Kategorie namens "constructor" oder "__proto__" darf nichts Geerbtes treffen.
    var byCat = Object.create(null);

    cfg.markers.forEach(function (m) {
      if (!m.category) { return; }
      if (!byCat[m.category]) {
        byCat[m.category] = { name: m.category, count: 0, icon: m.icon || '', color: m.color || cfg.markerColor };
        cats.push(byCat[m.category]);
      }
      byCat[m.category].count++;
    });

    // Eine einzige Kategorie auszublenden hieße, die Karte zu leeren; die Legende lohnt erst ab zwei.
    if (cats.length < 2) { return null; }

    var title = cfg.legendTitle || 'Kategorien';
    var hidden = Object.create(null);
    var box;

    return {
      onAdd: function () {
        box = document.createElement('div');
        box.className = 'maplibregl-ctrl maplibre-legend';
        box.setAttribute('role', 'group');
        box.setAttribute('aria-label', title);

        var list = document.createElement('div');
        list.className = 'maplibre-legend__list';

        if (collapsed !== null) {
          var toggle = document.createElement('button');
          toggle.type = 'button';
          toggle.className = 'maplibre-legend__toggle';
          toggle.textContent = title;
          var sync = function () {
            box.classList.toggle('maplibre-legend--collapsed', collapsed);
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
          };
          toggle.addEventListener('click', function () { collapsed = !collapsed; sync(); });
          box.appendChild(toggle);
          sync();
        }

        cats.forEach(function (c) {
          var label = document.createElement('label');
          label.className = 'maplibre-legend__item';

          var input = document.createElement('input');
          input.type = 'checkbox';
          input.checked = true;
          input.addEventListener('change', function () {
            if (input.checked) { delete hidden[c.name]; } else { hidden[c.name] = true; }
            onChange(hidden);
          });

          var icon = document.createElement('span');
          icon.className = 'maplibre-legend__icon' + (c.icon ? '' : ' maplibre-legend__icon--dot');
          icon.style.backgroundColor = c.color;
          if (c.icon) { setMask(icon, c.icon); }

          var name = document.createElement('span');
          name.className = 'maplibre-legend__name';
          name.textContent = c.name;

          var count = document.createElement('span');
          count.className = 'maplibre-legend__count';
          count.textContent = String(c.count);

          label.appendChild(input);
          label.appendChild(icon);
          label.appendChild(name);
          label.appendChild(count);
          list.appendChild(label);
        });

        box.appendChild(list);
        return box;
      },
      onRemove: function () {
        if (box && box.parentNode) { box.parentNode.removeChild(box); }
      }
    };
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
      zoom: typeof cfg.zoom === 'number' ? cfg.zoom : 13,
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
    // Die Legende filtert über applyFilter; im Cluster-Modus ist die Funktion erst nach dem Style-Load da.
    var applyFilter = null;
    var pendingHidden = null;

    function setFilter(fn) {
      applyFilter = fn;
      if (pendingHidden) { fn(pendingHidden); }
    }

    if (cfg.cluster && cfg.markers.length > 1) {
      if (map.isStyleLoaded()) {
        setFilter(addClustered(map, cfg));
      } else {
        map.on('load', function () { setFilter(addClustered(map, cfg)); });
      }
    } else {
      setFilter(addPins(map, cfg));
    }

    if (cfg.legend) {
      var legend = buildLegend(cfg, function (hidden) {
        pendingHidden = hidden;
        if (applyFilter) { applyFilter(hidden); }
      }, el.clientWidth > 0 && el.clientWidth < 480 ? true : null);

      if (legend) { map.addControl(legend, 'top-left'); }
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

    // Bedienung der Legende soll die Karte nicht aktivieren (sonst fängt sie danach das Seiten-Scrollen ab).
    var onFirstTouch = function (e) {
      if (e.target && e.target.closest && e.target.closest('.maplibre-legend')) { return; }
      el.removeEventListener('click', onFirstTouch);
      el.removeEventListener('touchstart', onFirstTouch);
      activate();
    };

    el.addEventListener('click', onFirstTouch);
    el.addEventListener('touchstart', onFirstTouch, { passive: true });
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
