import { Controller } from '@hotwired/stimulus';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

const GLYPHS = 'https://protomaps.github.io/basemaps-assets/fonts/{fontstack}/{range}.pbf';
const LABEL_FONT = ['Noto Sans Medium'];
const EMPTY = { type: 'FeatureCollection', features: [] };
const PALETTE = {
    land: '#f3efe6',
    building: '#e3ddd1',
    buildingEdge: '#d5cec0',
    green: '#d9e6c9',
    park: '#a9d18e',
    parkEdge: '#6fa35a',
    water: '#bcd5e6',
    rail: '#a9a39a',
    tram: '#b08fb8',
    street: '#ffffff',
    casing: '#cbc3b3',
    named: '#2b8a3e',
    revealed: '#868e96',
    hover: '#f2b705',
    selected: '#e8590c',
    label: '#2a2722',
    place: '#4f4a42',
    neighbourhood: '#4a443c',
};
const PLACE_ICONS = {
    tram: { glyph: '\ue9ec', fill: '#f5c400', ink: '#1d1b18' },
    station: { glyph: '\ue496', fill: '#1f3a5f', ink: '#ffffff' },
    museum: { glyph: '\ue0b4', fill: '#6b4fa0', ink: '#ffffff' },
    gallery: { glyph: '\ue6c8', fill: '#b0306a', ink: '#ffffff' },
    shopping: { glyph: '\ue416', fill: '#c2410c', ink: '#ffffff' },
    park: { glyph: '\ue6da', fill: '#2f7d32', ink: '#ffffff' },
};
const ICON_FONT = 'Phosphor-Fill';
const STREET_WIDTH = { major: [4, 22], minor: [2.5, 16], path: [1.2, 7] };

const polygons = (rings) => ({
    type: 'FeatureCollection',
    features: rings.map((ring) => ({ type: 'Feature', properties: {}, geometry: { type: 'Polygon', coordinates: [ring] } })),
});
const lines = (paths) => ({
    type: 'FeatureCollection',
    features: paths.map((path) => ({ type: 'Feature', properties: {}, geometry: { type: 'LineString', coordinates: path } })),
});
const widthAt = (stop, scale, extra) => ['match', ['get', 'kind'],
    'major', STREET_WIDTH.major[stop] * scale + extra,
    'minor', STREET_WIDTH.minor[stop] * scale + extra,
    STREET_WIDTH.path[stop] * scale + extra];
const streetWidth = (scale = 1, extra = 0) => ['interpolate', ['exponential', 1.6], ['zoom'],
    14, widthAt(0, scale, extra),
    18, widthAt(1, scale, extra)];

function segmentDistance(p, a, b) {
    const dx = b.x - a.x;
    const dy = b.y - a.y;
    const lengthSquared = dx * dx + dy * dy || 1e-9;
    const t = Math.max(0, Math.min(1, ((p.x - a.x) * dx + (p.y - a.y) * dy) / lengthSquared));
    return Math.hypot(p.x - (a.x + t * dx), p.y - (a.y + t * dy));
}

function formatClock(ms) {
    const total = Math.max(0, Math.round(ms / 1000));
    return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, '0')}`;
}

function formatDistance(metres) {
    return metres < 1000 ? `${Math.round(metres / 10) * 10} m` : `${(metres / 1000).toFixed(1)} km`;
}

function heat(metres) {
    if (metres < 100) return '#c92a2a';
    if (metres < 250) return '#e8590c';
    if (metres < 500) return '#f08c00';
    if (metres < 900) return '#1c7ed6';
    return '#364fc7';
}

export default class extends Controller {
    static targets = ['map', 'loading', 'intro', 'clock', 'score', 'panel', 'input', 'submit', 'hint', 'hintButton', 'revealButton', 'guesses', 'message'];
    static values = {
        mapUrl: String,
        guessUrl: String,
        hintUrl: String,
        revealUrl: String,
        endUrl: String,
        csrfToken: String,
        deadline: Number,
        startedAt: Number,
        serverNow: Number,
        total: Number,
        named: Array,
        revealed: Array,
        maxHintLevel: Number,
    };

    connect() {
        this.clockOffset = this.serverNowValue - Date.now();
        this.settled = new Map();
        this.namedValue.forEach(({ id, name }) => this.settled.set(id, { name, status: 'named' }));
        this.revealedValue.forEach(({ id, name }) => this.settled.set(id, { name, status: 'revealed' }));
        this.current = null;
        this.hoverId = null;
        this.finishing = false;
        this.onKeydown = (event) => event.key === 'Escape' && this.close();
        document.addEventListener('keydown', this.onKeydown);

        this.tick();
        this.timer = setInterval(() => this.tick(), 250);
        this.load();
    }

    disconnect() {
        clearInterval(this.timer);
        document.removeEventListener('keydown', this.onKeydown);
        this.map?.remove();
    }

    async load() {
        try {
            const response = await fetch(this.mapUrlValue, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error(String(response.status));
            this.buildMap(await response.json());
        } catch {
            this.loadingTarget.textContent = 'The map could not be loaded. Refresh the page to try again.';
        }
    }

    buildMap(data) {
        this.shapes = new Map(data.streets.map((street) => [street.id, street.lines]));
        const [west, south, east, north] = data.bounds;
        const padX = (east - west) * 0.25;
        const padY = (north - south) * 0.25;

        this.map = new maplibregl.Map({
            container: this.mapTarget,
            style: this.style(data),
            bounds: [[west, south], [east, north]],
            maxBounds: [[west - padX, south - padY], [east + padX, north + padY]],
            maxZoom: 19,
            dragRotate: false,
            pitchWithRotate: false,
            fadeDuration: 0,
            attributionControl: { compact: true, customAttribution: '© OpenStreetMap contributors' },
        });
        this.map.touchZoomRotate.disableRotation();
        this.map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'bottom-right');
        if (data.hotel) this.addHotelMarker(data.hotel);

        this.map.on('load', () => {
            this.map.setMinZoom(this.map.getZoom() - 0.5);
            this.settled.forEach((street, id) => this.paint(id, street.status));
            this.refreshLabels();
            this.addPlaces(data.basemap.places ?? []);
            this.loadingTarget.hidden = true;
        });
        this.map.on('mousemove', (event) => this.hover(event.point));
        this.map.on('mouseout', () => this.setHover(null));
        this.map.on('movestart', () => this.setHover(null));
        this.map.on('click', (event) => {
            const touch = event.originalEvent?.pointerType === 'touch';
            const id = this.nearestStreet(event.point, touch ? 22 : 14);
            if (id !== null) this.select(id);
        });
    }

    style(data) {
        const [west, south, east, north] = data.bounds;
        const streets = {
            type: 'FeatureCollection',
            features: data.streets.map((street) => ({
                type: 'Feature',
                id: street.id,
                properties: { kind: street.kind },
                geometry: { type: 'MultiLineString', coordinates: street.lines },
            })),
        };
        const neighbourhoods = {
            type: 'FeatureCollection',
            features: (data.neighbourhoods ?? []).map((neighbourhood) => ({
                type: 'Feature',
                properties: { name: neighbourhood.name, colour: neighbourhood.colour },
                geometry: { type: 'MultiPolygon', coordinates: neighbourhood.polygons.map((ring) => [[...ring, ring[0]]]) },
            })),
        };
        const outside = {
            type: 'Feature',
            geometry: {
                type: 'Polygon',
                coordinates: [
                    [[-180, -85], [180, -85], [180, 85], [-180, 85], [-180, -85]],
                    [[west, south], [west, north], [east, north], [east, south], [west, south]],
                ],
            },
        };
        const statusColour = ['match', ['feature-state', 'status'], 'named', PALETTE.named, 'revealed', PALETTE.revealed, 'rgba(0,0,0,0)'];

        return {
            version: 8,
            glyphs: GLYPHS,
            sources: {
                green: { type: 'geojson', data: polygons(data.basemap.green ?? []) },
                parks: { type: 'geojson', data: polygons(data.basemap.parks) },
                water: { type: 'geojson', data: polygons(data.basemap.water) },
                waterways: { type: 'geojson', data: lines(data.basemap.waterways) },
                buildings: { type: 'geojson', data: polygons(data.basemap.buildings) },
                rail: { type: 'geojson', data: lines(data.basemap.rail) },
                tram: { type: 'geojson', data: lines(data.basemap.tram) },
                streets: { type: 'geojson', data: streets, tolerance: 0.2 },
                outside: { type: 'geojson', data: outside },
                labels: { type: 'geojson', data: EMPTY },
                neighbourhoods: { type: 'geojson', data: neighbourhoods },
            },
            layers: [
                { id: 'land', type: 'background', paint: { 'background-color': PALETTE.land } },
                { id: 'green', type: 'fill', source: 'green', paint: { 'fill-color': PALETTE.green } },
                { id: 'water', type: 'fill', source: 'water', paint: { 'fill-color': PALETTE.water } },
                { id: 'waterways', type: 'line', source: 'waterways', paint: { 'line-color': PALETTE.water, 'line-width': ['interpolate', ['linear'], ['zoom'], 14, 2, 18, 12] } },
                { id: 'buildings', type: 'fill', source: 'buildings', paint: { 'fill-color': PALETTE.building, 'fill-outline-color': PALETTE.buildingEdge } },
                { id: 'neighbourhood-fill', type: 'fill', source: 'neighbourhoods', paint: { 'fill-color': ['get', 'colour'], 'fill-opacity': 0.45 } },
                { id: 'neighbourhood-edge', type: 'line', source: 'neighbourhoods', paint: { 'line-color': ['get', 'colour'], 'line-width': 2, 'line-opacity': 0.9 } },
                { id: 'parks', type: 'fill', source: 'parks', paint: { 'fill-color': PALETTE.park, 'fill-outline-color': PALETTE.parkEdge } },
                { id: 'rail', type: 'line', source: 'rail', paint: { 'line-color': PALETTE.rail, 'line-width': 1.5, 'line-dasharray': [3, 2] } },
                { id: 'tram', type: 'line', source: 'tram', paint: { 'line-color': PALETTE.tram, 'line-width': 1.5 } },
                { id: 'street-casing', type: 'line', source: 'streets', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': PALETTE.casing, 'line-width': streetWidth(1, 2) } },
                { id: 'street', type: 'line', source: 'streets', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': PALETTE.street, 'line-width': streetWidth() } },
                { id: 'street-status', type: 'line', source: 'streets', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': statusColour, 'line-width': streetWidth(), 'line-opacity': 0.75 } },
                {
                    id: 'street-focus', type: 'line', source: 'streets', layout: { 'line-cap': 'round', 'line-join': 'round' },
                    paint: {
                        'line-color': ['case', ['boolean', ['feature-state', 'selected'], false], PALETTE.selected, PALETTE.hover],
                        'line-width': streetWidth(1.4, 3),
                        'line-opacity': ['case', ['boolean', ['feature-state', 'selected'], false], 1, ['boolean', ['feature-state', 'hover'], false], 0.7, 0],
                    },
                },
                { id: 'street-hit', type: 'line', source: 'streets', paint: { 'line-color': '#000', 'line-width': 24, 'line-opacity': 0 } },
                { id: 'outside', type: 'fill', source: 'outside', paint: { 'fill-color': '#1d1b18', 'fill-opacity': 0.35 } },
                {
                    id: 'neighbourhood-names', type: 'symbol', source: 'neighbourhoods', maxzoom: 16.5,
                    layout: {
                        'text-field': ['upcase', ['get', 'name']],
                        'text-font': LABEL_FONT,
                        'text-size': ['interpolate', ['linear'], ['zoom'], 13, 10, 16, 13],
                        'text-letter-spacing': 0.1,
                        'text-max-width': 7,
                        'text-variable-anchor': ['center', 'top', 'bottom', 'left', 'right'],
                        'text-radial-offset': 0.6,
                    },
                    paint: { 'text-color': PALETTE.neighbourhood, 'text-halo-color': 'rgba(255,255,255,0.85)', 'text-halo-width': 1.5 },
                },
                {
                    id: 'labels', type: 'symbol', source: 'labels',
                    layout: { 'symbol-placement': 'line', 'text-field': ['get', 'name'], 'text-font': LABEL_FONT, 'text-size': 13, 'symbol-spacing': 400 },
                    paint: { 'text-color': PALETTE.label, 'text-halo-color': '#ffffff', 'text-halo-width': 2 },
                },
            ],
        };
    }

    addHotelMarker(hotel) {
        const element = document.createElement('div');
        const icon = document.createElement('i');
        const name = document.createElement('span');
        element.className = 'streetle-hotel';
        icon.className = 'ph-fill ph-building';
        name.textContent = hotel.name;
        element.append(icon, name);

        new maplibregl.Marker({ element, anchor: 'bottom', offset: [0, -6] }).setLngLat(hotel.at).addTo(this.map);
    }

    async addPlaces(places) {
        try {
            await document.fonts.load(`28px "${ICON_FONT}"`);
        } catch {
            return;
        }
        if (!this.map) return;

        Object.entries(PLACE_ICONS).forEach(([kind, icon]) => this.map.addImage(`place-${kind}`, this.drawIcon(icon), { pixelRatio: 2 }));
        this.map.addSource('places', {
            type: 'geojson',
            data: {
                type: 'FeatureCollection',
                features: places.map((place) => ({
                    type: 'Feature',
                    properties: { name: place.name ?? '', kind: place.kind },
                    geometry: { type: 'Point', coordinates: place.at },
                })),
            },
        });
        this.map.addLayer({
            id: 'places',
            type: 'symbol',
            source: 'places',
            layout: {
                'icon-image': ['concat', 'place-', ['get', 'kind']],
                'icon-size': ['interpolate', ['linear'], ['zoom'], 13, 0.7, 16, 1],
                'icon-allow-overlap': true,
                'text-field': ['step', ['zoom'], '', 15, ['get', 'name']],
                'text-font': LABEL_FONT,
                'text-size': 11,
                'text-max-width': 8,
                'text-anchor': 'top',
                'text-offset': [0, 1.2],
                'text-optional': true,
            },
            paint: { 'text-color': PALETTE.place, 'text-halo-color': '#ffffff', 'text-halo-width': 1.5 },
        }, 'neighbourhood-names');
    }

    drawIcon({ glyph, fill, ink }) {
        const size = 52;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const context = canvas.getContext('2d');
        context.beginPath();
        context.arc(size / 2, size / 2, size / 2 - 3, 0, Math.PI * 2);
        context.fillStyle = fill;
        context.fill();
        context.lineWidth = 3;
        context.strokeStyle = '#ffffff';
        context.stroke();
        context.fillStyle = ink;
        context.font = `28px "${ICON_FONT}"`;
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.fillText(glyph, size / 2, size / 2 + 1);
        return context.getImageData(0, 0, size, size);
    }

    nearestStreet(point, radius) {
        const box = [[point.x - radius, point.y - radius], [point.x + radius, point.y + radius]];
        let best = null;
        let bestDistance = radius + 0.01;
        for (const feature of this.map.queryRenderedFeatures(box, { layers: ['street-hit'] })) {
            const paths = feature.geometry.type === 'LineString' ? [feature.geometry.coordinates] : feature.geometry.coordinates;
            for (const path of paths) {
                let previous = null;
                for (const coordinate of path) {
                    const projected = this.map.project(coordinate);
                    const distance = previous ? segmentDistance(point, previous, projected) : Math.hypot(point.x - projected.x, point.y - projected.y);
                    if (distance < bestDistance) {
                        bestDistance = distance;
                        best = feature.id;
                    }
                    previous = projected;
                }
            }
        }
        return best;
    }

    hover(point) {
        this.lastPoint = point;
        if (this.hoverFrame) return;
        this.hoverFrame = requestAnimationFrame(() => {
            this.hoverFrame = 0;
            this.setHover(this.nearestStreet(this.lastPoint, 14));
        });
    }

    setHover(id) {
        if (!this.map || id === this.hoverId) return;
        if (this.hoverId !== null) this.map.setFeatureState({ source: 'streets', id: this.hoverId }, { hover: false });
        if (id !== null) this.map.setFeatureState({ source: 'streets', id }, { hover: true });
        this.hoverId = id;
        this.map.getCanvas().style.cursor = id === null ? '' : 'pointer';
    }

    select(id) {
        if (this.current) this.map.setFeatureState({ source: 'streets', id: this.current.id }, { selected: false });
        this.map.setFeatureState({ source: 'streets', id }, { selected: true });
        this.current = { id, hints: 0, tried: new Set() };

        this.introTarget.hidden = true;
        this.panelTarget.hidden = false;
        this.guessesTarget.replaceChildren();
        this.hintTarget.textContent = '';
        this.inputTarget.value = '';

        const settled = this.settled.get(id);
        this.setMessage(settled ? `This is ${settled.name}.` : '', settled?.status === 'named' ? 'good' : '');
        this.lock(Boolean(settled));
        if (!settled) this.inputTarget.focus();
    }

    close() {
        if (!this.current) return;
        this.map.setFeatureState({ source: 'streets', id: this.current.id }, { selected: false });
        this.current = null;
        this.panelTarget.hidden = true;
    }

    async guess(event) {
        event.preventDefault();
        const raw = this.inputTarget.value.trim();
        if (!this.current || raw === '') return;

        const key = raw.toLowerCase().replace(/\s+/g, ' ');
        if (this.current.tried.has(key)) return this.setMessage('You already tried that.');
        this.current.tried.add(key);
        this.inputTarget.value = '';

        const street = this.current.id;
        const result = await this.send(this.guessUrlValue, { street, guess: raw });
        if (!result || result.ended || this.current?.id !== street) return;

        this.updateScore(result.stats);
        if (result.correct) {
            this.settle(street, result.name, 'named');
            this.setMessage(`Correct! ${result.name}.`, 'good');
            this.lock(true);
            this.finishIfComplete();
            return;
        }

        this.addGuess(result.guessedName ?? raw, result.distance);
        if (result.distance === null) {
            this.setMessage('No street by that name in the play area.');
        } else {
            this.setMessage(result.distance < 150 ? 'Very close!' : result.distance < 400 ? 'Getting warm.' : 'Not quite.');
        }
        this.inputTarget.focus();
    }

    async hint() {
        if (!this.current || this.current.hints >= this.maxHintLevelValue) return;
        const street = this.current.id;
        this.current.hints++;
        const result = await this.send(this.hintUrlValue, { street, level: this.current.hints });
        if (!result || result.ended || this.current?.id !== street) return;

        this.hintTarget.textContent = result.hint;
        this.hintButtonTarget.disabled = this.current.hints >= this.maxHintLevelValue;
        this.inputTarget.focus();
    }

    async reveal() {
        if (!this.current) return;
        const street = this.current.id;
        const result = await this.send(this.revealUrlValue, { street });
        if (!result || result.ended || this.current?.id !== street) return;

        this.updateScore(result.stats);
        this.settle(street, result.name, 'revealed');
        this.setMessage(`It's ${result.name}.`, 'bad');
        this.lock(true);
        this.finishIfComplete();
    }

    finishIfComplete() {
        if (this.settled.size >= this.totalValue) this.end();
    }

    async end() {
        if (this.finishing) return;
        this.finishing = true;
        if (!await this.send(this.endUrlValue, {})) this.finishing = false;
    }

    tick() {
        const now = Date.now() + this.clockOffset;
        if (this.deadlineValue) {
            this.clockTarget.textContent = formatClock(this.deadlineValue - now);
            this.clockTarget.classList.toggle('streetle-clock--low', this.deadlineValue - now < 30000);
            if (this.isExpired()) this.end();
            return;
        }
        this.clockTarget.textContent = formatClock(now - this.startedAtValue);
    }

    isExpired() {
        return this.deadlineValue > 0 && Date.now() + this.clockOffset >= this.deadlineValue;
    }

    async send(url, payload) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-Token': this.csrfTokenValue },
                body: JSON.stringify(payload),
            });
            if (response.status === 409) {
                this.setMessage('You have already done this street.');
                return null;
            }
            if (!response.ok) throw new Error(String(response.status));

            const result = await response.json();
            if (result.ended) window.location.assign(result.summaryUrl);
            return result;
        } catch {
            this.setMessage('Something went wrong. Please try again.', 'bad');
            return null;
        }
    }

    settle(id, name, status) {
        this.settled.set(id, { name, status });
        this.paint(id, status);
        this.refreshLabels();
    }

    paint(id, status) {
        this.map?.setFeatureState({ source: 'streets', id }, { status });
    }

    refreshLabels() {
        if (!this.map?.getSource('labels')) return;
        const features = [];
        this.settled.forEach((street, id) => {
            const shape = this.shapes.get(id);
            if (shape) features.push({ type: 'Feature', properties: { name: street.name }, geometry: { type: 'MultiLineString', coordinates: shape } });
        });
        this.map.getSource('labels').setData({ type: 'FeatureCollection', features });
    }

    updateScore(stats) {
        if (stats) this.scoreTarget.textContent = stats.named;
    }

    addGuess(name, distance) {
        const item = document.createElement('li');
        const label = document.createElement('span');
        const detail = document.createElement('span');
        label.textContent = name;
        detail.className = 'streetle-panel__distance';
        if (distance === null) {
            detail.textContent = 'not in the play area';
        } else {
            detail.textContent = `${formatDistance(distance)} away`;
            detail.style.color = heat(distance);
        }
        item.append(label, detail);
        this.guessesTarget.prepend(item);
    }

    lock(locked) {
        [this.inputTarget, this.submitTarget, this.hintButtonTarget, this.revealButtonTarget].forEach((element) => {
            element.disabled = locked;
        });
    }

    setMessage(text, tone = '') {
        this.messageTarget.textContent = text;
        this.messageTarget.className = `streetle-panel__message${tone ? ` streetle-panel__message--${tone}` : ''}`;
    }
}
