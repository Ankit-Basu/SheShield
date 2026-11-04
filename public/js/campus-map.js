/* global L, SS, lpuLocations */
/* Shared LPU map: satellite and street views, campus outline, landmarks. */
(function () {
  'use strict';

  const ESRI = 'https://server.arcgisonline.com/ArcGIS/rest/services';
  const CREDIT = 'Imagery &copy; Esri, Maxar &middot; Outline &copy; OpenStreetMap';
  const tile = (path, extra = {}) => L.tileLayer(`${ESRI}/${path}/MapServer/tile/{z}/{y}/{x}`, {
    maxZoom: 20, maxNativeZoom: 18, updateWhenZooming: false, keepBuffer: 3, ...extra,
  });

  const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const landmarkIcons = { academic: 'graduation-cap', residential: 'house', commercial: 'store', entrance: 'door-open', recreational: 'trophy' };

  function create(elementId) {
    const map = L.map(elementId, { zoomControl: false, zoomSnap: 0.5 });
    map.attributionControl.setPrefix(false);
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    const satellite = L.layerGroup([
      tile('World_Imagery', { attribution: CREDIT }),
      tile('Reference/World_Transportation', { opacity: 0.55 }),
      tile('Reference/World_Boundaries_and_Places', { maxNativeZoom: 17 }),
    ]);
    const streets = L.layerGroup([
      tile('World_Street_Map', { attribution: 'Tiles &copy; Esri &middot; Outline &copy; OpenStreetMap' }),
    ]);
    satellite.addTo(map);

    // Satellite / street switch, in the same glass style as the rest of the page.
    const Switcher = L.Control.extend({
      options: { position: 'topright' },
      onAdd() {
        const box = L.DomUtil.create('div', 'map-switch tabs');
        box.innerHTML = `<button type="button" class="tab active" data-view="satellite">${SS.icon('satellite')} Satellite</button>
          <button type="button" class="tab" data-view="streets">${SS.icon('map')} Streets</button>`;
        L.DomEvent.disableClickPropagation(box);
        box.addEventListener('click', (e) => {
          const b = e.target.closest('[data-view]');
          if (!b) return;
          box.querySelectorAll('.tab').forEach((t) => t.classList.toggle('active', t === b));
          const showSatellite = b.dataset.view === 'satellite';
          map.removeLayer(showSatellite ? streets : satellite);
          map.addLayer(showSatellite ? satellite : streets);
          outline.setStyle(showSatellite ? { color: '#ffd9a8', fillOpacity: 0.05 } : { color: '#c4461f', fillOpacity: 0.04 });
        });
        return box;
      },
    });
    new Switcher().addTo(map);

    // Real campus boundary, so the university reads at a glance.
    const outline = L.polygon(window.LPU_CAMPUS || [], {
      color: '#ffd9a8', weight: 2, opacity: 0.9, dashArray: '2 7', lineCap: 'round', fillColor: '#ffb27a', fillOpacity: 0.05, interactive: false,
    }).addTo(map);

    const coords = {};
    (typeof lpuLocations !== 'undefined' ? lpuLocations : []).forEach((l) => { coords[l.name.toLowerCase()] = l; });

    // Open on the part of campus people actually use; the outline still shows the whole university.
    const points = Object.values(coords).map((l) => [l.lat, l.lng]);
    if (points.length) map.fitBounds(L.latLngBounds(points), { padding: [90, 90], maxZoom: 17.5 });
    else map.setView([31.2533, 75.704], 16);

    const addLandmarks = () => {
      Object.values(coords).forEach((l) => {
        const icon = L.divIcon({
          className: '',
          html: `<div class="map-landmark">${SS.icon(landmarkIcons[l.type] || 'map-pin')}<span>${escapeHtml(l.name)}</span></div>`,
          iconSize: [0, 0],
        });
        L.marker([l.lat, l.lng], { icon, zIndexOffset: -100 }).addTo(map)
          .bindPopup(`<b>${escapeHtml(l.name)}</b>${escapeHtml(l.description || '')}`);
      });
    };

    setTimeout(() => map.invalidateSize(), 250);
    return { map, coords, addLandmarks, escapeHtml };
  }

  window.CampusMap = { create };
}());
