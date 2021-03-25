import $ from 'jquery';
import { Lists } from '../../list/model/index';
import dialog from './../../../phraseanet-common/components/dialog';
import Selectable from '../../utils/selectable';
import pushAddUser from './addUser';
import * as _ from 'underscore';
const humane = require('humane-js');
require('./../../../phraseanet-common/components/tooltip');

const Feedback = function (services, options) {
    const { configService, localeService, appEvents } = services;
    const url = configService.get('baseUrl');
    let $container;
    let { containerId, context } = options;

    this.url = url;
    this.container = $container = $(containerId);
    this.userList = new Lists();

    this.Context = context;

    this.selection = new Selectable(services,
        $('.user_content .badges', this.container),
        {
            selector: '.badge'
        }
    );

    pushAddUser(services).initialize({$container: this.container});

    var $this = this;

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
        };

        $('.LeftColumn .content .lists', $container).empty().addClass('loading');

        $this.userList.get(callback, 'html');
    });


    this.container.on('click', '.content .options .select-all', function (event) {
        $this.selection.selectAll();
    });

    this.container.on('click', '.content .options .unselect-all', function (event) {
        $this.selection.empty();
    });

    this.container.on('click', '.content .options .delete-selection', function (event) {
        _.each($('.badges.selectionnable').children(), function(item) {
            var $elem = $(item);
            if($elem.hasClass('selected')) {
                $elem.fadeOut(function () {
                    $elem.remove();
                });
            }
        });
        return false;
    });

    $('.UserTips', this.container).tooltip();

    /*this.container.on('click', '.user_adder', function (event) {
        event.preventDefault();
        const url = configService.get('baseUrl');
        var $this = $(this);

        $.ajax({
            type: 'GET',
            url: `${url}prod/push/add-user/`,
            dataType: 'html',
            beforeSend: function () {
                var options = {
                    size: 'Medium',
                    title: $this.html()
                };
                dialog.create(services, options, 2).getDomElement().addClass('loading');
            },
            success: function (data) {
                dialog.get(2).getDomElement().removeClass('loading').empty().append(data);
                return;
            },
            error: function () {
                dialog.get(2).close();
                return;
            },
            timeout: function () {
                dialog.get(2).close();
                return;
            }
        });

        return false;
    });*/

    this.container.on('click', '.recommended_users', function (event) {
        var usr_id = $('input[name="usr_id"]', $(this)).val();

        $this.loadUser(usr_id, $this.selectUser);

        return false;
    });

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

            $this.loadUser(usr_id, $this.selectUser);

            return false;
        });

        $dialog.getDomElement().find('a.adder').each(function (i, el) {

            var usr_id = $(this).closest('tr').find('input[name="usr_id"]').val();

            if ($('.badge_' + usr_id, $this.container).length > 0) {
                $(this).addClass('added');
            }

        });


        return false;
    });

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

    $('.FeedbackSend', this.container).bind('click', function (event) {
        const $el = $(event.currentTarget);
        if ($('.badges .badge', $container).length === 0) {
            alert(localeService.t('FeedBackNoUsersSelected'));
            return;
        }

        var buttons = {};

        buttons[localeService.t('annuler')] = function () {
            $dialog.close();
        };

        buttons[localeService.t('send')] = function () {
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

            $dialog.close();

            if ($el.data('feedback-action') !== 'adduser') {
                $('input[name="name"]', $FeedBackForm).val($('input[name="name"]', $dialog.getDomElement()).val());
                $('input[name="duration"]', $FeedBackForm).val($('select[name="duration"]', $dialog.getDomElement()).val());
            }

            $('textarea[name="message"]', $FeedBackForm).val($('textarea[name="message"]', $dialog.getDomElement()).val());
            $('input[name="recept"]', $FeedBackForm).prop('checked', $('input[name="recept"]', $dialog.getDomElement()).prop('checked'));
            $('input[name="force_authentication"]', $FeedBackForm).prop('checked', $('input[name="force_authentication"]', $dialog.getDomElement()).prop('checked'));
            $('input[name="notify"]', $FeedBackForm).val('1');

            $FeedBackForm.trigger('submit');
        };

        if ($el.data('feedback-action') === 'adduser') {
            buttons[localeService.t('feedbackNotNotify')] = function () {
                $dialog.close();

                $('textarea[name="message"]', $FeedBackForm).val($('textarea[name="message"]', $dialog.getDomElement()).val());
                $('input[name="recept"]', $FeedBackForm).prop('checked', $('input[name="recept"]', $dialog.getDomElement()).prop('checked'));
                $('input[name="force_authentication"]', $FeedBackForm).prop('checked', $('input[name="force_authentication"]', $dialog.getDomElement()).prop('checked'));
                $('input[name="notify"]', $FeedBackForm).val('0');

                $FeedBackForm.trigger('submit');
            };
        }

        var options = {
            size: '558x352',
            buttons: buttons,
            loading: true,
            title: localeService.t('send'),
            closeOnEscape: true,
        };

        if($el.hasClass('validation')) {
            options.isValidation = true;
            options.size = '558x415'
        }

        var $dialog = dialog.create(services, options, 2);

        $dialog.getDomElement().closest('.ui-dialog').addClass('dialog_container');
        if(options.isValidation) {
            $dialog.getDomElement().closest('.ui-dialog').addClass('validation');
        }


        var $FeedBackForm = $('form[name="FeedBackForm"]', $container);

        var html = '';
        // if the window is just for adding/removing user
        if ($el.data('feedback-action') === 'adduser') {
            html = _.template($('#feedback_adduser_sendform_tpl').html());
        } else {
            html = _.template($('#feedback_sendform_tpl').html());
        }

        $dialog.setContent(html);

        var feedbackTitle =  $('#feedbackTitle').val();
        var pushTitle =  $('#pushTitle').val();

        if (options.isValidation) {
            $('input[name="name"]').attr("placeholder", feedbackTitle);
        }else {
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
        $('.' + $this.Context, $dialog.getDomElement()).show();

        $('form', $dialog.getDomElement()).submit(function () {
            return false;
        });
    });

    $('.user_content .badges', this.container).disableSelection();


    // toggle download feature for users
    this.container.on('click', '.user_content .badges .badge .toggle', function (event) {
        var $this = $(this);

        $this.toggleClass('status_off status_on');

        $this.find('input').val($this.hasClass('status_on') ? '1' : '0');

        return false;
    });

    this.container.on('mouseenter', '#info-box-trigger', function(event) {
        $('#info-box').show();
    });

    this.container.on('mouseleave', '#info-box-trigger', function(event) {
        $('#info-box').hide();
    });

    // toggle feature state of selected users
    this.container.on('click', '.general_togglers .general_toggler', function (event) {
        var feature = $(this).attr('feature');

        var $badges = $('.user_content .badge.selected', this.container);

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

    this.container.on('click', '.user_content .badges .badge .deleter', function (event) {
        var $elem = $(this).closest('.badge');
        let userEmailEl = $elem.find('.user-email').val();

        let value = $('#newParticipantsUser').val();
        let actualParticipantsName = value.split('; ');
        // remove the user in the list of new participant if yet exist
        let key = $.inArray(userEmailEl, actualParticipantsName);
        if (key > -1) {
            actualParticipantsName.splice(key, 1);
            if (actualParticipantsName.length != 0) {
                value = actualParticipantsName.join('; ');
                $('#newParticipantsUser').val(value);
            } else {
                $('#newParticipantsUser').val('');
            }
        }

        $elem.fadeOut(function () {
            $elem.remove();
        });
        return false;
    });

    this.container.on('click', '.list_manager', function (event) {
        $('#PushBox').hide();
        $('#ListManager').show();
        return false;
    });

    this.container.on('click', 'a.list_push_loader', function (event) {
        var url = $(this).attr('href');

        var callbackList = function (list) {
            for (let i in list.entries) {
                this.selectUser(list.entries[i].User);
            }
        };

        $this.loadList(url, callbackList);

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
        $this.createList({ name: $input.val(), collection: users });
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
                    $this.selectUser(ui.item);
                } else if (ui.item.type === 'LIST') {
                    for (let e in ui.item.entries) {
                        $this.selectUser(ui.item.entries[e].User);
                    }
                }
                return false;
            }
        })
        .data('ui-autocomplete')._renderItem = function (ul, item) {
        var html = '';

        if (item.type === 'USER') {
            html = _.template($('#list_user_tpl').html())({
                item: item
            });

            if ($this.Context === 'Push') {
                setTimeout(() => {
                    $('.ui-menu .ui-menu-item a').css('box-shadow', 'inset 0 -1px #2196f3');
                    $('img[src="/assets/common/images/icons/user-orange.png"]').attr('src', '/assets/common/images/icons/user-blue.png');
                }, 100);
            }
            else if ($this.Context === 'Feedback') {
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

    return this;
};

Feedback.prototype = {
    selectUser: function (user) {
        if (typeof user !== 'object') {
            if (window.console) {
                console.log('trying to select a user with wrong datas');
            }
        }
        if ($('.badge_' + user.usr_id, this.container).length > 0) {
            humane.info('User already selected');
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

        var html = _.template($('#' + this.Context.toLowerCase() + '_badge_tpl').html())({
            user: user
        });

        // p4.Feedback.appendBadge(html);
        this.appendBadge(html);
    },
    loadUser: function (usr_id, callback) {
        var $this = this;
        $.ajax({
            type: 'GET',
            url: `${this.url}prod/push/user/${usr_id}/`,
            dataType: 'json',
            data: {
                usr_id: usr_id
            },
            success: function (data) {
                if (typeof callback === 'function') {
                    callback.call($this, data);
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
        var $this = this;

        $.ajax({
            type: 'GET',
            url: url,
            dataType: 'json',
            success: function (data) {
                if (typeof callback === 'function') {
                    callback.call($this, data);
                }
            }
        });
    },
    appendBadge: function (badge) {
        $('.user_content .badges', this.container).prepend(badge);
    },
    addUser: function (options) {
        let {$userForm, callback} = options;
        var $this = this;
        $.ajax({
            type: 'POST',
            url: `${this.url}prod/push/add-user/`,
            dataType: 'json',
            data: $userForm.serializeArray(),
            success: function (data) {
                if (data.success) {
                    humane.info(data.message);
                    $this.selectUser(data.user);
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


export default Feedback;
