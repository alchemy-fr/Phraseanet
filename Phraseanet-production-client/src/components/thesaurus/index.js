import $ from 'jquery';
import _ from 'underscore';
import {sprintf} from 'sprintf-js';
import * as AppCommons from './../../phraseanet-common';
import dialog from './../../phraseanet-common/components/dialog';

require('./../../phraseanet-common/components/vendors/contextMenu');

const thesaurusService = services => {
    const { configService, localeService, appEvents } = services;
    let options = {};
    let config = {};
    let sbas;
    let bas2sbas;
    let trees; // @TODO remove global

    let dragging = false;       // true when an object is dragged over the th zone
    let dragTarget = null;      // the target where the mouse is over
    let dragUniqueSbid = null;  // will end-up as : null (nothing dragged) ; false (many sbids) ; sbid (same sbid for all)
    let dragLstRecords = ''     // list or records, format as expected for RecordsRequest::fromRequest
    const url = configService.get('baseUrl');

    let searchSelection = {asArray: [], serialized: ''};


    const initialize = params => {
        let { $container } = params;

        config = configService.get('thesaurusConfig');
        // set up thlist:
        options.thlist = {};
        options.tabs = null;
        for (let db in config.availableDatabases) {
            if (config.availableDatabases.hasOwnProperty(db)) {
                let curDb = config.availableDatabases[db];
                options.thlist['s' + curDb.id] = new ThesauThesaurusSeeker(
                    curDb.id
                );
            }
        }

        startThesaurus();
        // console.log("hello from thesaurus ! container=", $container);
        let cclicks = 0;
        const cDelay = 350;
        let cTimer = null;
        /* unknown usefullness:*/
        /*let bclicks = 0, bDelay = 350, bTimer = null;
         $('body')
         .on('click', '.thesaurus-from-facets-action', (event) => {
         event.preventDefault();
         bclicks++;

         if(bclicks === 1) {
         bTimer = setTimeout(function() {
         thesau_clickThesaurus(event);
         bclicks = 0;
         }, bDelay);

         } else {
         console.log('double click')
         clearTimeout(bTimer);
         thesau_dblclickThesaurus(event);
         bclicks = 0;
         }
         })*/

        $container
            .on('click', '.thesaurus-branch-action', event => {
                let $el = $(event.currentTarget);
                event.preventDefault();
                cclicks++;

                if (cclicks === 1) {
                    cTimer = setTimeout(function () {
                        Xclick(event);
                        cclicks = 0;
                    }, cDelay);
                } else {
                    clearTimeout(cTimer);

                    if ($el.data('context') === 'thesaurus') {
                        TXdblClick(event);
                    } else {
                        CXdblClick(event);
                    }
                    cclicks = 0;
                }
            })
            .on('dblclick', '.thesaurus-branch-action', event => {
                // dbl is handled by click event
                event.preventDefault();
            })
            .on('click', '.thesaurus-cancel-wizard-action', event => {
                // dbl is handled by click event
                event.preventDefault();
                thesauCancelWizard();
            })
            .on('keyup', '.thesaurus-filter-suggest-action', event => {
                event.preventDefault();
                searchValue($(event.currentTarget).val());
            })
            .on('submit', '.thesaurus-filter-submit-action', event => {
                event.preventDefault();
                T_Gfilter(event.currentTarget);
            });

        /**
         * drag/drop on terms : we will not set each term as droppable (costly), but the whole tx zone.
         */
        $('#THPD_T_tree')
            .droppable({
                accept: function(elem) {
                    let lstbr = searchSelection.asArray;

                    dragUniqueSbid = null;
                    lstbr.forEach(sbid_rid => {
                        sbid_rid = sbid_rid.split('_');
                        let sbid = sbid_rid[0];
                        let rid = sbid_rid[1];
                        dragUniqueSbid = (dragUniqueSbid===null) ? sbid : (sbid===dragUniqueSbid ? sbid : false);
                    });
                    dragLstRecords = lstbr.join(';');   // a list as expected for RecordsRequest::fromRequest

                    $(this).removeClass('draggingOver');
                    // console.log("accept", elem);
                    // if ($(elem).hasClass('grouping') && !$(elem).hasClass('SSTT')) {
                    //     return true;
                    // }
                    dragging = false;    // == not yet dragging something over th


                    // the th zone can accet drags only when in front (activated tab)
                    // 'hash' is set by the 'workzone' js code.
                    // return $('#idFrameC .tabs').data('hash') === '#thesaurus_tab';

                    if($('#idFrameC .tabs').data('hash') !== '#thesaurus_tab') {
                        return false;   // can't drop on th if the th tab is not front
                    }

                    // by using classes on both main container AND the (unique) acceptable thesaurus zone
                    // we can have custom drag/drop css for both ok / reject
                    $('#THPD_T_tree', $container).removeClass('draggingOver');      // the container
                    $('#THPD_T_tree>LI', $container).removeClass('draggingOver');   // all thesaurus

                    if(dragUniqueSbid === null || dragUniqueSbid === false) {
                        // many sbids
                        // return false;    // don't return false now, as it will prevent "over" and will not apply css (no "not-allowed" cursor)
                    }

                    return true;
                },
                scope: 'objects',
                hoverClass: 'groupDrop',
                tolerance: 'pointer',
                over: function(event, ui) {
                    const target = typeof(event.toElement) === 'undefined' ?
                        $(event.originalEvent.target)   // ffox
                        :
                        $(event.toElement);             // chrome

                    // console.log("over", event, ui, target);

                    $('#THPD_T_tree', $container).addClass('draggingOver').click(function() {return true;});
                    if(dragUniqueSbid !== null && dragUniqueSbid !== false) {
                        $('#TX_P\\.'+dragUniqueSbid+'\\.T', $container).addClass('draggingOver');
                    }
//                    $('<style></style>').appendTo($container).remove();
                    /*
                    $(this).addClass('draggingOver');

                    if(dragTarget) {
                        // something was already hilighted (should no happen)
                        dragTarget.removeClass('dragOver');
                    }
                    dragging = true;            // == dragging something over th
                    dragTarget = null;
                    // for now, target can only be a term (which has a sbas_id and tx_term_id props)
                    const target = $(event.toElement);
                    const sbas_id = target.data('sbas_id');
                    const tx_term_id = target.data('tx_term_id');
                    if(sbas_id && tx_term_id) {
                        dragTarget = target;
                        dragTarget.addClass('dragOver');
                        console.log("IN : " + dragTarget.attr('id'));
                    }
                    */
                },
                out: function(event, ui) {
                    const target = typeof(event.toElement) === 'undefined' ?
                        $(event.originalEvent.target)   // ffox
                        :
                        $(event.toElement);             // chrome

                    // console.log("out", event, ui, target);

                    $('#THPD_T_tree', $container).removeClass('draggingOver');
                    $('#THPD_T_tree>LI', $container).removeClass('draggingOver');
//                    $('<style></style>').appendTo($container).remove();
                    /*
                    $(this).removeClass('draggingOver');
                    if(dragTarget) {
                        // something was hilighted
                        dragTarget.removeClass('dragOver');
                    }
                    dragging = false;    // == no more dragging something over th
                    dragTarget = null;

                     */
                },
                drop: (event, ui) => {
                    // the event relates from the whole tx zone (<ul>), we must find the exact element of the mouseup
                    // too bad, jquery does not seem to handle that in a cross-browser way.
                    const target = typeof(event.toElement) === 'undefined' ?
                        $(event.originalEvent.target)   // ffox
                        :
                        $(event.toElement);             // chrome

                    // console.log("drop", event, ui, target);

                    $('#THPD_T_tree', $container).removeClass('draggingOver');
                    $('#THPD_T_tree>LI', $container).removeClass('draggingOver');

                    let sbas_id = target.data('sbas_id');         // set on html by ThesaurusXmlHttpController.php
                    let tx_term_id = target.data('tx_term_id');   // set on html by ThesaurusXmlHttpController.php

                    if(sbas_id && tx_term_id) {
                        sbas_id = sbas_id.toString();       // be carefull because data() will cast digits as int
                        tx_term_id = tx_term_id.toString();
                        if(sbas_id === dragUniqueSbid) {
                            dropRecordsOnTerm(sbas_id, tx_term_id, dragLstRecords);
                        }
                    }

                        /*
                        $(this).removeClass('draggingOver');
                        if(dragTarget) {
                            // const tid = $(event.toElement).data('tx_term_id');
                            console.log("DROP ON id=" + dragTarget.attr('id'));
                            dragTarget.removeClass('dragOver');
    //                        appEvents.emit('searchAdvancedForm.activateDatabase', { databases: [sbid] });
                        }
                        dragging = false;    // == no more dragging something over th
                        dragTarget = null;

                         */
                }
            })
        //    .click(function() {return true;})
        ;

        /*
                    // track the mouse
                    .mousemove( (event) => {
                        if(dragging) {
                            const target = $(event.toElement);
                            const sbas_id = target.data('sbas_id');         // set on html by ThesaurusXmlHttpController.php
                            const tx_term_id = target.data('tx_term_id');   // set on html by ThesaurusXmlHttpController.php
                            const oldTarget = dragTarget;
                            dragTarget = (sbas_id && tx_term_id) ? target : null;

                            // const oldTargetId  = oldTarget ? oldTarget.attr('id') : null;
                            // const dragTargetId = dragTarget ? dragTarget.attr('id') : null;
                            // console.log("oldTargetId="+oldTargetId+" ; dragTargetId="+dragTargetId);

                            if(oldTarget && !oldTarget.is(dragTarget)) {
                                // the mouse has quit a overed term (oldTargetId)
                                oldTarget.removeClass('dragOver');
                                console.log("OUT : " + oldTarget.attr('id'));
                            }

                            if(dragTarget && !dragTarget.is(oldTarget)) {
                                // the mouse just overs a new term
                                dragTarget.addClass('dragOver');
                                console.log("IN : " + dragTarget.attr('id'));
                            }
                        }

                    });
        */

        searchValue = _.debounce(searchValue, 300);
    };


    function dropRecordsOnTerm(sbas_id, tx_term_id, lstRecords) {
        let dlg = dialog.create(
            services,
            {
                size: 'Custom',
                customWidth: 770,
                customHeight: 400,
                // title: localeService.t('add data'),
                loading: true
            },
            0
        );

        let data = {        // declaring data structure avoids phpstorm warnings
            dlg_title:   undefined,
            dlg_content: undefined,
            rec_refs:    undefined,
            commit_url:  undefined,
        };
        $.getJSON(
            `${url}prod/thesaurus/droprecords`,
            {
                'dlg_level': 0,
                'sbas_id': sbas_id,
                'tx_term_id': tx_term_id,
                'lst': lstRecords
            },
            function (dlgData) {

                let $container = dlg.getDomElement().closest('.ui-dialog'); // the whole dlg, including title & buttons
                $container.addClass('black-dialog-wrap');

                dlg.setOption("title", dlgData.dlg_title);
                dlg.setContent(dlgData.dlg_content);

                /**
                 * update the dlg (show/hide selects & buttons) depending on form status
                 */
                let updateUx = function () {
                    // console.log("====== update =========================");

                    let okbutton = false;   // must we show the ok button ?

                    /**
                     * loop on advanced-mode fields
                     */
                    $('#TXCLASSIFICATION_ADVANCED .action', $container).each(function () {
                        let $this    = $(this);
                        let n        = $this.data('n');

                        switch($this.val()) {   // action
                            case "":                        // first "select..." option
                                $('.value_container._'+n, $container).hide();
                                break;
                            case "clear":                   // clear a mono-value : no need value selection
                                $('.value_container._'+n, $container).hide();
                                okbutton = true;
                                break;
                            default:
                                $('.value_container._'+n, $container).show();
                                okbutton = true;
                        }

                    });

                    /**
                     * if the "simple-mode" is front, show "ok" button
                     */
                    let seltab_idx = $('.tabs', $container).tabs('option', 'active');
                    let seltab_id  = $('.tabs>UL.ui-tabs-nav>LI:eq('+seltab_idx+')', $container).data('tab_id');    // "SIMPLE" or "ADVANCED"
                    if(seltab_id === "SIMPLE") {
                        // simple ux:  ok is possible
                        okbutton = true;
                    }

                    $(' .okbutton', $container).toggle(okbutton);
                }

                /**
                 * add buttons
                 */
                dlg.setOption("buttons",
                    [
                        /**
                         * OK button
                         */
                        {
                            text:  "Ok",
                            class: "fieldSelected okbutton",
                            style: "display:none",
                            click: function () {
                                // don't submit the complex form, better build json
                                let actions = [];

                                /**
                                 * find the active tab ("SIMPLE" or "ADVANCED")
                                 */
                                let seltab_idx = $('.tabs', $container).tabs('option', 'active');
                                let seltab_id  = $('.tabs>UL.ui-tabs-nav>LI:eq('+seltab_idx+')', $container).data('tab_id');     // "SIMPLE" or "ADVANCED"

                                /**
                                 * extract data only from the front tab (div)
                                 */
                                let box = $("#TXCLASSIFICATION_"+seltab_id, $container);
                                $('.action', box).each(
                                    function() {
                                        let $this    = $(this);
                                        let n        = $this.data('n');
                                        let action   = $this.val();
                                        if(action !== "") {
                                            let field = $('.field._' + n, box).val();
                                            let value = $('.value._' + n, box).val();

                                            switch(action) {
                                                case "replace": // replace all multi-values
                                                    actions.push({
                                                        'field_name':   field,
                                                        'action':       "replace",
                                                        'replace_with': value
                                                    });
                                                    break;
                                                case "clear":   // clear a mono-value
                                                    actions.push({
                                                        'field_name':   field,
                                                        'action':       "delete"
                                                    });
                                                    break;
                                                default:        // all other actions don't need patch
                                                    actions.push({
                                                        'field_name': field,
                                                        'action':     action,
                                                        'value':      value
                                                    });
                                            }

                                        }
                                    });

                                /**
                                 * post actions
                                 */
                                data = {
                                    'records': dlgData.rec_refs,
                                    'actions': {
                                        'metadatas': actions
                                    }
                                };

                                // console.log(data);

                                $.ajax({
                                        url:         dlgData.commit_url,
                                        type:        "POST",
                                        contentType: "application/json",
                                        data:        JSON.stringify(data),
                                        success:     function () {
                                            dlg.close();
                                        }
                                    },
                                );

                                return false;
                            }
                        },

                        /**
                         * Cancel button
                         */
                        {
                            text:  "Cancel",
                            click: function () {
                                $(this).dialog("close");
                            }
                        }
                    ]
                );

                /**
                 * format the dlg content
                 */
                $('SELECT', $container).menu();
                $('.tabs', $container).tabs({'activate':updateUx});
                $('.action', $container).change(updateUx)

                updateUx();  // enforce initial update;

            }

        ).fail(function( jqxhr, textStatus, error ) {
            // the dlg content failed, report onto the dlg (better than forever loading)
            let err = textStatus + ", " + error;
            dlg.setContent( "Request Failed: " + err );
        });
    }






    function show() {
        // first show of thesaurus
        if (options.currentWizard === '???') {
            thesauShowWizard('wiz_0', false);
        }
    }

    function thesauCancelWizard() {
        thesauShowWizard('wiz_0', true);
    }

    function thesauShowWizard(wizard, refreshFilter) {
        var offsetTabHeight = $('#THPD_tabs .ui-tabs-nav')[0].offsetHeight;
        if (wizard !== options.currentWizard) {
            $('#THPD_WIZARDS DIV.wizard', options.tabs).hide();
            $('#THPD_WIZARDS .' + wizard, options.tabs).show();
            $('#THPD_T', options.tabs).css(
                'top',
                $('#THPD_WIZARDS', options.tabs).height() + offsetTabHeight
            );
            $('#THPD_C', options.tabs).css(
                'top',
                $('#THPD_WIZARDS', options.tabs).height() + offsetTabHeight
            );

            options.currentWizard = wizard;

            if (refreshFilter) {
                searchValue(
                    $('#THPD_WIZARDS .gform', options.tabs).eq(0).val()
                );
            }
            // browse
            if (wizard === 'wiz_0') {
                $('#THPD_WIZARDS .th_cancel', options.tabs).hide();
            } else {
                $('#THPD_WIZARDS .th_cancel', options.tabs).show();
            }
            // accept
            if (wizard === 'wiz_1') {
                $('#THPD_WIZARDS .th_ok', options.tabs).hide();
            } else {
                $('#THPD_WIZARDS .th_ok', options.tabs).show();
            }

            $('#THPD_WIZARDS FORM :text')[0].focus();
        }
    }

    // here when the 'filter' forms is submited with key <enter> or button <ok>
    // force immediate search
    function T_Gfilter(o) {
        var f;
        if (o.nodeName === 'FORM') {
            f = $(o).find('input[name=search_value]').val();
        } else if (o.nodeName === 'INPUT') {
            f = $(o).val();
        }

        searchValue(f);

        switch (options.currentWizard) {
            case 'wiz_0': // browse
                break;
            case 'wiz_1': // accept
                break;
            case 'wiz_2': // replace
                T_replaceBy2(f);
                break;
            default:
                break;
        }
    }

    // here when a key is pressed in the 'filter' form
    let searchValue = f => {
        switch (options.currentWizard) {
            case 'wiz_0': // browse
                searchValueByMode(f, 'ALL');
                break;
            case 'wiz_1': // accept
                searchValueByMode(f, 'CANDIDATE');
                break;
            case 'wiz_2': // replace
                searchValueByMode(f, 'CANDIDATE');
                break;
            default:
                break;
        }
    };

    function T_replaceBy2(f) {
        if (trees.C._selInfos.n !== 1) {
            return;
        }
        let term = trees.C._selInfos.sel.eq(0).find('span span').html();
        let cid = trees.C._selInfos.sel[0].getAttribute('id').split('.');
        cid.shift();
        let sbas = cid.shift();
        cid = cid.join('.');

        trees.C._toReplace = { sbas: sbas, cid: cid, replaceby: f };

        let msg = sprintf(config.replaceMessage, { from: term, to: f });

        let confirmBox = dialog.create(services, {
            size: 'Alert',
            closeOnEscape: true,
            cancelButton: true,
            buttons: {
                Ok: function () {
                    confirmBox.close();
                    T_replaceCandidates_OK();
                }
            }
        });
        confirmBox.setContent(msg);
    }

    function searchValueByMode(f, mode) {
        if (mode === 'ALL') {
            let type;
            let id;
            let z = '';
            if (
                $('.ui-tabs-nav li.ui-state-active a', options.tabs).attr(
                    'href'
                ) === '#THPD_T'
            ) {
                //thesaurus
                type = 'TH';
                id = 'T';
            } else {
                //candidate
                type = 'CT';
                id = 'C';
            }
            // search in every base, everywhere
            for (let i in sbas) {
                let zurl =
                    '/xmlhttp/search_term_prod.j.php' +
                    '?sbid=' +
                    sbas[i].sbid +
                    '&typ=' +
                    type +
                    '&id=' +
                    id +
                    '&t=' +
                    encodeURIComponent(f);
                $('#THPD_T_treeBox').addClass('loading');
                sbas[i].seeker = $.ajax({
                    url: zurl,
                    type: 'POST',
                    data: [],
                    dataType: 'json',
                    success: function (j) {
                        var z = '#TX_P\\.' + j.parm.sbid + '\\.T';
                        if (type === 'TH') {
                            z = '#TX_P\\.' + j.parm.sbid + '\\.' + id;
                        } else {
                            z = '#CX_P\\.' + j.parm.sbid + '\\.' + id;
                        }

                        var o = $(z);
                        var isLast = o.hasClass('last');

                        o.replaceWith(j.html);

                        if (isLast) {
                            $(z).addClass('last');
                        }
                    },
                    complete: function () {
                        $('#THPD_T_treeBox').removeClass('loading');
                    }
                });
            }
        } else if (mode === 'CANDIDATE') {
            // search only on the good base and the good branch(es)
            for (let i in sbas) {
                var zurl =
                    '/xmlhttp/search_term_prod.j.php?sbid=' +
                    sbas[i].sbid +
                    '&typ=TH' +
                    '&id=T';

                $('#THPD_T_treeBox').addClass('loading');
                if (sbas[i].sbid === trees.C._selInfos.sbas) {
                    zurl +=
                        '&t=' +
                        encodeURIComponent(f) +
                        '&field=' +
                        encodeURIComponent(trees.C._selInfos.field);
                }
                sbas[i].seeker = $.ajax({
                    url: zurl,
                    type: 'POST',
                    data: [],
                    dataType: 'json',
                    success: function (j) {
                        var z = '#TX_P\\.' + j.parm.sbid + '\\.T';

                        var o = $(z);
                        var isLast = o.hasClass('last');

                        o.replaceWith(j.html);

                        if (isLast) {
                            $(z).addClass('last');
                        }
                    },
                    complete: function () {
                        $('#THPD_T_treeBox').removeClass('loading');
                    }
                });
            }
        }
    }

    // ======================================================================================================

    function T_replaceCandidates_OK() {
        var replacingBox = dialog.create(services, {
            size: 'Alert'
        });
        replacingBox.setContent(config.replaceInProgressMsg);

        var parms = {
            url: '/xmlhttp/replacecandidate.j.php',
            data: {
                'id[]': trees.C._toReplace.sbas + '.' + trees.C._toReplace.cid,
                t: trees.C._toReplace.replaceby,
                debug: '0'
            },
            async: false,
            cache: false,
            dataType: 'json',
            timeout: 10 * 60 * 1000, // 10 minutes !
            success: function (result, textStatus) {
                trees.C._toReplace = null;
                thesauShowWizard('wiz_0', false);

                replacingBox.close();

                if (result.msg !== '') {
                    var alert = dialog.create(services, {
                        size: 'Alert',
                        closeOnEscape: true,
                        closeButton: true
                    });
                    alert.setContent(result.msg);
                }

                for (let i in result.ctermsDeleted) {
                    var cid =
                        '#CX_P\\.' +
                        result.ctermsDeleted[i].replace(
                            new RegExp('\\.', 'g'),
                            '\\.'
                        ); // escape les '.' pour jquery
                    $(cid).remove();
                }
            },
            _ret: null // private alchemy
        };

        $.ajax(parms);
    }

    function T_acceptCandidates_OK() {
        let same_sbas = true;
        let acceptingBox = dialog.create(services, {
            size: 'Alert'
        });
        acceptingBox.setContent(config.acceptMsg);

        let t_ids = [];
        let dst = trees.C._toAccept.dst.split('.');
        dst.shift();
        let sbid = dst.shift();
        dst = dst.join('.');
        // obviously the candidates and the target already complies (same sbas, good tbranch)
        trees.C._selInfos.sel.each(function () {
            var x = this.getAttribute('id').split('.');
            x.shift();
            if (x.shift() !== sbid) {
                same_sbas = false;
            }
            t_ids.push(x.join('.'));
        });

        if (!same_sbas) {
            return;
        }

        var parms = {
            url: '/xmlhttp/acceptcandidates.j.php',
            data: {
                // "debug": false,
                sbid: sbid,
                tid: dst,
                'cid[]': t_ids,
                typ: trees.C._toAccept.type,
                piv: trees.C._toAccept.lng
            },
            async: false,
            cache: false,
            dataType: 'json',
            success: function (result, textStatus) {
                for (let i in result.refresh) {
                    var zurl =
                        '/xmlhttp/openbranch_prod.j.php' +
                        '?type=' +
                        result.refresh[i].type +
                        '&sbid=' +
                        result.refresh[i].sbid +
                        '&sortsy=1' +
                        '&id=' +
                        encodeURIComponent(result.refresh[i].id);

                    $.get(
                        zurl,
                        [],
                        function (j) {
                            var z =
                                '#' +
                                j.parm.type +
                                'X_P\\.' +
                                j.parm.sbid +
                                '\\.' +
                                j.parm.id.replace(
                                    new RegExp('\\.', 'g'),
                                    '\\.'
                                ); // escape les '.' pour jquery

                            $(z).children('ul').eq(0).replaceWith(j.html);
                        },
                        'json'
                    );
                }
                trees.C._toAccept = null;
                thesauShowWizard('wiz_0', false);
                acceptingBox.close();
            },
            error: function () {
                acceptingBox.close();
            },
            timeout: function () {
                acceptingBox.close();
            },
            _ret: null // private alchemy
        };

        $.ajax(parms);
    }

    function C_deleteCandidates_OK() {
        var deletingBox = dialog.create(services, {
            size: 'Alert'
        });
        deletingBox.setContent(config.deleteMsg);

        var t_ids = [];
        var lisel = trees.C.tree.find('LI .selected');
        trees.C.tree.find('LI .selected').each(function () {
            var x = this.getAttribute('id').split('.');
            x.shift();
            t_ids.push(x.join('.'));
        });
        var parms = {
            url: '/xmlhttp/replacecandidate.j.php',
            data: { 'id[]': t_ids },
            async: false,
            cache: false,
            dataType: 'json',
            timeout: 10 * 60 * 1000, // 10 minutes !
            success: function (result, textStatus) {
                deletingBox.close();

                if (result.msg !== '') {
                    var alert = dialog.create(services, {
                        size: 'Alert',
                        closeOnEscape: true,
                        closeButton: true
                    });
                    alert.setContent(result.msg);
                }

                for (let i in result.ctermsDeleted) {
                    var cid =
                        '#CX_P\\.' +
                        result.ctermsDeleted[i].replace(
                            new RegExp('\\.', 'g'),
                            '\\.'
                        ); // escape les '.' pour jquery
                    $(cid).remove();
                }
            },
            _ret: null
        };

        $.ajax(parms);
    }

    // menu option T:accept as...
    function T_acceptCandidates(menuItem, menu, type) {
        var lidst = trees.T.tree.find('LI .selected');
        if (lidst.length !== 1) {
            return;
        }

        var lisel = trees.C.tree.find('LI .selected');
        if (lisel.length === 0) {
            return;
        }

        var msg;

        if (lisel.length === 1) {
            var term = lisel.eq(0).find('span span').html();
            msg = sprintf(config.candidateUniqueMsg, term);
        } else {
            msg = sprintf(config.candidateManyMsg, lisel.length);
        }

        trees.C._toAccept.type = type;
        trees.C._toAccept.dst = lidst.eq(0).attr('id');

        var confirmBox = dialog.create(services, {
            size: 'Alert',
            closeOnEscape: true,
            cancelButton: true,
            buttons: {
                Ok: function () {
                    confirmBox.close();
                    T_acceptCandidates_OK();
                }
            }
        });
        confirmBox.setContent(msg);
    }

    // menu option T:search
    function T_search(menuItem, menu, cmenu, e, label) {
        if (!menu._li) {
            return;
        }
        var tcids = menu._li.attr('id').split('.');
        tcids.shift();
        var sbid = tcids.shift();
        var term = menu._li.find('span span').html();

        doThesSearch('T', sbid, term, null);
    }

    function C_MenuOption(menuItem, menu, option, parm) {
        // nothing selected in candidates ?
        if (!trees.C._selInfos) {
            return;
        }

        trees.C._toAccept = null; // cancel previous 'accept' action anyway
        trees.C._toReplace = null; // cancel previous 'replace' action anyway
        // display helpful message into the thesaurus box...
        let msg;
        let term;
        switch (option) {
            case 'ACCEPT':
                // glue selection to the tree
                trees.C._toAccept = { lng: parm.lng };

                if (trees.C._selInfos.n === 1) {
                    msg = sprintf(
                        config.acceptCandidateUniqueMsg,
                        menu._srcElement.find('span').html()
                    );
                } else {
                    msg = sprintf(
                        config.acceptCandidateManyMsg,
                        trees.C._selInfos.n
                    );
                }

                // set the content of the wizard
                $('#THPD_WIZARDS .wiz_1 .txt').html(msg);
                // ... and switch to the thesaurus tab
                options.tabs.tabs('option', 'active', 0);
                thesauShowWizard('wiz_1', true);

                break;

            case 'REPLACE':
                if (trees.C._selInfos.n === 1) {
                    term = trees.C._selInfos.sel.eq(0).find('span span').html();
                    msg = sprintf(config.replaceCandidateUniqueMsg, term);
                } else {
                    msg = sprintf(
                        config.replaceCandidateManyMsg,
                        trees.C._selInfos.n
                    );
                }

                options.tabs.tabs('option', 'active', 0);

                // set the content of the wizard
                $('#THPD_WIZARDS .wiz_2 .txt').html(msg);
                // ... and switch to the thesaurus tab
                thesauShowWizard('wiz_2', true);

                break;

            case 'DELETE':
                $('#THPD_WIZARDS DIV', options.tabs).hide();

                if (trees.C._selInfos.n === 1) {
                    term = trees.C._selInfos.sel.eq(0).find('span span').html();
                    msg = sprintf(config.deleteCandidateUniqueMsg, term);
                } else {
                    msg = sprintf(
                        config.deleteCandidateManyMsg,
                        trees.C._selInfos.n
                    );
                }

                let confirmBox = dialog.create(services, {
                    size: 'Alert',
                    closeOnEscape: true,
                    cancelButton: true,
                    buttons: {
                        Ok: function () {
                            confirmBox.close();
                            C_deleteCandidates_OK();
                        }
                    }
                });
                confirmBox.setContent(msg);

                break;
            default:
        }
    }

    function Xclick(e) {
        let x = e.srcElement ? e.srcElement : e.target;
        let li = $(x).closest('li');
        let tids = li.attr('id').split('.');
        let type;
        switch (x.nodeName) {
            case 'DIV': // +/-
                var tid = tids.shift();
                var sbid = tids.shift();
                type = tid.substr(0, 1);
                // TX_P ou CX_P
                if (
                    (type === 'T' || type === 'C') &&
                    tid.substr(1, 4) === 'X_P'
                ) {
                    var ul = li.children('ul').eq(0);
                    if (
                        ul.css('display') === 'none' ||
                        AppCommons.utilsModule.is_ctrl_key(e)
                    ) {
                        if (AppCommons.utilsModule.is_ctrl_key(e)) {
                            ul.text(config.loadingMsg);
                            li.removeAttr('loaded');
                        }

                        ul.show();

                        if (!li.attr('loaded')) {
                            var zurl =
                                '/xmlhttp/openbranch_prod.j.php?type=' +
                                type +
                                '&sbid=' +
                                sbid +
                                '&id=' +
                                encodeURIComponent(tids.join('.'));
                            if (li.hasClass('last')) {
                                zurl += '&last=1';
                            }
                            zurl += '&sortsy=1';

                            $.get(
                                zurl,
                                [],
                                function (j) {
                                    ul.replaceWith(j.html);
                                    li.attr('loaded', '1');
                                },
                                'json'
                            );
                        }
                    } else {
                        ul.hide();
                    }
                }
                break;
            case 'SPAN':
                type = tids[0].substr(0, 1);
                if ((type === 'T' && tids.length > 2) || tids.length === 4) {
                    tids.pop();
                    var tid3 = tids.join('.');
                    if (
                        !AppCommons.utilsModule.is_ctrl_key(e) &&
                        !AppCommons.utilsModule.is_shift_key(e)
                    ) {
                        $('LI', trees[type].tree).removeClass('selected');
                        options.lastClickedCandidate = null;
                    } else {
                        // if($("#THPD_C_treeBox")._lastClicked)
                        if (options.lastClickedCandidate !== null) {
                            if (options.lastClickedCandidate.tid3 !== tid3) {
                                $('LI', trees[type].tree).removeClass(
                                    'selected'
                                );
                                options.lastClickedCandidate = null;
                            } else {
                                if (e.shiftKey) {
                                    var lip = li.parent().children('li');
                                    var idx0 = lip.index(
                                        options.lastClickedCandidate.item
                                    );
                                    var idx1 = lip.index(li);
                                    if (idx0 < idx1) {
                                        lip
                                            .filter(function (index) {
                                                return (
                                                    index >= idx0 &&
                                                    index < idx1
                                                );
                                            })
                                            .addClass('selected');
                                    } else {
                                        lip
                                            .filter(function (index) {
                                                return (
                                                    index > idx1 &&
                                                    index <= idx0
                                                );
                                            })
                                            .addClass('selected');
                                    }
                                }
                            }
                        }
                    }
                    li.toggleClass('selected');
                    if (type === 'C') {
                        options.lastClickedCandidate = { item: li, tid3: tid3 };
                    }
                }
                break;
            default:
                break;
        }
    }

    function TXdblClick(e) {
        let x = e.srcElement ? e.srcElement : e.target;
        let tid = $(x).closest('li').attr('id');
        let term;
        switch (x.nodeName) {
            case 'SPAN': // term
                switch (options.currentWizard) {
                    case 'wiz_0': // simply browse
                        if (tid.substr(0, 5) === 'TX_P.') {
                            var tids = tid.split('.');
                            if (tids.length > 3) {
                                var sbid = tids[1];
                                term = $(x).hasClass('separator')
                                    ? $(x).prev().text()
                                    : $(x).text();
                                doThesSearch('T', sbid, term, null);
                            }
                        }
                        break;
                    case 'wiz_2': // replace by
                        if (tid.substr(0, 5) === 'TX_P.') {
                            term = $(x).text();
                            $('#THPD_WIZARDS .wiz_2 :text').val(term);
                            T_replaceBy2(term);
                        }
                        break;
                    default:
                }
                break;
            default:
                break;
        }
    }

    function CXdblClick(e) {
        var x = e.srcElement ? e.srcElement : e.target;
        switch (x.nodeName) {
            case 'SPAN': // term
                var li = $(x).closest('li');
                var field = li.closest('[field]').attr('field');
                if (typeof field !== 'undefined') {
                    var tid = li.attr('id');
                    if (tid.substr(0, 5) === 'CX_P.') {
                        var sbid = tid.split('.')[1];
                        var term = $(x).text();
                        doThesSearch('C', sbid, term, field);
                    }
                }
                break;
            default:
                break;
        }
    }

    function doThesSearch(type, sbid, term, field) {
        appEvents.emit('searchAdvancedForm.activateDatabase', { databases: [sbid] });

        let queryString = '';
        if (type === 'T') {
            queryString = '[' + term + ']';
        } else {
            queryString = field + '="' + term + '"';
        }
        appEvents.emit('facets.doResetSelectedFacets');
        $('#EDIT_query').val(queryString);
        appEvents.emit('searchAdvancedForm.checkFilters');
        appEvents.emit('search.doNewSearch', queryString);
        //searchModule.newSearch(v);
    }

    /* unknown usefullness:
     function thesau_clickThesaurus(event)	// onclick dans le thesaurus
     {
     // on cherche ou on a clique
     for(var e=event.srcElement ? event.srcElement : event.target; e && ((!e.tagName) || (!e.id)); e=e.parentNode)
     ;
     if(e)
     {
     switch(e.id.substr(0,4))
     {
     case "TH_P":	// +/- de deploiement de mot
     js = "thesau_thesaurus_ow('"+e.id.substr(5)+"')";
     self.setTimeout(js, 10);
     break;
     }
     }
     return(false);
     }

     function thesau_dblclickThesaurus(event)	// onclick dans le thesaurus
     {
     var err;
     try
     {
     options.lastTextfocus.focus();
     }
     catch(err)
     {
     return;
     }

     // on cherche ou on a clique
     for(var e=event.srcElement; e && ((!e.tagName) || (!e.id)); e=e.parentNode)
     ;
     if(e)
     {
     switch(e.id.substr(0,4))
     {
     case "GL_W":	// double click sur le mot
     var t = e.id.split(".");
     t.shift();
     var sbid = t.shift();
     var thid = t.join(".");
     var url = "/xmlhttp/getsy_prod.x.php";
     var parms  = "bid=" + sbid + "&id=" + thid;

     var xmlhttp = new XMLHttpRequest();
     xmlhttp.open("POST", url, false);
     xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
     xmlhttp.send(parms);
     var ret = xmlhttp.responseXML;

     result = ret.getElementsByTagName("result");
     if(result.length==1)
     {
     val = result.item(0).getAttribute("t");
     replaceEditSel(val);
     }
     break;
     }
     }
     return(false);
     }
     function replaceEditSel(value)
     {
     if(!options.lastTextfocus || !options.lastTextfocus.selectedTerm)
     return;

     options.lastTextfocus.value = options.lastTextfocus.value.substr(0, options.lastTextfocus.selectedTerm.start) + value + options.lastTextfocus.value.substr(options.lastTextfocus.selectedTerm.end);
     if(typeof(document.selection) != 'undefined')
     {
     // explorer
     var range = options.lastTextfocus.createTextRange();
     range.move('character', options.lastTextfocus.selectedTerm.start + value.length);
     range.select();
     }
     else if(typeof(options.lastTextfocus.selectionStart) != 'undefined')
     {
     // gecko (safari)
     options.lastTextfocus.selectionStart = options.lastTextfocus.selectionEnd = options.lastTextfocus.selectedTerm.start + value.length;
     }
     cbEditing2(options.lastTextfocus, "MOUSEUP");	// force le calcul de la nouvelle selection
     options.lastTextfocus.focus();
     return;
     }


     function thesau_thesaurus_ow(id)	// on ouvre ou ferme une branche de thesaurus
     {
     var o = document.getElementById("TH_K."+id);
     if(o.className=="o")
     {
     // on ferme
     o.className = "c";
     document.getElementById("TH_P."+id).innerHTML = "+";
     document.getElementById("TH_K."+id).innerHTML = config.loadingMsg;
     }
     else if(o.className=="c" || o.className=="h")
     {
     // on ouvre
     o.className = "o";
     document.getElementById("TH_P."+id).innerHTML = "-";

     var t_id = id.split(".");
     var sbas_id = t_id[0];
     t_id.shift();
     var thid = t_id.join(".");
     var url = "/xmlhttp/getterm_prod.x.php";
     var parms  = "bid=" + sbas_id;
     parms += "&lng="+p4.lng;
     parms += "&sortsy=1";
     parms += "&id=" + thid;
     parms += "&typ=TH";

     options.thlist['s'+sbas_id].openBranch(id, thid);
     }
     return(false);
     }

     function cbEditing2(textarea, act)
     {
     var sbas_id = p4.edit.sbas_id;
     tmpCurField = 0;

     if(textarea.id=="idZTextArea")
     {
     tmpCurField = p4.edit.curField ;
     }
     else
     {
     if(textarea.id=="idZTextAreaReg")
     tmpCurField = p4.edit.curFieldReg;
     }

     options.lastTextfocus = textarea;
     textarea.selectedTerm = null;
     var p0 = -1;
     var p1 = -1;
     if(typeof(document.selection) != 'undefined')
     {
     // ici si explorer
     var range = document.selection.createRange();
     var i;
     var oldrange = range.duplicate();
     for(i=0; i<200; i++, p0++)
     {
     pe = range.parentElement();
     if(pe != textarea)
     break;
     range.moveStart("character", -1);
     }
     range = oldrange.duplicate();
     for(i=0; i<200; i++, p1++)
     {
     pe = range.parentElement();
     if(pe != textarea)
     break;
     range.moveEnd("character", -1);
     }
     }
     else if(typeof(textarea.selectionStart) != "undefined")
     {
     // ici si gecko (safari)
     p0 = textarea.selectionStart;
     p1 = textarea.selectionEnd;
     }
     if(p0 != -1 && p1 != -1)
     {
     var c;
     // on etend les positions a tout le keyword (entre ';')
     t = textarea.value;
     l = t.length;
     for( ; p0 > 0; p0--)
     {
     c = t.charCodeAt(p0-1);
     if(c==59 || c==10 || c==13)	// 59==";"
     break;
     }
     for( ; p1 < l; p1++)
     {
     c = t.charCodeAt(p1);
     if(c==59 || c==10 || c==13)
     break;
     }
     // on copie le resultat dans le textarea
     textarea.selectedTerm = { start:p0, end:p1 };

     // on cherche le terme dans le thesaurus
     var zText = textarea.value.substr(p0, p1-p0);

     if(document.forms["formSearchTH"].formSearchTHck.checked)
     {
     if(zText && zText.length>2 && document.forms["formSearchTH"].formSearchTHfld.value != zText)
     {
     document.forms["formSearchTH"].formSearchTHfld.value = zText;

     document.getElementById("TH_searching").src = "/assets/common/images/icons/ftp-loader.gif";
     options.thlist['s'+sbas_id].search(zText);
     }
     }
     }
     return(true);
     }
     */

    function ThesauThesaurusSeeker(sbas_id) {
        this.sbas_id = sbas_id;
        this._ctimer = null;
        this._xmlhttp = null;
        this.tObj = { TH_searching: null, TH_P: null, TH_K: null };
        this.search = function (txt) {
            if (this._ctimer) {
                clearTimeout(this._ctimer);
            }
            this._ctimer = setTimeout(() => {
                return options.thlist['s' + this.sbas_id].search_delayed(
                    '"' + txt.replace("'", "\\'") + '"'
                );
            }, 100);
        };
        this.search_delayed = function (txt) {
            var me = this;
            if (
                this._xmlttp.abort &&
                typeof this._xmlttp.abort === 'function'
            ) {
                this._xmlhttp.abort();
            }
            var url = '/xmlhttp/openbranches_prod.x.php';
            var parms = {
                bid: this.sbas_id,
                t: txt,
                mod: 'TREE'
            };

            this._xmlhttp = $.ajax({
                url: url,
                type: 'POST',
                data: parms,
                success: function (ret) {
                    me.xmlhttpstatechanged(ret);
                },
                error: function () {},
                timeout: function () {}
            });

            this._ctimer = null;
        };
        this.openBranch = function (id, thid) {
            var me = this;
            if (
                this._xmlttp.abort &&
                typeof this._xmlttp.abort === 'function'
            ) {
                this._xmlhttp.abort();
            }
            var url = '/xmlhttp/getterm_prod.x.php';
            var parms = {
                bid: this.sbas_id,
                sortsy: 1,
                id: thid,
                typ: 'TH'
            };

            this._xmlhttp = $.ajax({
                url: url,
                type: 'POST',
                data: parms,
                success: function (ret) {
                    me.xmlhttpstatechanged(ret, id);
                },
                error: function () {},
                timeout: function () {}
            });
        };
        this.xmlhttpstatechanged = function (ret, id) {
            try {
                if (!this.tObj.TH_searching) {
                    this.tObj.TH_searching = document.getElementById(
                        'TH_searching'
                    );
                }
                this.tObj.TH_searching.src =
                    '/assets/common/images/icons/ftp-loader-blank.gif';
                // && (typeof(ret.parsed)=="undefined" || ret.parsed))
                if (ret) {
                    let htmlnodes = ret.getElementsByTagName('html');
                    let htmlnode = htmlnodes.item(0).firstChild;
                    if (htmlnodes && htmlnodes.length === 1 && htmlnode) {
                        if (typeof id === 'undefined') {
                            // called from search or 'auto' : full thesaurus search
                            if (!this.tObj.TH_P) {
                                this.tObj.TH_P = document.getElementById(
                                    'TH_P.' + this.sbas_id + '.T'
                                );
                            }
                            if (!this.tObj.TH_K) {
                                this.tObj.TH_K = document.getElementById(
                                    'TH_K.' + this.sbas_id + '.T'
                                );
                            }
                            this.tObj.TH_P.innerHTML = '...';
                            this.tObj.TH_K.className = 'h';
                            this.tObj.TH_K.innerHTML = htmlnode.nodeValue;
                        } else {
                            // called from 'openBranch'
                            //			var js = "document.getElementById('TH_K."+thid+"').innerHTML = \""+htmlnode.nodeValue+"\"";
                            //			self.setTimeout(js, 10);
                            document.getElementById('TH_K.' + id).innerHTML =
                                htmlnode.nodeValue;
                        }
                    }
                }
            } catch (err) {}
        };
    }

    function startThesaurus() {
        options.thlist = config.thlist;
        options.currentWizard = '???';

        sbas = config.sbas;
        bas2sbas = config.bas2sbas;

        options.lastTextfocus = null;

        options.lastClickedCandidate = null;

        options.tabs = $('#THPD_tabs');
        options.tabs.tabs();

        trees = {
            T: {
                tree: $('#THPD_T_tree', options.tabs)
            },
            C: {
                tree: $('#THPD_C_tree', options.tabs),
                // may contain : {'type', 'dst', 'lng'}
                _toAccept: null,
                _toReplace: null,
                // may contain : {'sel':lisel, 'field':field, 'sbas':sbas, 'n':lisel.length}
                _selInfos: null
            }
        };

        trees.T.tree.contextMenu(
            [
                {
                    label: config.searchMsg,
                    onclick: function (menuItem, menu, cmenu, e, label) {
                        T_search(menuItem, menu, cmenu, e, label);
                    }
                },
                {
                    label: config.acceptSpecificTermMsg,
                    onclick: function (menuItem, menu) {
                        T_acceptCandidates(menuItem, menu, 'TS');
                    }
                },
                {
                    label: config.acceptSynonymeMsg,
                    onclick: function (menuItem, menu) {
                        T_acceptCandidates(menuItem, menu, 'SY');
                    }
                }
            ],
            {
                className: 'THPD_TMenu',
                beforeShow: function () {
                    var menuOptions = $(this.menu).find('.context-menu-item');
                    menuOptions.eq(1).addClass('context-menu-item-disabled');
                    menuOptions.eq(2).addClass('context-menu-item-disabled');

                    var x = this._showEvent.srcElement
                        ? this._showEvent.srcElement
                        : this._showEvent.target;
                    var li = $(x).closest('li');
                    this._li = null;
                    var tcids = li.attr('id').split('.');
                    if (
                        tcids.length > 2 &&
                        tcids[0] === 'TX_P' &&
                        tcids[2] !== 'T' &&
                        x.nodeName !== 'LI'
                    ) {
                        this._li = li;
                        tcids.shift();
                        var sbas = tcids.shift();

                        // this._srcElement = li;		// private alchemy
                        if (!li.hasClass('selected')) {
                            // rclick OUTSIDE the selection : unselect all
                            trees.T.tree.find('LI').removeClass('selected');

                            $('li', trees.T.tree).removeClass('selected');
                            li.addClass('selected');
                        }

                        if (
                            trees.C._selInfos &&
                            trees.C._selInfos.sbas === sbas
                        ) {
                            // whe check if the candidates can be validated here
                            // aka does the tbranch of the field (of candidates) reaches the paste location ?
                            var parms = {
                                url:
                                    '/xmlhttp/checkcandidatetarget.j.php' +
                                    '?sbid=' +
                                    sbas +
                                    '&acf=' +
                                    encodeURIComponent(
                                        trees.C._selInfos.field
                                    ) +
                                    '&id=' +
                                    encodeURIComponent(tcids.join('.')),
                                data: [],
                                async: false,
                                cache: false,
                                dataType: 'json',
                                timeout: 1000,
                                success: function (result, textStatus) {
                                    this._ret = result;
                                    if (result.acceptable) {
                                        menuOptions
                                            .eq(1)
                                            .removeClass(
                                                'context-menu-item-disabled'
                                            );
                                        menuOptions
                                            .eq(2)
                                            .removeClass(
                                                'context-menu-item-disabled'
                                            );
                                    }
                                },
                                _ret: null // private alchemy
                            };

                            $.ajax(parms);
                        }
                    }
                    return true;
                }
            }
        );

        var contextMenu = [];
        for (let i = 0; i < config.langContextMenu.length; i++) {
            var langPlist = config.langContextMenu[i];
            contextMenu.push({
                label: langPlist.label,
                onclick: function (menuItem, menu) {
                    C_MenuOption(menuItem, menu, 'ACCEPT', {
                        lng: langPlist.lngCode
                    });
                }
            });
        }

        contextMenu.push({
            label: config.replaceWithMsg,
            //      disabled:true,
            onclick: function (menuItem, menu) {
                C_MenuOption(menuItem, menu, 'REPLACE', null);
            }
        });

        contextMenu.push({
            label: config.removeActionMsg,
            //      disabled:true,
            onclick: function (menuItem, menu) {
                C_MenuOption(menuItem, menu, 'DELETE', null);
            }
        });

        trees.C.tree.contextMenu(contextMenu, {
            beforeShow: function () {
                var ret = false;

                var x = this._showEvent.srcElement
                    ? this._showEvent.srcElement
                    : this._showEvent.target;
                var li = $(x).closest('li');

                if (!li.hasClass('selected')) {
                    // rclick OUTSIDE the selection : unselect all
                    // lisel.removeClass('selected');
                    trees.C.tree.find('LI').removeClass('selected');
                    options.lastClickedCandidate = null;
                }
                var tcids = li.attr('id').split('.');
                if (
                    tcids.length === 4 &&
                    tcids[0] === 'CX_P' &&
                    x.nodeName !== 'LI'
                ) {
                    // candidate context menu only clicking on final term
                    if (!li.hasClass('selected')) {
                        li.addClass('selected');
                    }
                    //				this._cutInfos = { sbid:tcids[1], field:li.parent().attr('field') };	// private alchemy
                    this._srcElement = li; // private alchemy

                    // as selection changes, compute usefull info (field, sbas)
                    var lisel = trees.C.tree.find('LI .selected');
                    if (lisel.length > 0) {
                        // lisel are all from the same candidate field, so check the first li
                        var li0 = lisel.eq(0);
                        var field = li0.parent().attr('field');
                        var sbas = li0.attr('id').split('.')[1];

                        // glue selection info to the tree
                        trees.C._selInfos = {
                            sel: lisel,
                            field: field,
                            sbas: sbas,
                            n: lisel.length
                        };

                        if (lisel.length === 1) {
                            $(this.menu)
                                .find('.context-menu-item')
                                .eq(config.languagesCount)
                                .removeClass('context-menu-item-disabled');
                        } else {
                            $(this.menu)
                                .find('.context-menu-item')
                                .eq(config.languagesCount)
                                .addClass('context-menu-item-disabled');
                        }
                    } else {
                        trees.C._selInfos = null;
                    }

                    ret = true;
                }
                return ret;
            }
        });
    }

    appEvents.listenAll({
        'broadcast.searchResultSelection': (selection) => {
            searchSelection = selection;
        }
    });


    return { initialize, show };
};

export default thesaurusService;
