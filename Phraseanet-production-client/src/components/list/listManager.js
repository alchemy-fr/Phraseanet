import $ from 'jquery';
import {List, Lists} from './model/index';
import listEditor from './listEditor';
import listShare from './listShare';
import dialog from './../../phraseanet-common/components/dialog';
import Selectable from '../utils/selectable';
import pushOrShareAddUser from '../record/pushOrShare/addUser';
import * as _ from 'underscore';

const humane = require('humane-js');

const ListManager = function (services, options) {
    const {
              configService,
              localeService,
              appEvents
          }             = services;
    const {containerId} = options;
    const url           = configService.get('baseUrl');
    let $container;
    const _this         = this;

    this.list      = null;
    this.container = $container = $(containerId);
    this.userList  = new Lists();

//    this.removeUserItemsArray = [];
//    this.addUserItemsArray = [];
//    this.removeUserMethod = '';
//    this.addUserMethod = '';


    pushOrShareAddUser(services).initialize({$container: this.container});

    // console.log("==== declare ListManager");
    appEvents.listenAll({
        // users lists (left) are async loaded
        'usersLists.usersListsChanged': function (o) {
            const nbadges = $('.badge', $container).length;
            const ListCounter = $('#ListManager .counter.current, #ListManager .lists .list.selected .counter');

            ListCounter.each(function (i, el) {
                 $(el).text(nbadges);
            });

        }
    });

    $container.on('click', '.back_link', function () {
        $('#PushBox').show();
        $('#ListManager').hide();
        let $dialogEl = dialog.get(1).getDomElement().closest('.ui-dialog');

        if ($dialogEl.hasClass('Sharebasket')) {
            dialog.get(1).setOption('title', localeService.t('shareTitle'));
        } else if ($dialogEl.hasClass('Feedback')) {
            dialog.get(1).setOption('title', localeService.t('feedbackTitle'));
        }

        return false;

    }).on('click', '.push-list-share-action', (event) => {

        event.preventDefault();
        let $el      = $(event.currentTarget);
        const listId = $el.data('list-id');

        listShare(services).openModal({
            listId,
            modalOptions: {
                size:        '288x500',
                closeButton: true,
                title:       $el.attr('title')
            },
            modalLevel:   2
        });
        return false;

    }).on('click', 'a.user_adder', function () {

        var $this = $(this);

        $.ajax({
            type:       'GET',
            url:        $this.attr('href'),
            dataType:   'html',
            beforeSend: function () {
                var options = {
                    size:  'Medium',
                    title: $this.html()
                };
                dialog.create(services, options, 2).getDomElement().addClass('loading');
            },
            success:    function (data) {
                dialog.get(2).getDomElement().removeClass('loading').empty().append(data);
            },
            error:      function () {
                dialog.get(2).close();
            },
            timeout:    function () {
                dialog.get(2).close();
            }
        });

        return false;

    }).on('mouseenter', '.list-trash-btn', function (event) {

        var $el = $(event.currentTarget);
        $el.find('.image-normal').hide();
        $el.find('.image-hover').show();

    }).on('mouseleave', '.list-trash-btn', function (event) {

        var $el = $(event.currentTarget);
        $el.find('.image-normal').show();
        $el.find('.image-hover').hide();

    }).on('click', '.list-trash-btn', function (event) {

        var $el     = $(event.currentTarget);
        var list_id = $el.parent().data('list-id');
        appEvents.emit('push.removeList', {
                list_id:   list_id,
                container: containerId
            }
        );
    });

    var initLeft = () => {
        // console.log("==== declare initLeft");

        $container.on('click', '.push-refresh-list-action', (event) => {
            event.preventDefault();
            //$('a.list_refresh', $container).bind('click', (event) => {
            // /prod/lists/all/

            var selectedList = $('.lists_manager_list.selected').data('list-id');

            var callback = function (datas, selected) {
                $('.all-lists', $container).removeClass('loading').append(datas);

                if (typeof selected === 'number') {
                    $('.all-lists').find('.lists_manager_list[data-list-id="' + selected + '"]').addClass('selected');
                }
                // initLeft();
            };

            $('.all-lists', $container).empty().addClass('loading');

            this.userList.get(callback, 'html', selectedList);

        });

        $container.on('click', '.push-add-list-action', (event) => {
            event.preventDefault();
            var makeDialog = (box) => {

                var buttons = {};

                buttons[localeService.t('valider')] = () => {

                    var callbackOK = function () {
                        $('a.list_refresh', $container).trigger('click');
                        dialog.get(2).close();
                    };

                    var name = $('input[name="name"]', dialog.get(2).getDomElement()).val();

                    if ($.trim(name) === '') {
                        alert($('#push-list-name-empty').val());
                        return;
                    }

                    this.userList.create(name, callbackOK);
                };
// /prod/lists/list/
                var options                         = {
                    cancelButton: true,
                    buttons:      buttons,
                    title:        $('#push-new-list-title').val(),
                    size:         '450x170'
                };

                const $dialog = dialog.create(services, options, 2);
                $dialog.getDomElement().closest('.ui-dialog').addClass('dialog_container dialog_add_list')
                       .find('.ui-dialog-buttonset button:first-child .ui-button-text').text($('#btn-add').val());
                $dialog.setContent(box);
            };

            var html = _.template($('#list_editor_dialog_add_tpl').html());
            makeDialog(html);

            return false;
        });

        /**
         * load a list by click on one list on the left
         */
        $container.on('click', '.list-edit-action', (event) => {
            event.preventDefault();
//            _this.removeUserItemsArray = [];
//            _this.addUserItemsArray = [];
//            _this.removeUserMethod = '';
//            _this.addUserMethod = '';

            let $el      = $(event.currentTarget);
            const listId = $el.data('list-id');
            const el_url = $el.attr('href');

            const callbackList = function (list) {
                for (let i in list.entries) {
                    this.selectUser(list.entries[i].User);
                }
                appEvents.emit('usersLists.usersListsChanged', {
                    container: $container
                });
            };

            $el.closest('.lists').find('.list').removeClass('selected');
            $el.parent().addClass('selected');

            $.ajax({
                type:       'GET',
                url:        `${url}prod/push/edit-list/${listId}/`,
                dataType:   'html',
                success:    (data) => {
                    this.workOn(listId);
                    $('.editor', $container).removeClass('loading').append(data);
                    this.loadList(el_url, callbackList);
                    initRight();
                    listEditor(services, {
                        $container,
                        listManagerInstance: _this
                    });
                },
                beforeSend: function () {
                    $('.editor', $container).empty(); // .addClass('loading');
                }
            });
        });

        $container.on('click', '.listmanager-delete-list-user-action', (event) => {
            event.preventDefault();
            let $el      = $(event.currentTarget);
            const listId = $el.data('list-id');
            const userId = $el.data('user-id');

            var badge = $(this).closest('.badge');
            // var usr_id = badge.find('input[name="id"]').val();
            this.getList().removeUser(userId, function (list, data) {
                badge.remove();
            });
        });
    };

    var initRight = function () {
        // console.log("==== declare initRight");

        var $container = $('#ListManager .editor');
        var selection  = new Selectable(services, $('.user_content .badges', _this.container), {
            selector: '.badge'
        });

        $('form[name="list-editor-search"]', $container).bind('submit', function (event) {
            event.preventDefault();
            var $this = $(this);
            var dest  = $('.list-editor-results', $container);

            $.ajax({
                url:        $this.attr('action'),
                type:       $this.attr('method'),
                dataType:   'html',
                data:       $this.serializeArray(),
                beforeSend: function () {
                    dest.empty().addClass('loading');
                },
                success:    function (datas) {
                    dest.empty().removeClass('loading').append(datas);
                    listEditor(services, {
                        $container,
                        listManagerInstance: _this
                    });
                }
            });
        });

        $('form[name="list-editor-search"] select, form[name="list-editor-search"] input[name="ListUser"]', $container).bind('change', function () {
            $(this).closest('form').trigger('submit');
        });

        $('.EditToggle', $container).bind('click', function (event) {
            event.preventDefault();
            $('.content.readselect, .content.readwrite, .editor_header', $('#ListManager')).toggle();
        });
        $('.Refresher', $container).bind('click', function (event) {
            event.preventDefault();
            $('#ListManager ul.lists .list.selected a').trigger('click');
        });

        $container.off('submit', 'form[name="SaveName"]')
                  .on('submit', 'form[name="SaveName"]', function () {
                      // console.log("==== on submit form[name=\"SaveName\"]");
                      const $this = $(this);
                      $.ajax({
                          type:       $this.attr('method'),
                          url:        $this.attr('action'),
                          dataType:   'json',
                          data:       $this.serializeArray(),
                          beforeSend: function () {

                          },
                          success:    function (data) {
                              if (data.success) {
                                  humane.info(data.message);
                                  $('#ListManager .lists .list_refresh').trigger('click');
                              }
                              else {
                                  humane.error(data.message);
                              }
                          },
                          error:      function () {
                          },
                          timeout:    function () {
                          }
                      });

                      return false;
                  });

        // //button.deleter
        // $('.listmanager-delete-list-action', $container).bind('click', function (event) {

        //     var list_id = $(this).data('list-id');

        //     var makeDialog = function (box) {

        //         var buttons = {};

        //         buttons[localeService.t('valider')] = function () {

        //             var callbackOK = function () {
        //                 $('#ListManager .all-lists a.list_refresh').trigger('click');
        //                 dialog.get(2).close();
        //             };

        //             var List = new List(list_id);
        //             List.remove(callbackOK);
        //         };

        //         var options = {
        //             cancelButton: true,
        //             buttons: buttons,
        //             size: 'Alert'
        //         };

        //         dialog.create(services, options, 2).setContent(box);
        //     };

        //     var html = _.template($('#list_editor_dialog_delete_tpl').html());

        //     makeDialog(html);

        //     return false;
        // });


        // console.log("========== setting deleter");
        $container.off('click', '.badges a.deleter')
                  .on('click', '.badges a.deleter', null, function (event) {
                      const badge   = $(event.currentTarget).closest('.badge');
                      // console.log("======== badge ", badge);
                      const usr_id  = badge.find('input[name="id"]').val();
                      const $editor = $('#list-editor-search-results');

                      badge.remove();

                      $('tbody tr', $editor).each(function (i, el) {
                          const $el   = $(el);
                          const $elID = $('input[name="usr_id"]', $el).val();

                          if (usr_id === $elID) {
                              $el.removeClass('selected');
                          }
                      });
                      _this.getList().removeUser(usr_id);

                      appEvents.emit('usersLists.usersListsChanged', {
                          container: $container
                      });

                      return false;
                  });


        /**
         * add a user from the completion of the search input
         *
         * @param ul
         * @param item
         * @returns {*}
         * @private
         */
        $('input[name="users-search"]', $container).autocomplete({
            minLength: 2,
            source:    function (request, response) {
                $.ajax({
                    url:      `${url}prod/push/search-user/`,
                    dataType: 'json',
                    data:     {
                        query: request.term
                    },
                    success:  function (data) {
                        response(data);
                    }
                });
            },
            focus:     function (event, ui) {
                // $('input[name="users-search"]').val(ui.item.label);
            },
            select:    function (event, ui) {
                if (ui.item.type === 'USER') {
                    _this.selectUser(ui.item);
                    //_this.updateUsersHandler('add', ui.item.usr_id);
                    _this.getList().addUser(ui.item.usr_id);

                }
                else if (ui.item.type === 'LIST') {
                    for (let e in ui.item.entries) {
                        _this.selectUser(ui.item.entries[e].User);
                        //_this.updateUsersHandler('add', ui.item.entries[e].User.usr_id);
                        _this.getList().addUser(ui.item.entries[e].User.usr_id);
                    }
                }
                appEvents.emit('usersLists.usersListsChanged', {
                    container: $container
                });

                // the list has changed, show the save button
//                $('#saveListFooter').show();

                return false;
            }

        }).data('ui-autocomplete')._renderItem = function (ul, item) {

            var html = '';

            if (item.type === 'USER') {
                html = _.template($('#list_user_tpl').html())({

                    item: item
                });
            }
            else if (item.type === 'LIST') {
                html = _.template($('#list_list_tpl').html())({
                    item: item
                });
            }

            return $(html).data('ui-autocomplete-item', item).appendTo(ul);
        };

        $('.user_content .badges', _this.container).disableSelection();

        $container.on('click', '.content .options .select-all', function () {
            selection.selectAll();
        });

        $container.on('click', '.content .options .unselect-all', function () {
            selection.empty();
        });

        $container.on('click', '.content .options .delete-selection', function () {
            var $elems = $('#ListManager .badges.selectionnable .badge.selected');
            _.each($elems, function (item) {
                var $elem   = $(item);
                var $elemID = $elem.find('input[name=id]').val();
                // if($elem.hasClass('selected')
                //     && _this.removeUserItemsArray.indexOf($elemID) === -1) {
                //     _this.updateUsersHandler('remove', $elemID);
                // }
                if ($elem.hasClass('selected')) {
                    _this.getList().removeUser($elemID);
                }
            });

            $elems.fadeOut(300, 'swing', function () {
                $(this).remove();
//                $('#saveListFooter').show();
                appEvents.emit('usersLists.usersListsChanged', {
                    container: $container
                });
            });

        });
        /*
                $container.off('submit', 'form.list_saver')
                          .on('submit', 'form.list_saver', function (event) {
                                console.log("==== on submit form.list_saver");
                                event.preventDefault();
                                var $form = $(event.currentTarget);
                                var name = $('.header h2', $container).text();
                                var users = _this.getUsers();

                                if (users.length === 0) {
                                    humane.error('No users');
                                    return false;
                                }
                                else {
                                    if (_this.removeUserMethod === 'remove' && _this.removeUserItemsArray.length > 0) {
                                        var $editor = $('#list-editor-search-results');

                                        _.each(_this.removeUserItemsArray, function (item) {

                                            $('tbody tr', $editor).each(function(i, el) {
                                                var $el = $(el);
                                                var $elID = $('input[name="usr_id"]', $el).val();
                                                if(item == $elID)
                                                    $el.removeClass('selected');
                                            });

                                            _this.getList().removeUser(item);
                                        });

                                        var ListCounter = $('#ListManager .counter.current, #ListManager .lists .list.selected .counter');

                                        ListCounter.each(function (i, el) {
                                            var n = parseInt($(el).text(), 10);
                                            if($(el).hasClass('current'))
                                                $(el).text(n - _this.removeUserItemsArray.length + ' people');
                                            else
                                                $(el).text(n - _this.removeUserItemsArray.length);
                                        });

                                        // $('#saveListFooter').hide();
                                        _this.removeUserItemsArray = [];
                                        _this.removeUserMethod = '';
                                    }
                                    else if (_this.addUserMethod === 'add' && _this.addUserItemsArray.length > 0) {
                                        var $editor = $('#list-editor-search-results');

                                        _.each(_this.addUserItemsArray, function (item) {

                                            $('tbody tr', $editor).each(function(i, el) {

                                                var $el = $(el);
                                                var $elID = $('input[name="usr_id"]', $el).val();

                                                if(item == $elID)
                                                    $el.addClass('selected');
                                            });

                                            _this.getList().addUser(item);
                                        });

                                        var ListCounter = $('#ListManager .counter.current, #ListManager .lists .list.selected .counter');

                                        ListCounter.each(function (i, el) {
                                            var n = parseInt($(el).text(), 10);

                                            if($(el).hasClass('current'))
                                                $(el).text(n + _this.addUserItemsArray.length + ' people');
                                            else
                                                $(el).text(n + _this.addUserItemsArray.length);
                                        });

                                        // $('#saveListFooter').hide();
                                        _this.addUserItemsArray = [];
                                        _this.addUserMethod = '';
                                    }
                                }
                          });
        */
    };


    initLeft();

    return this;

};

ListManager.prototype = {
    selectUser:  function (user) {
        if (typeof user !== 'object') {
            if (window.console) {
                console.log('trying to select a user with wrong datas');
            }
        }
        if ($('.badge_' + user.usr_id, this.container).length > 0) {
            humane.info('User already selected');
            return;
        }
        else {
            var html = _.template($('#_badge_tpl').html())({
                user:    user,
                context: 'ListManager'
            });

            // p4.Feedback.appendBadge(html);
            // this.getList().addUser(user.usr_id);
            this.appendBadge(html);
        }
    },
    workOn:      function (list_id) {
        this.list = new List(list_id);
    },
    getList:     function () {
        return this.list;
    },
    appendBadge: function (datas) {
        $('#ListManager .badges').append(datas);
    },
    createList:  function (options) {
        let {
                name,
                collection
            } = options;

        this.userList.create(name, function (list) {
            list.addUsers(collection);
        });
    },
    removeList:  function (list_id, callback) {
        this.list = new List(list_id);
        this.list.remove(callback);
    },
    loadList:    function (url, callback) {
        let $this = this;
        $.ajax({
            type:     'GET',
            url:      url,
            dataType: 'json',
            success:  function (data) {
                if (typeof callback === 'function') {
                    callback.call($this, data);
                }
            }
        });
    },
    updateUsers: function (action) {
        if (action === 'remove') {

        }
        return removedItems;
    },
    getUsers:    function () {
        return $('.user_content .badge', this.container).map(function () {
            return $('input[name="id"]', $(this)).val();
        });
    },
    // updateUsersHandler: function (method, item) {
    //     if (method === 'remove') {
    //         this.removeUserItemsArray.push(item);
    //         this.removeUserMethod = method;
    //     }
    //     else if (method === 'add') {
    //         this.addUserItemsArray.push(item);
    //         this.addUserMethod = method;
    //     }
    // }
};


export default ListManager;
