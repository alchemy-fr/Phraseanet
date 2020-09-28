import _ from 'underscore';
/**
 * Set a provider
 *
 */

const provider = (services) => {
    const {configService, localeService, eventEmitter} = services;
    let accessToken;
    let defaultPosition;
    let defaultZoom;
    let markerDefaultZoom;
    let activeProvider;
    let fieldPosition;
    let defaultMapProvider;
    let transitionOptions = Object.create(null);
    let mapLayers = [{name: 'streets', value: 'mapbox://styles/mapbox/streets-v9'}];
    const initialize = () => {
        let isValid = false;
        // select geocoding provider:
        let geocodingProviders = configService.get('geocodingProviders');
        _.each(geocodingProviders, (provider) => {
            if (provider.enabled === true) {
                activeProvider = provider;
                accessToken = provider['public-key'];
                let fieldMapping = provider['position-fields'] !== undefined ? provider['position-fields'] : [];

                fieldPosition = {};

                if (fieldMapping.length > 0) {
                    _.each(fieldMapping, (mapping) => {
                        // latitude and longitude are combined in a composite field
                        if (mapping.type === 'latlng') {
                            fieldPosition = {
                                latitude: (poi) => extractFromPosition('lat', poi[mapping.name]),
                                longitude: (poi) => extractFromPosition('lon', poi[mapping.name])
                            };
                        } else if (mapping.type === 'lat') {
                            // if latitude field mapping is provided, fallback:
                            fieldPosition.latitude = (poi) => isNaN(parseFloat(poi[mapping.name], 10)) ? false : parseFloat(poi[mapping.name], 10);
                        } else if (mapping.type === 'lon') {
                            // if longitude field mapping is provided, fallback:
                            fieldPosition.longitude = (poi) => isNaN(parseFloat(poi[mapping.name], 10)) ? false : parseFloat(poi[mapping.name], 10);
                        }
                    });
                    if (fieldPosition.latitude !== undefined && fieldPosition.longitude !== undefined) {
                        isValid = true;
                    }
                } else {
                    fieldPosition = {
                        latitude: (poi) => getCoordinatesFromTechnicalInfo(poi, 'lat'),
                        longitude: (poi) => getCoordinatesFromTechnicalInfo(poi, 'lng')
                    };
                    isValid = true;
                }




                // set default values:


                 var defaultPositionValue = $('#map-position-from-setting').val();
                if(defaultPositionValue != '') {
                    defaultPositionValue = defaultPositionValue.split('"');
                    var arr = [];
                    arr.push(defaultPositionValue[1]);
                    arr.push(defaultPositionValue[3]);
                    defaultPosition = arr ;
                } else {
                    defaultPosition = provider['default-position'];
                }


                defaultZoom = $('#map-zoom-from-setting').val()!='' ? $('#map-zoom-from-setting').val() :  provider['default-zoom'] || 2;
                markerDefaultZoom = provider['marker-default-zoom'] || 12;
                defaultMapProvider = provider['map-provider'] || "mapboxWebGL";
                if (provider['map-layers'] && provider['map-layers'].length > 0) {
                    //update map layer;
                    mapLayers = provider['map-layers'];
                }
                if (provider['transition-mapboxgl'] !== undefined) {
                    var options = provider['transition-mapboxgl'][0] || [];
                    transitionOptions.animate = options['animate'] !== undefined ? options['animate'] : true;
                    transitionOptions.speed = options['speed'] || 1.2;
                    transitionOptions.curve = options['curve'] || 1.42;
                }
            }
        });
        if (accessToken === undefined) {
            isValid = false;
        }
        return isValid;
    }

    const getCoordinatesFromTechnicalInfo = (poi, fieldMapping) => {
        if (poi["technicalInfo"] !== undefined) {
            if (fieldMapping == 'lat') {
                return isNaN(parseFloat(poi["technicalInfo"].latitude, 10)) ? false : parseFloat(poi["technicalInfo"].latitude, 10)
            } else {
                return isNaN(parseFloat(poi["technicalInfo"].longitude, 10)) ? false : parseFloat(poi["technicalInfo"].longitude, 10)
            }
        }
        return false;
    }

    /**
     * extract latitude or longitude from a position
     * @param name
     * @param source
     * @returns {*}
     */
    const extractFromPosition = (name, source) => {
        if (source === undefined || source === null) {
            return false;
        }

        let position = source.split(' ');

        if (position.length !== 2) {
            position = source.split(',');
        }

        // ok parse lat
        if (position.length === 2) {
            if (name === 'lat' || name === 'latitude') {
                return parseFloat(position[0])
            }
            return parseFloat(position[1])
        } else {
            // invalid
            return false;
        }
    };

    const getConfiguration = () => {
        return {
            defaultPosition,
            defaultZoom,
            markerDefaultZoom,
            fieldPosition,
            accessToken,
            defaultMapProvider,
            mapLayers,
            provider: activeProvider,
            transitionOptions
        }
    };

    return {
        initialize, getConfiguration
    }
}

export default provider;
