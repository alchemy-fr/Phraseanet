/* eslint-disable quotes */
/* eslint-disable no-undef */
import $ from 'jquery';
import _ from 'underscore';
import markerCollection from './markerCollection';
import markerGLCollection from './markerGLCollection';
import {generateRandStr} from '../../utils/utils';
import provider from '../provider';
import leafletLocaleFr from './locales/fr';
import merge from 'lodash.merge';
import MapboxGeocoder from '@mapbox/mapbox-gl-geocoder';
import '@mapbox/mapbox-gl-geocoder/dist/mapbox-gl-geocoder.css';
require('mapbox.js/theme/style.css');
require('mapbox-gl/dist/mapbox-gl.css');
require('./mapbox.css');
require('leaflet-draw/dist/leaflet.draw.css');
require('leaflet-contextmenu/dist/leaflet.contextmenu.css');

const leafletMap = (services) => {
    const {configService, localeService, eventEmitter} = services;
    let $container = null;
    let parentOptions = {};
    let tabOptions = {};
    let mapOptions = {};
    let mapUID;
    let mapbox;
    let mapboxgl;
    let leafletDraw;
    let featureLayer = null;
    let map = null;
    let geocoder = null;
    let mapboxClient = null;
    let markerGl = [];
    let $tabContent;
    let tabContainerName = 'leafletTabContainer';
    let editable;
    let drawable;
    let searchable;
    let drawnItems;
    let activeProvider = {};
    let recordConfig = {};
    let currentZoomLevel = 0;
    let shouldUpdateZoom = false;
    let features = null;
    let geojson = {};
    let labelLayerId;
    let mapboxGLDefaultPosition;
    let shapesWebGl = {};
    let MapboxCircle;
    let mapboxCircleCollection = [];
    let shouldDrawCircle = false;
    let shouldRemoveCircle = false;
    let turf = null;
    let editableCircleOpts = {
        editable: true,
        minRadius: 10,
        fillColor: '#000000',
        fillOpacity: 0.05,
        strokeColor: '#0000ff',
        strokeOpacity: 0.25,
        strokeWeight: 2,
        debugEl: document.getElementById('debug')
    };

    //let markerMapboxGl = {};
    const initialize = (options) => {
        let initWith = {$container, parentOptions} = options;
        tabOptions = options.tabOptions || false;
        mapOptions = options.mapOptions !== undefined ? _.extend(mapOptions, options.mapOptions) : mapOptions;
        editable = options.editable || false;
        drawable = options.drawable || false;
        searchable = options.searchable || false;
        drawnItems = options.drawnItems || false;
        recordConfig = parentOptions.recordConfig || false;

        mapUID = 'leafletMap' + generateRandStr(5);

        let providerConfig = provider(services);
        let isProviderInitalized = providerConfig.initialize();

        if (isProviderInitalized === true) {
            activeProvider = providerConfig.getConfiguration();

            if (tabOptions !== false) {
                // @TODO deepmerge
                let tabPlist = _.extend({
                    tabProperties: {
                        id: tabContainerName,
                        title: localeService.t('Geolocalisation'),
                        classes: 'descBoxes'
                    },
                    position: 1
                }, tabOptions);
                eventEmitter.emit('appendTab', tabPlist);
            }
        }
        onResizeEditor = _.debounce(onResizeEditor, 300);
        return isProviderInitalized;
    };

    const onRecordSelectionChanged = (params) => {
        if (activeProvider.accessToken === undefined) {
            return;
        }
        let {selection, selectionPos} = params;

        if(map != null ) {
            if (shouldUseMapboxGl() && !map.loaded()) {
                //refresh marker after 2 sec
                setTimeout(function () {
                    refreshMarkers(selection);
                }, 2000);
            } else {
                refreshMarkers(selection);
            }
        }
    };

    const onTabAdded = (params) => {
        if (activeProvider.accessToken === undefined) {
            return;
        }
        let {origParams, selection} = params;
        if (origParams.tabProperties.id === tabContainerName) {
            $container = $(`#${tabContainerName}`, parentOptions.$container);
            appendMapContent({selection});
        }
    };

    const appendMapContent = (params) => {
        let {selection} = params;
        initializeMap(selection);
    }

    const initializeMap = (pois) => {
        // if not access token provided - stop mapbox loading
        if (activeProvider.accessToken === undefined) {
            throw new Error('MapBox require an access token');
        }
        require.ensure([], () => {
            // select geocoding provider:
            mapbox = require('mapbox.js');
            leafletDraw = require('leaflet-draw');
            require('leaflet-contextmenu');
            mapboxgl = require('mapbox-gl');
            let MapboxClient = require('mapbox');
            let MapboxLanguage = require('@mapbox/mapbox-gl-language');
            MapboxCircle = require('mapbox-gl-circle');
            turf = require('@turf/turf');

            $container.empty().append(`<div id="${mapUID}" class="phrasea-popup" style="width: 100%;height:100%; position: absolute;top:0;left:0"></div>`);

            if (!shouldUseMapboxGl()) {
                L.mapbox.accessToken = activeProvider.accessToken;
                map = L.mapbox.map(mapUID, _, mapOptions);
                shouldUpdateZoom = false;
                map.setView(activeProvider.defaultPosition, activeProvider.defaultZoom);
                if (searchable) {
                    map.addControl(L.mapbox.geocoderControl('mapbox.places'));
                }
                var layers = {
                    Streets: L.mapbox.styleLayer('mapbox://styles/mapbox/streets-v11'),
                    Outdoors: L.mapbox.styleLayer('mapbox://styles/mapbox/outdoors-v11'),
                    Satellite: L.mapbox.styleLayer('mapbox://styles/mapbox/satellite-v9')
                };

                layers.Streets.addTo(map);
                L.control.layers(layers).addTo(map);
                geocoder = L.mapbox.geocoder('mapbox.places');

                if (drawable) {
                    addDrawableLayers();
                }
                addMarkersLayers();
                refreshMarkers(pois);
                addNoticeControlJS(drawable, editable);

                if (editable) {
                    map.on('contextmenu', function(eContext) {
                        let buttonText = localeService.t("Change position");
                        if (pois.length === 1) {
                            let poiIndex = 0;
                            let selectedPoi = pois[poiIndex];
                            let poiCoords = haveValidCoords(selectedPoi);

                            // if has no coords
                            if (poiCoords === false) {
                                buttonText = localeService.t("mapMarkerAdd");
                            }
                        }

                        let popupDialog = L.popup({ closeOnClick: false })
                            .setLatLng(eContext.latlng)
                            .setContent('<button class="add-position btn btn-inverse btn-small btn-block">' + buttonText + '</button>')
                            .openOn(map);

                        let popup = document.getElementsByClassName('leaflet-popup');
                        $(popup[0]).on('click', '.add-position', function(event) {
                            for (let i = 0; i < pois.length; i++) {
                                addMarkerOnce(eContext, i, pois[i]);
                            }
                        });

                    });
                }
            } else {
                mapboxgl.accessToken = activeProvider.accessToken
                if (mapboxGLDefaultPosition == null) {
                    mapboxGLDefaultPosition = $.extend([], activeProvider.defaultPosition);
                    mapboxGLDefaultPosition.reverse();
                }
                map = new mapboxgl.Map({
                    container: mapUID,
                    style: activeProvider.mapLayers[0].value,
                    center: mapboxGLDefaultPosition, // format different lng/lat
                    zoom: activeProvider.defaultZoom
                });

                if (!isIE()) {
                    //use mapboxlanguage if not IE11. Waiting for PR to be merged here https://github.com/mapbox/mapbox-gl-language/pulls
                    var language = new MapboxLanguage({defaultLanguage: $('html').attr('lang') || 'en'});
                    map.addControl(language);
                }

                //markerMapboxGl = new mapboxgl.Marker();

                shouldUpdateZoom = false;

                mapboxClient = new MapboxClient(mapboxgl.accessToken);

                if (drawable) {
                    // disable map rotation using right click + drag
                    map.dragRotate.disable();
                    // disable map rotation using touch rotation gesture
                    map.touchZoomRotate.disableRotation();
                    map.addControl(new mapboxgl.NavigationControl({
                        showCompass: false
                    }));

                    $('.map_search_dialog .ui-dialog-titlebar-close').on('click', function (event) {
                        event.preventDefault();
                        $('#EDIT_query').val('');
                        //eventEmitter.emit('shapeRemoved', {shapes: {}, drawnItems: {}});
                        eventEmitter.emit('updateCircleGeo', {shapes: [], drawnItems: []});
                        eventEmitter.emit('updateSearchValue');
                        removeCircleIfExist();
                        removeNoticeControlGL();
                    });

                    $('.submit-geo-search-action').on('click', function (event) {
                        removeCircleIfExist();
                        removeNoticeControlGL();
                    });

                    addCircleDrawControl();
                    addCircleGeoDrawing(drawnItems);

                } else {
                    map.addControl(new mapboxgl.NavigationControl());
                }

                addNoticeControlGL(drawable, editable);

                if (searchable) {
                    let geocoderSearch = new MapboxGeocoder({
                        accessToken: mapboxgl.accessToken,
                        mapboxgl: mapboxgl,
                        marker: false
                    });
                    map.addControl(geocoderSearch, 'top-left');
                }

                map.on('style.load', function () {
                    // Triggered when `setStyle` is called.
                    if (map.getStyle().name == "Mapbox Streets" || map.getStyle().name == "Mapbox Light") {
                        add3DBuildingsLayersGL();
                    }

                    if (geojson.hasOwnProperty('features')) addMarkersLayersGL(geojson);

                });

                map.on('load', function () {

                    var layers = map.getStyle().layers;

                    for (var i = 0; i < layers.length; i++) {
                        if (layers[i].type === 'symbol' && layers[i].layout['text-field']) {
                            labelLayerId = layers[i].id;
                            break;
                        }
                    }

                    geojson = {
                        type: 'FeatureCollection',
                        features: []
                    };

                    if (activeProvider.mapLayers.length > 1) {
                        addMapLayerControl(activeProvider.mapLayers);
                    }

                    if (!drawable) {

                        for (let i = 0; i < pois.length; i++) {
                            // add class for the icon
                            let el = document.createElement('div');
                            el.className = 'mapboxGl-phrasea-marker';

                            markerGl[pois[i]._rid] = new mapboxgl.Marker(el);
                        }

                        addMarkersLayersGL(geojson);
                        refreshMarkers(pois);
                    } else {
                        map.flyTo({
                            center: mapboxGLDefaultPosition, zoom: activeProvider.defaultZoom,
                            ...activeProvider.transitionOptions
                        });
                        //if bounds exist, move to bounds
                        // if (!_.isEmpty(drawnItems)) {
                        //     map.fitBounds(drawnItems[0].originalBounds);
                        // } else {
                        //     map.flyTo({
                        //         center: mapboxGLDefaultPosition, zoom: activeProvider.defaultZoom,
                        //         ...activeProvider.transitionOptions
                        //     });
                        // }

                        //map.on('moveend', calculateBounds).on('zoomend', calculateBounds);
                    }

                });

                if (editable) {
                    map.on('contextmenu', function(eContext) {
                        let buttonText = localeService.t("Change position");
                        if (pois.length === 1) {
                            let poiIndex = 0;
                            let selectedPoi = pois[poiIndex];
                            let poiCoords = haveValidCoords(selectedPoi);

                            // if has no coords
                            if (poiCoords === false) {
                                buttonText = localeService.t("mapMarkerAdd");
                            }
                        }

                        let popup = document.getElementsByClassName('mapboxgl-popup');
                        // Check if there is already a popup on the map and if so, remove it
                        if (popup[0]) {
                            popup[0].parentElement.removeChild(popup[0]);
                        }

                        let popupDialog = new mapboxgl.Popup({ closeOnClick: false })
                            .setLngLat(eContext.lngLat)
                            .setHTML('<button class="add-position btn btn-inverse btn-small btn-block">' + buttonText + '</button>')
                            .addTo(map);

                        popup = document.getElementsByClassName('mapboxgl-popup');
                        $(popup[0]).on('click', '.add-position', function(event) {
                            popup[0].parentElement.removeChild(popup[0]);
                            for (let i = 0; i < pois.length; i++) {
                                addMarkerOnce(eContext, i, pois[i]);
                            }
                        });

                    });
                }
            }


            currentZoomLevel = activeProvider.markerDefaultZoom;

            map.on('zoomend', function () {
                if (shouldUpdateZoom) {
                    currentZoomLevel = map.getZoom();
                }
                $('#map-zoom-to-setting').val(map.getZoom());
            });

            map.on('dragend', function () {
                var LngLat = map.getCenter();
                var arr= [];
                arr.push(String(LngLat['lat']));
                arr.push(String(LngLat['lng']));
               $('#map-position-to-setting').val('["'+LngLat['lat']+'","'+LngLat['lng'] +'"]');
            });

            map.on('remove', function () {
                console.log('remove');
            });

        });
    };

    const removeCircleIfExist = () => {
        if (mapboxCircleCollection.length > 0) {
            _.each(mapboxCircleCollection, function (circleObj) {
                circleObj.remove();
            });
        }
    }

    const boundsTo5percentRadius = (bounds) => {
        // noinspection JSUnresolvedVariable
        // noinspection JSCheckFunctionSignatures
        return Math.round(
            turf.distance(bounds.getSouthWest().toArray(), bounds.getNorthEast().toArray(), {units: 'meters'}) * .05);
    }

    const calculateBounds = () => {
        //get visible bounds of map
        var bounds = map.getBounds();
        var refactoredBoundsCoordinates = refactoredBounds(bounds);
        shapesWebGl['0'] = {
            type: 'rectangle',
            latlng: refactoredBoundsCoordinates,
            bounds: getMappedFieldsCollection(refactoredBoundsCoordinates),
            originalBounds: bounds
        };
        eventEmitter.emit('shapeCreated', {shapes: shapesWebGl, drawnItems: shapesWebGl});
    }

    const refactoredBounds = (bounds) => {
        if (bounds !== undefined) {
            var LngLat = [];
            var sw = bounds._sw;
            var nw = {lng: bounds._sw.lng, lat: bounds._ne.lat};
            var ne = bounds._ne;
            var se = {lng: bounds._ne.lng, lat: bounds._sw.lat};
            var LngLat = [sw, nw, ne, se];
            return LngLat;
        }
    }

    const addNoticeControlGL = (drawable, editable) => {
        let controlContainerSearch = $('.map_search_dialog .mapboxgl-control-container');
        let controlContainerEdit = $('#EDITWINDOW .mapboxgl-control-container');

        let $noticeButton = null;
        let $noticeBox = null;
        if (drawable) {
            $noticeButton = $('<button id="map-notice-btn"><img src="/assets/common/images/icons/button-information-grey.png" width="34" height="34"/></button>');

            $noticeBox = $('<div id="notice-box"><span class="notice-header"><img src="/assets/common/images/icons/information-grey.png" width="18" height="18" /><span class="notice-title">' +
                localeService.t("title notice") + '</span></span><span class="notice-desc">' + localeService.t("description notice") + '</span><span class="notice-close-btn"><img src="/assets/common/images/icons/button-close-gray.png" /></span></div>');

            controlContainerSearch.append($noticeButton);
            controlContainerSearch.append($noticeBox);
        }

        if (editable) {
            $noticeButton = $('<button id="map-info-btn"><img src="/assets/common/images/icons/button-information-grey.png" width="34" height="34"/></button>');

            $noticeBox = $('<div id="notice-info-box"><span class="notice-header"><img src="/assets/common/images/icons/information-grey.png" width="18" height="18" /><span class="notice-title">' +
                localeService.t("mapboxgl title info") + '</span></span><span class="notice-desc">' + localeService.t("mapboxgl description info") + '</span><span class="notice-close-btn"><img src="/assets/common/images/icons/button-close-gray.png" /></span></div>');

            controlContainerEdit.append($noticeButton);
            controlContainerEdit.append($noticeBox);
        }

        if ($noticeButton != null) {
            $noticeButton.on('click', function (event) {
                $noticeBox.show();
                $noticeButton.hide();
            });
        }

        $('.notice-close-btn').on('click', function (event) {
            $noticeBox.hide();
            $noticeButton.show();
        });
    }

    const removeNoticeControlGL = () => {
        let controlContainerSearch = $('.map_search_dialog .mapboxgl-control-container');
        let controlContainerEdit = $('#EDITWINDOW .mapboxgl-control-container');

        if (controlContainerSearch.find('#notice-box').length > 0) {
            $('#notice-box').remove();
        }
        if (controlContainerEdit.find('#notice-info-box').length > 0) {
            $('#notice-info-box').remove();
        }
    }

    const addNoticeControlJS = (drawable) => {
        let controlContainerSearch = $('.map_search_dialog .leaflet-control-container');
        let controlContainerEdit = $('#EDITWINDOW .leaflet-control-container');

        let $noticeButtonJs = null;
        let $noticeBoxJs = null;
        if (drawable) {
            $noticeButtonJs = $('<button id="map-noticeJs-btn"><img src="/assets/common/images/icons/button-information-grey.png" width="34" height="34"/></button>');

            $noticeBoxJs = $('<div id="noticeJs-box"><span class="notice-header"><img src="/assets/common/images/icons/information-grey.png" width="18" height="18" /><span class="notice-title">' +
                localeService.t("mapboxjs title notice") + '</span></span><span class="notice-desc">' + localeService.t("mapboxjs description notice") + '</span><span class="notice-close-btn"><img src="/assets/common/images/icons/button-close-gray.png" /></span></div>');

            controlContainerSearch.append($noticeButtonJs);
            controlContainerSearch.append($noticeBoxJs);
        }

        if (editable) {
            $noticeButtonJs = $('<button id="map-infoJs-btn"><img src="/assets/common/images/icons/button-information-grey.png" width="34" height="34"/></button>');

            $noticeBoxJs = $('<div id="notice-infoJs-box"><span class="notice-header"><img src="/assets/common/images/icons/information-grey.png" width="18" height="18" /><span class="notice-title">' +
                localeService.t("mapboxjs title info") + '</span></span><span class="notice-desc">' + localeService.t("mapboxjs description info") + '</span><span class="notice-close-btn"><img src="/assets/common/images/icons/button-close-gray.png" /></span></div>');

            controlContainerEdit.append($noticeButtonJs);
            controlContainerEdit.append($noticeBoxJs);
        }

        if ($noticeButtonJs != null) {
            $noticeButtonJs.on('click', function (event) {
                $noticeBoxJs.show();
                $noticeButtonJs.hide();
            });
        }

        $('.notice-close-btn').on('click', function (event) {
            $noticeBoxJs.hide();
            $noticeButtonJs.show();
        });
    }

    const removeNoticeControlJS = () => {
        let controlContainerSearch = $('.map_search_dialog .leaflet-control-container');
        let controlContainerEdit = $('#EDITWINDOW .leaflet-control-container');

        if (controlContainerSearch.find('#noticeJs-box').length > 0) {
            $('#noticeJs-box').remove();
        }
        if (controlContainerEdit.find('#notice-infoJs-box').length > 0) {
            $('#notice-infoJs-box').remove();
        }
    }

    const addCircleDrawControl = () => {
        // let controlContainerList = $('.mapboxgl-control-container');
        // let $circleControlContainer = $('<div class="circle-control-container"></div>');
        // controlContainerList.append($circleControlContainer);
        // let $toggleDrawCircleButton = $('<button class="draw-icon" id="map-circle-draw-btn"><i class="fa fa-plus-square" aria-hidden="true"></i></button>');
        // let $toggleRemoveCircleButton = $('<button class="draw-icon" id="map-circle-remove-btn"><i class="fa fa-trash" aria-hidden="true"></i></button>');
        // $circleControlContainer.empty().append($toggleDrawCircleButton).append($toggleRemoveCircleButton);
        //
        // $toggleDrawCircleButton.on('click', function (event) {
        //     $(this).toggleClass('selected');
        //     shouldDrawCircle = !shouldDrawCircle;
        //     if (shouldDrawCircle) {
        //         if ($('#map-circle-remove-btn').hasClass('selected')) {
        //             $('#map-circle-remove-btn').removeClass('selected');
        //             shouldRemoveCircle = !shouldRemoveCircle;
        //         }
        //     }
        // });
        //
        // $toggleRemoveCircleButton.on('click', function (event) {
        //     $(this).toggleClass('selected');
        //     shouldRemoveCircle = !shouldRemoveCircle;
        //     if (shouldRemoveCircle) {
        //         if ($('#map-circle-draw-btn').hasClass('selected')) {
        //             $('#map-circle-draw-btn').removeClass('selected');
        //             shouldDrawCircle = !shouldDrawCircle;
        //         }
        //     }
        // });
        //
        // map.on('click', function (e) {
        //     if (shouldDrawCircle) {
        //         addCircle(e.lonLat, boundsTo5percentRadius(map.getBounds()));
        //     }
        // });

        map.on('contextmenu', function (e) {
            addCircle(e.lngLat, boundsTo5percentRadius(map.getBounds()));
        });
    }

    const addCircleGeoDrawing = (drawnItems) => {
        _.map(drawnItems, function (items) {
            var lngLat = {};
            lngLat['lng'] = items.center.lng;
            lngLat['lat'] = items.center.lat;
            addCircle(lngLat, items.radius);
        });
    }

    const addCircle = (lngLat, radius) => {
        var myCircle = new MapboxCircle(lngLat, radius, editableCircleOpts)
            .once('click', function (mapMouseEvent) {
                var instanceId = myCircle.__instanceId;
                myCircle.remove();
                mapboxCircleCollection = _.reject(mapboxCircleCollection, function (circleObj) {
                    return circleObj.__instanceId === instanceId;
                });
                eventEmitter.emit('updateCircleGeo', {
                    shapes: mapboxCircleCollection,
                    drawnItems: mapboxCircleCollection
                });
            })
            .on('centerchanged', function (circleObj) {
                eventEmitter.emit('updateCircleGeo', {
                    shapes: mapboxCircleCollection,
                    drawnItems: mapboxCircleCollection
                });
            })
            .on('radiuschanged', function (circleObj) {
                eventEmitter.emit('updateCircleGeo', {
                    shapes: mapboxCircleCollection,
                    drawnItems: mapboxCircleCollection
                });
            })
            .addTo(map);

        mapboxCircleCollection.push(myCircle);
        eventEmitter.emit('updateCircleGeo', {shapes: mapboxCircleCollection, drawnItems: mapboxCircleCollection});
    }

    const addMapLayerControl = (layerArray) => {
        let controlContainerList = $('.mapboxgl-control-container');

        _.each(controlContainerList, (controlContainer) => {
            if ($(controlContainer).find('.map-selection-container').length > 0) {
                $(controlContainer).find('.map-selection-container').remove();
            }

            let mapSelectionContainer =
                $('<div class="dropdown map-selection-container"><button class="map-drop-btn"><i class="fa fa-map" aria-hidden="true"></i></button><div id="mapSelectionDropDown" class="map-dropdown-content"></div></div>');

            var $mapSelectionDropDown = mapSelectionContainer.find('#mapSelectionDropDown');

            $(controlContainer).append(mapSelectionContainer);

            $(controlContainer).on('click', 'button', function (event) {
                $mapSelectionDropDown.get(0).classList.toggle("show");
            });


            var map_list_div = document.createElement('div');
            _.each(layerArray, (layer, index) => {
                var div_layer = document.createElement('div');
                //add checked attr for first element
                var isChecked = index == 0 ? "checked=checked" : "";
                $(div_layer).append(`<label><input id=${layer.name} name="mapradio" type='radio' value=${layer.value} ${isChecked}>
            <span for=${layer.name}>${layer.name}</span></label>`);
                $(map_list_div).append(div_layer);
            });

            $mapSelectionDropDown.empty().append(map_list_div);
            $(controlContainer).on('click', 'input[name="mapradio"]', function (event) {
                switchLayer($(event.target));
            });


            $('body').on('click', function (event) {
                if ($(event.target).is('button.map-drop-btn') ||
                    $(event.target).is('button.map-drop-btn i')) {
                    return;
                } else {
                    var dropdowns = $mapSelectionDropDown;
                    var i;
                    for (i = 0; i < dropdowns.length; i++) {
                        var openDropdown = dropdowns[i];
                        if (openDropdown.classList.contains('show')) {
                            openDropdown.classList.remove('show');
                        }
                    }
                }
            });
        });

    }

    const switchLayer = ($elem) => {
        map.setStyle($elem.val());
    }

    const addDrawableLayers = () => {

        if (localeService.getLocale() === 'fr') {
            L.drawLocal = leafletLocaleFr;
        }
        // should restore drawn items?
        // user.getPreferences
        let drawingGroup;
        drawingGroup = new L.FeatureGroup();

        map.addLayer(drawingGroup);

        // Initialise the draw control and pass it the FeatureGroup of editable layers
        let drawControl = new L.Control.Draw({
            draw: {
                circle: false,
                polyline: false,
                polygon: false,
                marker: false,
                position: 'topleft',
                rectangle: {
                    //title: 'Draw a sexy polygon!',
                    allowIntersection: false,
                    drawError: {
                        color: '#b00b00',
                        timeout: 1000
                    },
                    shapeOptions: {
                        color: '#0c4554'
                    },
                    showArea: true
                }
            },
            edit: {
                featureGroup: drawingGroup
            }
        });
        let shapesDrawned = {};
        map.addControl(drawControl);

        map.on('draw:created', (event) => {
            let type = event.layerType;
            let layer = event.layer;
            let layerId = drawingGroup.getLayerId(layer);

            shapesDrawned[layerId] = {
                type: type,
                options: layer.options,
                latlng: layer.getLatLngs(),
                bounds: getMappedFieldsCollection(layer.getLatLngs())
            };
            drawingGroup.addLayer(layer);
            eventEmitter.emit('shapeCreated', {shapes: shapesDrawned, drawnItems: shapesDrawned});
        });

        map.on('draw:edited', (event) => {
            let layers = event.layers;

            layers.eachLayer(function (layer) {
                let layerId = drawingGroup.getLayerId(layer);
                // get type from drawed shape:
                let currentType = shapesDrawned[layerId].type;
                shapesDrawned[layerId] = merge(shapesDrawned[layerId], {
                    options: layer.options,
                    latlng: layer.getLatLngs(),
                    bounds: getMappedFieldsCollection(layer.getLatLngs())
                })
            });
            eventEmitter.emit('shapeEdited', {shapes: shapesDrawned, drawnItems: shapesDrawned});
        });

        map.on('draw:deleted', (event) => {
            let layers = event.layers;
            layers.eachLayer(function (layer) {
                let layerId = drawingGroup.getLayerId(layer);
                delete shapesDrawned[layerId];
            });
            eventEmitter.emit('shapeRemoved', {shapes: shapesDrawned, drawnItems: shapesDrawned});
        });

        // draw serialized items:
        applyDrawings(drawnItems, drawingGroup);
    };

    /***
     * Draw serialized shapes
     * @param shapesDrawned
     * @param drawingGroup
     */
    const applyDrawings = (shapesDrawned, drawingGroup) => {
        for (let shapeIndex in shapesDrawned) {
            if (shapesDrawned.hasOwnProperty(shapeIndex)) {
                let shape = shapesDrawned[shapeIndex];

                let newShape = L.rectangle(shape.latlng, shape.options);
                let newShapeType = '';
                switch (shape.type) {
                    case 'rectangle':
                        newShape = L.rectangle(shape.latlng, shape.options);
                        newShapeType = L.Draw.Rectangle.TYPE;
                        break;
                    default:
                        newShape = L.rectangle(shape.latlng, shape.options);
                        newShapeType = L.Draw.Rectangle.TYPE;
                }
                // start editor for new shape:
                newShape.editing.enable();
                drawingGroup.addLayer(newShape);
                // fire created event manually:
                map.fire('draw:created', {layer: newShape, layerType: newShapeType});
                newShape.editing.disable();
            }
        }
    }
    const addMarkerOnce = (e, poiIndex, poi) => {
        // inject coords into poi's fields:
        let mappedCoords = '';
        if (shouldUseMapboxGl()) {
            mappedCoords = getMappedFields(e.lngLat);
        } else {
            mappedCoords = getMappedFields(e.latlng);
        }

        let pois = [merge(poi, mappedCoords)];
        refreshMarkers(pois).then(() => {
            // broadcast event:
            let wrappedMappedFields = {};
            // values needs to be wrapped in a array:
            for (let fieldIndex in mappedCoords) {
                if (mappedCoords.hasOwnProperty(fieldIndex)) {
                    wrappedMappedFields[fieldIndex] = [mappedCoords[fieldIndex]]
                }
            }

            let presets = {
                fields: wrappedMappedFields //presetFields
            };
            if (!shouldUseMapboxGl()) {
                map.contextmenu.disable();
            }
            eventEmitter.emit('recordEditor.addPresetValuesFromDataSource', {data: presets, recordIndex: poiIndex});
        });
    }

    const add3DBuildingsLayersGL = () => {
        map.addLayer({
            'id': '3d-buildings',
            'source': 'composite',
            'source-layer': 'building',
            'filter': ['==', 'extrude', 'true'],
            'type': 'fill-extrusion',
            'minzoom': 15,
            'paint': {
                'fill-extrusion-color': '#aaa',

                // use an 'interpolate' expression to add a smooth transition effect to the
                // buildings as the user zooms in
                'fill-extrusion-height': [
                    "interpolate", ["linear"], ["zoom"],
                    15, 0,
                    15.05, ["get", "height"]
                ],
                'fill-extrusion-base': [
                    "interpolate", ["linear"], ["zoom"],
                    15, 0,
                    15.05, ["get", "min_height"]
                ],
                'fill-extrusion-opacity': .6
            }
        }, labelLayerId);
    }

    const addMarkersLayersGL = (geojson) => {

        map.addSource('data', {
            type: 'geojson',
            data: geojson
        });

        // map.loadImage(
        //     '/assets/common/images/icons/marker_icon.png',
        //     function (error, image) {
        //         if (error) throw error;
        //         map.addImage('custom-marker', image);
        //
        //         // Add a symbol layer
        //         map.addLayer({
        //             id: 'points',
        //             source: 'data',
        //             type: 'symbol',
        //             layout: {
        //                 "icon-image": 'custom-marker'
        //             },
        //         });
        //     }
        // );
    }

    const addMarkersLayers = () => {
        if (featureLayer !== null) {
            featureLayer.clearLayers();
        } else {
            featureLayer = L.mapbox.featureLayer([], {
                pointToLayer: function (feature, latlng) {
                    if (feature.properties.radius !== undefined) {
                        // L.circleMarker() draws a circle with fixed radius in pixels.
                        // To draw a circle overlay with a radius in meters, use L.circle()
                        return L.circleMarker(latlng, {radius: feature.properties.radius || 10});
                    } else {
                        let marker = require('mapbox.js/src/marker.js'); //L.marker(feature);
                        return marker.style(feature, latlng, {accessToken: activeProvider.accessToken});
                    }
                }
            }).addTo(map);
        }
    };

    const refreshMarkers = (pois) => {

        return buildGeoJson(pois).then((geoJsonPoiCollection) => {
            if(map != null) {
                if (shouldUseMapboxGl()) {
                    geojson = {
                        type: 'FeatureCollection',
                        features: geoJsonPoiCollection
                    };

                    map.getSource('data').setData(geojson);

                    markerGl.forEach(function (item, index) {
                        item.remove();
                    });

                    let markerGlColl = markerGLCollection(services);
                    markerGlColl.initialize({map, geojson, markerGl, editable});

                    if (geojson.features.length > 0) {
                        shouldUpdateZoom = true;
                        // var popup = new mapboxgl.Popup()
                        //     .setText(geojson.features[0].properties.title);
                        //markerMapboxGl.setLngLat(geojson.features[0].geometry.coordinates).setPopup(popup).addTo(map);
                        map.flyTo({
                            center: geojson.features[0].geometry.coordinates, zoom: currentZoomLevel,
                            ...activeProvider.transitionOptions
                        });
                        var position = {};
                        position.lng = geojson.features[0].geometry.coordinates[0];
                        position.lat = geojson.features[0].geometry.coordinates[1];
                        updateMarkerPosition(geojson.features[0].properties.recordIndex, position);

                    } else {
                        shouldUpdateZoom = false;
                        //markerMapboxGl.setLngLat(activeProvider.defaultPosition).addTo(map);
                        map.flyTo({
                            center: mapboxGLDefaultPosition, zoom: activeProvider.defaultZoom,
                            ...activeProvider.transitionOptions
                        });
                    }
                } else {
                    addMarkersLayers();

                    let markerColl = markerCollection(services);
                    markerColl.initialize({map, featureLayer, geoJsonPoiCollection, editable});

                    if (featureLayer.getLayers().length > 0) {
                        shouldUpdateZoom = true;
                        map.fitBounds(featureLayer.getBounds(), {maxZoom: currentZoomLevel});
                        var position = {};
                        position.lng = featureLayer.getGeoJSON()[0].geometry.coordinates[0];
                        position.lat = featureLayer.getGeoJSON()[0].geometry.coordinates[1];
                        updateMarkerPosition(featureLayer.getGeoJSON()[0].properties.recordIndex, position);
                    } else {
                        // set default position
                        shouldUpdateZoom = false;
                        map.setView(activeProvider.defaultPosition, activeProvider.defaultZoom);
                    }
                }
            }
        })

    };
    /**
     * build geoJson features return as a promise
     * @param pois
     * @returns {*}
     */
    const buildGeoJson = (pois) => {
        let geoJsonPoiCollection = [];
        let asyncQueries = [];
        let geoJsonPromise = $.Deferred();

        for (let poiIndex in pois) {
            let poi = pois[poiIndex];
            let poiCoords = extractCoords(poi);
            let poiTitle = poi.FileName || poi.Filename || poi.Title || poi.NomDeFichier;
            if (poiCoords[0] !== false && poiCoords[1] !== false) {
                geoJsonPoiCollection.push({
                    type: 'Feature',
                    geometry: {
                        type: 'Point',
                        coordinates: poiCoords
                    },
                    properties: {
                        _rid: poi._rid,
                        recordIndex: poiIndex,
                        'marker-color': '0c4554',
                        'marker-zoom': currentZoomLevel,
                        title: `${poiTitle}`
                    }
                });
            } else {
                // coords are not available, fallback on city/province/country if available

                let query = '';
                query += poi.City !== undefined && poi.City !== null ? poi.City : '';
                query += poi.Country !== undefined && poi.Country !== null ? `, ${poi.Country} ` : '';

                if (query !== '') {
                    if (shouldUseMapboxGl()) {
                        getDataForMapboxGl(asyncQueries, query, poiIndex, poiTitle, geoJsonPoiCollection);
                    } else {
                        getDataForMapbox(asyncQueries, query, poiIndex, poiTitle, geoJsonPoiCollection);
                    }
                }
            }
        }

        if (asyncQueries.length > 0) {
            $.when.apply(null, asyncQueries).done(function () {
                geoJsonPromise.resolve(geoJsonPoiCollection)
            });
        } else {
            geoJsonPromise.resolve(geoJsonPoiCollection)
        }
        return geoJsonPromise.promise();
    };

    const getDataForMapboxGl = (asyncQueries, query, poiIndex, poiTitle, geoJsonPoiCollection) => {
        let geoPromise = $.Deferred();
        mapboxClient.geocodeForward(query, (err, data) => {
            // take the first feature if exists
            if (data !== undefined) {
                if (data.features.length > 0) {
                    let bestResult = data.features[0];
                    bestResult.properties.recordIndex = poiIndex;
                    bestResult.properties['marker-zoom'] = currentZoomLevel;
                    bestResult.properties['marker-color'] = "0c4554";
                    bestResult.properties.title = `${poiTitle}`;
                    geoJsonPoiCollection.push(bestResult);
                }
            }
            geoPromise.resolve(geoJsonPoiCollection)
        });
        asyncQueries.push(geoPromise);
    }

    const getDataForMapbox = (asyncQueries, query, poiIndex, poiTitle, geoJsonPoiCollection) => {
        let geoPromise = $.Deferred();
        geocoder.query(query, (err, data) => {
            // take the first feature if exists
            if (data.results !== undefined) {
                if (data.results.features.length > 0) {

                    /*let circleArea = {
                     type: 'Feature',
                     geometry: {
                     type: 'Point',
                     coordinates: data.results.features[0].center //[[ data.bounds ]]
                     },
                     properties: {
                     title: `${poi.FileName}`
                     }
                     };
                     circleArea.properties['marker-zoom'] = 5;
                     circleArea.properties.radius = 50;
                     geoJsonPoiCollection.push(circleArea);*/

                    let bestResult = data.results.features[0];
                    bestResult.properties.recordIndex = poiIndex;
                    bestResult.properties['marker-zoom'] = currentZoomLevel;
                    bestResult.properties.title = `${poiTitle}`;
                    geoJsonPoiCollection.push(bestResult);
                }
            }
            geoPromise.resolve(geoJsonPoiCollection)
        });
        asyncQueries.push(geoPromise);
    }

    const extractCoords = (poi) => {
        if (poi !== undefined) {
            return [activeProvider.fieldPosition.longitude(poi), activeProvider.fieldPosition.latitude(poi)];
        }
        return [false, false];
    };

    const haveValidCoords = (poi) => {
        if (poi !== undefined) {
            return activeProvider.fieldPosition.longitude(poi) && activeProvider.fieldPosition.latitude(poi)
        }
        return false;
    };

    let onResizeEditor = () => {
        if (activeProvider.accessToken === undefined) {
            return;
        }
        if (map !== null) {
            if (shouldUseMapboxGl()) {
                map.resize();
                if (geojson.hasOwnProperty('features') && geojson.features.length > 0) {
                    shouldUpdateZoom = true;
                    //markerMapboxGl.setLngLat(geojson.features[0].geometry.coordinates).addTo(map);
                    map.flyTo({
                        center: geojson.features[0].geometry.coordinates, zoom: currentZoomLevel,
                        ...activeProvider.transitionOptions
                    });
                } else {
                    shouldUpdateZoom = false;
                    //markerMapboxGl.setLngLat(activeProvider.defaultPosition).addTo(map);
                    map.flyTo({
                        center: mapboxGLDefaultPosition, zoom: activeProvider.defaultZoom,
                        ...activeProvider.transitionOptions
                    });
                }
            } else {
                map.invalidateSize();
                if (featureLayer.getLayers().length > 0) {
                    shouldUpdateZoom = true;
                    map.fitBounds(featureLayer.getBounds(), {maxZoom: currentZoomLevel});
                } else {
                    // set default position
                    shouldUpdateZoom = false;
                    map.setView(activeProvider.defaultPosition, activeProvider.defaultZoom);
                }
            }

        }
    };

    const onMarkerChange = (params) => {
        let {marker, position} = params;

        if (editable) {
            updateMarkerPosition(marker.feature.properties.recordIndex, position);
        }
    };

    const updateMarkerPosition = (recordIndex, position) => {
        let mappedFields = getMappedFields(position);
        let wrappedMappedFields = {};
        // values needs to be wrapped in a array:
        for (let mappedFieldIndex in mappedFields) {
            if (mappedFields.hasOwnProperty(mappedFieldIndex)) {
                wrappedMappedFields[mappedFieldIndex] = [mappedFields[mappedFieldIndex]]
            }
        }

        let presets = {
            fields: wrappedMappedFields //presetFields
        };

        eventEmitter.emit('recordEditor.addPresetValuesFromDataSource', {data: presets, recordIndex});
}
    const getMappedFields = (position) => {
        let fieldMapping = activeProvider.provider['position-fields'] !== undefined ? activeProvider.provider['position-fields'] : [];
        let mappedFields = {};
        if (fieldMapping.length > 0) {

            _.each(fieldMapping, (mapping) => {
                // latitude and longitude are combined in a composite field
                if (mapping.type === 'latlng') {
                    mappedFields[mapping.name] = `${position.lat} ${position.lng}`;
                } else if (mapping.type === 'lat') {
                    mappedFields[mapping.name] = `${position.lat}`;
                } else if (mapping.type === 'lon') {
                    mappedFields[mapping.name] = `${position.lng}`;
                }
            });
        } else {
            mappedFields["meta.Latitude"] = `${position.lat}`;
            mappedFields["meta.Longitude"] = `${position.lng}`;
        }
        return mappedFields;
    }

    const getMappedFieldsCollection = (positions) => {
        let mappedPositions = [];
        for (let positionIndex in positions) {
            if (positions.hasOwnProperty(positionIndex)) {
                mappedPositions.push(getMappedFields(positions[positionIndex]))
            }
        }
        return mappedPositions;
    }

    const shouldUseMapboxGl = () => {
        if (activeProvider.defaultMapProvider === "mapboxWebGL" && mapboxgl !== undefined && mapboxgl.supported()) {
            return true;
        }
        return false;
    }

    const isIE = () => {
        let ua = navigator.userAgent;
        /* MSIE used to detect old browsers and Trident used to newer ones*/
        var is_ie = ua.indexOf("MSIE ") > -1 || ua.indexOf("Trident/") > -1;

        return is_ie;
    }

    eventEmitter.listenAll({
        'recordSelection.changed': onRecordSelectionChanged,
        'appendTab.complete': onTabAdded,
        /* eslint-disable quote-props */
        'markerChange': onMarkerChange,
        'tabChange': onResizeEditor,
    });
    return {initialize, appendMapContent}
};

export default leafletMap;
