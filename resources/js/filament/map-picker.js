/*
 * Map used by the MapPicker form field and the MapEntry infolist entry.
 * Build with `npm run build:filament`: the output in resources/js/dist is published by `php artisan filament:assets`.
 */
import L from 'leaflet';
import '@geoman-io/leaflet-geoman-free';
import 'leaflet/dist/leaflet.css';
import '@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css';
import markerIconUrl from 'leaflet/dist/images/marker-icon.png';
import markerIconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png';

// Bundling breaks Leaflet's detection of its image folder, so the marker images are given explicitly.
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconUrl: markerIconUrl,
    iconRetinaUrl: markerIconRetinaUrl,
    shadowUrl: markerShadowUrl,
});

// Matches the decimal(10, 7) latitude / longitude columns.
const COORDINATE_PRECISION = 7;
const EARTH_RADIUS_METERS = 6378137;
const OUTLINE_STYLE = { color: '#f59e0b', weight: 2, fillOpacity: 0.15 };

/**
 * One pin (latitude / longitude) and at most one outline (a GeoJSON Polygon geometry).
 * Leaflet objects are kept in this closure, outside Alpine's reactive proxies, which would break them.
 */
export default function mapPicker({ boundary, latitude, longitude, center, isReadOnly, labels }) {
    let map = null;
    let pin = null;
    let outline = null;
    let resizeObserver = null;
    let hasFittedView = false;

    return {
        boundary,
        latitude,
        longitude,
        areaText: null,

        init() {
            map = L.map(this.$refs.map, { scrollWheelZoom: ! isReadOnly }).setView(center, 13);

            const satellite = L.layerGroup([
                L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 19,
                    attribution: 'Imagery &copy; Esri',
                }),
                L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 19,
                }),
            ]);
            const streets = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            });

            satellite.addTo(map);
            L.control.layers({ [labels.satellite]: satellite, [labels.streets]: streets }).addTo(map);

            this.drawOutline(outlineLatLngs(this.boundary));
            this.drawPin(toLatLng(this.latitude, this.longitude));

            if (! isReadOnly) {
                this.enableEditing();
            }

            // Typing the coordinates moves the pin.
            this.$watch('latitude', () => this.drawPin(toLatLng(this.latitude, this.longitude)));
            this.$watch('longitude', () => this.drawPin(toLatLng(this.latitude, this.longitude)));

            // The map can start hidden (collapsed section, other wizard step): redraw it, and frame the
            // pin / outline once it has a size, which Leaflet needs to compute the zoom.
            resizeObserver = new ResizeObserver(() => {
                map.invalidateSize();

                if (! hasFittedView && this.$refs.map.clientWidth > 0) {
                    hasFittedView = true;
                    this.fitView();
                }
            });
            resizeObserver.observe(this.$refs.map);
        },

        destroy() {
            resizeObserver?.disconnect();
            map?.remove();
        },

        enableEditing() {
            map.pm.addControls({
                position: 'topleft',
                drawMarker: true,
                drawPolygon: true,
                drawRectangle: true,
                editMode: true,
                dragMode: true,
                removalMode: true,
                drawCircle: false,
                drawCircleMarker: false,
                drawPolyline: false,
                drawText: false,
                cutPolygon: false,
                rotateMode: false,
            });

            // A plain click drops (or moves) the pin, unless a drawing tool is in use.
            map.on('click', (event) => {
                if (isToolActive()) {
                    return;
                }

                this.setPin(event.latlng);
            });

            // Shapes drawn with the toolbar replace the existing pin / outline: there is only one of each.
            map.on('pm:create', ({ shape, layer }) => {
                layer.remove();

                if (shape === 'Marker') {
                    this.setPin(layer.getLatLng());

                    return;
                }

                this.drawOutline(layer.getLatLngs()[0]);
                this.syncOutline();

                if (! pin) {
                    this.setPin(outline.getBounds().getCenter());
                }
            });

            map.on('pm:remove', ({ layer }) => {
                if (layer === pin) {
                    pin = null;
                    this.latitude = null;
                    this.longitude = null;
                }

                if (layer === outline) {
                    outline = null;
                    this.boundary = null;
                    this.areaText = null;
                }
            });
        },

        setPin(latLng) {
            const wrapped = latLng.wrap();

            this.latitude = round(wrapped.lat);
            this.longitude = round(wrapped.lng);
            this.drawPin(L.latLng(this.latitude, this.longitude));
        },

        drawPin(latLng) {
            if (! latLng) {
                pin?.remove();
                pin = null;

                return;
            }

            if (pin) {
                pin.setLatLng(latLng);

                return;
            }

            pin = L.marker(latLng, { draggable: ! isReadOnly }).addTo(map);
            pin.on('dragend pm:edit pm:dragend', () => this.setPin(pin.getLatLng()));
        },

        drawOutline(latLngs) {
            outline?.remove();
            outline = null;
            this.areaText = null;

            if (! latLngs) {
                return;
            }

            outline = L.polygon(latLngs, OUTLINE_STYLE).addTo(map);
            outline.on('pm:edit pm:dragend', () => this.syncOutline());
            this.areaText = formatArea(geodesicArea(latLngs), labels.area);
        },

        syncOutline() {
            this.boundary = outline.toGeoJSON(COORDINATE_PRECISION).geometry;
            this.areaText = formatArea(geodesicArea(outline.getLatLngs()[0]), labels.area);
        },

        fitView() {
            if (outline) {
                map.fitBounds(outline.getBounds(), { padding: [24, 24], maxZoom: 18 });
            } else if (pin) {
                map.setView(pin.getLatLng(), 16);
            }
        },
    };

    function isToolActive() {
        return map.pm.globalDrawModeEnabled()
            || map.pm.globalEditModeEnabled()
            || map.pm.globalDragModeEnabled()
            || map.pm.globalRemovalModeEnabled();
    }
}

function round(value) {
    return Number(value.toFixed(COORDINATE_PRECISION));
}

/**
 * The coordinates come from text inputs or decimal columns, so they may be strings or blank.
 */
function toLatLng(latitude, longitude) {
    if (latitude === null || latitude === '' || longitude === null || longitude === '') {
        return null;
    }

    const lat = Number(latitude);
    const lng = Number(longitude);

    if (! Number.isFinite(lat) || ! Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
        return null;
    }

    return L.latLng(lat, lng);
}

/**
 * GeoJSON rings are [lng, lat] and repeat the first position at the end; Leaflet polygons are [lat, lng] and open.
 */
function outlineLatLngs(geometry) {
    const ring = geometry?.coordinates?.[0];

    if (! Array.isArray(ring) || ring.length < 4) {
        return null;
    }

    return ring.slice(0, -1).map(([lng, lat]) => L.latLng(lat, lng));
}

/**
 * Area on the Earth's surface in m², using the same spherical formula as Leaflet.draw.
 */
function geodesicArea(latLngs) {
    const toRadians = Math.PI / 180;
    let area = 0;

    latLngs.forEach((point, index) => {
        const next = latLngs[(index + 1) % latLngs.length];

        area += (next.lng - point.lng) * toRadians
            * (2 + Math.sin(point.lat * toRadians) + Math.sin(next.lat * toRadians));
    });

    return Math.abs(area * EARTH_RADIUS_METERS * EARTH_RADIUS_METERS / 2);
}

function formatArea(squareMeters, label) {
    const meters = new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(squareMeters);
    const hectares = new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(squareMeters / 10000);

    return `${label}: ≈ ${meters} m² (${hectares} ha)`;
}
