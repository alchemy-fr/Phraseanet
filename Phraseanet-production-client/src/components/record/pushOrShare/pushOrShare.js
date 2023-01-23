import $ from 'jquery';
import {Lists} from '../../list/model/index';
import dialog from './../../../phraseanet-common/components/dialog';
import Selectable from '../../utils/selectable';
import pushOrShareAddUser from './addUser';
import * as _ from 'underscore';

const humane = require('humane-js');
require('./../../../phraseanet-common/components/tooltip');

const pushOrShare = function (services, container) {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let $container;
    let { containerId, context } = container;

    this.appEvents = appEvents;
    this.url = url;
    this.container = $container = $(containerId);
    this.userList = new Lists();

    this.Context = context;

    var pushOrShare = this;


    this.selection = new Selectable(services,
        $('.user_content .badges', this.container),
        {
            selector: '.badge',
            selectStop: function(event, ui) {
                appEvents.emit('sharebasket.participantsSelectionChanged', {container:$container});
            }
        }
    );

    pushOrShareAddUser(services).initialize({$container: this.container});

    this.container.on('mouseenter', '.list-trash-btn', function (event) {
        var $el = $(event.currentTarget);
        $el.find('.image-normal').hide();
        $el.find('.image-hover').show();
    });

    this.container.on('mouseleave', '.list-trash-btn', function (event) {
        var $el = $(event.currentTarget);
        $el.find('.image-normal').show();
        $el.find('.image-hover').hide();
    });

    this.container.on('click', '.list-trash-btn', function (event) {
        var $el = $(event.currentTarget);
        var list_id = $el.parent().data('list-id');

        appEvents.emit('push.removeList', {
                list_id: list_id,
                container: containerId
            }
        );
    });

    this.container.on('click', '.push-refresh-list-action', function (event) {
        event.preventDefault();

        var callback = function callback(datas) {
            var context = $(datas);
            var dataList = $(context).find('.lists').prop('outerHTML');

            var refreshContent = $('.LeftColumn .content .lists', $container);
            refreshContent.removeClass('loading').append(dataList);

            appEvents.emit('sharebasket.usersListsChanged', { container:pushOrShare.container, context:'reload' });
        };

        $('.LeftColumn .content .lists', $container).empty().addClass('loading');

        pushOrShare.userList.get(callback, 'html');
    });


    this.container.on('click', '.content .options .select-all', function (event) {
        pushOrShare.selection.selectAll();
    });

    this.container.on('click', '.content .options .unselect-all', function (event) {
        pushOrShare.selection.empty();
    });

    this.container.on('click', '.content .general_togglers .delete-selection', function (event) {
        _.each($('.badges.selectionnable').children(), function(item) {
            var $elem = $(item);
            if($elem.hasClass('selected')) {
                let userEmail = $elem.find('.user-email').val();

                let action = $('input[name="feedbackAction"]').val();

                if (action == 'adduser') {
                    let value = $('#newParticipantsUser').val();
                    let actualParticipantsName = value.split('; ');
                    // remove the user in the list of new participant if yet exist
                    let key = $.inArray(userEmail, actualParticipantsName);
                    if (key > -1) {
                        actualParticipantsName.splice(key, 1);
                        if (actualParticipantsName.length != 0) {
                            value = actualParticipantsName.join('; ');
                            $('#newParticipantsUser').val(value);
                        } else {
                            $('#newParticipantsUser').val('');
                        }
                    }
                }

                $elem.fadeOut(function () {
                    $elem.remove();

                    appEvents.emit('sharebasket.participantsChanged', {container:$container, context:'user-added'});
                });
            }
        });

        return false;
    });

    $('.UserTips', this.container).tooltip();

    this.container.on('click', '.recommended_users', function (event) {
        var usr_id = $('input[name="usr_id"]', $(this)).val();
        pushOrShare.loadUser(usr_id, pushOrShare.selectUser);

        return false;
    });

// !!!!!!!!!!!!!!!!!!!!!!!!!!!! this is never called because the link is not shown ??? ( see templates/web/prod/actions/Push.html.twig
    this.container.on('click', '.recommended_users_list', function (event) {

        var content = $('#push_user_recommendations').html();

        var options = {
            size: 'Small',
            title: $(this).attr('title')
        };

        let $dialog = dialog.create(services, options, 2);
        $dialog.setContent(content);

        $dialog.getDomElement().find('a.adder').bind('click', function () {

            $(this).addClass('added');

            var usr_id = $(this).closest('tr').find('input[name="usr_id"]').val();

            pushOrShare.loadUser(usr_id, pushOrShare.selectUser);

            return false;
        });

        $dialog.getDomElement().find('a.adder').each(function (i, el) {

            var usr_id = $(this).closest('tr').find('input[name="usr_id"]').val();

            if ($('.badge_' + usr_id, pushOrShare.container).length > 0) {
                $(this).addClass('added');
            }

        });


        return false;
    });
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

    //this.container.on('submit', '#PushBox form[name="FeedBackForm"]', function (event) {
    $('#PushBox form[name="FeedBackForm"]').bind('submit', function () {

        var $this = $(this);

        $.ajax({
            type: $this.attr('method'),
            url: $this.attr('action'),
            dataType: 'json',
            data: $this.serializeArray(),
            beforeSend: function () {

            },
            success: function (data) {
                if (data.success) {
                    humane.info(data.message);
                    dialog.close(1);
                    appEvents.emit('workzone.refresh');
                } else {
                    humane.error(data.message);
                }
                return;
            },
            error: function () {

                return;
            },
            timeout: function () {

                return;
            }
        });

        return false;
    });

    /**
     * click on the main dlg send (or save) button
     */
    $('.FeedbackSend', this.container).bind('click', function (event) {
        const $el = $(event.currentTarget);

        if ($('.badges .badge', $container).length === 0) {
            alert(localeService.t('FeedBackNoUsersSelected'));

            return;
        }

        if ($('input[name="voteExpires"]', $container).is(":visible") && $('input[name="voteExpires"]', $container).val() === '') {
            alert(localeService.t('FeedBackNoExpires'));

            return;
        }

        var buttons = {};

        // if we edit an existing basket, add a "save" button to send without email notification
        //
        if ($el.data('feedback-action') === 'adduser') {
            buttons[localeService.t('feedbackSaveNotNotify')] = function () {
                $dialog.close();

                $('textarea[name="message"]', $FeedBackForm).val($('textarea[name="message"]', $dialog.getDomElement()).val());
                $('input[name="send_reminder"]', $FeedBackForm).prop('checked', $('input[name="send_reminder"]', $dialog.getDomElement()).prop('checked'));
                $('input[name="recept"]', $FeedBackForm).prop('checked', $('input[name="recept"]', $dialog.getDomElement()).prop('checked'));
                $('input[name="force_authentication"]', $FeedBackForm).prop('checked', $('input[name="force_authentication"]', $dialog.getDomElement()).prop('checked'));
                $('input[name="notify"]', $FeedBackForm).val('0');

                $FeedBackForm.trigger('submit');
            };
        }

        // normal "send button"
        //
        buttons[localeService.t('feedbackSend')] = function () {

            // if we must create a new basket, we must get a name for it
            if ($el.data('feedback-action') !== 'adduser') {
                if ($.trim($('input[name="name"]', $dialog.getDomElement()).val()) === '') {
                    var options = {
                        size: 'Alert',
                        closeButton: true,
                        title: localeService.t('warning')
                    };
                    var $dialogAlert = dialog.create(services, options, 3);
                    $dialogAlert.setContent(localeService.t('FeedBackNameMandatory'));

                    return false;
                }
            }

            // complete the main dlg (ux) form with infos from save dlg
            if ($el.data('feedback-action') !== 'adduser') {
                $('input[name="name"]', $FeedBackForm).val($('input[name="name"]', $dialog.getDomElement()).val());
                $('input[name="duration"]', $FeedBackForm).val($('select[name="duration"]', $dialog.getDomElement()).val());
            }

            $('textarea[name="message"]', $FeedBackForm).val($('textarea[name="message"]', $dialog.getDomElement()).val());
            $('input[name="send_reminder"]', $FeedBackForm).prop('checked', $('input[name="send_reminder"]', $dialog.getDomElement()).prop('checked'));
            $('input[name="recept"]', $FeedBackForm).prop('checked', $('input[name="recept"]', $dialog.getDomElement()).prop('checked'));
            $('input[name="force_authentication"]', $FeedBackForm).prop('checked', $('input[name="force_authentication"]', $dialog.getDomElement()).prop('checked'));
            $('input[name="notify"]', $FeedBackForm).val('1');

            // close the popup save dlg
            $dialog.close();

            // submit the main form
            $FeedBackForm.trigger('submit');
        };

        buttons[localeService.t('annuler')] = function () {
            $dialog.close();
        };

        var options = {
            size: '600x415',
            buttons: buttons,
            loading: true,
            title: localeService.t('send'),
            closeOnEscape: true,
        };

        if($el.hasClass('validation')) {
            options.isValidation = true;
            options.size = '600x455'
        }

        var $dialog = dialog.create(services, options, 2);

        $dialog.getDomElement().closest('.ui-dialog').addClass('dialog_container');
        if(options.isValidation) {
            $dialog.getDomElement().closest('.ui-dialog').addClass('validation');
        }


        var $FeedBackForm = $('form[name="FeedBackForm"]', $container);

        var context = '';
        if ($el.attr('data-context') == 'Sharebasket') {
            if ($("INPUT[name=isFeedback]").val() == '0') {
                context = "sharebasket";
            } else {
                context = "feedback";
            }
        } else {
            context = "push";
        }

        var html = '';
        // if the window is just for adding/removing user
        if ($el.data('feedback-action') === 'adduser') {
            html = _.template($('#feedback_adduser_sendform_tpl').html())({
                context: context
            });
        } else {
            html = _.template($('#feedback_sendform_tpl').html())({
                context: context
            });
        }

        $dialog.setContent(html);

        var feedbackTitle =  $('#feedbackTitle').val();
        var pushTitle =  $('#pushTitle').val();
        var sharedTitle = $('#sharedTitle').val();

        if (context == 'feedback') {
            $('input[name="name"]').attr("placeholder", feedbackTitle);
        } else if(context == 'sharebasket') {
            $('input[name="name"]').attr("placeholder", sharedTitle);
        } else {
            $('input[name="name"]').attr("placeholder", pushTitle);
        }

        if ($el.data('feedback-action') !== 'adduser') {
            $('input[name="name"]', $dialog.getDomElement()).val($('input[name="name"]', $FeedBackForm).val());
        } else {
            // display the list of new user in the dialog window when add user
            let lisNewUser = $('#newParticipantsUser').val();
            if (lisNewUser == '') {
                $('.email-to-notify').hide();
            } else {
                $('.email-to-notify').show();
                $('#email-to-notify').empty().append($('#newParticipantsUser').val());
            }
        }

        $('textarea[name="message"]', $dialog.getDomElement()).val($('textarea[name="message"]', $FeedBackForm).val());
        $('.' + pushOrShare.Context, $dialog.getDomElement()).show();

        $('form', $dialog.getDomElement()).submit(function () {
            return false;
        });
    });

    $('.user_content .badges', this.container).disableSelection();

    this.container.on('mouseenter', '#info-box-trigger', function(event) {
        $('#info-box').show();
    });

    this.container.on('mouseleave', '#info-box-trigger', function(event) {
        $('#info-box').hide();
    });

    // toggle feature state of selected users
    this.container.on('click', '.general_togglers .general_toggler', function (event) {
        var feature = $(this).attr('feature');

        var $badges = $('.user_content .badge.selected', $container.container);

        var toggles = $('.status_off.toggle_' + feature, $badges);

        if (toggles.length === 0) {
            toggles = $('.status_on.toggle_' + feature, $badges);
        }
        if (toggles.length === 0) {
            humane.info('No user selected');
        }

        toggles.trigger('click');
        return false;
    });

    this.container.on('click', '.list_manager', function (event) {
        $('#PushBox').hide();
        $('#ListManager').show();

        dialog.get(1).setOption('title', localeService.t('listmanagerTitle'));

        return false;
    });

    $('form.list_saver', this.container).bind('submit', () => {
        var $form = $(event.currentTarget);
        var $input = $('input[name="list_name"]', $form);

        var users = this.getUsers();

        if (users.length === 0) {
            humane.error('No users');
            return false;
        }

        // appEvents.emit('push.createList', {name: $input.val(), collection: users});
        pushOrShare.createList({ name: $input.val(), collection: users });
        $input.val('');
        /*
         p4.Lists.create($input.val(), function (list) {
         $input.val('');
         list.addUsers(users);
         });*/

        return false;
    });

    $('input[name="users-search"]', this.container).autocomplete({
            minLength: 2,
            source: function (request, response) {
                $.ajax({
                    url: `${url}prod/push/search-user/`,
                    dataType: 'json',
                    data: {
                        query: request.term
                    },
                    success: function (data) {
                        response(data);
                    }
                });
            },
            focus: function (event, ui) {
                // $('input[name="users-search"]').val(ui.item.label);
            },
            select: function (event, ui) {
                if (ui.item.type === 'USER') {
                    pushOrShare.selectUser(ui.item);
                }
                else if (ui.item.type === 'LIST') {
                    for (let e in ui.item.entries) {
                        pushOrShare.selectUser(ui.item.entries[e].User);
                    }
                }
                $('input[name="users-search"]', this).val('');
                return false;
            }.bind(this.container)
        })
        .data('ui-autocomplete')._renderItem = function (ul, item) {
        var html = '';

        if (item.type === 'USER') {
            html = _.template($('#list_user_tpl').html())({
                item: item
            });

            if (pushOrShare.Context === 'Push') {
                setTimeout(() => {
                    $('.ui-menu .ui-menu-item a').css('box-shadow', 'inset 0 -1px #2196f3');
                    $('img[src="/assets/common/images/icons/user-orange.png"]').attr('src', '/assets/common/images/icons/user-blue.png');
                }, 100);
            }
            else if (pushOrShare.Context === 'Feedback') {
                setTimeout(() => {
                    $('.ui-menu .ui-menu-item a').css('box-shadow', 'inset 0 -1px #8bc34a');
                    $('img[src="/assets/common/images/icons/user-orange.png"]').attr('src', '/assets/common/images/icons/user-green.png');
                }, 100);
            }
        }
        else if (item.type === 'LIST') {
            html = _.template($('#list_list_tpl').html())({
                item: item
            });
        }

        return $(html).data('ui-autocomplete-item', item).appendTo(ul);
    };



    appEvents.listenAll({
        // users lists (left) are async loaded
        'sharebasket.usersListsChanged': function(o) {

            o.container
             .off('click', '.LeftColumn .content .lists a.list_link')
             .on('click', '.LeftColumn .content .lists a.list_link', function (event) {
                const url = $(this).attr('href');

                var callbackList = function (list) {
                    for (let i in list.entries) {
                        this.selectUser(list.entries[i].User, false);   // false: do not send participantsChanged event
                    }
                    appEvents.emit('sharebasket.participantsChanged', {container:container, context:'user-added'});
                };

                pushOrShare.loadList(url, callbackList);

                return false;
            });
        },
        'sharebasket.participantsChanged': function(o) {

            // the list on participants (badges) have changed : set event handlers on specific elements...

            const $toggles = $('.user_content .toggles .toggle', $container);

            // ... delete badge handler
            //
            $container.off('click', '.user_content .badges .badge .deleter')
             .on('click', '.user_content .badges .badge .deleter', function (event) {
                 var $elem       = $(this).closest('.badge');
                 let userEmailEl = $elem.find('.user-email').val();
                 let action      = $('input[name="feedbackAction"]').val();

                 if (action === 'adduser') {
                     let value = $('#newParticipantsUser').val();
                     let actualParticipantsName = value.split('; ');
                     // remove the user in the list of new participant if yet exist
                     let key                    = $.inArray(userEmailEl, actualParticipantsName);
                     if (key > -1) {
                         actualParticipantsName.splice(key, 1);
                         if (actualParticipantsName.length != 0) {
                             value = actualParticipantsName.join('; ');
                             $('#newParticipantsUser').val(value);
                         }
                         else {
                             $('#newParticipantsUser').val('');
                         }
                     }
                 }

                 $elem.fadeOut(function () {
                     $elem.remove();
                     appEvents.emit('sharebasket.participantsChanged', {
                         container: $container,
                         context:   'user-deleted'
                     });
                 });

                 return false;
             });

            // ... toggle buttons handlers
            //
            $toggles.off('click')
                   .on('click', function (event) {

                event.stopPropagation();

                const $this = $(this);

                $this.toggleClass('status_off status_on');

                const input_value = $this.hasClass('status_on') ? '1' : '0';
                $this.parent().find('input').val(input_value);

                if($(event.currentTarget).attr('id') === 'toggleFeedback') {
                    appEvents.emit('sharebasket.toggleFeedbackChanged', { container:$container, context:o.context });
                }
                else {
                    // normal toggle
                    appEvents.emit('sharebasket.toggleChanged', {
                        container: $container,
                        context:   'toggle-changed'
                    });
                }

                return false;
            });
            // if user list changes, selection also ?
            appEvents.emit('sharebasket.participantsSelectionChanged', { container:$container, context:'init' });
            // fake toggleFeedbackChanged to show/hide togglers of (new) participants
            appEvents.emit('sharebasket.toggleFeedbackChanged', { container:$container,  context: 'init' });
        },
        'sharebasket.toggleFeedbackChanged': function(o) {

            if($("INPUT[name=isFeedback]").val() === '0') {
                // simple share
                $('.whole_dialog_container').addClass('Sharebasket').removeClass('Feedback');
                $('.feedback_only_true', o.container).hide();
                $('.feedback_only_false', o.container).show();

                dialog.get(1).setOption('title', localeService.t('shareTitle'));
            } else if($("INPUT[name=isFeedback]").val() === '1') {
                // we want feedback from this share
                $('.whole_dialog_container').addClass('Feedback').removeClass('Sharebasket');
                $('.feedback_only_false', o.container).hide();
                $('.feedback_only_true', o.container).show();

                dialog.get(1).setOption('title', localeService.t('feedbackTitle'));
            }
        },
        'sharebasket.participantsSelectionChanged': function(o) {
            // a toggle on a user badge was changed
            const $badges = $('.user_content .badges .badge', $container);
            const selectedCount = $badges.filter('.selected').length;
            if(selectedCount === 0) {
                // none selected
                $('.selected_all').hide();
                $('.selected_partial').hide();
                $('.selected_none').show();
            }
            else if (selectedCount === $badges.length) {
                // all selected
                $('.selected_none').hide();
                $('.selected_partial').hide();
                $('.selected_all').show();
            }
            else {
                // partial selection
                $('.selected_none').hide();
                $('.selected_all').hide();
                $('.selected_partial').show();
            }
        }
    });

    // load users lists (left zone)
    $('.push-refresh-list-action', this.container).click();

    appEvents.emit('sharebasket.participantsChanged', { container:this.container, context:'init' });

    return this;
};

pushOrShare.prototype = {
    /**
     * - a user is selected from the search result list, OR
     * - a user is added from a user list.
     * @param user
     * @param participantsChanged avoid refresh for each user when a user list is loaded : list loader will do
     */
    selectUser: function (user, participantsChanged) {
        if (typeof user !== 'object') {
            if (window.console) {
                console.log('trying to select a user with wrong datas');
            }
        }
        if ($('.badge_' + user.usr_id, this.container).length > 0) {
            // humane.info('User already selected');
            return;
        }

        if ($('input[name="feedbackAction"]').val() == 'adduser') {
            let actualParticipantsUserIds = $('#participantsUserIds').val();
            actualParticipantsUserIds = actualParticipantsUserIds.split('_');

            if (!($.inArray(user.usr_id.toString(), actualParticipantsUserIds) > -1)) {
                let value = $('#newParticipantsUser').val();
                let glue = (value == '') ? '' : '; ' ;
                value = value + glue + user.email;
                $('#newParticipantsUser').val(value);
            }
        }

        var html = _.template($('#_badge_tpl').html())({
            user: user,
            context: this.Context
        });
        // p4.Feedback.appendBadge(html);
        this.appendBadge(html);

        if(typeof participantsChanged === 'undefined' || participantsChanged === true) {
            this.appEvents.emit('sharebasket.participantsChanged', {
                container: this.container,
                context:   'user-added'
            });
        }

    },
    loadUser: function (usr_id, callback) {
        var _this = this;
        $.ajax({
            type: 'GET',
            url: `${this.url}prod/push/user/${usr_id}/`,
            dataType: 'json',
            data: {
                usr_id: usr_id
            },
            success: function (data) {
                if (typeof callback === 'function') {
                    callback.call(_this, data);
                }
            }
        });
    },
    createList: function (options) {
        let { name, collection } = options;

        this.userList.create(name, function (list) {
            list.addUsers(collection);
        });
    },
    loadList: function (url, callback) {
        var _this = this;

        $.ajax({
            type: 'GET',
            url: url,
            dataType: 'json',
            success: function (data) {
                if (typeof callback === 'function') {
                    callback.call(_this, data);
                }
            }
        });
    },
    appendBadge: function (badge) {
        $('.user_content .badges', this.container).prepend(badge);
    },
    addUser: function (options) {
        let {$userForm, callback} = options;
        var _this = this;
        $.ajax({
            type: 'POST',
            url: `${this.url}prod/push/add-user/`,
            dataType: 'json',
            data: $userForm.serializeArray(),
            success: function (data) {
                if (data.success) {
                    humane.info(data.message);
                    _this.selectUser(data.user);
                    callback;
                } else {
                    humane.error(data.message);
                }
            }
        });
    },
    getSelection: function () {
        return this.selection;
    },
    getUsers: function () {
        return $('.user_content .badge', this.container).map(function () {
            return $('input[name="id"]', $(this)).val();
        });
    }
};


export default pushOrShare;
