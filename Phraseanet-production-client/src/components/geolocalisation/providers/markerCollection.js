import $ from 'jquery';

const markerCollection = (services) => {
    const {configService, localeService, eventEmitter} = services;
    let markerCollection = {};
    let cachedGeoJson;
    let featureLayer;
    let map;
    let geoJsonPoiCollection;
    let editable;

    const initialize = (params) => {
        let initWith = {map, featureLayer, geoJsonPoiCollection} = params;
        editable = params.editable || false;
        setCollection(geoJsonPoiCollection)
    };

    const setCollection = (geoJsonPoiCollection) => {
        featureLayer.setGeoJSON(geoJsonPoiCollection);
        cachedGeoJson = featureLayer.getGeoJSON();
        featureLayer.eachLayer(function (layer) {
            setMarkerPopup(layer);
        });

    };

    const triggerRefresh = () => {
        setCollection(cachedGeoJson);
        cachedGeoJson = featureLayer.getGeoJSON();
    };

    const setMarkerPopup = (marker) => {
        switch (marker.feature.geometry.type) {
            case 'Polygon':
                break;
            case 'Point':
                setPoint(marker);
                break;
            default:
        }
    };

    const setPoint = (marker) => {
        markerCollection[marker._leaflet_id] = marker;
        let $content = $('<div style="min-width: 200px"/>');

        let template = `<p>${marker.feature.properties.title}</p> `;

        if (editable === true && marker.dragging !== undefined) {
            template += `
            <div class="view-mode">
                    <button class="edit-position btn btn-inverse btn-small btn-block" data-marker-id="${marker._leaflet_id}">${localeService.t('mapMarkerEdit')}</button>
            </div>
            <div class="edit-mode">
                <p class="help">${localeService.t('mapMarkerMoveLabel')}</p>
                <p><span class="updated-position"></span></p>
                <div>
                    <button class="cancel-position btn btn-inverse btn-small btn-block" data-marker-id="${marker._leaflet_id}">${localeService.t('mapMarkerEditCancel')}</button>
                    <button class="submit-position btn btn-inverse btn-small btn-block" data-marker-id="${marker._leaflet_id}">${localeService.t('mapMarkerEditSubmit')}</button>
                </div>
            </div>`;
        }

        $content.append(template);

        $content.find('.edit-mode').hide();

        $content.on('click', '.edit-position', (event) => {
            let $el = $(event.currentTarget);
            let marker = getMarker($el.data('marker-id'));
            marker._originalPosition = marker.getLatLng().wrap();
            marker.dragging.enable();
            $content.find('.view-mode').hide();
            $content.find('.edit-mode').show();
            $content.find('.help').show();
        });

        $content.on('click', '.submit-position', (event) => {
            let $el = $(event.currentTarget);
            let marker = getMarker($el.data('marker-id'));

            marker.dragging.disable();
            $content.find('.view-mode').show();
            $content.find('.help').hide();
            $content.find('.edit-mode').hide();
            marker._originalPosition = marker.getLatLng().wrap();
            eventEmitter.emit('markerChange', {marker, position: marker.getLatLng().wrap()});
        });


        $content.on('click', '.cancel-position', (event) => {
            let $el = $(event.currentTarget);
            let marker = getMarker($el.data('marker-id'));
            marker.dragging.disable();
            $content.find('.view-mode').show();

            marker.setLatLng(marker._originalPosition);
            triggerRefresh();
        });

        marker.bindPopup($content.get(0));

        marker.on('dragend', () => {
            let position = marker.getLatLng().wrap();
            $content.find('.updated-position').html(`${position.lat}<br>${position.lng}`);
            $content.find('.edit-mode').show();
            marker.bindPopup($content.get(0));
            marker.openPopup();
        })

    }

    const getMarker = (markerId) => {
        return markerCollection[markerId];
    }

    return {
        initialize, setCollection
    }
}

export default markerCollection;
