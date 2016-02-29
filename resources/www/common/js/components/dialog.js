;
var dialogModule = (function ($) {
    var $body = null;
    var bodySize = {};
    var _dialog = {};

    $('document').ready(function(){
        $body = $('body');

        $(window).on('resize', function () {
            bodySize.y = $body.height();
            bodySize.x = $body.width();

            //@TODO modal resize should be in a stream
            $('.overlay').height(bodySize.y).width(bodySize.x);
            //_resizeAll();
        });
    });


    function getLevel(level) {

        level = parseInt(level);

        if (isNaN(level) || level < 1) {
            return 1;
        }

        return level;
    };

    function getId(level) {
        return 'DIALOG' + getLevel(level);
    };

    function _addButtons(buttons, dialog) {
        if (dialog.options.closeButton === true) {
            buttons[language.fermer] = function () {
                dialog.close();
            };
        }
        if (dialog.options.cancelButton === true) {
            buttons[language.annuler] = function () {
                dialog.close();
            };
        }

        return buttons;
    }

    var _phraseaDialog = function (options, level) {

        var _createDialog = function (level) {

            var $dialog = $('#' + getId(level));

            if ($dialog.length > 0) {
                throw 'Dialog already exists at this level';
            }

            $dialog = $('<div style="display:none;" id="' + getId(level) + '"></div>');
            $('body').append($dialog);

            return $dialog;
        };

        var defaults = {
                size: 'Medium',
                buttons: {},
                loading: true,
                title: '',
                closeOnEscape: true,
                confirmExit: false,
                closeCallback: false,
                closeButton: false,
                cancelButton: false
            },
            options = typeof options === 'object' ? options : {},
            width,
            height,
            $dialog,
            $this = this;

        this.closing = false;

        this.options = $.extend(defaults, options);

        this.level = getLevel(level);

        this.options.buttons = _addButtons(this.options.buttons, this);

        if (/\d+x\d+/.test(this.options.size)) {
            var dimension = this.options.size.split('x');
            height = dimension[1];
            width = dimension[0];
        } else {

            bodySize.y = $body.height();
            bodySize.x = $body.width();

            switch (this.options.size) {
                case 'Full':
                    height = bodySize.y - 30;
                    width = bodySize.x - 30;
                    break;
                case 'Medium':
                    width = Math.min(bodySize.x - 30, 730);
                    height = Math.min(bodySize.y - 30, 520);
                    break;
                default:
                case 'Small':
                    width = Math.min(bodySize.x - 30, 420);
                    height = Math.min(bodySize.y - 30, 300);
                    break;
                case 'Alert':
                    width = Math.min(bodySize.x - 30, 300);
                    height = Math.min(bodySize.y - 30, 150);
                    break;
            }
        }

        /*
         * 3 avaailable dimensions :
         *
         *  - Full   | Full size ()
         *  - Medium | 420 x 450
         *  - Small  | 730 x 480
         *
         **/
        this.$dialog = _createDialog(this.level),
            zIndex = Math.min(this.level * 5000 + 5000, 32767);

        var _closeCallback = function () {
            if (typeof $this.options.closeCallback === 'function') {
                $this.options.closeCallback($this.$dialog);
            }

            if ($this.closing === false) {
                $this.closing = true;
                $this.close();
            }
        };

        if (this.$dialog.data('ui-dialog')) {
            this.$dialog.dialog('destroy');
        }

        this.$dialog.attr('title', this.options.title)
            .empty()
            .dialog({
                buttons: this.options.buttons,
                draggable: false,
                resizable: false,
                closeOnEscape: this.options.closeOnEscape,
                modal: true,
                width: width,
                height: height,
                open: function () {
                    $(this).dialog("widget").css("z-index", zIndex);
                },
                close: _closeCallback
            })
            .dialog('open').addClass('dialog-' + this.options.size);

        if (this.options.loading === true) {
            this.$dialog.addClass('loading');
        }

        if (this.options.size === 'Full') {
            var $this = this;
            $(window).unbind('resize.DIALOG' + getLevel(level))
                .bind('resize.DIALOG' + getLevel(level), function () {
                    if ($this.$dialog.data("ui-dialog")) {
                        $this.$dialog.dialog('option', {
                            width: bodySize.x - 30,
                            height: bodySize.y - 30
                        });
                    }
                });
        }

        return this;
    };

    _phraseaDialog.prototype = {
        close: function () {
            _dialog.close(this.level);
        },
        setContent: function (content) {
            this.$dialog.removeClass('loading').empty().append(content);
        },
        getId: function () {
            return this.$dialog.attr('id');
        },
        load: function (url, method, params) {
            var $this = this;
            this.loader = {
                url: url,
                method: typeof method === 'undefined' ? 'GET' : method,
                params: typeof params === 'undefined' ? {} : params
            };

            $.ajax({
                type: this.loader.method,
                url: this.loader.url,
                dataType: 'html',
                data: this.loader.params,
                beforeSend: function () {
                },
                success: function (data) {
                    $this.setContent(data);
                    return;
                },
                error: function () {
                    return;
                },
                timeout: function () {
                    return;
                }
            });
        },
        refresh: function () {
            if (typeof this.loader === 'undefined') {
                throw 'Nothing to refresh';
            }
            this.load(this.loader.url, this.loader.method, this.loader.params);
        },
        getDomElement: function () {
            return this.$dialog;
        },
        getOption: function (optionName) {
            if (this.$dialog.data("ui-dialog")) {
                return this.$dialog.dialog('option', optionName);
            }
            return null;
        },
        setOption: function (optionName, optionValue) {
            if (optionName === 'buttons') {
                optionValue = _addButtons(optionValue, this);
            }
            if (this.$dialog.data("ui-dialog")) {
                this.$dialog.dialog('option', optionName, optionValue);
            }
        }
    };

    var Dialog = function () {
        this.currentStack = {};
    };

    Dialog.prototype = {
        create: function (options, level) {

            if (this.get(level) instanceof _phraseaDialog) {
                this.get(level).close();
            }

            $dialog = new _phraseaDialog(options, level);

            this.currentStack[$dialog.getId()] = $dialog;

            return $dialog;
        },
        get: function (level) {

            var id = getId(level);

            if (id in this.currentStack) {
                return this.currentStack[id];
            }

            return null;
        },
        close: function (level) {

            $(window).unbind('resize.DIALOG' + getLevel(level));

            this.get(level).closing = true;
            var dialog = this.get(level).getDomElement();
            if (dialog.data('ui-dialog')) {
                dialog.dialog('close').dialog('destroy');
            }
            dialog.remove();

            var id = this.get(level).getId();

            if (id in this.currentStack) {
                delete this.currentStack.id;
            }
        }
    };

    _dialog = new Dialog();
    return {
        dialog: _dialog
    }

}(jQuery));
