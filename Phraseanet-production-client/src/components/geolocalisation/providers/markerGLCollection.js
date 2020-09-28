import $ from 'jquery';
let mapboxgl = require('mapbox-gl');

const markerGLCollection = (services) => {
    const {configService, localeService, eventEmitter} = services;
    let markerCollection = {};
    let cachedGeoJson;
    let map;
    let geojson;
    let editable;
    let isDraggable = false;
    let isCursorOverPoint = false;
    let isDragging = false;
    let popup = {};
    let popupDialog;

    const initialize = (params) => {
        let initWith = {map, geojson} = params;
        editable = params.editable || false;
        setCollection(geojson)
    };

    const setCollection = (geojson) => {
        cachedGeoJson = geojson;
        geojson.features.forEach(function (marker) {
            setMarkerPopup(marker);
        });
    };

    const setMarkerPopup = (marker) => {
        switch (marker.geometry.type) {
            case 'Polygon':
                break;
            case 'Point':
                setPoint(marker);
                break;
            default:
        }
    };

    const setPoint = (marker) => {
        let $content = $('<div style="min-width: 200px"/>');

        let template = `<p>${marker.properties.title}</p> `;

        if (editable === true) {
            template += `
            <div class="view-mode">
                    <button class="edit-position btn btn-inverse btn-small btn-block" data-marker-id="${marker.properties.recordIndex}">${localeService.t('mapMarkerEdit')}</button>
            </div>
            <div class="edit-mode">
                <p class="help" style="font-size: 12px;font-style: italic;">${localeService.t('mapMarkerMoveLabel')}</p>
                <p><span class="updated-position" style="font-size: 12px;"></span></p>
                <div>
                    <button class="cancel-position btn btn-inverse btn-small btn-block" data-marker-id="${marker.properties.recordIndex}">${localeService.t('mapMarkerEditCancel')}</button>
                    <button class="submit-position btn btn-inverse btn-small btn-block" data-marker-id="${marker.properties.recordIndex}">${localeService.t('mapMarkerEditSubmit')}</button>
                </div>
            </div>`;
        }

        $content.append(template);

        $content.find('.edit-mode').hide();
        //
        $content.on('click', '.edit-position', (event) => {
            let $el = $(event.currentTarget);
            let marker = getMarker($el.data('marker-id'));
            marker._originalPosition = marker.lngLat.wrap();
            $content.find('.view-mode').hide();
            $content.find('.edit-mode').show();
            $content.find('.help').show();
            isDraggable = true;

            map.on('mousedown', mouseDown);
        });

        $content.on('click', '.submit-position', (event) => {
            let $el = $(event.currentTarget);
            let marker = getMarker($el.data('marker-id'));

            isDraggable = false;
            $content.find('.view-mode').show();
            $content.find('.help').hide();
            $content.find('.updated-position').html('');
            $content.find('.edit-mode').hide();

            var popup = document.getElementsByClassName('mapboxgl-popup');
            if (popup[0]) popup[0].parentElement.removeChild(popup[0]);

            marker.lngLat = {
                lng: cachedGeoJson.features[0].geometry.coordinates[0],
                lat: cachedGeoJson.features[0].geometry.coordinates[1]
            };
            marker.feature = marker.features[0];
            eventEmitter.emit('markerChange', {marker, position: marker.lngLat});
        });


        $content.on('click', '.cancel-position', (event) => {
            let $el = $(event.currentTarget);
            let marker = getMarker($el.data('marker-id'));
            isDraggable = false;
            $content.find('.view-mode').show();
            $content.find('.updated-position').html('');
            $content.find('.edit-mode').hide();
            $content.find('.help').hide();

            var popup = document.getElementsByClassName('mapboxgl-popup');
            if (popup[0]) popup[0].parentElement.removeChild(popup[0]);

            cachedGeoJson.features[0].geometry.coordinates = [marker._originalPosition.lng, marker._originalPosition.lat];
            map.getSource('data').setData(cachedGeoJson);
        });

        // When the cursor enters a feature in the point layer, prepare for dragging.
        map.on('mouseenter', 'points', function () {
            if (!isDraggable) {
                return;
            }
            map.getCanvas().style.cursor = 'move';
            isCursorOverPoint = true;
            map.dragPan.disable();
        });

        map.on('mouseleave', 'points', function () {
            if (!isDraggable) {
                return;
            }
            map.getCanvas().style.cursor = '';
            isCursorOverPoint = false;
            map.dragPan.enable();
        });

        map.on('click', 'points', function (e) {
            markerCollection[e.features[0].properties.recordIndex] = e;
            var coordinates = e.features[0].geometry.coordinates.slice();

            while (Math.abs(e.lngLat.lng - coordinates[0]) > 180) {
                coordinates[0] += e.lngLat.lng > coordinates[0] ? 360 : -360;
            }

            var popup = document.getElementsByClassName('mapboxgl-popup');
            // Check if there is already a popup on the map and if so, remove it
            if (popup[0]) popup[0].parentElement.removeChild(popup[0]);

            popupDialog = new mapboxgl.Popup({closeOnClick: false}).setLngLat(coordinates)
                .setDOMContent($content.get(0))
                .addTo(map);

            popupDialog.on('close', function (event) {
                if (editable) {
                    resetMarkerPosition($content);
                }
            });
        });

        function mouseDown() {
            if (!isCursorOverPoint) return;

            isDragging = true;

            // Set a cursor indicator
            map.getCanvas().style.cursor = 'grab';

            // Mouse events
            map.on('mousemove', onMove);
            map.once('mouseup', onUp);

            var popup = document.getElementsByClassName('mapboxgl-popup');
            if (popup[0]) popup[0].parentElement.removeChild(popup[0]);
        }

        function onMove(e) {
            if (!isDragging) return;
            var coords = e.lngLat;

            // Set a UI indicator for dragging.
            map.getCanvas().style.cursor = 'grabbing';

            // Update the Point feature in `geojson` coordinates
            // and call setData to the source layer `point` on it.
            cachedGeoJson.features[0].geometry.coordinates = [coords.lng, coords.lat];
            map.getSource('data').setData(cachedGeoJson);
        }

        function onUp(e) {
            if (!isDragging) return;
            let position = e.lngLat;

            map.getCanvas().style.cursor = '';
            isDragging = false;

            // Unbind mouse events
            map.off('mousemove', onMove);
            $content.find('.updated-position').html(`${position.lat}<br>${position.lng}`);
            popupDialog = new mapboxgl.Popup({closeOnClick: false}).setLngLat(position)
                .setDOMContent($content.get(0))
                .addTo(map);

            popupDialog.on('close', function (event) {
                if (editable) {
                    resetMarkerPosition($content);
                }
            });
        }

    }

    const resetMarkerPosition = ($content) => {
        let marker = getMarker($content.find('.edit-position').data('marker-id'))
        $content.find('.view-mode').show();
        $content.find('.updated-position').html('');
        $content.find('.edit-mode').hide();
        $content.find('.help').hide();
        isDraggable = false;
        if (marker._originalPosition !== undefined) {
            cachedGeoJson.features[0].geometry.coordinates = [marker._originalPosition.lng, marker._originalPosition.lat];
            map.getSource('data').setData(cachedGeoJson);
        }
    }

    const getMarker = (markerId) => {
        return markerCollection[markerId];
    }

    return {
        initialize, setCollection
    }
}

export default markerGLCollection;
