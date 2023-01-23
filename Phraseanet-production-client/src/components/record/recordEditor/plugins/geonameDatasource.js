import $ from 'jquery';
import _ from 'underscore';
require('geonames-server-jquery-plugin/jquery.geonames.js');

const geonameDatasource = (services) => {
    const {configService, localeService, recordEditorEvents} = services;
    const tabContainerName = 'geonameTabContainer';
    let autoActivateTabOnce = true;
    let $container = null;
    let parentOptions = {};
    let $editTextArea;
    let $tabContent;
    let geonamesFieldMapping = false;
    let cityFields = [];
    let provinceFields = [];
    let countryFields = [];
    let latitudeFields = [];
    let longitudeFields = [];

    const initialize = (options) => {
        let initWith = {$container, parentOptions, $editTextArea} = options;
        let geocodingProviders = configService.get('geocodingProviders');
        _.each(geocodingProviders, (provider) => {
            //geoname field mapping
            if (provider['geonames-field-mapping'] == true) {
                if (provider['cityfields']) {
                    cityFields = provider['cityfields'].split(',').map(item => item.trim());
                }
                if (provider['provincefields']) {
                    provinceFields = provider['provincefields'].split(',').map(item => item.trim());
                }
                if (provider['countryfields']) {
                    countryFields = provider['countryfields'].split(',').map(item => item.trim());
                }
                if (provider['latitudefields']) {
                    latitudeFields = provider['latitudefields'].split(',').map(item => item.trim());
                }
                if (provider['longitudefields']) {
                    longitudeFields = provider['longitudefields'].split(',').map(item => item.trim());
                }
            }
        });
        recordEditorEvents.emit('appendTab', {
            tabProperties: {
                id: tabContainerName,
                title: localeService.t('Geoname Datasource'),
            },
            position: 1
        });
        // reset for each fields
        autoActivateTabOnce = true;

    };

    const onTabAdded = (params) => {
        let {origParams} = params;
        if (origParams.tabProperties.id === tabContainerName) {
            $tabContent = $(`#${tabContainerName}`, $container);
            bindEvents();
        }
    };

    const bindEvents = () => {
        let cclicks = 0;
        const cDelay = 350;
        let cTimer = null;
        $tabContent.on('click', '.geoname-add-action', (event) => {
            event.preventDefault();
            cclicks++;

            if (cclicks === 1) {
                cTimer = setTimeout(function () {
                    cclicks = 0;
                }, cDelay);

            } else {
                clearTimeout(cTimer);
                onSelectValue(event);
                cclicks = 0;
            }
        })
    };

    const onSelectValue = (event) => {
        event.preventDefault();
        let $el = $(event.currentTarget);
        let value = $el.data('city');

        // the field may have changed over time
        let field = parentOptions.fieldCollection.getActiveField();

        switch (field.name) {
            case 'City':
                value = $el.data('city');
                break;
            case 'Country':
                value = $el.data('country');
                break;
            case 'Province':
                value = $el.data('province');
                break;
            case 'Longitude':
                value = $el.data('longitude');
                break;
            case 'Latitude':
                value = $el.data('latitude');
                break;
            default:
                break;
        }

        // send prefill instruction for related fields:
        // send data for all geo fields (same as preset API)
        let fields = {};
        let presets = {};
        _.each(cityFields, function (field) {
            fields[field] = [$el.data('city')];
        });
        _.each(provinceFields, function (field) {
            fields[field] = [$el.data('province')];
        });
        _.each(countryFields, function (field) {
            fields[field] = [$el.data('country')];
        });

        $("#dialog-edit_lat_lon").dialog({
            resizable: false,
            height: "auto",
            width: 400,
            modal: true,
            buttons: {
                confirmYes : function () {
                    $(this).dialog("close");
                    _.each(latitudeFields, function (field) {
                        fields[field] = [String($el.data('latitude'))];
                    });
                    _.each(longitudeFields, function (field) {
                        fields[field] = [String($el.data('longitude'))];
                    });
                    presets.fields = fields;
                    recordEditorEvents.emit('recordEditor.addPresetValuesFromDataSource', {data: presets, mode: ''});
                    // force update on current field:
                    recordEditorEvents.emit('recordEditor.addValueFromDataSource', {value: value, field: field});
                    console.log('Lat & Lon updated')
                },
                confirmNo: function () {
                    presets.fields = fields;
                    recordEditorEvents.emit('recordEditor.addPresetValuesFromDataSource', {data: presets, mode: ''});
                    // force update on current field:
                    recordEditorEvents.emit('recordEditor.addValueFromDataSource', {value: value, field: field});
                    $(this).dialog("close");
                },

            },
            open: function open() {
                $('.ui-button-text:contains(confirmYes)').html($('#dialog-edit-yes').val());
                $('.ui-button-text:contains(confirmNo)').html($('#dialog-edit-no').val());
            },
            close:  function () {
            },
        });

    };

    const highlight = (s, t) => {
        if (t === '') {
            return s;
        }
        var matcher = new RegExp('(' + t + ')', 'ig');
        return s.replace(matcher, '<span class="ui-state-highlight">$1</span>');
    };

    const searchValue = (params) => {

        let {event, value, field} = params;
        let datas = {
            sort: '',
            sortParams: '',
            'client-ip': null,
            country: '',
            name: '',
            limit: 20
        };
        let searchType = false;
        let name;
        let country;

        let terms = value.split(',');
        if (terms.length === 2) {
            country = terms.pop();
        }

        name = terms.pop();

        if (cityFields.filter(item => item.toLowerCase() == field.name.toLowerCase()).length > 0) {
            searchType = 'city';
            datas.name = $.trim(name);
            datas.country = $.trim(country);
        } else if (provinceFields.filter(item => item.toLowerCase() == field.name.toLowerCase()).length > 0) {
            // @TODO - API can't search by region/province
            searchType = 'city';
            datas.province = $.trim(name);
            // datas.country = $.trim(country);
        } else if (countryFields.filter(item => item.toLowerCase() == field.name.toLowerCase()).length > 0) {
            searchType = 'city';
            datas.country = $.trim(name);
        // } else if (latitudeFields.filter(item => item.toLowerCase() == field.name.toLowerCase()).length > 0) {
        //     searchType = 'latitude';
        //     datas.latitude = $.trim(name);
        }

        if (searchType === false) {
            return;
        }

        // switch tab only on the first search:
        if (autoActivateTabOnce === true) {
            recordEditorEvents.emit('recordEditor.activateToolTab', tabContainerName);
            autoActivateTabOnce = false;
        }

        fetchGeoname(
            searchType,
            datas,
            function (jqXhr, status, error) {
                if (jqXhr.status !== 0 && jqXhr.statusText !== 'abort') {
                    console.log('error occured', [jqXhr, status, error])
                }
            },
            function (data) {
                return data;
            }
        );
    };

    const fetchGeoname = (resource, datas, errorCallback, parseresults) => {
        let url = configService.get('geonameServerUrl');
        url = url.substr(url.length - 1) === '/' ? url : url + '/';

        return $.ajax({
            url: url + resource,
            data: datas,
            dataType: 'jsonp',
            jsonpCallback: 'parseresults',
            success: function (data) {
                let template = '';
                _.map(data || [], function (item) {
                    let matchWith = datas.name !== '' ? datas.name : datas.country;

                    let labelName = highlight(item.name, matchWith);
                    let labelCountry = highlight((item.country ? item.country.name || '' : ''), matchWith);
                    let regionName = (item.region ? item.region.name || '' : '');
                    let labelRegion = highlight(regionName, name);

                    let location = {
                        value: labelName + (labelRegion !== '' ? ', <span class="region">' + labelRegion + '</span>' : ''),
                        label: (labelCountry !== '' ? labelCountry : ''),
                        geonameid: item.geonameid
                    };

                    template += `
                    <li class="geoname-add-action" data-city="${item.name}" data-country="${item.country.name}" data-province="${regionName}" data-latitude="${item.location.latitude}" data-longitude="${item.location.longitude}"><p>
                        <span>${location.value}</span>
                        <br>
                        <span>${location.label}</span></p>
                    </li>`;
                });

                $tabContent.empty().append(`<ul class="geoname-results">${template}</ul>`);
            },
            error: function (xhr, status, error) {
                console.log(status + '; ' + error);
            }
        });
    };

    recordEditorEvents.listenAll({
        'appendTab.complete': onTabAdded,
        'recordEditor.userInputValue': searchValue
    });

    return {initialize};
};
export default geonameDatasource;
