import $ from 'jquery';
let mapboxgl = require('mapbox-gl');

const markerGLCollection = (services) => {
    const {configService, localeService, eventEmitter} = services;
    let cachedGeoJson;
    let map;
    let geojson;
    let markerGl;
    let editable;
    let isDraggable = false;
    let isCursorOverPoint = false;
    let isDragging = false;
    let popup = {};
    let popupDialog;

    const initialize = (params) => {
        let initWith = {map, geojson, markerGl} = params;
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
        let markerId = marker.properties.recordIndex;

        if (marker.properties._rid !== undefined) {
            markerId = marker.properties._rid;
        }

        let markerElement = getMarker(markerId);

        if (markerElement === undefined) {
            let el = document.createElement('div');
            el.className = 'mapboxGl-phrasea-marker';

            markerElement = markerGl[markerId] = new mapboxgl.Marker(el);
        }

        markerElement.feature = {
            properties : {
                recordIndex : marker.properties.recordIndex
            }
        };

        // add marker to map
        markerElement
            .setLngLat(marker.geometry.coordinates)
            .addTo(map);

        let $content = $('<div style="min-width: 200px"/>');

        let template = `<p>${marker.properties.title}</p> `;

        if (editable === true) {
            template += `
            <div class="view-mode">
                    <button class="edit-position btn btn-inverse btn-small btn-block" data-marker-id="${marker.properties._rid}">${localeService.t('mapMarkerEdit')}</button>
            </div>
            <div class="edit-mode">
                <p class="help" style="font-size: 12px;font-style: italic;">${localeService.t('mapMarkerMoveLabel')}</p>
                <p><span class="updated-position" style="font-size: 12px;"></span></p>
                <div>
                    <button class="cancel-position btn btn-inverse btn-small btn-block" data-marker-id="${marker.properties._rid}">${localeService.t('mapMarkerEditCancel')}</button>
                    <button class="submit-position btn btn-inverse btn-small btn-block" data-marker-id="${marker.properties._rid}">${localeService.t('mapMarkerEditSubmit')}</button>
                </div>
            </div>`;
        }

        $content.append(template);

        $content.find('.edit-mode').hide();

        let popupDialog = new mapboxgl.Popup({closeOnClick: false})
            .setDOMContent($content.get(0));

        popupDialog.on('close', function (event) {
            if (editable) {
                markerElement.setDraggable(false);
            }
        });

        // bind popup to the marker element
        markerElement.setPopup(popupDialog);

        markerElement.on('dragend', () => {
            let position = markerElement.getLngLat().wrap();
            $content.find('.updated-position').html(`${position.lat}<br>${position.lng}`);
            $content.find('.edit-mode').show();
        });

        $content.on('click', '.edit-position', (event) => {
            let $el = $(event.currentTarget);
            let markerSelected = getMarker($el.data('marker-id'));
            markerSelected._originalPosition = markerElement.getLngLat().wrap();
            $content.find('.view-mode').hide();
            $content.find('.edit-mode').show();
            $content.find('.help').show();

            markerSelected.setDraggable(true);
        });

        $content.on('click', '.submit-position', (event) => {
            let $el = $(event.currentTarget);
            let markerSelected = getMarker($el.data('marker-id'));

            markerSelected.setDraggable(false);
            $content.find('.view-mode').show();
            $content.find('.help').hide();
            $content.find('.updated-position').html('');
            $content.find('.edit-mode').hide();

            markerSelected.togglePopup();

            markerSelected._originalPosition = markerSelected.getLngLat().wrap();
            eventEmitter.emit('markerChange', {marker: markerSelected, position: markerSelected.getLngLat().wrap()});
        });


        $content.on('click', '.cancel-position', (event) => {
            let $el = $(event.currentTarget);
            let markerSelected = getMarker($el.data('marker-id'));

            markerSelected.setDraggable(false);
            $content.find('.view-mode').show();
            $content.find('.updated-position').html('');
            $content.find('.edit-mode').hide();
            $content.find('.help').hide();

            markerSelected.togglePopup();

            resetMarkerPosition($content, markerSelected);
        });
    }

    const resetMarkerPosition = ($content, marker) => {
        $content.find('.view-mode').show();
        $content.find('.updated-position').html('');
        $content.find('.edit-mode').hide();
        $content.find('.help').hide();
        isDraggable = false;
        if (marker._originalPosition !== undefined) {
            cachedGeoJson.features[0].geometry.coordinates = [marker._originalPosition.lng, marker._originalPosition.lat];
            marker.setLngLat(marker._originalPosition);
        }
    }

    const getMarker = (markerId) => {
        return markerGl[markerId];
    }

    return {
        initialize, setCollection
    }
}

export default markerGLCollection;
