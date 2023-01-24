require('./facets.scss');

import $ from 'jquery';

require('jquery-ui');
require('jquery.fancytree/src/jquery.fancytree');
import * as _ from 'underscore';

const workzoneFacets = services => {
    const {configService, localeService, appEvents} = services;
    let selectedFacets = {};
    let facets = null;

    const ORDER_BY_BCT = 'ORDER_BY_BCT';
    const ORDER_ALPHA_ASC = 'ORDER_ALPHA_ASC';
    const ORDER_ALPHA_DESC = 'ORDER_ALPHA_DESC';
    const ORDER_BY_HITS = 'ORDER_BY_HITS';
    const ORDER_BY_HITS_ASC = 'ORDER_BY_HITS_ASC';

    let facetStatus = $.parseJSON(sessionStorage.getItem('facetStatus')) || [];
    let hiddenFacetsList = [];


    var resetSelectedFacets = function () {
        selectedFacets = {};
        return selectedFacets;
    };

    /**
     *  add missing selected facets fields into "facets", from "selectedFacets"
     *  why : because if we negates all values for a facet field (all red), the facet will disapear from next query->answers
     *        (not in "facets" anymore, not in ux). So we lose the posibility to delete or invert a facet value.
     *  nb : negating all facets values does not mean there will be 0 results, because the field can be empty for some records.
     */
    function facetsAddMissingSelected(_selectedFacets, _facets) {
        _.each(_selectedFacets, function (v, k) {
            var found = _.find(_facets, function (facet) {
                return (facet.field == k);
            });
            if (!found) {
                var i = _facets.push(_.clone(v)); // add a "fake" facet to facets
                _facets[i - 1].values = [];      // with no values
            }
        });
    };

    var loadFacets = function (data) {
        hiddenFacetsList = data.hiddenFacetsList;

        function sortIteration(i) {
            switch (data.facetValueOrder) {
                case ORDER_ALPHA_ASC:
                    return i.value.toString().toLowerCase();
                    break;
                case ORDER_BY_HITS_ASC:
                    return i.count ;
                    break;;
                case ORDER_BY_HITS:
                    return i.count * -1;
                    break;
            }
        }

        facetsAddMissingSelected(selectedFacets, data.facets);

        // Convert facets data to fancytree source format
        var treeSource = _.map(data.facets, function (facet) {
            // Values
            var values = _.map(_.sortBy(facet.values, sortIteration), function (value) {
                var type = facet.type;     // todo : define a new phraseanet "color" type for fields. for now we push a "type" for every value, copied from field type
                // patch "color" type values
                var textLimit = 15;     // cut long values (set to 0 to not cut)
                var text = (value.value).toString();
                var label = text;
                var title = text;
                var tooltip = text;
                var match = text.match(/^(.*)\[#([0-9a-fA-F]{6})].*$/);
                if(match && match[2] != null) {
                    // text looks like a color !
                    var colorCode = '#' + match[2];
                    // add color circle and remove color code from text;
                    var textWithoutColorCode = text.replace('[' + colorCode + ']', '');
                    if (textLimit > 0 && textWithoutColorCode.length > textLimit) {
                        textWithoutColorCode = textWithoutColorCode.substring(0, textLimit) + '…';
                    }
                    // patch
                    type = "COLOR-AGGREGATE";
                    label = textWithoutColorCode;
                    tooltip = _.escape(textWithoutColorCode);
                    title = '<span class="color-dot" style="background-color: ' + colorCode + ';"></span> ' + tooltip;
                }
                else {
                    // keep text as it is, just cut if too long
                    if (textLimit > 0 && text.length > textLimit) {
                        text = text.substring(0, textLimit) + '…';
                    }
                    label = text;
                    /*title = tooltip = _.escape(text);*/
                }

                return {
                    // custom data
                    query:     value.query,
                    field:     facet.field,
                    raw_value: value.raw_value,
                    value:     value.value,
                    label:     label,             // displayed when selected (blue/red), escape is done later (render)
                    type:      type,              // todo ? define a new phraseanet "color" type for fields. for now we push a "type" for every value
                    count:     value.count,
                    // jquerytree data
                    title:     title + ' (' + formatNumber(value.count) + ')',
                    tooltip:   tooltip + ' (' + formatNumber(value.count) + ')'
                };
            });
            // Facet
            return {
                // custom data
                name:     facet.name,
                field:    facet.field,
                label:    facet.label,
                type:     facet.type,
                // jquerytree data
                title:    facet.label,
                folder:   true,
                children: values,
                expanded: !_.some(facetStatus, function(o) { return _.has(o, facet.name)})
            };

        });

        if (data.facetOrder == ORDER_ALPHA_ASC) {
            treeSource.sort(
                _sortFacets('title', true, function (a) {
                    return a.toUpperCase();
                })
            );

        }

        if (data.facetOrder == ORDER_ALPHA_DESC) {
            treeSource.sort(
                _sortFacets('title', false, function (a) {
                    return a.toUpperCase();
                })
            );

        }

        if (data.filterFacet == true) {
            treeSource = _hideSingleValueFacet(treeSource);
        }

        if (hiddenFacetsList.length > 0) {
            treeSource = _shouldMaskNodes(treeSource, hiddenFacetsList);
        }

        treeSource = _parseColors(treeSource);

        treeSource = _colorUnsetText(treeSource);

        return _getFacetsTree().reload(treeSource)
            .done(function () {
                _.each($('#proposals').find('.fancytree-expanded'), function (element, i) {
                    $(element).find('.fancytree-title, .fancytree-expander').css('line-height', '50px');
                    $(element).find('.mask-facets-btn, .fancytree-expander').css('height', '50px');

                    var li_s = $(element).next().children('li');
                    var ul = $(element).next();
                    if (li_s.length > 5) {
                        _.each(li_s, function (el, i) {
                            if (i > 4) {
                                $(el).hide();
                            }
                        });
                        ul.append('<button class="see_more_btn">See more</button>');
                    }
                });
                $('.see_more_btn').on('click', function () {
                    $(this).closest('ul').children().show();
                    $(this).hide();
                    return false;
                });
            });
    };

    function _parseColors(source) {
        _.forEach(source, function (facet) {
            if (!_.isUndefined(facet.children) && (facet.children.length > 0)) {
                _.forEach(facet.children, function (child) {
                    var title = child.title;
                    child.title = _formatColorText(title.toString());
                });
            }
        });
        return source;
    }

    function _formatColorText(string) {
        var textLimit = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;

        //get color code from text if exist
        var regexp = /^(.*)\[#([0-9a-fA-F]{6})].*$/;

        var match = string.match(regexp);
        if (match && match[2] != null) {
            var colorCode = '#' + match[2];
            // //add color circle and re move color code from text;
            var textWithoutColorCode = string.replace('[' + colorCode + ']', '');
            if (textLimit > 0 && textWithoutColorCode.length > textLimit) {
                textWithoutColorCode = textWithoutColorCode.substring(0, textLimit) + '…';
            }
            textWithoutColorCode = $('<div/>').text(textWithoutColorCode).html();   // escape html
            return '<span class="color-dot" style="background-color: ' + colorCode + '"></span>' + ' ' + textWithoutColorCode;
        } else {
            if (textLimit > 0 && string.length > textLimit) {
                string = string.substring(0, textLimit) + '…';
            }
            string = $('<div/>').text(string).html();   // escape html
            return string;
        }
    }

    function _colorUnsetText(source) {
        _.forEach(source, function (facet) {
            if (!_.isUndefined(facet.children) && (facet.children.length > 0)) {
                _.forEach(facet.children, function (child) {
                    if (child.raw_value.toString() === '_unset_') {
                        var title = child.title;
                        child.title = '<span style="color:#2196f3;">' + title.toString() +'</span>';
                    }
                });
            }
        });

        return source;
    }


    // from stackoverflow
    // http://stackoverflow.com/questions/979256/sorting-an-array-of-javascript-objects/979325#979325
    function _sortFacets(field, reverse, primer) {
        var key = function (x) {
            return primer ? primer(x[field]) : x[field];
        };

        return function (a, b) {
            let A = key(a);
            let B = key(b);
            return (A < B ? -1 : A > B ? 1 : 0) * [-1, 1][+!!reverse];
        };
    }

    function _shouldMaskNodes(source, facetsList) {
        let filteredSource = source.slice();
        _.each(facetsList, function (facetsValue, index) {
            for (let i = filteredSource.length - 1; i > -1; --i) {
                let facet = filteredSource[i];
                if (facet['name'] !== undefined) {
                    if (facet['name'] === facetsValue.name) {
                        filteredSource.splice(i, 1);
                    }
                }
            }
        });
        return filteredSource;
    }

    /**
     * hide facets with only one value (experimental)
     *
     * @param source    treesource
     * @returns {*}
     */
    function _hideSingleValueFacet(source) {
        var filteredSource = [];
        _.forEach(source, function (facet) {
            if (!_.isUndefined(facet.children) && (facet.children.length > 1 || !_.isUndefined(selectedFacets[facet.field]))) {
                filteredSource.push(facet);
            }
        });
        source = filteredSource;

        return source;
    }

    function _sortByPredefinedFacets(source, field, predefinedFieldOrder) {
        let filteredSource = source.slice();
        let ordered = [];

        _.each(predefinedFieldOrder, function (fieldValue, index) {
            for (let i = filteredSource.length - 1; i > -1; --i) {
                let facet = filteredSource[i];
                if (facet[field] !== undefined) {
                    if (facet[field] === fieldValue) {
                        ordered.push(facet);
                        // remove from filtered
                        filteredSource.splice(i, 1);
                    }
                }
            }
        });

        const olen = filteredSource.length;
        // fill predefined facets with non predefined facets
        for (let i = 0; i < olen; i++) {
            ordered.push(filteredSource[i]);
        }
        return ordered;
    }
    /*Format number to local fr */
    function formatNumber(number) {
        var locale = 'fr';
        var formatter = new Intl.NumberFormat(locale);
        return formatter.format(number);
    }

    function _getFacetsTree() {
        var $facetsTree = $('#proposals');
        if (!$facetsTree.data('ui-fancytree')) {
            $facetsTree.fancytree({
                clickFolderMode: 2, // expand
                icons: false,
                source: [],
                activate: function (event, data) {
                    var eventType = event.originalEvent;
                    //if user did not click, then no need to perform any query
                    if (eventType == null) {
                        return;
                    }
                    var facet = data.node.parent;
                    var facetData = {
                        value: data.node.data,
                        enabled: true,
                        negated: event.altKey // ,
                        // mode:    event.altKey ? "EXCEPT" : "AND"
                    };

                    if (selectedFacets[facet.data.field] == null) {
                        selectedFacets[facet.data.field] = facet.data;
                        selectedFacets[facet.data.field].values = [];
                    }
                    selectedFacets[facet.data.field].values.push(facetData);
                    appEvents.emit('search.doRefreshState');
                },
                collapse: function (event, data) {
                    var dict = {};
                    dict[data.node.data.name] = "collapse";
                    if (_.findWhere(facetStatus, dict) !== undefined) {
                        facetStatus = _.without(facetStatus, _.findWhere(facetStatus, dict))
                    }
                    facetStatus.push(dict);
                    sessionStorage.setItem('facetStatus', JSON.stringify(facetStatus));
                },
                expand: function (event, data) {
                    var dict = {};
                    dict[data.node.data.name] = "collapse";
                    if (_.findWhere(facetStatus, dict) !== undefined) {
                        facetStatus = _.without(facetStatus, _.findWhere(facetStatus, dict))
                    }
                    sessionStorage.setItem('facetStatus', JSON.stringify(facetStatus));
                },
                renderNode: function (event, data) {
                    var facetFilter = "";
                    var node = data.node;
                    var $nodeSpan = $(node.span);

                    // check if span of node already rendered
                    if (!$nodeSpan.data('rendered')) {
                        var deleteButton = $('<div class="mask-facets-btn"><a></a></div>');
                        $nodeSpan.append(deleteButton);
                        deleteButton.hide();

                        $nodeSpan.hover(function () {
                            /*Dont show deleteButton if there is selected facet*/
                            if ($('.fancytree-folder', data.node.li).find('[class^="facetFilter_"]').length === 0) {
                                deleteButton.show();
                            }
                        }, function () {
                            deleteButton.hide();
                        });

                        deleteButton.click(function () {
                            var nodeObj = {name: node.data.name, title: node.title};
                            hiddenFacetsList.push(nodeObj);
                            node.remove();
                            appEvents.emit('searchAdvancedForm.saveHiddenFacetsList', hiddenFacetsList);
                            appEvents.emit('search.reloadHiddenFacetList', hiddenFacetsList);
                        });

                        // span rendered
                        $nodeSpan.data('rendered', true);

                        if (data.node.folder) {
                            // here we render a "fieldname" level
                            if (!_.isUndefined(selectedFacets[data.node.data.field])) {
                                // here the field already contains selected facetvalues (to be rendered blue or red)
                                if ($('.fancytree-folder', data.node.li).find('.dataNode').length == 0) {
                                    var dataNode = document.createElement('div');
                                    dataNode.setAttribute('class', 'dataNode');
                                    $('.fancytree-folder', data.node.li).append(dataNode);
                                }
                                else {
                                    //remove existing facets
                                    $('.dataNode', data.node.li).empty();
                                }

                                _.each(selectedFacets[data.node.data.field].values, function (facetValue) {
                                    var label = facetValue.value.label;
                                    var facetFilter = facetValue.value.label;
                                    var facetTitle = facetValue.value.value + ' ('+formatNumber(facetValue.value.count)+')';

                                    var s_label = document.createElement('SPAN');
                                    s_label.setAttribute('class', 'facetFilter-label');
                                    s_label.setAttribute('title', facetTitle );


                                    var f_except =  $('#facet_except').val();
                                    var f_and =  $('#facet_and').val();
                                    var f_close =  $('#facet_remove').val();
                                    var selected_facet_tooltip = (facetValue.negated ? f_and : f_except) + ' : ' + facetTitle;
                                    var remove_facet_tooltip = f_close + ' : ' + facetTitle;


                                    var length = 15;
                                    var facetFilterString = _formatColorText(facetFilter.toString(), length);

                                    _.each($.parseHTML(facetFilterString), function (elem) {
                                        s_label.appendChild(elem);
                                    });

                                    var buttonsSpan = document.createElement('SPAN');
                                    buttonsSpan.setAttribute('class', 'buttons-span');

                                    var s_inverse = document.createElement('A');
                                    s_inverse.setAttribute('class', 'facetFilter-inverse');
                                    s_inverse.setAttribute('title', selected_facet_tooltip);

                                    var s_closer = document.createElement('A');
                                    s_closer.setAttribute('class', 'facetFilter-closer');
                                    s_closer.setAttribute('title', remove_facet_tooltip);

                                    var s_gradient = document.createElement('SPAN');
                                    s_gradient.setAttribute('class', 'facetFilter-gradient');
                                    s_gradient.appendChild(document.createTextNode('\u00A0'));

                                    s_label.appendChild(s_gradient);

                                    var s_facet = document.createElement('SPAN');
                                    var s_class = 'facetFilter' + '_' + (facetValue.negated ? 'EXCEPT' : 'AND');
                                    s_facet.setAttribute('class', s_class);
                                    s_facet.removeAttribute('title');
                                    s_facet.appendChild(s_label);
                                    s_facet.appendChild(buttonsSpan);

                                    buttonsSpan.appendChild(s_inverse);
                                    buttonsSpan.appendChild(s_closer);

                                    $(s_closer).on('click',
                                        function (event) {
                                            event.stopPropagation();
                                            var $facet = $(this).parent().parent();
                                            var facetField = $facet.data('facetField');
                                            var facetLabel = $facet.data('facetLabel');
                                            var facetNegated = $facet.data('facetNegated');
                                            selectedFacets[facetField].values = _.reject(selectedFacets[facetField].values, function (facetValue) {
                                                return (facetValue.value.label == facetLabel && facetValue.negated == facetNegated);
                                            });

                                            appEvents.emit('search.doRefreshState');
                                            return false;
                                        }
                                    );

                                    $(s_inverse).on('click',
                                        function (event) {
                                            event.stopPropagation();
                                            var $facet = $(this).parent().parent();
                                            var facetField = $facet.data('facetField');
                                            var facetLabel = $facet.data('facetLabel');
                                            var facetNegated = $facet.data('facetNegated');
                                            var found = _.find(selectedFacets[facetField].values, function (facetValue) {
                                                return (facetValue.value.label == facetLabel && facetValue.negated == facetNegated);
                                            });
                                            if (found) {
                                                var s_class = "facetFilter" + '_' + (found.negated ? "EXCEPT" : "AND");
                                                $facet.removeClass(s_class);
                                                found.negated = !found.negated;
                                                s_class = "facetFilter" + '_' + (found.negated ? "EXCEPT" : "AND");
                                                $facet.addClass(s_class);

                                                appEvents.emit('search.doRefreshState');

                                            }
                                            return false;
                                        }
                                    );

                                    var newNode = document.createElement('div');
                                    newNode.setAttribute('class', 'newNode');
                                    s_facet = $(newNode.appendChild(s_facet));
                                    s_facet.data('facetField', data.node.data.field);
                                    s_facet.data('facetLabel', label);
                                    s_facet.data('facetNegated', facetValue.negated);

                                    var newNodeSearch = $(newNode).clone();
                                    /*add selected facet tooltip*/
                                   // s_facet.attr('title', facetValue.value.value);

                                    s_facet.hover(function () {
                                        $(buttonsSpan).show();

                                    }, function () {
                                        $(buttonsSpan).hide();
                                    });

                                    $('.fancytree-folder .dataNode', data.node.li).append(newNode);

                                    //  begin generating facets filter under search form
                                    newNodeSearch.css({"float": "left"});

                                    var labelNewNodeSearch = newNodeSearch.find('.facetFilter-label');
                                    labelNewNodeSearch.attr('title', data.node.data.label + ' > ' + facetTitle);

                                    var s_facetInSearch = newNodeSearch.find('span').first();
                                    s_facetInSearch.data('facetField', data.node.data.field);
                                    s_facetInSearch.data('facetLabel', label);
                                    s_facetInSearch.data('facetNegated', facetValue.negated);

                                    $('#facet_filter_in_search').append(newNodeSearch);

                                });
                            }
                        }
                        else {
                            // here we render a facet value
                        }
                    }
                }
            });
        }
        return $facetsTree.fancytree('getTree');
    }

    var setSelectedFacets = function setSelectedFacets(facets) {
        if(!_.isObject(facets) || facets.length === 0) {
            facets = {};
        }
        selectedFacets = facets;
    }

    var getSelectedFacets = function getSelectedFacets(cb) {
        cb(selectedFacets);
    }

    appEvents.listenAll({
        'facets.doLoadFacets': loadFacets,
        'facets.doResetSelectedFacets': resetSelectedFacets,
        'facets.doAddMissingSelectedFacets': facetsAddMissingSelected,
        'facets.setSelectedFacets': setSelectedFacets,
        'facets.getSelectedFacets': getSelectedFacets,
    });

    return {
        loadFacets: loadFacets,
        getSelectedFacets: getSelectedFacets,
        resetSelectedFacets: resetSelectedFacets,
        setSelectedFacets: setSelectedFacets,
    };
};

export default workzoneFacets;
