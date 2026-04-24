import { MarkerClusterer, type Marker as ClusterMarker, type Renderer } from '@googlemaps/markerclusterer';

declare global {
  interface Window {
    google: typeof google;
    BILLBOARDY_MAP_CONFIG?: Partial<MapConfig>;
    __billboardyGoogleMapsReady?: () => void;
  }
}

type MapConfig = {
  apiBase: string;
  googleMapsApiKey: string;
  contactUrl: string;
  placeholderImageUrl: string;
  googleMapsMapId: string;
  defaultCenter: google.maps.LatLngLiteral;
  defaultZoom: number;
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
  type?: 'point';
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

type MapBoundsPayload = {
  north: number;
  south: number;
  east: number;
  west: number;
};

type MapCluster = {
  type: 'cluster';
  id: string;
  title: string;
  mediaType: string;
  latitude: number;
  longitude: number;
  count: number;
  locationLabel: string;
  bounds: MapBoundsPayload;
};

type MapItem = MapPoint | MapCluster;

type MapPayload = {
  mode: 'points' | 'clusters' | 'mixed';
  items?: MapItem[];
  meta?: {
    total?: number;
    returned?: number;
    zoom?: number;
  };
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

type AdSpacesPayload = {
  data: AdSpace[];
  pagination: {
    page: number;
    perPage: number;
    total: number;
    totalPages: number;
  };
};

type CoordinateMarkerGroup = {
  id: string;
  point: MapPoint;
  points: MapPoint[];
};

type BillboardyMarker = google.maps.marker.AdvancedMarkerElement & {
  __billboardyMediaType?: string;
};

type BillboardyClusterMarker = ClusterMarker & {
  __billboardyMediaType?: string;
};

type RenderMode = 'direct' | 'clusterer';

type RenderedMarkerEntry = {
  marker: BillboardyMarker;
  signature: string;
  mode: RenderMode;
};

type MarkerSpec = {
  key: string;
  signature: string;
  mode: RenderMode;
  position: google.maps.LatLngLiteral;
  create: () => BillboardyMarker;
  bind: (marker: BillboardyMarker) => void;
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
  areas: 'oblastí',
  sameLocation: 'Plochy na rovnakej lokalite',
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
  googleMapsMapId: import.meta.env.PUBLIC_GOOGLE_MAPS_MAP_ID ?? 'DEMO_MAP_ID',
  defaultCenter: { lat: 48.669, lng: 19.699 },
  defaultZoom: Number(import.meta.env.PUBLIC_GOOGLE_MAPS_DEFAULT_ZOOM ?? 7),
};

const config: MapConfig = {
  ...defaultConfig,
  ...(window.BILLBOARDY_MAP_CONFIG ?? {}),
  defaultCenter: {
    ...defaultConfig.defaultCenter,
    ...(window.BILLBOARDY_MAP_CONFIG?.defaultCenter ?? {}),
  },
};

const mapPayloadCache = new Map<string, MapPayload>();
const maxMapPayloadCacheEntries = 40;
const selectionPageSize = 10;
// Keep the visible-area list hidden until the user is close to individual points.
const selectionMinZoom = 12;

const state: {
  map: google.maps.Map | null;
  clusterer: MarkerClusterer | null;
  markers: BillboardyMarker[];
  renderedMarkers: Map<string, RenderedMarkerEntry>;
  markerById: Map<string, BillboardyMarker>;
  markerGroupById: Map<string, CoordinateMarkerGroup>;
  infoWindow: google.maps.InfoWindow | null;
  detailsCache: Map<string, AdSpace>;
  points: MapPoint[];
  items: MapItem[];
  mapRequestController: AbortController | null;
  selectionRequestController: AbortController | null;
  selectionPage: number;
  requestSeq: number;
  selectionRequestSeq: number;
} = {
  map: null,
  clusterer: null,
  markers: [],
  renderedMarkers: new Map(),
  markerById: new Map(),
  markerGroupById: new Map(),
  infoWindow: null,
  detailsCache: new Map(),
  points: [],
  items: [],
  mapRequestController: null,
  selectionRequestController: null,
  selectionPage: 1,
  requestSeq: 0,
  selectionRequestSeq: 0,
};

let googleMapsPromise: Promise<void> | null = null;

const root = document.querySelector<HTMLElement>('#billboardy-map-app');

if (!root) {
  throw new Error('Missing #billboardy-map-app root element.');
}

if (!root.querySelector('.bb-map-canvas')) {
  renderShell(root);
}

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
      zoom: config.defaultZoom,
      mapId: config.googleMapsMapId,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: true,
      clickableIcons: false,
    });
    state.infoWindow = new google.maps.InfoWindow({ maxWidth: 360 });

    bindControls(container);
    await waitForMapIdle();
    await refreshPoints(container, { includeBounds: true, fitToResults: false });
    bindMapViewport(container);
    void hydrateFilters(container);
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
        <aside class="bb-map-results" aria-label="${strings.selection}" hidden>
          <div class="bb-map-results-head">
            <h2>${strings.selection}</h2>
            <p>Priblížte mapu pre výber plôch v aktuálnej oblasti.</p>
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
  renderMediaFilterPills(container, filters.mediaTypes);
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

function renderMediaFilterPills(container: HTMLElement, options: FilterOption[]): void {
  const pills = container.querySelector<HTMLElement>('[data-map-filter-pills]');

  if (!pills) {
    return;
  }

  pills.innerHTML = [
    `<button type="button" class="bb-map-filter-pill is-active" data-filter-value="" aria-pressed="true">${strings.allTypes.replace(' typy', '')}</button>`,
    ...options.map((option) => (
      `<button type="button" class="bb-map-filter-pill" data-filter-value="${escapeAttribute(option.value)}" aria-pressed="false">${escapeHtml(option.label)}</button>`
    )),
  ].join('');
}

function bindControls(container: HTMLElement): void {
  const form = mustGet<HTMLFormElement>('.bb-map-filters', container);
  const results = mustGet<HTMLElement>('.bb-map-results-list', container);
  const mediaSelect = container.querySelector<HTMLSelectElement>('select[name="media_type"]');
  const filterToggle = container.querySelector<HTMLButtonElement>('[data-filter-toggle]');
  const advancedFilters = container.querySelector<HTMLElement>('#bb-map-advanced');
  let searchTimer = window.setTimeout(() => undefined, 0);

  form.addEventListener('change', () => {
    syncMediaFilterPills(container);
    state.selectionPage = 1;
    void refreshPoints(container, { includeBounds: true, fitToResults: false });
  });

  form.addEventListener('input', (event) => {
    if (!(event.target instanceof HTMLInputElement)) {
      return;
    }

    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
      state.selectionPage = 1;
      void refreshPoints(container, { includeBounds: true, fitToResults: false });
    }, 260);
  });

  form.addEventListener('reset', () => {
    window.setTimeout(() => {
      syncMediaFilterPills(container);
      state.selectionPage = 1;
      void refreshPoints(container, { includeBounds: true, fitToResults: false });
    }, 0);
  });

  container.addEventListener('click', (event) => {
    const pill = event.target instanceof Element ? event.target.closest<HTMLButtonElement>('[data-filter-value]') : null;

    if (!pill || !mediaSelect) {
      return;
    }

    mediaSelect.value = pill.dataset.filterValue ?? '';
    syncMediaFilterPills(container);
    state.selectionPage = 1;
    void refreshPoints(container, { includeBounds: true, fitToResults: false });
  });

  filterToggle?.addEventListener('click', () => {
    if (!advancedFilters) {
      return;
    }

    const nextExpanded = advancedFilters.hidden;
    advancedFilters.hidden = !nextExpanded;
    filterToggle.setAttribute('aria-expanded', String(nextExpanded));
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
        void openPoint(point, marker, true);
      }
    }

    if (action === 'focus-ad-space') {
      const adSpace = state.detailsCache.get(id);

      if (adSpace) {
        void focusAdSpace(adSpace);
      }
    }

    if (action === 'selection-page') {
      const page = Number(target.dataset.selectionPage ?? '1');

      if (Number.isFinite(page) && page >= 1) {
        state.selectionPage = page;
        void refreshSelectionResults(container);
      }
    }

  });

  document.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target.closest<HTMLElement>('[data-popup-action="open-point"]') : null;

    if (!target) {
      return;
    }

    event.preventDefault();
    const id = target.dataset.pointId ?? '';
    const point = state.points.find((item) => item.id === id);
    const marker = state.markerById.get(id);

    if (point && marker) {
      void openPoint(point, marker, true);
    }
  });
}

function syncMediaFilterPills(container: HTMLElement): void {
  const mediaSelect = container.querySelector<HTMLSelectElement>('select[name="media_type"]');
  const value = mediaSelect?.value ?? '';

  for (const pill of container.querySelectorAll<HTMLButtonElement>('[data-filter-value]')) {
    const isActive = (pill.dataset.filterValue ?? '') === value;
    pill.classList.toggle('is-active', isActive);
    pill.setAttribute('aria-pressed', String(isActive));
  }
}

function bindMapViewport(container: HTMLElement): void {
  if (!state.map) {
    return;
  }

  let viewportTimer = window.setTimeout(() => undefined, 0);

  state.map.addListener('idle', () => {
    window.clearTimeout(viewportTimer);
    viewportTimer = window.setTimeout(() => {
      state.selectionPage = 1;
      void refreshPoints(container, { includeBounds: true, fitToResults: false });
    }, 320);
  });
}

async function hydrateFilters(container: HTMLElement): Promise<void> {
  try {
    const filters = await fetchJson<ApiCollection<FiltersPayload>>(`${config.apiBase}/filters`);
    populateFilters(container, filters.data);
  } catch (error) {
    console.error('Unable to load map filters.', error);
  }
}

function waitForMapIdle(): Promise<void> {
  if (!state.map) {
    return Promise.resolve();
  }

  return new Promise((resolve) => {
    google.maps.event.addListenerOnce(state.map as google.maps.Map, 'idle', () => resolve());
  });
}

async function refreshPoints(
  container: HTMLElement,
  options: { includeBounds: boolean; fitToResults: boolean } = { includeBounds: true, fitToResults: false },
): Promise<void> {
  const requestId = ++state.requestSeq;
  const status = mustGet<HTMLElement>('.bb-map-status', container);
  state.mapRequestController?.abort();
  const controller = new AbortController();
  state.mapRequestController = controller;
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
      const paddedBounds = paddedSerializableBounds(bounds, Math.round(state.map?.getZoom() ?? config.defaultZoom));
      params.set('north', paddedBounds.north.toString());
      params.set('east', paddedBounds.east.toString());
      params.set('south', paddedBounds.south.toString());
      params.set('west', paddedBounds.west.toString());
    }
  }

  params.set('zoom', String(Math.round(state.map?.getZoom() ?? config.defaultZoom)));

  const url = `${config.apiBase}/map-points${params.size > 0 ? `?${params.toString()}` : ''}`;
  let payload: MapPayload;

  try {
    payload = await fetchCachedMapPayload(url, controller.signal);
  } catch (error) {
    if (isAbortError(error) || controller.signal.aborted || requestId !== state.requestSeq) {
      return;
    }

    console.error('Unable to load map points.', error);
    setStatus(status, strings.error, 'error');
    return;
  } finally {
    if (state.mapRequestController === controller) {
      state.mapRequestController = null;
    }
  }

  if (controller.signal.aborted || requestId !== state.requestSeq) {
    return;
  }

  state.items = payload.items ?? [];
  state.points = state.items.filter(isMapPoint);
  renderMarkers(container, state.items, options.fitToResults);
  void refreshSelectionResults(container);

  if (state.items.length === 0) {
    updateMapSummary(container, strings.empty);
    setStatus(status, strings.empty, 'empty');
    return;
  }

  const total = payload.meta?.total ?? state.points.length;
  const returned = payload.meta?.returned ?? state.items.length;
  const modeLabel = payload.mode === 'clusters' ? strings.areas : strings.spaces;
  updateMapSummary(container, `${total} ${strings.spaces} dostupných · Slovensko`);
  setStatus(status, `${strings.count}: ${total}. ${returned} ${modeLabel}`, 'ready');
}

function updateMapSummary(container: HTMLElement, message: string): void {
  const summary = container.querySelector<HTMLElement>('[data-map-summary]');

  if (summary) {
    summary.textContent = message;
  }
}

async function refreshSelectionResults(container: HTMLElement): Promise<void> {
  const results = container.querySelector<HTMLElement>('.bb-map-results');

  if (!results || !state.map) {
    return;
  }

  const zoom = Math.round(state.map.getZoom() ?? config.defaultZoom);

  if (zoom < selectionMinZoom) {
    state.selectionRequestController?.abort();
    hideSelectionResults(container);
    return;
  }

  const bounds = state.map.getBounds();

  if (!bounds) {
    hideSelectionResults(container);
    return;
  }

  const requestId = ++state.selectionRequestSeq;
  state.selectionRequestController?.abort();
  const controller = new AbortController();
  state.selectionRequestController = controller;
  showSelectionLoading(container);

  const params = selectionRequestParams(container, bounds, zoom);
  const url = `${config.apiBase}/ad-spaces?${params.toString()}`;

  try {
    const payload = await fetchJson<AdSpacesPayload>(url, { signal: controller.signal });

    if (controller.signal.aborted || requestId !== state.selectionRequestSeq) {
      return;
    }

    renderSelectionCards(container, payload);
  } catch (error) {
    if (isAbortError(error) || controller.signal.aborted || requestId !== state.selectionRequestSeq) {
      return;
    }

    console.error('Unable to load visible ad spaces.', error);
    renderSelectionError(container);
  } finally {
    if (state.selectionRequestController === controller) {
      state.selectionRequestController = null;
    }
  }
}

function selectionRequestParams(container: HTMLElement, bounds: google.maps.LatLngBounds, zoom: number): URLSearchParams {
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

  const viewport = serializableViewportBounds(bounds);
  params.set('north', viewport.north.toString());
  params.set('east', viewport.east.toString());
  params.set('south', viewport.south.toString());
  params.set('west', viewport.west.toString());
  params.set('zoom', String(zoom));
  params.set('page', String(state.selectionPage));
  params.set('per_page', String(selectionPageSize));

  return params;
}

function serializableViewportBounds(bounds: google.maps.LatLngBounds): MapBoundsPayload {
  const northEast = bounds.getNorthEast();
  const southWest = bounds.getSouthWest();

  return {
    north: roundCoordinate(northEast.lat(), 5),
    east: roundCoordinate(northEast.lng(), 5),
    south: roundCoordinate(southWest.lat(), 5),
    west: roundCoordinate(southWest.lng(), 5),
  };
}

function hideSelectionResults(container: HTMLElement): void {
  const results = container.querySelector<HTMLElement>('.bb-map-results');
  const head = container.querySelector<HTMLElement>('.bb-map-results-head p');
  const list = container.querySelector<HTMLElement>('.bb-map-results-list');

  if (results) {
    results.hidden = true;
    results.classList.remove('is-visible', 'is-loading');
  }

  if (head) {
    head.textContent = 'Priblížte mapu pre výber plôch v aktuálnej oblasti.';
  }

  if (list) {
    list.innerHTML = '';
  }
}

function showSelectionLoading(container: HTMLElement): void {
  const results = mustGet<HTMLElement>('.bb-map-results', container);
  const head = mustGet<HTMLElement>('.bb-map-results-head p', container);
  const list = mustGet<HTMLElement>('.bb-map-results-list', container);
  const keepCurrentList = results.classList.contains('is-visible') && list.childElementCount > 0;

  results.hidden = false;
  results.classList.add('is-visible', 'is-loading');
  head.textContent = 'Načítavam 10 plôch v aktuálnej oblasti...';
  list.setAttribute('aria-busy', 'true');

  if (!keepCurrentList) {
    list.innerHTML = '<div class="bb-map-results-loading">Načítavam...</div>';
  }
}

function renderSelectionCards(container: HTMLElement, payload: AdSpacesPayload): void {
  const results = mustGet<HTMLElement>('.bb-map-results', container);
  const head = mustGet<HTMLElement>('.bb-map-results-head p', container);
  const list = mustGet<HTMLElement>('.bb-map-results-list', container);
  const page = payload.pagination.page;
  const totalPages = Math.max(1, payload.pagination.totalPages);
  const total = payload.pagination.total;

  results.hidden = false;
  results.classList.add('is-visible');
  results.classList.remove('is-loading');
  list.removeAttribute('aria-busy');

  if (total === 0) {
    head.textContent = 'V aktuálnej oblasti nie sú žiadne plochy.';
    list.innerHTML = '';
    return;
  }

  head.textContent = `${total} plôch v aktuálnej oblasti. Zobrazené ${payload.data.length} z ${selectionPageSize}.`;

  for (const adSpace of payload.data) {
    state.detailsCache.set(adSpace.id, adSpace);
  }

  const cards = payload.data.map((adSpace) => selectionCard(adSpace)).join('');
  const pagination = totalPages > 1 ? selectionPagination(page, totalPages) : '';
  list.innerHTML = cards + pagination;
}

function selectionCard(adSpace: AdSpace): string {
  const image = adSpace.imageUrl || config.placeholderImageUrl;

  return `
    <article class="bb-map-result-card" data-card-action="focus-ad-space" data-card-id="${escapeAttribute(adSpace.id)}">
      ${image ? `<img src="${escapeAttribute(image)}" alt="${escapeAttribute(adSpace.title)}" loading="lazy" />` : ''}
      <div class="bb-map-result-body">
        <strong>${escapeHtml(adSpace.title)}</strong>
        <span>${escapeHtml(adSpace.locationLabel)}</span>
        ${adSpace.sizeLabel ? `<small>${escapeHtml(adSpace.sizeLabel)}</small>` : ''}
      </div>
      <button type="button" class="bb-map-card-action" data-card-action="focus-ad-space" data-card-id="${escapeAttribute(adSpace.id)}">
        ${strings.showOnMap}
      </button>
    </article>
  `;
}

function selectionPagination(page: number, totalPages: number): string {
  const previousPage = Math.max(1, page - 1);
  const nextPage = Math.min(totalPages, page + 1);

  return `
    <nav class="bb-map-results-pagination" aria-label="Stránkovanie výberu">
      <button type="button" data-card-action="selection-page" data-selection-page="${previousPage}" ${page <= 1 ? 'disabled' : ''}>Predošlé</button>
      <span>${page} / ${totalPages}</span>
      <button type="button" data-card-action="selection-page" data-selection-page="${nextPage}" ${page >= totalPages ? 'disabled' : ''}>Ďalšie</button>
    </nav>
  `;
}

function renderSelectionError(container: HTMLElement): void {
  const results = mustGet<HTMLElement>('.bb-map-results', container);
  const head = mustGet<HTMLElement>('.bb-map-results-head p', container);
  const list = mustGet<HTMLElement>('.bb-map-results-list', container);

  results.hidden = false;
  results.classList.add('is-visible');
  results.classList.remove('is-loading');
  list.removeAttribute('aria-busy');
  head.textContent = strings.error;
  list.innerHTML = '';
}

async function focusAdSpace(adSpace: AdSpace): Promise<void> {
  if (!state.map || !state.infoWindow) {
    return;
  }

  const point = adSpacePoint(adSpace);

  if (!point) {
    return;
  }

  const position = { lat: point.latitude, lng: point.longitude };
  const marker = state.markerById.get(point.id);

  state.map.panTo(position);
  state.map.setZoom(Math.max(state.map.getZoom() ?? 14, 15));

  if (marker) {
    await openPoint(point, marker, true);
    return;
  }

  google.maps.event.addListenerOnce(state.map, 'idle', () => {
    const nextMarker = state.markerById.get(point.id);

    if (nextMarker) {
      void openPoint(point, nextMarker, true);
      return;
    }

    if (!state.map || !state.infoWindow) {
      return;
    }

    state.infoWindow.setContent(popupContent(adSpace));
    state.infoWindow.setPosition(position);
    state.infoWindow.open({ map: state.map });
  });
}

function adSpacePoint(adSpace: AdSpace): MapPoint | null {
  const latitude = Number(adSpace.latitude);
  const longitude = Number(adSpace.longitude);

  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
    return null;
  }

  return {
    type: 'point',
    id: adSpace.id,
    code: adSpace.code,
    title: adSpace.title,
    mediaType: adSpace.mediaType,
    latitude,
    longitude,
    imageUrl: adSpace.imageUrl,
    locationLabel: adSpace.locationLabel,
    sizeLabel: adSpace.sizeLabel,
  };
}

async function fetchCachedMapPayload(url: string, signal?: AbortSignal): Promise<MapPayload> {
  const cached = mapPayloadCache.get(url);

  if (cached) {
    return cached;
  }

  const payload = await fetchJson<MapPayload>(url, { signal });
  mapPayloadCache.set(url, payload);

  if (mapPayloadCache.size > maxMapPayloadCacheEntries) {
    const oldestKey = mapPayloadCache.keys().next().value;

    if (oldestKey) {
      mapPayloadCache.delete(oldestKey);
    }
  }

  return payload;
}

function paddedSerializableBounds(bounds: google.maps.LatLngBounds, zoom: number): MapBoundsPayload {
  const northEast = bounds.getNorthEast();
  const southWest = bounds.getSouthWest();
  const paddingRatio = zoom >= 13 ? 0.65 : zoom >= 10 ? 0.45 : 0.25;
  const precision = zoom >= 13 ? 4 : zoom >= 10 ? 3 : 2;
  const latPadding = Math.abs(northEast.lat() - southWest.lat()) * paddingRatio;
  const lngPadding = Math.abs(northEast.lng() - southWest.lng()) * paddingRatio;

  return {
    north: roundCoordinate(Math.min(85, northEast.lat() + latPadding), precision),
    east: roundCoordinate(Math.min(180, northEast.lng() + lngPadding), precision),
    south: roundCoordinate(Math.max(-85, southWest.lat() - latPadding), precision),
    west: roundCoordinate(Math.max(-180, southWest.lng() - lngPadding), precision),
  };
}

function roundCoordinate(value: number, precision: number): number {
  const factor = 10 ** precision;

  return Math.round(value * factor) / factor;
}

function renderMarkers(container: HTMLElement, items: MapItem[], fitToResults: boolean): void {
  if (!state.map) {
    return;
  }

  mustGet<HTMLElement>('.bb-map-stage', container).classList.add('is-ready');

  const bounds = new google.maps.LatLngBounds();
  const markerGroups = groupPointsByCoordinate(items.filter(isMapPoint));
  const clusters = items.filter(isMapCluster);
  const renderMode: RenderMode = clusters.length > 0 ? 'direct' : 'clusterer';
  const specs = [
    ...clusters.map((cluster) => clusterMarkerSpec(cluster, renderMode)),
    ...markerGroups.map((group) => pointGroupMarkerSpec(group, renderMode)),
  ];

  state.markerById.clear();
  state.markerGroupById.clear();
  state.markers = diffRenderedMarkers(specs, renderMode);

  for (const spec of specs) {
    bounds.extend(spec.position);
  }

  if (!fitToResults) {
    return;
  }

  if (items.length === 1) {
    state.map.setCenter({ lat: items[0].latitude, lng: items[0].longitude });
    state.map.setZoom(15);
    return;
  }

  if (!bounds.isEmpty()) {
    state.map.fitBounds(bounds, 48);
  }
}

function diffRenderedMarkers(specs: MarkerSpec[], renderMode: RenderMode): BillboardyMarker[] {
  if (renderMode === 'direct' && state.clusterer) {
    state.clusterer.clearMarkers(true);
    state.clusterer.setMap(null);
    state.clusterer = null;
  }

  const desiredKeys = new Set(specs.map((spec) => spec.key));
  const clustererMarkersToAdd: BillboardyMarker[] = [];
  const clustererMarkersToRemove: BillboardyMarker[] = [];
  const markers: BillboardyMarker[] = [];

  for (const [key, entry] of state.renderedMarkers.entries()) {
    if (!desiredKeys.has(key)) {
      removeRenderedMarker(entry, clustererMarkersToRemove);
      state.renderedMarkers.delete(key);
    }
  }

  for (const spec of specs) {
    const existing = state.renderedMarkers.get(spec.key);
    let marker = existing?.marker;

    if (existing && (existing.signature !== spec.signature || existing.mode !== spec.mode)) {
      removeRenderedMarker(existing, clustererMarkersToRemove);
      state.renderedMarkers.delete(spec.key);
      marker = undefined;
    }

    if (!marker) {
      marker = spec.create();
      state.renderedMarkers.set(spec.key, {
        marker,
        signature: spec.signature,
        mode: spec.mode,
      });

      if (spec.mode === 'clusterer') {
        clustererMarkersToAdd.push(marker);
      }
    }

    spec.bind(marker);
    markers.push(marker);

    if (spec.mode === 'direct') {
      setMarkerMap(marker, state.map);
    }
  }

  if (renderMode === 'clusterer') {
    syncClusterer(markers, clustererMarkersToAdd, clustererMarkersToRemove);
  }

  return markers;
}

function removeRenderedMarker(entry: RenderedMarkerEntry, clustererMarkersToRemove: BillboardyMarker[]): void {
  if (entry.mode === 'clusterer' && state.clusterer) {
    clustererMarkersToRemove.push(entry.marker);
    return;
  }

  setMarkerMap(entry.marker, null);
}

function syncClusterer(markers: BillboardyMarker[], markersToAdd: BillboardyMarker[], markersToRemove: BillboardyMarker[]): void {
  if (!state.map) {
    return;
  }

  if (!state.clusterer) {
    state.clusterer = new MarkerClusterer({
      map: state.map,
      markers,
      renderer: markerClusterRenderer,
    });
    return;
  }

  if (markersToRemove.length > 0) {
    state.clusterer.removeMarkers(markersToRemove as ClusterMarker[], true);
    markersToRemove.forEach((marker) => setMarkerMap(marker, null));
  }

  if (markersToAdd.length > 0) {
    state.clusterer.addMarkers(markersToAdd as ClusterMarker[], true);
  }

  if (markersToAdd.length > 0 || markersToRemove.length > 0) {
    state.clusterer.render();
  }
}

function clusterMarkerSpec(cluster: MapCluster, mode: RenderMode): MarkerSpec {
  const mediaType = normalizeMarkerMediaType(cluster.mediaType);
  const position = { lat: cluster.latitude, lng: cluster.longitude };

  return {
    key: `cluster:${cluster.id}`,
    signature: [
      cluster.latitude.toFixed(6),
      cluster.longitude.toFixed(6),
      cluster.count,
      mediaType,
      cluster.bounds.north.toFixed(6),
      cluster.bounds.south.toFixed(6),
      cluster.bounds.east.toFixed(6),
      cluster.bounds.west.toFixed(6),
    ].join('|'),
    mode,
    position,
    create: () => {
      const marker = new google.maps.marker.AdvancedMarkerElement({
        position,
        title: `${cluster.count} ${strings.spaces}`,
        content: markerContent(mediaType, cluster.count, 'server-cluster'),
        gmpClickable: true,
      }) as BillboardyMarker;
      marker.__billboardyMediaType = mediaType;
      marker.addEventListener('gmp-click', () => {
        focusCluster(cluster);
      });

      return marker;
    },
    bind: (marker) => {
      marker.__billboardyMediaType = mediaType;
    },
  };
}

function pointGroupMarkerSpec(group: CoordinateMarkerGroup, mode: RenderMode): MarkerSpec {
  const mediaType = normalizeMarkerMediaType(group.point.mediaType);
  const position = { lat: group.point.latitude, lng: group.point.longitude };
  const pointIds = group.points.map((point) => point.id).join(',');

  return {
    key: `point:${group.id}`,
    signature: [
      group.point.latitude.toFixed(6),
      group.point.longitude.toFixed(6),
      group.points.length,
      pointIds,
      mediaType,
      group.point.title,
      group.point.locationLabel,
    ].join('|'),
    mode,
    position,
    create: () => {
      const marker = new google.maps.marker.AdvancedMarkerElement({
        position,
        title: group.points.length > 1 ? `${group.points.length} ${strings.spaces} - ${group.point.locationLabel}` : group.point.title,
        content: markerContent(group.point.mediaType, group.points.length, group.points.length > 1 ? 'same-location' : 'point'),
        gmpClickable: true,
      }) as BillboardyMarker;
      marker.__billboardyMediaType = mediaType;
      marker.addEventListener('gmp-click', () => {
        if (group.points.length > 1) {
          openCoordinateGroup(group, marker);
          return;
        }

        void openPoint(group.point, marker);
      });

      return marker;
    },
    bind: (marker) => {
      marker.__billboardyMediaType = mediaType;

      for (const point of group.points) {
        state.markerById.set(point.id, marker);
        state.markerGroupById.set(point.id, group);
      }
    },
  };
}

function setMarkerMap(marker: BillboardyMarker, map: google.maps.Map | null): void {
  marker.map = map;
}

function groupPointsByCoordinate(points: MapPoint[]): CoordinateMarkerGroup[] {
  const groups = new Map<string, MapPoint[]>();

  for (const point of points) {
    const key = `${point.latitude.toFixed(6)}:${point.longitude.toFixed(6)}`;
    const group = groups.get(key) ?? [];
    group.push(point);
    groups.set(key, group);
  }

  return Array.from(groups.entries()).map(([id, group]) => ({
    id,
    point: group[0],
    points: group.sort((a, b) => a.code.localeCompare(b.code, 'sk', { numeric: true })),
  }));
}

function isMapCluster(item: MapItem): item is MapCluster {
  return item.type === 'cluster';
}

function isMapPoint(item: MapItem): item is MapPoint {
  return item.type !== 'cluster';
}

function focusCluster(cluster: MapCluster): void {
  if (!state.map) {
    return;
  }

  const map = state.map;
  const currentZoom = Math.round(map.getZoom() ?? config.defaultZoom);
  const targetZoom = clusterDrillZoom(cluster, currentZoom);
  const bounds = new google.maps.LatLngBounds(
    { lat: cluster.bounds.south, lng: cluster.bounds.west },
    { lat: cluster.bounds.north, lng: cluster.bounds.east },
  );

  if (cluster.bounds.north === cluster.bounds.south && cluster.bounds.east === cluster.bounds.west) {
    map.panTo({ lat: cluster.latitude, lng: cluster.longitude });
    map.setZoom(targetZoom);
    return;
  }

  google.maps.event.addListenerOnce(map, 'idle', () => {
    if (!state.map) {
      return;
    }

    const nextZoom = Math.round(state.map.getZoom() ?? currentZoom);

    if (nextZoom < targetZoom) {
      state.map.setCenter({ lat: cluster.latitude, lng: cluster.longitude });
      state.map.setZoom(targetZoom);
    }
  });

  map.fitBounds(bounds, 80);
}

function clusterDrillZoom(cluster: MapCluster, currentZoom: number): number {
  const minimumZoom = cluster.count > 800 ? 13 : 12;
  const stepZoom = currentZoom + (cluster.count > 400 ? 4 : cluster.count > 100 ? 3 : 2);

  return Math.min(17, Math.max(minimumZoom, stepZoom));
}

async function openPoint(point: MapPoint, marker: BillboardyMarker, forceSingle = false): Promise<void> {
  if (!state.infoWindow || !state.map) {
    return;
  }

  if (!forceSingle) {
    const group = state.markerGroupById.get(point.id);

    if (group && group.points.length > 1) {
      openCoordinateGroup(group, marker);
      return;
    }
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

function openCoordinateGroup(group: CoordinateMarkerGroup, marker: BillboardyMarker): void {
  if (!state.infoWindow || !state.map) {
    return;
  }

  state.infoWindow.setContent(coordinateGroupPopup(group));
  state.infoWindow.open({ anchor: marker, map: state.map });
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

function coordinateGroupPopup(group: CoordinateMarkerGroup): string {
  const visibleItems = group.points.slice(0, 6);
  const hiddenCount = group.points.length - visibleItems.length;

  return `
    <article class="bb-map-popup bb-map-popup-group">
      <div class="bb-map-popup-body">
        <h2>${strings.sameLocation}</h2>
        <p class="bb-map-popup-location">${escapeHtml(group.point.locationLabel)}</p>
        <p><strong>${group.points.length} ${strings.spaces}</strong></p>
        <div class="bb-map-popup-card-grid">
          ${visibleItems.map((point) => `
            <a class="bb-map-popup-mini-card" href="#" data-popup-action="open-point" data-point-id="${escapeAttribute(point.id)}">
              ${point.imageUrl ? `<img src="${escapeAttribute(point.imageUrl)}" alt="${escapeAttribute(point.title)}" loading="lazy" />` : ''}
              <div>
                <strong>${strings.code} ${escapeHtml(point.code)}</strong>
                <span>${escapeHtml(point.mediaType)}</span>
                ${point.sizeLabel ? `<small>${escapeHtml(point.sizeLabel)}</small>` : ''}
                <em>${strings.showOnMap}</em>
              </div>
            </a>
          `).join('')}
        </div>
        ${hiddenCount > 0 ? `<p class="bb-map-popup-more">+${hiddenCount}</p>` : ''}
      </div>
    </article>
  `;
}

function buildContactUrl(code: string): string {
  const url = new URL(config.contactUrl, window.location.href);
  url.searchParams.set('ad_space', code);

  return url.toString();
}

const markerClusterRenderer: Renderer = {
  render: ({ count, position, markers }) => {
    const mediaType = dominantMarkerMediaType(markers as BillboardyClusterMarker[]);
    const marker = new google.maps.marker.AdvancedMarkerElement({
      position,
      title: `${count} ${strings.spaces}`,
      content: markerContent(mediaType, count, 'client-cluster'),
      gmpClickable: true,
      zIndex: 1000 + count,
    }) as BillboardyMarker;
    marker.__billboardyMediaType = mediaType;

    return marker;
  },
};

function dominantMarkerMediaType(markers: BillboardyClusterMarker[]): string {
  const counts = new Map<string, number>();

  for (const marker of markers) {
    const mediaType = normalizeMarkerMediaType(marker.__billboardyMediaType ?? 'unknown');
    counts.set(mediaType, (counts.get(mediaType) ?? 0) + 1);
  }

  const sorted = Array.from(counts.entries()).sort((a, b) => b[1] - a[1]);

  if (sorted.length > 1 && sorted[0][1] !== markers.length) {
    return 'mixed';
  }

  return sorted[0]?.[0] ?? 'unknown';
}

function normalizeMarkerMediaType(mediaType: string): string {
  const value = mediaType.toLowerCase();

  if (value.startsWith('cl') || value.includes('city')) {
    return 'citylight';
  }

  if (value.includes('billboard') || value === 'blb') {
    return 'billboard';
  }

  if (value.includes('bigboard')) {
    return 'bigboard';
  }

  if (value === 'mixed') {
    return 'mixed';
  }

  return 'unknown';
}

function markerContent(mediaType: string, count = 1, kind: 'point' | 'same-location' | 'server-cluster' | 'client-cluster' = 'point'): HTMLElement {
  const normalizedMediaType = normalizeMarkerMediaType(mediaType);
  const color = markerColor(normalizedMediaType);
  const marker = document.createElement('span');
  marker.className = count > 1 ? 'bb-map-marker bb-map-marker-count' : 'bb-map-marker';
  marker.dataset.mediaType = normalizedMediaType;
  marker.dataset.markerKind = kind;
  marker.style.setProperty('--bb-marker-color', color);

  if (count > 1) {
    marker.textContent = String(count);
  }

  return marker;
}

function markerColor(mediaType: string): string {
  const colors: Record<string, string> = {
    billboard: '#0f8b5f',
    citylight: '#d03f2f',
    bigboard: '#2563eb',
    mixed: '#3f3f46',
    unknown: '#71717a',
  };

  return colors[mediaType] ?? colors.unknown;
}

async function fetchJson<T>(url: string, options: { signal?: AbortSignal } = {}): Promise<T> {
  const response = await fetch(url, {
    headers: {
      Accept: 'application/json',
    },
    signal: options.signal,
  });

  if (!response.ok) {
    throw new Error(`Request failed: ${response.status} ${url}`);
  }

  return response.json() as Promise<T>;
}

function loadGoogleMaps(apiKey: string): Promise<void> {
  if (isGoogleMapsReady()) {
    return Promise.resolve();
  }

  if (googleMapsPromise) {
    return googleMapsPromise;
  }

  googleMapsPromise = new Promise((resolve, reject) => {
    const existing = document.querySelector<HTMLScriptElement>('script[data-billboardy-google-maps]');

    const finish = async () => {
      try {
        await window.google.maps.importLibrary('maps');
        await window.google.maps.importLibrary('marker');

        if (!isGoogleMapsReady()) {
          throw new Error('Google Maps API loaded without required map libraries.');
        }

        resolve();
      } catch (error) {
        reject(error);
      }
    };

    window.__billboardyGoogleMapsReady = () => {
      void finish();
    };

    if (existing) {
      if (isGoogleMapsReady()) {
        resolve();
        return;
      }

      existing.addEventListener('load', () => {
        window.setTimeout(() => {
          if (isGoogleMapsReady()) {
            resolve();
          }
        }, 0);
      }, { once: true });
      existing.addEventListener('error', () => reject(new Error('Google Maps failed to load.')), { once: true });
      return;
    }

    const script = document.createElement('script');
    script.dataset.billboardyGoogleMaps = 'true';
    script.async = true;
    script.defer = true;
    script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&v=weekly&loading=async&callback=__billboardyGoogleMapsReady&libraries=marker`;
    script.addEventListener('error', () => reject(new Error('Google Maps failed to load.')), { once: true });
    document.head.append(script);
  });

  return googleMapsPromise;
}

function isGoogleMapsReady(): boolean {
  return typeof window.google?.maps?.Map === 'function'
    && typeof window.google?.maps?.marker?.AdvancedMarkerElement === 'function'
    && typeof window.google?.maps?.InfoWindow === 'function';
}

function setStatus(node: HTMLElement, message: string, mode: 'loading' | 'ready' | 'empty' | 'error'): void {
  node.textContent = message;
  node.dataset.state = mode;

  if (mode !== 'ready') {
    const summary = node.closest('#billboardy-map-app')?.querySelector<HTMLElement>('[data-map-summary]');

    if (summary) {
      summary.textContent = message;
    }
  }
}

function mustGet<T extends Element>(selector: string, scope: ParentNode = document): T {
  const node = scope.querySelector<T>(selector);

  if (!node) {
    throw new Error(`Missing required element: ${selector}`);
  }

  return node;
}

function isAbortError(error: unknown): boolean {
  return error instanceof DOMException && error.name === 'AbortError';
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
