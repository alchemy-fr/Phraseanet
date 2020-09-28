import $ from 'jquery';
import * as Rx from 'rx';
import * as appCommons from './../../phraseanet-common';
const Selectable = function (services, $container, options) {
    const { configService, localeService, appEvents } = services;
    let defaults = {
            allow_multiple: false,
            selector: '',
            callbackSelection: null,
            selectStart: null,
            selectStop: null,
            limit: null,
            localeService: localeService
        };
    options = typeof options === 'object' ? options : {};

    let $this = this;

    if ($container.data('selectionnable')) {
        /* this container is already selectionnable */
        if (window.console) {
            console.error('Trying to apply new selection to existing one');
        }

        return $container.data('selectionnable');
    }

    this.stream = new Rx.Subject();
    this.$container = $container;
    this.options = $.extend(defaults, options);
    this.datas = [];

    this.$container.data('selectionnable', this);
    this.$container.addClass('selectionnable');
    this.$container
        .on('click', this.options.selector, function (event) {
            event.preventDefault();
            if (typeof $this.options.selectStart === 'function') {
                $this.options.selectStart($.extend($.Event('selectStart'), event), $this);
            }

            let $that = $(this);

            let k = get_value($that, $this);

            if (appCommons.utilsModule.is_shift_key(event) && $('.last_selected', this.$container).filter($this.options.selector).length !== 0) {
                let lst = $($this.options.selector, this.$container);

                let index1 = $.inArray($('.last_selected', this.$container).filter($this.options.selector)[0], lst);
                let index2 = $.inArray($that[0], lst);

                if (index2 < index1) {
                    let tmp = index1;
                    index1 = index2 - 1 < 0 ? index2 : index2 - 1;
                    index2 = tmp;
                }

                let stopped = false;

                if (index2 !== -1 && index1 !== -1) {
                    let exp = $this.options.selector + ':gt(' + index1 + '):lt(' + (index2 - index1) + ')';

                    $.each($(exp, this.$container), function (i, n) {
                        if (!$(n).hasClass('selected') && stopped === false) {
                            if (!$this.hasReachLimit()) {
                                let contain = get_value($(n), $this);
                                $this.push(contain);
                                $(n).addClass('selected');
                            } else {
                                alert(localeService.t('max_record_selected'));
                                stopped = true;
                            }
                        }
                    });
                }

                if ($this.has(k) === false && stopped === false) {
                    if (!$this.hasReachLimit()) {
                        $this.push(k);
                        $that.addClass('selected');
                    } else {
                        alert(localeService.t('max_record_selected'));
                    }
                }
            } else {
                if (!appCommons.utilsModule.is_ctrl_key(event)) {
                    $this.empty().push(k);
                    $('.selected', this.$container).filter($this.options.selector).removeClass('selected');
                    $that.addClass('selected');
                } else {
                    if ($this.has(k) === true) {
                        $this.remove(k);
                        $that.removeClass('selected');
                    } else {
                        if (!$this.hasReachLimit()) {
                            $this.push(k);
                            $that.addClass('selected');
                        } else {
                            alert(localeService.t('max_record_selected'));
                        }
                    }
                }
            }

            $('.last_selected', this.$container).removeClass('last_selected');
            $that.addClass('last_selected');

            $this.stream.onNext({
                asArray: $this.datas,
                serialized: $this.serialize()
            });
            if (typeof $this.options.selectStop === 'function') {
                $this.options.selectStop($.extend($.Event('selectStop'), event), $this);
            }

        });

    return this;
};

function get_value(element, Selectable) {
    if (typeof Selectable.options.callbackSelection === 'function') {
        return Selectable.options.callbackSelection($(element));
    } else {
        return $('input[name="id"]', $(element)).val();
    }
}

Selectable.prototype = {
    push: function (element) {
        if (this.options.allow_multiple === true || !this.has(element)) {
            this.datas.push(element);
        }

        return this;
    },
    hasReachLimit: function () {
        if (this.options.limit !== null && this.options.limit <= this.datas.length) {
            return true;
        }
        return false;
    },
    remove: function (element) {
        this.datas = $.grep(this.datas, function (n) {
            return (n !== element);
        });

        return this;
    },
    has: function (element) {

        return $.inArray(element, this.datas) >= 0;
    },
    get: function () {
        return this.datas;
    },
    empty: function () {
        const $this = this;
        this.datas = [];

        $(this.options.selector, this.$container).filter('.selected:visible').removeClass('selected');

        if (typeof $this.options.selectStop === 'function') {
            $this.options.selectStop($.Event('selectStop'), $this);
        }

        return this;
    },
    length: function () {

        return this.datas.length;
    },
    size: function () {

        return this.datas.length;
    },
    serialize: function (separator) {

        separator = separator || ';';

        return this.datas.join(separator);
    },
    selectAll: function () {
        this.select('*');

        return this;
    },
    select: function (selector) {
        const $this = this;
        let stopped = false;

        $(this.options.selector, this.$container).filter(selector).not('.selected').filter(':visible').each(function () {
            if (!$this.hasReachLimit()) {
                $this.push(get_value(this, $this));
                $(this).addClass('selected');
            } else {
                if (stopped === false) {
                    alert($this.options.localeService.t('max_record_selected'));
                }
                stopped = true;
            }
        });

        $this.stream.onNext({
            asArray: $this.datas,
            serialized: $this.serialize()
        });

        if (typeof $this.options.selectStop === 'function') {
            $this.options.selectStop($.Event('selectStop'), $this);
        }

        return this;
    }
};

export default Selectable;
