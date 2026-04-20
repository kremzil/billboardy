import { MarkerClusterer } from '@googlemaps/markerclusterer';

declare global {
  interface Window {
    google: typeof google;
    BILLBOARDY_MAP_CONFIG?: Partial<MapConfig>;
  }
}

type MapConfig = {
  apiBase: string;
  googleMapsApiKey: string;
  contactUrl: string;
  placeholderImageUrl: string;
  defaultCenter: google.maps.LatLngLiteral;
};

type ApiCollection<T> = {
  data: T;
};

type FilterOption = {
  value: string;
  label: string;
};

type FiltersPayload = {
  mediaTypes: FilterOption[];
  cities: FilterOption[];
};

type MapPoint = {
  id: string;
  code: string;
  title: string;
  mediaType: string;
  latitude: number;
  longitude: number;
  imageUrl: string;
  locationLabel: string;
  sizeLabel: string;
};

type AdSpace = MapPoint & {
  sourceId: number;
  mediaTypeLabel: string;
  city: string;
  addressText: string;
  widthCm: number | null;
  heightCm: number | null;
  descriptionHtml: string;
  status: string;
  visibility: string;
  detailUrl: string | null;
  excerpt: string;
};

const strings = {
  title: 'Mapa reklamných plôch',
  intro: 'Vyberte si plochu podľa lokality, typu alebo katalógového čísla.',
  mediaType: 'Typ plochy',
  city: 'Mesto',
  search: 'Vyhľadávanie',
  allTypes: 'Všetky typy',
  allCities: 'Všetky mestá',
  searchPlaceholder: 'Kód alebo lokalita',
  reset: 'Zrušiť filtre',
  loading: 'Načítavam reklamné plochy...',
  empty: 'Nenašli sa žiadne reklamné plochy.',
  error: 'Mapu sa nepodarilo načítať. Skúste to prosím neskôr.',
  missingKey: 'Chýba Google Maps API kľúč.',
  count: 'Zobrazených plôch',
  selection: 'Výber v aktuálnej oblasti',
  groupedHint: 'Priblížte mapu alebo vyberte skupinu pre presnejší zoznam.',
  zoomGroup: 'Priblížiť oblasť',
  showOnMap: 'Zobraziť na mape',
  spaces: 'plôch',
  code: 'Kód',
  type: 'Typ',
  location: 'Lokalita',
  size: 'Rozmer',
  cta: 'Mám záujem',
};

const defaultConfig: MapConfig = {
  apiBase: import.meta.env.PUBLIC_BILLBOARDY_API_BASE ?? '/wp-json/billboardy/v1',
  googleMapsApiKey: import.meta.env.PUBLIC_GOOGLE_MAPS_API_KEY ?? '',
  contactUrl: import.meta.env.PUBLIC_CONTACT_URL ?? '/kontaktujte-nas/',
  placeholderImageUrl: import.meta.env.PUBLIC_PLACEHOLDER_IMAGE_URL ?? '',
  defaultCenter: { lat: 48.1486, lng: 17.1077 },
};

const config: MapConfig = {
  ...defaultConfig,
  ...(window.BILLBOARDY_MAP_CONFIG ?? {}),
  defaultCenter: {
    ...defaultConfig.defaultCenter,
    ...(window.BILLBOARDY_MAP_CONFIG?.defaultCenter ?? {}),
  },
};

const state: {
  map: google.maps.Map | null;
  clusterer: MarkerClusterer | null;
  markers: google.maps.Marker[];
  markerById: Map<string, google.maps.Marker>;
  infoWindow: google.maps.InfoWindow | null;
  detailsCache: Map<string, AdSpace>;
  cardGroups: Map<string, MapPoint[]>;
  points: MapPoint[];
  requestSeq: number;
} = {
  map: null,
  clusterer: null,
  markers: [],
  markerById: new Map(),
  infoWindow: null,
  detailsCache: new Map(),
  cardGroups: new Map(),
  points: [],
  requestSeq: 0,
};

const root = document.querySelector<HTMLElement>('#billboardy-map-app');

if (!root) {
  throw new Error('Missing #billboardy-map-app root element.');
}

renderShell(root);
void boot(root);

async function boot(container: HTMLElement): Promise<void> {
  const status = mustGet<HTMLElement>('.bb-map-status', container);

  try {
    if (!config.googleMapsApiKey) {
      setStatus(status, strings.missingKey, 'error');
      return;
    }

    setStatus(status, strings.loading, 'loading');
    await loadGoogleMaps(config.googleMapsApiKey);

    state.map = new google.maps.Map(mustGet<HTMLElement>('.bb-map-canvas', container), {
      center: config.defaultCenter,
      zoom: 12,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: true,
      clickableIcons: false,
      styles: [
        { featureType: 'poi.business', stylers: [{ visibility: 'off' }] },
        { featureType: 'transit.station', stylers: [{ visibility: 'simplified' }] },
      ],
    });
    state.infoWindow = new google.maps.InfoWindow({ maxWidth: 360 });

    const [filters] = await Promise.all([
      fetchJson<ApiCollection<FiltersPayload>>(`${config.apiBase}/filters`),
      refreshPoints(container, { includeBounds: false, fitToResults: true }),
    ]);

    populateFilters(container, filters.data);
    bindControls(container);
    bindMapViewport(container);
  } catch (error) {
    console.error(error);
    setStatus(status, strings.error, 'error');
  }
}

function renderShell(container: HTMLElement): void {
  container.innerHTML = `
    <section class="bb-map-app" aria-labelledby="bb-map-title">
      <div class="bb-map-toolbar">
        <div class="bb-map-copy">
          <p class="bb-map-kicker">Billboardy.sk</p>
          <h1 id="bb-map-title">${strings.title}</h1>
          <p>${strings.intro}</p>
        </div>
        <form class="bb-map-filters" aria-label="Filtre reklamných plôch">
          <label>
            <span>${strings.mediaType}</span>
            <select name="media_type" disabled>
              <option value="">${strings.allTypes}</option>
            </select>
          </label>
          <label>
            <span>${strings.city}</span>
            <select name="city" disabled>
              <option value="">${strings.allCities}</option>
            </select>
          </label>
          <label class="bb-map-search">
            <span>${strings.search}</span>
            <input name="search" type="search" autocomplete="off" placeholder="${strings.searchPlaceholder}" />
          </label>
          <button class="bb-map-reset" type="reset">${strings.reset}</button>
        </form>
      </div>
      <div class="bb-map-stage">
        <div class="bb-map-status" role="status" aria-live="polite">${strings.loading}</div>
        <div class="bb-map-canvas" aria-label="${strings.title}"></div>
        <aside class="bb-map-results" aria-label="${strings.selection}">
          <div class="bb-map-results-head">
            <h2>${strings.selection}</h2>
            <p>${strings.loading}</p>
          </div>
          <div class="bb-map-results-list"></div>
        </aside>
      </div>
    </section>
  `;
}

function populateFilters(container: HTMLElement, filters: FiltersPayload): void {
  const mediaSelect = mustGet<HTMLSelectElement>('select[name="media_type"]', container);
  const citySelect = mustGet<HTMLSelectElement>('select[name="city"]', container);

  appendOptions(mediaSelect, filters.mediaTypes, strings.allTypes);
  appendOptions(citySelect, filters.cities, strings.allCities);
  mediaSelect.disabled = false;
  citySelect.disabled = false;
}

function appendOptions(select: HTMLSelectElement, options: FilterOption[], fallbackLabel: string): void {
  select.innerHTML = `<option value="">${fallbackLabel}</option>`;

  for (const option of options) {
    const node = document.createElement('option');
    node.value = option.value;
    node.textContent = option.label;
    select.append(node);
  }
}

function bindControls(container: HTMLElement): void {
  const form = mustGet<HTMLFormElement>('.bb-map-filters', container);
  const results = mustGet<HTMLElement>('.bb-map-results-list', container);
  let searchTimer = window.setTimeout(() => undefined, 0);

  form.addEventListener('change', () => {
    void refreshPoints(container, { includeBounds: true, fitToResults: false });
  });

  form.addEventListener('input', (event) => {
    if (!(event.target instanceof HTMLInputElement)) {
      return;
    }

    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
      void refreshPoints(container, { includeBounds: true, fitToResults: false });
    }, 260);
  });

  form.addEventListener('reset', () => {
    window.setTimeout(() => {
      void refreshPoints(container, { includeBounds: true, fitToResults: false });
    }, 0);
  });

  results.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target.closest<HTMLElement>('[data-card-action]') : null;

    if (!target) {
      return;
    }

    const action = target.dataset.cardAction;
    const id = target.dataset.cardId ?? '';

    if (action === 'focus-point') {
      const point = state.points.find((item) => item.id === id);
      const marker = state.markerById.get(id);

      if (point && marker && state.map) {
        state.map.panTo({ lat: point.latitude, lng: point.longitude });
        state.map.setZoom(Math.max(state.map.getZoom() ?? 14, 15));
        void openPoint(point, marker);
      }
    }

    if (action === 'focus-group') {
      const points = state.cardGroups.get(id);

      if (points && state.map) {
        fitToPoints(points, 80);
      }
    }
  });
}

function bindMapViewport(container: HTMLElement): void {
  if (!state.map) {
    return;
  }

  let viewportTimer = window.setTimeout(() => undefined, 0);

  state.map.addListener('idle', () => {
    window.clearTimeout(viewportTimer);
    viewportTimer = window.setTimeout(() => {
      void refreshPoints(container, { includeBounds: true, fitToResults: false });
    }, 320);
  });
}

async function refreshPoints(
  container: HTMLElement,
  options: { includeBounds: boolean; fitToResults: boolean } = { includeBounds: true, fitToResults: false },
): Promise<void> {
  const requestId = ++state.requestSeq;
  const status = mustGet<HTMLElement>('.bb-map-status', container);
  setStatus(status, strings.loading, 'loading');

  const params = new URLSearchParams();
  const form = container.querySelector<HTMLFormElement>('.bb-map-filters');

  if (form) {
    const formData = new FormData(form);

    for (const [key, value] of formData.entries()) {
      const text = String(value).trim();

      if (text !== '') {
        params.set(key, text);
      }
    }
  }

  if (options.includeBounds) {
    const bounds = state.map?.getBounds();

    if (bounds) {
      const northEast = bounds.getNorthEast();
      const southWest = bounds.getSouthWest();
      params.set('north', northEast.lat().toString());
      params.set('east', northEast.lng().toString());
      params.set('south', southWest.lat().toString());
      params.set('west', southWest.lng().toString());
    }
  }

  const url = `${config.apiBase}/map-points${params.size > 0 ? `?${params.toString()}` : ''}`;
  const payload = await fetchJson<ApiCollection<MapPoint[]>>(url);

  if (requestId !== state.requestSeq) {
    return;
  }

  state.points = payload.data;
  renderMarkers(container, state.points, options.fitToResults);
  renderResultCards(container, state.points);

  if (state.points.length === 0) {
    setStatus(status, strings.empty, 'empty');
    return;
  }

  setStatus(status, `${strings.count}: ${state.points.length}`, 'ready');
}

function renderMarkers(container: HTMLElement, points: MapPoint[], fitToResults: boolean): void {
  if (!state.map) {
    return;
  }

  state.clusterer?.clearMarkers();
  state.markers.forEach((marker) => marker.setMap(null));
  state.markers = [];
  state.markerById.clear();

  const bounds = new google.maps.LatLngBounds();

  state.markers = points.map((point) => {
    const marker = new google.maps.Marker({
      position: { lat: point.latitude, lng: point.longitude },
      title: point.title,
      icon: markerIcon(point.mediaType),
    });

    marker.addListener('click', () => {
      void openPoint(point, marker);
    });

    bounds.extend(marker.getPosition() as google.maps.LatLng);
    state.markerById.set(point.id, marker);
    return marker;
  });

  state.clusterer = new MarkerClusterer({
    map: state.map,
    markers: state.markers,
  });

  if (!fitToResults) {
    return;
  }

  if (points.length === 1) {
    state.map.setCenter({ lat: points[0].latitude, lng: points[0].longitude });
    state.map.setZoom(15);
    return;
  }

  if (!bounds.isEmpty()) {
    state.map.fitBounds(bounds, 48);
  }

  mustGet<HTMLElement>('.bb-map-stage', container).classList.add('is-ready');
}

function renderResultCards(container: HTMLElement, points: MapPoint[]): void {
  const head = mustGet<HTMLElement>('.bb-map-results-head p', container);
  const list = mustGet<HTMLElement>('.bb-map-results-list', container);
  const cards = buildResultCards(points);

  state.cardGroups.clear();

  if (points.length === 0) {
    head.textContent = strings.empty;
    list.innerHTML = '';
    return;
  }

  const grouped = cards.some((card) => card.kind === 'group');
  head.textContent = grouped ? `${points.length} ${strings.spaces}. ${strings.groupedHint}` : `${points.length} ${strings.spaces}`;

  list.innerHTML = cards.map((card) => {
    if (card.kind === 'group') {
      state.cardGroups.set(card.id, card.points);

      return `
        <article class="bb-map-result-card bb-map-result-group">
          <div>
            <strong>${card.points.length} ${strings.spaces}</strong>
            <span>${escapeHtml(card.label)}</span>
          </div>
          <button type="button" data-card-action="focus-group" data-card-id="${escapeAttribute(card.id)}">
            ${strings.zoomGroup}
          </button>
        </article>
      `;
    }

    return `
      <article class="bb-map-result-card">
        ${card.point.imageUrl ? `<img src="${escapeAttribute(card.point.imageUrl)}" alt="${escapeAttribute(card.point.title)}" loading="lazy" />` : ''}
        <div class="bb-map-result-body">
          <strong>${escapeHtml(card.point.title)}</strong>
          <span>${escapeHtml(card.point.locationLabel)}</span>
          ${card.point.sizeLabel ? `<small>${escapeHtml(card.point.sizeLabel)}</small>` : ''}
          <button type="button" data-card-action="focus-point" data-card-id="${escapeAttribute(card.point.id)}">
            ${strings.showOnMap}
          </button>
        </div>
      </article>
    `;
  }).join('');
}

function buildResultCards(points: MapPoint[]): Array<{ kind: 'point'; point: MapPoint } | { kind: 'group'; id: string; label: string; points: MapPoint[] }> {
  const zoom = state.map?.getZoom() ?? 12;

  if (points.length <= 80 && zoom >= 13) {
    return points
      .slice()
      .sort((a, b) => a.code.localeCompare(b.code, 'sk', { numeric: true }))
      .map((point) => ({ kind: 'point', point }));
  }

  const cellSize = zoom < 8 ? 0.75 : zoom < 10 ? 0.25 : zoom < 12 ? 0.08 : 0.025;
  const groups = new Map<string, MapPoint[]>();

  for (const point of points) {
    const latKey = Math.floor(point.latitude / cellSize);
    const lngKey = Math.floor(point.longitude / cellSize);
    const key = `${latKey}:${lngKey}`;
    const group = groups.get(key) ?? [];
    group.push(point);
    groups.set(key, group);
  }

  return Array.from(groups.entries())
    .map(([id, group]) => ({
      kind: 'group' as const,
      id,
      label: groupLabel(group),
      points: group,
    }))
    .sort((a, b) => b.points.length - a.points.length)
    .slice(0, 60);
}

function groupLabel(points: MapPoint[]): string {
  const labels = new Map<string, number>();

  for (const point of points) {
    const label = point.locationLabel.split(',').slice(-1)[0]?.trim() || point.locationLabel;
    labels.set(label, (labels.get(label) ?? 0) + 1);
  }

  return Array.from(labels.entries()).sort((a, b) => b[1] - a[1])[0]?.[0] ?? strings.selection;
}

function fitToPoints(points: MapPoint[], padding: number): void {
  if (!state.map || points.length === 0) {
    return;
  }

  if (points.length === 1) {
    state.map.panTo({ lat: points[0].latitude, lng: points[0].longitude });
    state.map.setZoom(Math.max(state.map.getZoom() ?? 14, 15));
    return;
  }

  const bounds = new google.maps.LatLngBounds();

  for (const point of points) {
    bounds.extend({ lat: point.latitude, lng: point.longitude });
  }

  state.map.fitBounds(bounds, padding);
}

async function openPoint(point: MapPoint, marker: google.maps.Marker): Promise<void> {
  if (!state.infoWindow || !state.map) {
    return;
  }

  state.infoWindow.setContent(popupLoading(point));
  state.infoWindow.open({ anchor: marker, map: state.map });

  try {
    const detail = await getAdSpace(point.id);
    state.infoWindow.setContent(popupContent(detail));
  } catch (error) {
    console.error(error);
    state.infoWindow.setContent(`<div class="bb-map-popup"><p>${strings.error}</p></div>`);
  }
}

async function getAdSpace(id: string): Promise<AdSpace> {
  const cached = state.detailsCache.get(id);

  if (cached) {
    return cached;
  }

  const payload = await fetchJson<ApiCollection<AdSpace>>(`${config.apiBase}/ad-spaces/${encodeURIComponent(id)}`);
  state.detailsCache.set(id, payload.data);

  return payload.data;
}

function popupLoading(point: MapPoint): string {
  return `
    <div class="bb-map-popup">
      <strong>${escapeHtml(point.title)}</strong>
      <p>${strings.loading}</p>
    </div>
  `;
}

function popupContent(adSpace: AdSpace): string {
  const image = adSpace.imageUrl || config.placeholderImageUrl;
  const contactUrl = buildContactUrl(adSpace.code);
  const description = adSpace.descriptionHtml ? `<div class="bb-map-popup-description">${adSpace.descriptionHtml}</div>` : '';

  return `
    <article class="bb-map-popup">
      ${image ? `<img src="${escapeAttribute(image)}" alt="${escapeAttribute(adSpace.title)}" loading="lazy" />` : ''}
      <div class="bb-map-popup-body">
        <h2>${escapeHtml(adSpace.title)}</h2>
        <dl>
          <div><dt>${strings.code}</dt><dd>${escapeHtml(adSpace.code)}</dd></div>
          <div><dt>${strings.type}</dt><dd>${escapeHtml(adSpace.mediaTypeLabel)}</dd></div>
          <div><dt>${strings.location}</dt><dd>${escapeHtml(adSpace.locationLabel)}</dd></div>
          ${adSpace.sizeLabel ? `<div><dt>${strings.size}</dt><dd>${escapeHtml(adSpace.sizeLabel)}</dd></div>` : ''}
        </dl>
        ${description}
        <a class="bb-map-popup-cta" href="${escapeAttribute(contactUrl)}">${strings.cta}</a>
      </div>
    </article>
  `;
}

function buildContactUrl(code: string): string {
  const url = new URL(config.contactUrl, window.location.href);
  url.searchParams.set('ad_space', code);

  return url.toString();
}

function markerIcon(mediaType: string): google.maps.Symbol {
  const color = mediaType === 'billboard' ? '#0f8b5f' : '#d03f2f';

  return {
    path: google.maps.SymbolPath.CIRCLE,
    fillColor: color,
    fillOpacity: 0.95,
    scale: 7,
    strokeColor: '#ffffff',
    strokeWeight: 2,
  };
}

async function fetchJson<T>(url: string): Promise<T> {
  const response = await fetch(url, {
    headers: {
      Accept: 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error(`Request failed: ${response.status} ${url}`);
  }

  return response.json() as Promise<T>;
}

function loadGoogleMaps(apiKey: string): Promise<void> {
  if (window.google?.maps) {
    return Promise.resolve();
  }

  return new Promise((resolve, reject) => {
    const existing = document.querySelector<HTMLScriptElement>('script[data-billboardy-google-maps]');

    if (existing) {
      existing.addEventListener('load', () => resolve(), { once: true });
      existing.addEventListener('error', () => reject(new Error('Google Maps failed to load.')), { once: true });
      return;
    }

    const script = document.createElement('script');
    script.dataset.billboardyGoogleMaps = 'true';
    script.async = true;
    script.defer = true;
    script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&v=weekly`;
    script.addEventListener('load', () => resolve(), { once: true });
    script.addEventListener('error', () => reject(new Error('Google Maps failed to load.')), { once: true });
    document.head.append(script);
  });
}

function setStatus(node: HTMLElement, message: string, mode: 'loading' | 'ready' | 'empty' | 'error'): void {
  node.textContent = message;
  node.dataset.state = mode;
}

function mustGet<T extends Element>(selector: string, scope: ParentNode = document): T {
  const node = scope.querySelector<T>(selector);

  if (!node) {
    throw new Error(`Missing required element: ${selector}`);
  }

  return node;
}

function escapeHtml(value: string): string {
  return value.replace(/[&<>"']/g, (char) => {
    const entities: Record<string, string> = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    };

    return entities[char];
  });
}

function escapeAttribute(value: string): string {
  return escapeHtml(value).replace(/`/g, '&#096;');
}
