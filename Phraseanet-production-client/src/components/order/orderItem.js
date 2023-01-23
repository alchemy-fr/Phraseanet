import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';
import * as appCommons from './../../phraseanet-common';
import order from './index';
import _ from 'underscore';
import pym from 'pym.js';

const orderItem = services => {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    const openModal = orderId => {
        let $dialog = dialog.create(services, {
            size: 'Full'
        });

        $.ajax({
            type: 'GET',
            url: `${url}prod/order/${orderId}`,
            success: function (data) {
                $dialog.setContent(data);
                _onOrderItemReady($dialog);
            }
        });

        return true;
    };

    const _onOrderItemReady = $dialog => {
        let userInfoIsVisible = false;
        let itemCount = 0;
        let elementsForValidation = [];
        let readyForValidation = false;
        let lastItemChosen = null;

        const ELEMENT_TYPE = {
            VALIDATED: 'validated',
            DENIED: 'denied',
            SELECTABLE: 'selectable',
            SELECTED: 'selected',
            WAITINGFORVALIDATION: 'waitingForValidation'
        };

        let trs = $('.order_list .order_row', $dialog.getDomElement());
        let lastSelectedRow;
        if ($('#notification_box').is(':visible')) {
            $('.notification_trigger').trigger('mousedown');
        }

        $('.order_launcher', $dialog.getDomElement()).on('click', function (event) {
            if (readyForValidation) {
                if (confirm(window.orderItemData.translatedText.message)) {
                    order(services).orderModal(event);
                }
            } else {
                order(services).orderModal(event);
            }
        });

        $('#email-btn', $dialog.getDomElement()).on('click', function () {
            let email = window.orderItemData.userEmail;
            let subject = window.orderItemData.subject;
            let body = window.orderItemData.body;
            if (email !== null) {
                let link =
                    'mailto:' +
                    email +
                    '?subject=' +
                    encodeURIComponent(subject) +
                    '&body=' +
                    encodeURIComponent(body);
                window.location.href = link;
            }
        });

        $(
            'input[name="select-all"]',
            $dialog.getDomElement()
        ).bind('click', function () {
            let checkboxElement = this;
            itemCount = 0;
            let selectable = [];
            $('.table-order .order_row').each(function () {
                let el = $(this);
                if (
                    checkboxElement.checked &&
                    el.hasClass(ELEMENT_TYPE.SELECTABLE)
                ) {
                    el.addClass(ELEMENT_TYPE.SELECTED);
                    itemCount++;
                    selectable.push(el);
                } else {
                    el.removeClass(ELEMENT_TYPE.SELECTED);
                }
            });
            //load preview for single item selected
            if (selectable.length === 1) {
                loadPreviewAndCaption(selectable[0]);
            }
            renderOrderDetailView(itemCount);
        });

        $(
            '.order_list .order_row',
            $dialog.getDomElement()
        ).bind('click', function (event) {
            let $this = $(this);
            lastItemChosen = $this;

            //disable select all checkbox if selected
            if ($('input[name="select-all"]').is(':checked')) {
                $('input[name="select-all"]').prop('checked', false);
            }

            if (appCommons.utilsModule.is_ctrl_key(event)) {
                if (!$this.hasClass(ELEMENT_TYPE.SELECTABLE)) {
                    return;
                }
                if ($this.hasClass(ELEMENT_TYPE.SELECTED)) {
                    $this.removeClass(ELEMENT_TYPE.SELECTED);
                    itemCount--;
                } else {
                    $this.addClass(ELEMENT_TYPE.SELECTED);
                    itemCount++;
                }
            } else if (appCommons.utilsModule.is_shift_key(event)) {
                if (!$this.hasClass(ELEMENT_TYPE.SELECTABLE)) {
                    return;
                }
                let currentIndex = $this.index('.order_list .order_row');
                let prevIndex = lastSelectedRow.index('.order_list .order_row');
                $(
                    '.order_list .selectable.selected',
                    $dialog.getDomElement()
                ).removeClass(ELEMENT_TYPE.SELECTED);
                itemCount = 0;
                selectRowsBetweenIndexes([prevIndex, currentIndex]);
            } else {
                $(
                    '.order_list .selectable.selected',
                    $dialog.getDomElement()
                ).removeClass(ELEMENT_TYPE.SELECTED);
                if ($this.hasClass(ELEMENT_TYPE.SELECTABLE)) {
                    $this.addClass(ELEMENT_TYPE.SELECTED);
                    lastSelectedRow = $this;
                }
                itemCount = 1;
            }

            if (itemCount === 1) {
                let selected = $(
                    '.order_list .selected',
                    $dialog.getDomElement()
                );
                loadPreviewAndCaption(selected);
            }
            renderOrderDetailView(itemCount);
        });

        function selectRowsBetweenIndexes(indexes) {
            indexes.sort(function (a, b) {
                return a - b;
            });
            for (let i = indexes[0]; i <= indexes[1]; i++) {
                if ($(trs[i]).hasClass(ELEMENT_TYPE.SELECTABLE)) {
                    $(trs[i]).addClass(ELEMENT_TYPE.SELECTED);
                    itemCount++;
                }
            }
        }

        $(
            '.captionTips, .captionRolloverTips, .infoTips',
            $dialog.getDomElement()
        ).tooltip({
            delay: 0
        });
        $('.previewTips', $dialog.getDomElement()).tooltip({
            fixable: true
        });

        $('button.send', $dialog.getDomElement()).bind('click', function () {
            updateValidation(ELEMENT_TYPE.VALIDATED);
            //send_documents(order_id);
        });

        $('button.deny', $dialog.getDomElement()).bind('click', function () {
            updateValidation(ELEMENT_TYPE.DENIED);
            //deny_documents(order_id);
        });

        $('button.reset', $dialog.getDomElement()).bind('click',function(){
            var itemsToBeReset = [];
            $('.order_list .order_row.selected.waitingForValidation', $dialog.getDomElement()).each(function(i,n){
                itemsToBeReset.push($(n));
            });
            //if item is not selected, delete item being previewed
            if(itemsToBeReset.length == 0 && lastItemChosen) {
                itemsToBeReset.push(lastItemChosen);
            }
            resetItemForValidation(itemsToBeReset);
            toggleValidationButton();

           //$('.order_row.selected').removeClass('to_be_denied');
           //$('.order_row.selected').removeClass('to_be_validated');
        });

        // comment on order_item.html.twig , line 171
        // $('.force_sender', $dialog.getDomElement()).bind('click', function () {
        //     if (confirm(localeService.t('forceSendDocument'))) {
        //         //updateValidation('validated');
        //         let element_id = [];
        //         element_id.push(
        //             $(this)
        //                 .closest('.order_row')
        //                 .find('input[name=order_element_id]')
        //                 .val()
        //         );
        //         let order_id = $('input[name=order_id]').val();
        //         do_send_documents(order_id, element_id, true);
        //     }
        // });

        $('#userInfo').hover(
            function () {
                let offset = $('#userInfo').position();
                $('#userInfoPreview').css({
                    left: offset.left - $('#userInfoPreview').width() + 48,
                    top: offset.top + $('#userInfo').height() + 8
                });
                $('#userInfoPreview').show();
            },
            function () {
                if (!userInfoIsVisible) {
                    $('#userInfoPreview').hide();
                }
            }
        );

        $('#userInfo').click(function () {
            let offset = $('#userInfo').position();
            if (!userInfoIsVisible) {
                userInfoIsVisible = true;
                $('#userInfoPreview').css({
                    left: offset.left - $('#userInfoPreview').width() + 48,
                    top: offset.top + $('#userInfo').height() + 8
                });
                $('#userInfoPreview').show();
            } else {
                userInfoIsVisible = false;
                $('#userInfoPreview').hide();
            }
        });

        let minimized_elements = $('.minimize');

        $('.minimize').each(function () {
            let t = $(this).text();
            if (t.length < 60) return;

            $(this).html(
                t.slice(0, 60) +
                    '<span>... </span><a href="#" class="more">' +
                    window.orderItemData.translatedText.moreText +
                    '</a>' +
                    '<span style="display:none;">' +
                    t.slice(60, t.length) +
                    ' <a href="#" class="less">' +
                    window.orderItemData.translatedText.lessText +
                    '</a></span>'
            );
        });

        $('a.more', minimized_elements).click(function (event) {
            event.preventDefault();
            $(this).hide().prev().hide();
            $(this).next().show();
        });

        $('a.less', minimized_elements).click(function (event) {
            event.preventDefault();
            $(this).parent().hide().prev().show().prev().show();
        });

        $('button.validate', $dialog.getDomElement()).bind('click', function (
            event
        ) {
            openValidationDialog(this, event);
            return false;
        });

        $('.basket-btn').click(function (event) {
            let titleCreate = window.orderItemData.translatedText.createTitle;
            let type = $(this).attr('type');
            var dialog_buttons = {};
            dialog_buttons[titleCreate] = function () {
                createBasket($innerDialog);
                $(this).dialog('close');
            };
            let $innerDialog = $('#basket-window')
                .dialog({
                    open: function (event, ui) {
                        $('.ui-dialog').css('z-index', 100000);
                        $('.ui-widget-overlay').css('z-index', 100000);
                    },
                    closeOnEscape: true,
                    width: 450,
                    height: 300,
                    modal: true,
                    draggable: false,
                    stack: false,
                    title: window.orderItemData.translatedText.dialogTitle,
                    overlay: {
                        backgroundColor: '#000',
                        opacity: 0.7
                    },
                    buttons: dialog_buttons
                })
                .dialog('open');
            populateBasketDialog($innerDialog, type);
            return false;
        });

        $('#myDropdown').on('click', function () {
            if ($('#myDropdown').hasClass('open')) {
                return;
            }
            if (
                $('.order_list .selected', $dialog.getDomElement()).length > 0
            ) {
                $('li[type="selected"]').removeClass('disabled');
            } else {
                //no selected item
                if (!$('li[type="selected"]').hasClass('disabled')) {
                    $('li[type="selected"]').addClass('disabled');
                }
            }
        });

        $('#validation-window .expireOn').datepicker({
            beforeShow: (input, inst) => {
                $(inst.dpDiv).addClass('expireOn');
            },
            changeYear: true,
            changeMonth: true,
            dateFormat: 'yy-mm-dd',
            onClose: (input, inst) => {
                $(inst.dpDiv).removeClass('expireOn');
            },
        });

        $('#expire-menu')
            .menu({
                select: function (event, ui) {
                    const $input = $('input[name="expireOn"]:visible');
                    const expire  = $(ui.item[0]).data('expireon');
                    if (expire === '') {
                        // expireon to null = no expiration for the right
                        $input.val('')
                    } else {
                        calculateExpireDate($input, expire);
                    }
                    $(this).hide();
                }
            })
            .mouseleave(function (event, ui) {
                $(this).hide();
            })
            .hide();

        // click to ... to drop
        $("BUTTON.expireOn-menu").click(
            function (event, ui) {
                $("#expire-menu")
                    .css({
                        top:  event.clientY,
                        left: event.clientX - 6
                    })
                    .show();
                return false;
            }
        );

        function calculateExpireDate($input, expire) {
            if (expire === null || expire === undefined || expire === '') {
                $input.val("");
            } else {
                const d = new Date();
                d.setDate(d.getDate() + parseInt(expire));
                const mm = ((d.getMonth() + 1) < 10 ? '0' : '') + (d.getMonth() + 1);
                const dd = (d.getDate() < 10 ? '0' : '') + d.getDate();

                $input.val(d.getFullYear() + '-' + mm + '-' + dd);
            }
        }

        function createBasket($innerDialog) {
            let $form = $('form', $innerDialog);
            let dialog = $innerDialog.closest('.ui-dialog');
            let buttonPanel = dialog.find('.ui-dialog-buttonpane');

            $.ajax({
                type: $form.attr('method'),
                url: $form.attr('action'),
                data: $form.serializeArray(),
                dataType: 'json',
                beforeSend: function () {
                    $(
                        ":button:contains('" + localeService.t('create') + "')",
                        buttonPanel
                    )
                        .attr('disabled', true)
                        .addClass('ui-state-disabled');
                },
                success: function (data) {
                    let order_id = $('input[name=order_id]').val();
                    let success = '0';
                    if (data.success) {
                        success = '1';
                    }

                    var url =
                        '../prod/order/' +
                        order_id +
                        '/?success=' +
                        success +
                        '&action=basket' +
                        '&message=' +
                        encodeURIComponent(data.message);
                    reloadDialog(url);
                    appEvents.emit('workzone.refresh');
                }
            });
        }

        function populateBasketDialog($innerDialog, type) {
            let lst = [];
            let orderDialog = $innerDialog;
            //set checkbox to true and disable it
            $('input[name="lst"]', orderDialog).prop('checked', true);
            $('.checkbox', orderDialog).css('visibility', 'hidden');
            //set default name
            let name = window.orderItemData.translatedText.defaultBasketTitle;
            $('input[name="name"]', orderDialog).val(name);
            let description = window.orderItemData.description;
            let elements_ids = [];
            switch (type) {
                case 'denied':
                    $(
                        '.order_list .order_row.' + type,
                        $dialog.getDomElement()
                    ).each(function (i, n) {
                        elements_ids.push($(n).attr('elementids'));
                    });
                    break;
                case 'validated':
                    $(
                        '.order_list .order_row.' + type,
                        $dialog.getDomElement()
                    ).each(function (i, n) {
                        elements_ids.push($(n).attr('elementids'));
                    });
                    break;
                default:
                    //selected elements;
                    $(
                        '.order_list .order_row.' + type,
                        $dialog.getDomElement()
                    ).each(function (i, n) {
                        elements_ids.push($(n).attr('elementids'));
                    });
            }
            $('textarea[name="description"]', orderDialog).val(description);
            $('input[name="lst"]', orderDialog).val(elements_ids.join('; '));
        }

        function openValidationDialog(el, event) {
            let submitTitle = window.orderItemData.translatedText.submit;
            let resetTitle = window.orderItemData.translatedText.reset;
            var dialog_buttons = {};

            dialog_buttons[submitTitle] = function () {
                //submit documents
                submitDocuments($(this));
            };
            dialog_buttons[resetTitle] = function () {
                if (confirm(window.orderItemData.translatedText.message)) {
                    resetAllItemForValidation();
                    toggleValidationButton();
                    $(this).dialog('close');
                }
            };
            $('#validation-window')
                .dialog({
                    open: function (event, ui) {
                        $('.ui-dialog').css('z-index', 100000);
                        $('.ui-widget-overlay').css('z-index', 100000);
                    },
                    closeOnEscape: true,
                    resizable: false,
                    width: 450,
                    height: 500,
                    modal: true,
                    draggable: false,
                    stack: false,
                    title: window.orderItemData.translatedText.validation,
                    buttons: dialog_buttons,
                    overlay: {
                        backgroundColor: '#000',
                        opacity: 0.7
                    }
                })
                .dialog('open');
            createValidationTable();
            const $input = $('input[name="expireOn"]:visible');
            const defaultExpire  = $input.data('default-expiration');
            calculateExpireDate($input, defaultExpire);
        }

        function submitDocuments(dialogElem) {
            let order_id = $('input[name=order_id]').val();
            let validatedArrayNoForceIds = _.filter(
                elementsForValidation,
                function (elem) {
                    return (
                        elem.newState === ELEMENT_TYPE.VALIDATED &&
                        elem.oldState !== ELEMENT_TYPE.DENIED
                    );
                }
            ).map(function (elem) {
                return elem.elementId;
            });

            let validatedArrayWithForceIds = _.filter(
                elementsForValidation,
                function (elem) {
                    return (
                        elem.newState === ELEMENT_TYPE.VALIDATED &&
                        elem.oldState === ELEMENT_TYPE.DENIED
                    );
                }
            ).map(function (elem) {
                return elem.elementId;
            });

            let deniedArrayIds = _.filter(elementsForValidation, function (
                elem
            ) {
                return elem.newState === ELEMENT_TYPE.DENIED;
            }).map(function (elem) {
                return elem.elementId;
            });

            if (validatedArrayNoForceIds.length > 0 && deniedArrayIds.length > 0) {
                do_validate_documents(order_id, validatedArrayNoForceIds, deniedArrayIds);
            } else {
                if (validatedArrayNoForceIds.length > 0) {
                    do_send_documents(order_id, validatedArrayNoForceIds, false);
                } else if (validatedArrayWithForceIds.length > 0) {
                    do_send_documents(order_id, validatedArrayWithForceIds, true);
                } else if (deniedArrayIds.length > 0){
                    do_deny_documents(order_id, deniedArrayIds);
                }
            }

            dialogElem.dialog('close');
        }

        function createValidationTable() {
            $('.validation-content').empty();
            let validatedArray = _.filter(elementsForValidation, function (
                elem
            ) {
                return elem.newState === ELEMENT_TYPE.VALIDATED;
            });
            let deniedArray = _.filter(elementsForValidation, function (elem) {
                return elem.newState === ELEMENT_TYPE.DENIED;
            });

            if (validatedArray.length > 0) {
                $("#validation-window:visible .order-expireon-wrap").show();
                $("#validation-window:visible input[name='expireOn']").blur();

                let html = '';
                html +=
                    '<h5>' +
                    window.orderItemData.translatedText.youHaveValidated +
                    ' ' +
                    validatedArray.length +
                    ' ' +
                    window.orderItemData.translatedText.item +
                    (validatedArray.length === 1 ? '' : 's') +
                    '</h5>';

                html += '<table class="validation-table">';
                _.each(validatedArray, function (elem) {
                    html += '<tr>';
                    html +=
                        '<td width="25%" align="center">' +
                        elem.elementPreview[0].outerHTML +
                        '</td>';
                    html +=
                        '<td width="75%">' +
                        elem.elementTitle[0].outerHTML +
                        '</td>';
                    html += '</tr>';
                });
                html += '</table>';
                $('.validation-content').append(html);
            } else {
                $("#validation-window:visible .order-expireon-wrap").hide();
            }

            if (deniedArray.length > 0) {
                let html = '';
                html +=
                    '<h5>' +
                    window.orderItemData.translatedText.youHaveDenied +
                    ' ' +
                    deniedArray.length +
                    ' ' +
                    window.orderItemData.translatedText.item +
                    (deniedArray.length === 1 ? '' : 's') +
                    '</h5>';
                html += '<table class="validation-table">';
                _.each(deniedArray, function (elem) {
                    html += '<tr>';
                    html +=
                        '<td width="25%" align="center">' +
                        elem.elementPreview[0].outerHTML +
                        '</td>';
                    html +=
                        '<td width="75%">' +
                        elem.elementTitle[0].outerHTML +
                        '</td>';
                    html += '</tr>';
                });
                html += '</table>';
                $('.validation-content').append(html);
            }
        }

        function removeItemFromArray(item) {
            var elementId = item.find('input[name=order_element_id]').val();
            var found = _.where(elementsForValidation, {elementId: elementId});
            if(found.length > 0) {
                item.removeClass(ELEMENT_TYPE.WAITINGFORVALIDATION);
                //replace content or row with original content
                item[0].innerHTML = found[0].element[0].innerHTML;
                //remove from array
                elementsForValidation = _.without(elementsForValidation, found[0]);
            }
        }
        function resetItemForValidation(itemsToBeReset) {
            var elementArrayType = [];
            itemsToBeReset.forEach(function(item){
                removeItemFromArray(item);
                updateButtonStatus(item.attr('class').split(/\s+/));
                elementArrayType.push(item.attr('class').split(/\s+/));
            });
            if(elementsForValidation.length == 0) {
                readyForValidation = false;
            }
            updateButtonStatusMultiple(elementArrayType);
            toggleValidationButton();
            //disable select all checkbox if selected
            if($('input[name="select-all"]').is(':checked')){
                $('input[name="select-all"]').prop('checked', false);
            }
        }
        function resetAllItemForValidation() {
            //var dialog = p4.Dialog.get(1);
            $('.order_list .order_row', $dialog.getDomElement()).each(function(i,n){
                removeItemFromArray($(n));
                updateButtonStatus($(n).attr('class').split(/\s+/));
            });
            readyForValidation = false;
            renderOrderDetailView(0);
        }

        function updateValidation(newState) {
            let count = 0;
            $('.order_list .order_row', $dialog.getDomElement()).each(function (
                i,
                n
            ) {
                if (
                    $(n).hasClass(ELEMENT_TYPE.SELECTED) &&
                    !$(n).hasClass(ELEMENT_TYPE.VALIDATED) &&
                    !$(n).hasClass(ELEMENT_TYPE.DENIED) &&
                    !$(n).hasClass(ELEMENT_TYPE.WAITINGFORVALIDATION)
                ) {
                    createItemForValidation(
                        $(n),
                        ELEMENT_TYPE.SELECTABLE,
                        newState
                    );
                    count++;
                } else if (
                    $(n).hasClass(ELEMENT_TYPE.SELECTED) &&
                    !$(n).hasClass(ELEMENT_TYPE.VALIDATED) &&
                    !$(n).hasClass(ELEMENT_TYPE.WAITINGFORVALIDATION)
                ) {
                    createItemForValidation(
                        $(n),
                        ELEMENT_TYPE.DENIED,
                        newState
                    );
                    count++;
                }
                $(n).removeClass(ELEMENT_TYPE.SELECTED);
            });

            //if item is not selected, delete item being previewed
            if(count == 0 && lastItemChosen) {
                createItemForValidation(lastItemChosen, ELEMENT_TYPE.SELECTABLE, newState);
                count++;
            }

            readyForValidation = true;
            toggleValidationButton();
            //disable select all checkbox if selected
            if ($('input[name="select-all"]').is(':checked')) {
                $('input[name="select-all"]').prop('checked', false);
            }

            //multiple items selected
            if (count > 1) {
                $('#wrapper-padding').hide();
                $('.external-order-action').hide();
                $('#wrapper-multiple').hide();
                $('#wrapper-no-item').show();
            }
        }

        function createItemForValidation(element, oldState, newState) {
            let order = {};
            order.elementTitle = element.find('span');
            order.elementPreview = element.find('.order_wrapper');
            order.elementId = element
                .find('input[name=order_element_id]')
                .val();
            order.element = element.clone(true);
            order.oldState = oldState;
            order.newState = newState;
            elementsForValidation.push(order);

            //element.removeClass('to_be_denied');
            //element.removeClass('to_be_validated');

            element.toggleClass(ELEMENT_TYPE.WAITINGFORVALIDATION);
            //element.addClass('to_be_'+order.newState);

            element.find('td:first-child').empty();
            element
                .find('td:first-child')
                .append('<img style="cursor:help;" src="/assets/common/images/icons/to_be_'+order.newState+'.svg" title="">');
            updateButtonStatus(element.attr('class').split(/\s+/));
        }

        function toggleValidationButton() {
            if (readyForValidation) {
                $('button.validate').show();
            } else {
                $('button.validate').hide();
            }
        }

        function do_send_documents(order_id, elements_ids, force) {
            let cont = $dialog.getDomElement();

            $('button.deny, button.send', cont).prop('disabled', true);
            $('.activity_indicator', cont).show();

            $.ajax({
                type: 'POST',
                url: '../prod/order/' + order_id + '/send/',
                dataType: 'json',
                data: {
                    'elements[]': elements_ids,
                    force: force ? 1 : 0,
                    expireOn: $('input[name="expireOn"]:visible').val()
                },
                success: function (data) {
                    let success = '0';

                    if (data.success) {
                        success = '1';
                    }

                    var url =
                        '../prod/order/' +
                        order_id +
                        '/?success=' +
                        success +
                        '&action=send';
                    reloadDialog(url);
                },
                error: function () {
                    $('button.deny, button.send', cont).prop('disabled', false);
                    $('.activity_indicator', cont).hide();
                },
                timeout: function () {
                    $('button.deny, button.send', cont).prop('disabled', false);
                    $('.activity_indicator', cont).hide();
                }
            });
        }

        function do_validate_documents(order_id, elements_send_ids, elements_deny_ids) {
            let cont = $dialog.getDomElement();

            $('button.deny, button.send', cont).prop('disabled', true);
            $('.activity_indicator', cont).show();

            $.ajax({
                type: 'POST',
                url: '../prod/order/' + order_id + '/validate/',
                dataType: 'json',
                data: {
                    'elementsSend[]': elements_send_ids,
                    'elementsDeny[]': elements_deny_ids,
                     expireOn: $('input[name="expireOn"]:visible').val()
                },
                success: function (data) {
                    let success = '0';

                    if (data.success) {
                        success = '1';
                    }

                    var url =
                        '../prod/order/' +
                        order_id +
                        '/?success=' +
                        success +
                        '&action=send';
                    reloadDialog(url);
                },
                error: function () {
                    $('button.deny, button.send', cont).prop('disabled', false);
                    $('.activity_indicator', cont).hide();
                },
                timeout: function () {
                    $('button.deny, button.send', cont).prop('disabled', false);
                    $('.activity_indicator', cont).hide();
                }
            });
        }

        function do_deny_documents(order_id, elements_ids) {
            let cont = $dialog.getDomElement();
            $('button.deny, button.send', cont).prop('disabled', true);
            $('.activity_indicator', cont).show();

            $.ajax({
                type: 'POST',
                url: '../prod/order/' + order_id + '/deny/',
                dataType: 'json',
                data: {
                    'elements[]': elements_ids
                },
                success: function (data) {
                    let success = '0';

                    if (data.success) {
                        success = '1';
                    }

                    var url =
                        '../prod/order/' +
                        order_id +
                        '/?success=' +
                        success +
                        '&action=deny';
                    reloadDialog(url);
                },
                error: function () {
                    $('button.deny, button.send', cont).prop('disabled', false);
                    $('.activity_indicator', cont).hide();
                },
                timeout: function () {
                    $('button.deny, button.send', cont).prop('disabled', false);
                    $('.activity_indicator', cont).hide();
                }
            });
        }

        function renderOrderDetailView(countSelected) {
            if (countSelected > 1) {
                $('#wrapper-padding').hide();
                $('.external-order-action').hide();
                $('#wrapper-multiple').show();
                $('#wrapper-no-item').hide();
                let elementArrayType = [];
                $(
                    '.order_list .selectable.selected',
                    $dialog.getDomElement()
                ).each(function (i, n) {
                    //elementArrayType = _.union(elementArrayType, $(n).attr('class').split(/\s+/));
                    elementArrayType.push($(n).attr('class').split(/\s+/));
                });
                updateButtonStatusMultiple(elementArrayType);
                //updateButtonStatus(elementArrayType);
            } else if (countSelected === 1) {
                $('#wrapper-padding').show();
                $('.external-order-action').show();
                $('#wrapper-multiple').hide();
                $('#wrapper-no-item').hide();
            } else {
                $('#wrapper-padding').hide();
                $('.external-order-action').hide();
                $('#wrapper-multiple').hide();
                $('#wrapper-no-item').show();
            }
            $('#preview-layout-multiple .title').html(countSelected);
        }

        function updateButtonStatusMultiple(elementArrayType) {
            $('#order-action button.deny, #order-action button.send').hide();
            let countObj = elementArrayType.reduce(
                function (m, v) {
                    for (let k in m) {
                        if (~v.indexOf(k)) m[k]++;
                    }
                    return m;
                },
                { validated: 0, selectable: 0, waitingForValidation: 0 }
            );

            let html = '';
            // if (countObj.validated > 0) {
            //     html +=
            //         '<p>' +
            //         window.orderItemData.translatedText.itemsAlreadySent +
            //         ': ' +
            //         countObj.validated +
            //         '</p>';
            // }

            // if (countObj.waitingForValidation > 0) {
            //     html +=
            //         '<p>' +
            //         window.orderItemData.translatedText.itemsWaitingValidation +
            //         ': ' +
            //         countObj.waitingForValidation +
            //         '</p>';
            // }

            //for the remaining items
            let remaining =
                countObj.selectable -
                (countObj.validated + countObj.waitingForValidation);
            if (remaining > 0) {
                // html +=
                //     '<p>' +
                //     window.orderItemData.translatedText.nonSentItems +
                //     ': ' +
                //     remaining +
                //     '</p>';
                $('#order-action button.deny, #order-action button.send').prop(
                    'disabled',
                    false
                );
                $(
                    '#order-action button.deny, #order-action button.send'
                ).show();
            }

            // $('#wrapper-multiple #text-content').empty();
            // $('#wrapper-multiple #text-content').append(html);
        }

        /* *
         * function to update status of send and deny button
         * params - array of type for each button selected
         */
        function updateButtonStatus(elementArrayType) {
            if (_.contains(elementArrayType, ELEMENT_TYPE.VALIDATED)) {
                $('#order-action button.deny, #order-action button.send, #order-action button.reset').hide();
                $('#order-action span.action-text').html(
                    window.orderItemData.translatedText.alreadyValidated +
                        '<i class="fa fa-check" aria-hidden="true"></i>'
                );
                $('#order-action span.action-text').show();
            } else if (
                _.contains(elementArrayType, ELEMENT_TYPE.WAITINGFORVALIDATION)
            ) {
                $('#order-action button.deny, #order-action button.send, #order-action span.action-text').hide();
                $('#order-action button.reset').show();
                //$('#order-action button.send').show();
                //$('#order-action button.send').prop('disabled', true);
            } else if (_.contains(elementArrayType, ELEMENT_TYPE.DENIED)) {
                $('#order-action button.deny, #order-action button.reset').hide();
                $('#order-action span.action-text').html(window.orderItemData.translatedText.refusedPreviously);
                //$('#order-action button.send').prop('disabled', false);
                $('#order-action button.send, #order-action span.action-text').show();
            } else {
                // $('#order-action button.send, #order-action button.deny').prop('disabled', false);
                $('#order-action button.send, #order-action button.deny').show();
                $('#order-action span.action-text, #order-action button.reset').hide();
            }
        }

        function loadPreviewAndCaption(elem) {
            $('#preview-layout').empty();
            $('#caption-layout').empty();
            updateButtonStatus(elem.attr('class').split(/\s+/));
            let elementids = elem.attr('elementids').split('_');
            let sbasId = elementids[0];
            let recordId = elementids[1];
            let prevAjax = $.ajax({
                type: 'GET',
                url: '../prod/records/record/' + sbasId + '/' + recordId + '/',
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        return;
                    }

                    $('#preview-layout').append(data.html_preview);
                    $('#caption-layout').append(data.desc);

                }
            });
        }

        function reloadDialog(url) {
            const baseUrl = configService.get('baseUrl');
            $.ajax({
                type: 'GET',
                url: `${baseUrl}${url}`,
                success: function (data) {
                    if (data.error) {
                        return;
                    }
                    $dialog.setContent(data);
                    _onOrderItemReady($dialog);
                }
            });
        }
    };

    return {
        openModal
    };
};


export default orderItem;
