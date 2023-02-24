(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory(require("jQuery"));
	else if(typeof define === 'function' && define.amd)
		define(["jQuery"], factory);
	else if(typeof exports === 'object')
		exports["app"] = factory(require("jQuery"));
	else
		root["app"] = factory(root["jQuery"]);
})(typeof self !== 'undefined' ? self : this, function(__WEBPACK_EXTERNAL_MODULE_0__) {
return webpackJsonpapp([5],{

/***/ 0:
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE_0__;

/***/ }),

/***/ 253:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function($) {

var _bootstrap = __webpack_require__(254);

var _bootstrap2 = _interopRequireDefault(_bootstrap);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var lightboxApplication = {
    bootstrap: _bootstrap2.default
};

if (typeof window !== 'undefined') {
    window.lightboxApplication = lightboxApplication;
}

$(window).on("load resize ", function (e) {
    /* See more basket btn*/
    $('.see_more_basket').on('click', function (e) {
        see_more('basket');
    });
    $('.see_more_feed').on('click', function (e) {
        see_more('feed');
    });

    function see_more(target) {
        $('.other_' + target).toggleClass('hidden');
        document.getElementById('see_more_' + target).scrollIntoView({
            behavior: 'smooth'
        });
        document.getElementById('see_less_' + target).scrollIntoView({
            behavior: 'smooth',
            block: "start"
        });
        $('.see_more_' + target).toggleClass('hidden');
    }
});
module.exports = lightboxApplication;
/* WEBPACK VAR INJECTION */}.call(exports, __webpack_require__(0)))

/***/ }),

/***/ 254:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

var _configService = __webpack_require__(16);

var _configService2 = _interopRequireDefault(_configService);

var _locale = __webpack_require__(20);

var _locale2 = _interopRequireDefault(_locale);

var _config = __webpack_require__(255);

var _config2 = _interopRequireDefault(_config);

var _emitter = __webpack_require__(15);

var _emitter2 = _interopRequireDefault(_emitter);

var _index = __webpack_require__(256);

var _index2 = _interopRequireDefault(_index);

var _mainMenu = __webpack_require__(78);

var _mainMenu2 = _interopRequireDefault(_mainMenu);

var _lodash = __webpack_require__(4);

var _lodash2 = _interopRequireDefault(_lodash);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

__webpack_require__(14);
__webpack_require__(19);
var humane = __webpack_require__(9);

var Bootstrap = function () {
    function Bootstrap(userConfig) {
        var _this = this;

        _classCallCheck(this, Bootstrap);

        var configuration = (0, _lodash2.default)({}, _config2.default, userConfig);

        this.appEvents = new _emitter2.default();
        this.configService = new _configService2.default(configuration);

        this.localeService = new _locale2.default({
            configService: this.configService
        });

        this.localeService.fetchTranslations().then(function () {
            _this.onConfigReady();
        });

        return this;
    }

    _createClass(Bootstrap, [{
        key: 'onConfigReady',
        value: function onConfigReady() {
            var _this2 = this;

            this.appServices = {
                configService: this.configService,
                localeService: this.localeService,
                appEvents: this.appEvents
            };

            window.bodySize = {
                x: 0,
                y: 0
            };

            /**
             * add components
             */

            (0, _jquery2.default)(document).ready(function () {
                var $body = (0, _jquery2.default)('body');
                window.bodySize.y = $body.height();
                window.bodySize.x = $body.width();

                (0, _index2.default)(_this2.appServices).initialize({ $container: $body });
                (0, _mainMenu2.default)(_this2.appServices).initialize({ $container: $body });

                var isReleasable = _this2.configService.get('releasable');

                if (isReleasable !== null) {
                    _this2.appLightbox.setReleasable(isReleasable);
                }

                humane.infoLarge = humane.spawn({ addnCls: 'humane-libnotify-info humane-large', timeout: 5000 });
                humane.info = humane.spawn({ addnCls: 'humane-libnotify-info', timeout: 1000 });
                humane.error = humane.spawn({ addnCls: 'humane-libnotify-error', timeout: 1000 });
                humane.forceNew = true;
            });
        }
    }]);

    return Bootstrap;
}();

var bootstrap = function bootstrap(userConfig) {
    return new Bootstrap(userConfig);
};

exports.default = bootstrap;

/***/ }),

/***/ 255:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});
var defaultConfig = {
    locale: 'fr',
    basePath: '/',
    translations: '/prod/language/'
};

exports.default = defaultConfig;

/***/ }),

/***/ 256:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

var _utils = __webpack_require__(57);

var _utils2 = _interopRequireDefault(_utils);

var _download = __webpack_require__(257);

var _download2 = _interopRequireDefault(_download);

var _pym = __webpack_require__(17);

var _pym2 = _interopRequireDefault(_pym);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

__webpack_require__(38);
var humane = __webpack_require__(9);


var lightbox = function lightbox(services) {
    var configService = services.configService,
        localeService = services.localeService,
        appEvents = services.appEvents;

    var downloadService = (0, _download2.default)(services);
    var _releasable = false;
    var _bodySize = {
        x: 0,
        y: 0
    };
    var $mainContainer = null;
    var activeThumbnailFrame = false;

    var initialize = function initialize() {

        $mainContainer = (0, _jquery2.default)('#mainContainer');
        _bodySize.y = $mainContainer.height();
        _bodySize.x = $mainContainer.width();

        (0, _jquery2.default)(undefined).data('slideshow', false);
        (0, _jquery2.default)(undefined).data('slideshow_ctime', false);

        (0, _jquery2.default)(window).bind('beforeunload', function () {
            if (_releasable !== false) {
                if (confirm(_releasable)) {
                    (0, _jquery2.default)('#basket_options .confirm_report').trigger('click');
                }
            }
        });

        _display_basket();

        //load iframe if type is document
        var $embedFrame = (0, _jquery2.default)('.lightbox_container', (0, _jquery2.default)('#record_main')).find('#phraseanet-embed-frame');
        var customId = 'phraseanet-embed-lightbox-frame';
        $embedFrame.attr('id', customId);
        var src = $embedFrame.attr('data-src');
        if ($embedFrame.hasClass('documentTips')) {
            activeThumbnailFrame = new _pym2.default.Parent(customId, src);
            activeThumbnailFrame.iframe.setAttribute('allowfullscreen', '');
        }

        (0, _jquery2.default)(window).bind('mousedown', function () {
            (0, _jquery2.default)(this).focus();
        }).trigger('mousedown');

        (0, _jquery2.default)('.basket_wrapper').hover(function () {
            (0, _jquery2.default)(this).addClass('hover');
        }, function () {
            (0, _jquery2.default)(this).removeClass('hover');
        }).bind('click', function () {
            var id = (0, _jquery2.default)('input[name=ssel_id]', this).val();
            document.location = '/lightbox/validate/' + id + '/';
            return;
        });

        downloadService.initialize({
            $container: $mainContainer
        });

        if ((0, _jquery2.default)('.right_column_wrapper_user').length > 0) {
            (0, _jquery2.default)('.right_column_title, #right_column_validation_toggle').bind('click', function () {
                if (!(0, _jquery2.default)('.right_column_wrapper_caption').is(':visible')) {
                    (0, _jquery2.default)('.right_column_wrapper_user').height((0, _jquery2.default)('.right_column_wrapper_user').height()).css('top', 'auto').animate({
                        height: 0
                    });
                    (0, _jquery2.default)('.right_column_wrapper_caption').slideDown();
                    (0, _jquery2.default)('#right_column_validation_toggle').show();
                } else {
                    (0, _jquery2.default)('.right_column_wrapper_user').height('auto').animate({
                        top: (0, _jquery2.default)('.right_column_title').height()
                    });
                    (0, _jquery2.default)('.right_column_wrapper_caption').slideUp();
                    (0, _jquery2.default)('#right_column_validation_toggle').hide();
                }
                var title = (0, _jquery2.default)('.right_column_title');
                title.hasClass('expanded') ? title.removeClass('expanded') : title.addClass('expanded');
            }).addClass('clickable');
        }
        var sselcont = (0, _jquery2.default)('#sc_container .basket_element:first');
        if (sselcont.length > 0) {
            _display_basket_element(false, sselcont.attr('id').split('_').pop());
        }

        _setSizeable((0, _jquery2.default)('#record_main .lightbox_container, #record_compare .lightbox_container'));

        (0, _jquery2.default)('#navigation').bind('change', function () {
            window.location.replace(window.location.protocol + '//' + window.location.host + '/lightbox/validate/' + (0, _jquery2.default)(this).val() + '/');
        });

        (0, _jquery2.default)('#left_scroller').bind('click', function () {
            _scrollElements(false);
        });

        (0, _jquery2.default)('#right_scroller').bind('click', function () {
            _scrollElements(true);
        });

        (0, _jquery2.default)(window).bind('resize', function () {
            _resizeLightbox();
        });
        _bind_keyboard();
    };

    function _resizeLightbox() {
        _bodySize.y = $mainContainer.height();
        _bodySize.x = $mainContainer.width();
        _displayRecord((0, _jquery2.default)('#record_compare').css('visibility') !== 'hidden');
    }

    function _display_basket() {
        var sc_wrapper = (0, _jquery2.default)('#sc_wrapper');
        var basket_options = (0, _jquery2.default)('#basket_options');

        (0, _jquery2.default)('.report').on('click', function () {
            _loadReport();
            return false;
        }).addClass('clickable');

        (0, _jquery2.default)('.confirm_report', basket_options).button().bind('click', function () {
            _getReseaseStatus((0, _jquery2.default)(this));
        });

        (0, _jquery2.default)('#validate-release').click(function () {
            (0, _jquery2.default)("#FeedbackRelease").modal("hide");
            _setRelease((0, _jquery2.default)(this));
            console.log('validation is done');
        });

        (0, _jquery2.default)('.basket_element', sc_wrapper).parent().bind('click', function (event) {
            _scid_click(event, this);
            _adjust_visibility(this);
            return false;
        });

        (0, _jquery2.default)('.agree_button, .disagree_button', sc_wrapper).bind('click', function (event) {
            var sselcont_id = (0, _jquery2.default)(this).closest('.basket_element').attr('id').split('_').pop();

            var agreement = (0, _jquery2.default)(this).hasClass('agree_button') ? 1 : -1;

            _setAgreement(event, (0, _jquery2.default)(this), sselcont_id, agreement);
            return false;
        }).addClass('clickable');

        var n = (0, _jquery2.default)('.basket_element', sc_wrapper).length;
        (0, _jquery2.default)('#sc_container').width(n * (0, _jquery2.default)('.basket_element_wrapper:first', sc_wrapper).outerWidth() + 1);

        (0, _jquery2.default)('.previewTips').tooltip();
    }

    function setReleasable(val) {
        _releasable = val;
    }

    function _bind_keyboard() {
        (0, _jquery2.default)(document).bind('keydown', function (event) {
            var stop = false;
            (0, _jquery2.default)('.notes_wrapper').each(function (i, n) {
                if (parseInt((0, _jquery2.default)(n).css('top'), 10) >= 0) {
                    stop = true;
                }
            });

            if (stop) {
                return true;
            }

            var cancelKey = false;
            var el;
            var id;

            if ((0, _jquery2.default)('body').hasClass('dialog-open') == false) {
                switch (event.keyCode) {
                    case 39:
                        _getNext();
                        cancelKey = true;
                        break;
                    case 37:
                        _getPrev();
                        cancelKey = true;
                        break;
                    case 32:
                        var bool = !(0, _jquery2.default)(document).data('slideshow');
                        _slideshow(bool);
                        break;
                    case 38:
                        // participants can vote
                        if ((0, _jquery2.default)('#basket_infos .user_infos .choices').length === 1) {
                            el = (0, _jquery2.default)('#sc_container .basket_element.selected');
                            if (el.length === 1) {
                                id = el.attr('id').split('_').pop();
                                _setAgreement(event, el, id, 1);
                            }
                        }

                        break;
                    case 40:
                        // participants can vote
                        if ((0, _jquery2.default)('#basket_infos .user_infos .choices').length === 1) {
                            el = (0, _jquery2.default)('#sc_container .basket_element.selected');
                            if (el.length === 1) {
                                id = el.attr('id').split('_').pop();
                                _setAgreement(event, el, id, -1);
                            }
                        }

                        break;
                    default:
                        break;
                }
            }

            if (cancelKey) {
                event.cancelBubble = true;
                if (event.stopPropagation) {
                    event.stopPropagation();
                }
                return false;
            }
            return true;
        });
    }

    function _loadReport() {
        _jquery2.default.ajax({
            type: 'GET',
            url: '/lightbox/ajax/LOAD_REPORT/' + (0, _jquery2.default)('#navigation').val() + '/',
            dataType: 'html',
            success: function success(data) {
                (0, _jquery2.default)('#report').empty().append(data);
                (0, _jquery2.default)('#report .reportTips').tooltip({
                    delay: false
                });
                (0, _jquery2.default)('#report').dialog({
                    width: 600,
                    modal: true,
                    resizable: false,
                    height: Math.round((0, _jquery2.default)(window).height() * 0.8)
                });

                return;
            }
        });
    }

    function _scid_click(event, el) {
        var compare = _utils2.default.is_ctrl_key(event);

        if (compare) {
            if ((0, _jquery2.default)('.basket_element', el).hasClass('selected')) {
                return;
            }
        } else {
            (0, _jquery2.default)('#sc_container .basket_element.selected').removeClass('selected');
            (0, _jquery2.default)('.basket_element', el).addClass('selected');
        }

        var sselcont_id = (0, _jquery2.default)('.basket_element', el).attr('id').split('_').pop();
        var ssel_id = (0, _jquery2.default)('#navigation').val();
        var url = (0, _jquery2.default)(el).attr('href');
        var container = (0, _jquery2.default)('#sc_container');

        var request = container.data('request');
        if (request && typeof request.abort === 'function') {
            request.abort();
        }

        request = _loadBasketElement(url, compare, sselcont_id);
        container.data('request', request);
    }

    function _loadBasketElement(url, compare, sselcont_id) {
        _jquery2.default.ajax({
            type: 'GET',
            url: url, //'/lightbox/ajax/LOAD_BASKET_ELEMENT/'+sselcont_id+'/',
            dataType: 'json',
            success: function success(datas) {
                var container = false;
                var data = datas;

                if (compare) {
                    container = (0, _jquery2.default)('#record_compare');
                } else {
                    container = (0, _jquery2.default)('#record_main');

                    (0, _jquery2.default)('#record_infos .lightbox_container').empty().append(data.caption);

                    (0, _jquery2.default)('#basket_infos').empty().append(data.agreement_html);
                }

                (0, _jquery2.default)('.display_id', container).empty().append(data.number);

                (0, _jquery2.default)('.title', container).empty().append(data.title).attr('title', data.title);

                var options_container = (0, _jquery2.default)('.options', container);
                options_container.empty().append(data.options_html);

                var customId = 'phraseanet-embed-lightbox-frame';
                var $template = (0, _jquery2.default)(data.preview);
                $template.attr('id', customId);
                var src = $template.attr('data-src');

                (0, _jquery2.default)('.lightbox_container', container).empty().append($template.get(0)).append(data.selector_html).append(data.note_html);

                if ((0, _jquery2.default)('.lightbox_container', container).hasClass('note_editing')) {
                    (0, _jquery2.default)('.lightbox_container', container).removeClass('note_editing');
                }

                if ($template.hasClass('documentTips')) {
                    activeThumbnailFrame = new _pym2.default.Parent(customId, src);
                    activeThumbnailFrame.iframe.setAttribute('allowfullscreen', '');
                }

                // $('.lightbox_container', container).empty()
                //     .append(data.preview + data.selector_html + data.note_html);

                _display_basket_element(compare, sselcont_id);
                (0, _jquery2.default)('.report').on('click', function () {
                    _loadReport();
                    return false;
                }).addClass('clickable');
                return;
            }
        });
    }

    function _display_basket_element(compare, sselcont_id) {
        var container;
        if (compare) {
            container = (0, _jquery2.default)('#record_compare');
        } else {
            container = (0, _jquery2.default)('#record_main');
        }
        (0, _jquery2.default)('.record_image', container).removeAttr('ondragstart');
        (0, _jquery2.default)('.record_image', container).draggable();

        var options_container = (0, _jquery2.default)('.options', container);

        (0, _jquery2.default)('.download_button', options_container).bind('click', function () {
            //		$(this).blur();
            downloadService.openModal((0, _jquery2.default)(this).next('form[name=download_form]').find('input').val());
            // _download($(this).next('form[name=download_form]').find('input').val());
        });

        (0, _jquery2.default)('.comment_button').bind('click', function () {
            //				$(this).blur();
            if ((0, _jquery2.default)('.lightbox_container', container).hasClass('note_editing')) {
                _hideNotes(container);
            } else {
                _showNotes(container);
            }
        });
        _activateNotes(container);

        (0, _jquery2.default)('.previous_button', options_container).bind('click', function () {
            //		$(this).blur();
            _getPrev();
        });

        (0, _jquery2.default)('.play_button', options_container).bind('click', function () {
            //		$(this).blur();
            _slideshow(true);
        });

        (0, _jquery2.default)('.pause_button', options_container).bind('click', function () {
            //		$(this).blur();
            _slideshow(false);
        });

        if ((0, _jquery2.default)(document).data('slideshow')) {
            (0, _jquery2.default)('.play_button, .next_button.play, .previous_button.play', options_container).hide();
            (0, _jquery2.default)('.pause_button, .next_button.pause, .previous_button.pause', options_container).show();
        } else {
            (0, _jquery2.default)('.play_button, .next_button.play, .previous_button.play', options_container).show();
            (0, _jquery2.default)('.pause_button, .next_button.pause, .previous_button.pause', options_container).hide();
        }

        (0, _jquery2.default)('.next_button', options_container).bind('click', function () {
            //		$(this).blur();
            _slideshow(false);
            _getNext();
        });

        (0, _jquery2.default)('.lightbox_container', container).bind('dblclick', function (event) {
            _displayRecord();
        });

        (0, _jquery2.default)('#record_wrapper .agree_' + sselcont_id + ', .big_box.agree').bind('click', function (event) {
            _setAgreement(event, (0, _jquery2.default)(this), sselcont_id, 1);
        }).addClass('clickable');

        (0, _jquery2.default)('#record_wrapper .disagree_' + sselcont_id + ', .big_box.disagree').bind('click', function (event) {
            _setAgreement(event, (0, _jquery2.default)(this), sselcont_id, -1);
        }).addClass('clickable');

        if (compare === (0, _jquery2.default)('#record_wrapper').hasClass('single')) {
            if (compare) {
                //      $('.agreement_selector').show();
                //			$('#record_wrapper').stop().animate({right:0},100,function(){display_record(compare);});
                (0, _jquery2.default)('#record_wrapper').css({
                    right: 0
                });
                _displayRecord(compare);
                (0, _jquery2.default)('#right_column').hide();
            } else {
                //      $('.agreement_selector').hide();
                (0, _jquery2.default)('#record_wrapper').css({
                    right: 250
                });
                _displayRecord(compare);
                (0, _jquery2.default)('#right_column').show();
                (0, _jquery2.default)('#record_compare .lightbox_container').empty();
            }
        } else {
            _displayRecord(compare);
        }
    }

    function _getPrev() {
        var current_wrapper = (0, _jquery2.default)('#sc_container .basket_element.selected').parent().parent();

        if (current_wrapper.length === 0) {
            return;
        }

        _slideshow(false);

        current_wrapper = current_wrapper.prev();
        if (current_wrapper.length === 0) {
            current_wrapper = (0, _jquery2.default)('#sc_container .basket_element_wrapper:last');
        }

        (0, _jquery2.default)('.basket_element', current_wrapper).parent().trigger('click');

        _adjust_visibility((0, _jquery2.default)('.basket_element', current_wrapper).parent());
    }

    function _getNext() {
        var current_wrapper = (0, _jquery2.default)('#sc_container .basket_element.selected').parent().parent();

        if (current_wrapper.length === 0) {
            return;
        }

        current_wrapper = current_wrapper.next();
        if (current_wrapper.length === 0) {
            current_wrapper = (0, _jquery2.default)('#sc_container .basket_element_wrapper:first');
        }

        (0, _jquery2.default)('.basket_element', current_wrapper).parent().trigger('click');

        _adjust_visibility((0, _jquery2.default)('.basket_element', current_wrapper).parent());

        if ((0, _jquery2.default)(document).data('slideshow')) {
            var timer = setTimeout(function () {
                return _getNext();
            }, 3500);
            (0, _jquery2.default)(document).data('slideshow_ctime', timer);
        }
    }

    function _slideshow(boolean_value) {
        if (boolean_value === (0, _jquery2.default)(document).data('slideshow')) {
            return;
        }

        if (!boolean_value && (0, _jquery2.default)(document).data('slideshow_ctime')) {
            clearTimeout((0, _jquery2.default)(document).data('slideshow_ctime'));
            (0, _jquery2.default)(document).data('slideshow_ctime', false);
        }

        (0, _jquery2.default)(document).data('slideshow', boolean_value);

        var headers = (0, _jquery2.default)('#record_wrapper .header');

        if (boolean_value) {
            (0, _jquery2.default)('.play_button, .next_button.play, .previous_button.play', headers).hide();
            (0, _jquery2.default)('.pause_button, .next_button.pause, .previous_button.pause', headers).show();
            _getNext();
        } else {
            (0, _jquery2.default)('.pause_button, .next_button.pause, .previous_button.pause', headers).hide();
            (0, _jquery2.default)('.play_button, .next_button.play, .previous_button.play', headers).show();
        }
    }

    function _adjust_visibility(el) {
        if (_isViewable(el)) {
            return;
        }

        var sc_wrapper = (0, _jquery2.default)('#sc_wrapper');
        var el_parent = (0, _jquery2.default)(el).parent();

        var sc_left = el_parent.position().left + el_parent.outerWidth() - sc_wrapper.width() / 2;

        sc_wrapper.stop().animate({
            scrollLeft: sc_left
        });
    }

    function _setAgreement(event, el, sselcont_id, agreeValue) {
        if (event.stopPropagation) {
            event.stopPropagation();
        }
        event.cancelBubble = true;

        var id = _jquery2.default.ajax({
            type: 'POST',
            url: '/lightbox/ajax/SET_ELEMENT_AGREEMENT/' + sselcont_id + '/',
            dataType: 'json',
            data: {
                agreement: agreeValue
            },
            success: function success(datas) {
                if (!datas.error) {
                    if (agreeValue === 1) {
                        (0, _jquery2.default)('.agree_' + sselcont_id + '').removeClass('not_decided');
                        (0, _jquery2.default)('.disagree_' + sselcont_id + '').addClass('not_decided');
                        (0, _jquery2.default)('.userchoice.me').addClass('agree').removeClass('disagree');
                    } else {
                        (0, _jquery2.default)('.agree_' + sselcont_id + '').addClass('not_decided');
                        (0, _jquery2.default)('.disagree_' + sselcont_id + '').removeClass('not_decided');
                        (0, _jquery2.default)('.userchoice.me').addClass('disagree').removeClass('agree');
                    }
                    _releasable = datas.releasable;
                    if (datas.releasable !== false) {
                        if (confirm(datas.releasable)) {
                            (0, _jquery2.default)('#basket_options .confirm_report').trigger('click');
                        }
                    }
                } else {
                    alert(datas.datas);
                }
                return;
            }
        });
    }

    function _displayRecord(compare) {
        var main_container = (0, _jquery2.default)('#record_wrapper');

        if (typeof compare === 'undefined') {
            compare = !main_container.hasClass('single');
        }

        var main_box = (0, _jquery2.default)('#record_main');
        var compare_box = (0, _jquery2.default)('#record_compare');

        var main_record = (0, _jquery2.default)('.lightbox_container .record', main_box);
        var compare_record = (0, _jquery2.default)('.lightbox_container .record', compare_box);

        var main_record_width = parseInt(main_record.attr('data-original-width'), 10);
        var main_record_height = parseInt(main_record.attr('data-original-height'), 10);
        var compare_record_width = parseInt(compare_record.attr('data-original-width'), 10);
        var compare_record_height = parseInt(compare_record.attr('data-original-height'), 10);

        var main_container_width = main_container.width();
        var main_container_innerwidth = main_container.innerWidth();
        var main_container_height = main_container.height();
        var main_container_innerheight = main_container.innerHeight();
        var smooth_image = false;
        if (compare) {
            (0, _jquery2.default)('.agreement_selector').show();
            main_container.addClass('comparison');

            var double_portrait_width = main_container_innerwidth / 2;
            var double_portrait_height = main_container_innerheight - (0, _jquery2.default)('.header', main_box).outerHeight();

            var double_paysage_width = main_container_innerwidth;
            var double_paysage_height = main_container_innerheight / 2 - (0, _jquery2.default)('.header', main_box).outerHeight();

            var main_display_portrait = _calculateDisplay(double_portrait_width, double_portrait_height, main_record_width, main_record_height);
            var main_display_paysage = _calculateDisplay(double_paysage_width, double_paysage_height, main_record_width, main_record_height);

            var compare_display_portrait = _calculateDisplay(double_portrait_width, double_portrait_height, compare_record_width, compare_record_height);
            var compare_display_paysage = _calculateDisplay(double_paysage_width, double_paysage_height, compare_record_width, compare_record_height);

            var surface_main_portrait = main_display_portrait.width * main_display_portrait.height;
            var surface_main_paysage = main_display_paysage.width * main_display_paysage.height;
            var surface_compare_portrait = compare_display_portrait.width * compare_display_portrait.height;
            var surface_compare_paysage = compare_display_paysage.width * compare_display_paysage.height;

            var double_portrait_surface = (surface_main_portrait + surface_compare_portrait) / 2;
            var double_paysage_surface = (surface_main_paysage + surface_compare_paysage) / 2;

            var m_width_image;
            var m_height_image;
            var c_width_image;
            var c_height_image;
            var dim_container;

            if (double_portrait_surface > double_paysage_surface) {
                if (!main_container.hasClass('portrait')) {
                    smooth_image = true;

                    _smoothTransform(main_box, '50%', '100%', function () {
                        _setContainerStatus('portrait');
                    });

                    compare_box.css('visibility', 'hidden');

                    _smoothTransform(compare_box, '50%', '100%', function () {
                        compare_box.css('display', 'none').css('visibility', 'visible').fadeIn();
                    });
                }
                m_width_image = main_display_portrait.width;
                m_height_image = main_display_portrait.height;
                c_width_image = compare_display_portrait.width;
                c_height_image = compare_display_portrait.height;
                dim_container = {
                    width: double_portrait_width,
                    height: double_portrait_height
                };
            } else {
                if (!main_container.hasClass('paysage')) {
                    smooth_image = true;

                    _smoothTransform(main_box, '100%', '50%', function () {
                        _setContainerStatus('paysage');
                    });

                    compare_box.css('visibility', 'hidden');

                    _smoothTransform(compare_box, '100%', '50%', function () {
                        compare_box.css('display', 'none').css('visibility', 'visible').fadeIn();
                    });
                }
                m_width_image = main_display_paysage.width;
                m_height_image = main_display_paysage.height;
                c_width_image = compare_display_paysage.width;
                c_height_image = compare_display_paysage.height;
                dim_container = {
                    width: double_paysage_width,
                    height: double_paysage_height
                };
            }

            var image_callback = _setImagePosition(false, compare_record, c_width_image, c_height_image, dim_container, function () {});
            _setImagePosition(smooth_image, main_record, m_width_image, m_height_image, dim_container, image_callback);
        } else {
            (0, _jquery2.default)('.agreement_selector').hide();
            main_container.removeClass('comparison');

            if (compare_box.is(':visible')) {
                compare_box.hide().css('visibility', 'hidden').css('display', 'block');
            }

            var main_display = _calculateDisplay(main_container_innerwidth, main_container_innerheight - (0, _jquery2.default)('.header', main_box).outerHeight(), main_record_width, main_record_height);

            if (!main_container.hasClass('single')) {
                main_box.width('100%').height('100%');

                _setContainerStatus('single');
            }
            _setImagePosition(smooth_image, main_record, main_display.width, main_display.height, {
                width: main_container_width,
                height: main_container_height - (0, _jquery2.default)('.header', main_box).outerHeight()
            });
        }
    }

    function _calculateDisplay(display_width, display_height, width, height, margin) {
        if (typeof margin === 'undefined') {
            margin = 10;
        }

        var display_ratio = display_width / display_height;
        var ratio = width / height;
        var w;
        var h;
        // landscape
        if (ratio > display_ratio) {
            w = display_width - 2 * margin;
            if (w > width) {
                w = width;
            }
            h = w / ratio;
        } else {
            h = display_height - 2 * margin;
            if (h > height) {
                h = height;
            }
            w = ratio * h;
        }

        return {
            width: w,
            height: h
        };
    }

    function _setSizeable(container) {
        (0, _jquery2.default)(container).bind('mousewheel', function (event, delta) {
            if ((0, _jquery2.default)(this).hasClass('note_editing')) {
                return;
            }

            var record = (0, _jquery2.default)('.record_image', this);

            if (record.length === 0) {
                return;
            }

            var o_top = parseInt(record.css('top'), 10);
            var o_left = parseInt(record.css('left'), 10);

            var o_width;
            var o_height;
            var width;
            var height;

            if (delta > 0) {
                if (record.width() > 29788 || record.height() >= 29788) {
                    return;
                }
                o_width = record.width();
                o_height = record.height();
                width = Math.round(o_width * 1.1);
                height = Math.round(o_height * 1.1);
            } else {
                if (record.width() < 30 || record.height() < 30) {
                    return;
                }
                o_width = record.width();
                o_height = record.height();
                width = Math.round(o_width / 1.05);
                height = Math.round(o_height / 1.05);
            }

            var top = Math.round(height / o_height * (o_top - (0, _jquery2.default)(this).height() / 2) + (0, _jquery2.default)(this).height() / 2);
            var left = Math.round(width / o_width * (o_left - (0, _jquery2.default)(this).width() / 2) + (0, _jquery2.default)(this).width() / 2);

            record.width(width).height(height).css({
                top: top,
                left: left
            });
        });
    }

    function _setImagePosition(smooth, image, width, height, container, callback) {
        var dimensions = {};

        if (typeof container !== 'undefined') {
            var c_width = container.width;
            var c_height = container.height;

            dimensions.top = parseInt((c_height - height) / 2, 10);
            dimensions.left = parseInt((c_width - width) / 2, 10);
        }
        if (typeof callback === 'undefined') {
            callback = function callback() {};
        }

        dimensions.width = width;
        dimensions.height = height;

        if (smooth) {
            (0, _jquery2.default)(image).stop().animate(dimensions, 500, callback);
        } else {
            (0, _jquery2.default)(image).css(dimensions);
            callback();
        }
    }

    function _scrollElements(boolean_value) {
        var sc_wrapper = (0, _jquery2.default)('#sc_wrapper');
        var value;
        if (boolean_value) {
            value = sc_wrapper.scrollLeft() + 400;
        } else {
            value = sc_wrapper.scrollLeft() - 400;
        }

        sc_wrapper.stop().animate({
            scrollLeft: value
        });
        return;
    }

    function _smoothTransform(box, width, height, callback) {
        if (typeof callback === 'undefined') {
            callback = function callback() {};
        }

        (0, _jquery2.default)(box).stop().animate({
            width: width,
            height: height
        }, 500, callback);
    }

    function _setContainerStatus(status) {
        (0, _jquery2.default)('#record_wrapper').removeClass('paysage portrait single').addClass(status);
    }

    function _isViewable(el) {
        var sc_wrapper = (0, _jquery2.default)('#sc_wrapper');
        var sc_container = (0, _jquery2.default)('#sc_container');

        var el_width = (0, _jquery2.default)(el).parent().width();
        var el_position = (0, _jquery2.default)(el).parent().offset();
        var sc_scroll_left = sc_wrapper.scrollLeft();

        var boundRight = sc_wrapper.width();
        var boundLeft = 0;
        var placeRight = el_position.left + el_width + sc_scroll_left;
        var placeLeft = el_position.left - sc_scroll_left;

        if (placeRight <= boundRight && placeLeft >= boundLeft) {
            return true;
        }
        return false;
    }

    function _saveNote(container, button) {
        var sselcont_id = (0, _jquery2.default)(button).attr('id').split('_').pop();
        var note = (0, _jquery2.default)('.notes_wrapper textarea', container).val();

        _jquery2.default.ajax({
            type: 'POST',
            url: '/lightbox/ajax/SET_NOTE/' + sselcont_id + '/',
            dataType: 'json',
            data: {
                note: note
            },
            success: function success(datas) {
                _hideNotes(container);
                (0, _jquery2.default)('.notes_wrapper', container).remove();
                (0, _jquery2.default)('.lightbox_container', container).append(datas.datas);
                _activateNotes(container);
                return;
            }
        });
    }

    function _activateNotes(container) {
        (0, _jquery2.default)('.note_closer', container).button({
            text: true
        }).bind('click', function () {
            (0, _jquery2.default)(this).blur();
            _hideNotes(container);
            return false;
        });

        (0, _jquery2.default)('.note_saver', container).button({
            text: true
        }).bind('click', function () {
            (0, _jquery2.default)(this).blur();
            _saveNote(container, this);
            return false;
        });
    }

    function _showNotes(container) {
        (0, _jquery2.default)('.notes_wrapper', container).animate({
            top: 0
        });
        (0, _jquery2.default)('.lightbox_container', container).addClass('note_editing');
    }

    function _hideNotes(container) {
        (0, _jquery2.default)('.notes_wrapper', container).animate({
            top: '-100%'
        });
        (0, _jquery2.default)('.lightbox_container', container).removeClass('note_editing');
    }

    /*Get status before send validation*/
    function _getReseaseStatus(el) {
        _jquery2.default.ajax({
            url: '/lightbox/ajax/GET_ELEMENTS/' + (0, _jquery2.default)('#navigation').val() + '/',
            dataType: 'json',
            error: function error(data) {
                (0, _jquery2.default)('.loader', el).css({
                    visibility: 'hidden'
                });
            },
            timeout: function timeout(data) {
                (0, _jquery2.default)('.loader', el).css({
                    visibility: 'hidden'
                });
            },
            success: function success(data) {
                (0, _jquery2.default)('.loader', el).css({
                    visibility: 'hidden'
                });
                if (data.datas) {
                    if (data.datas) {
                        if (data.datas.counts.nul == 0) {
                            _setRelease((0, _jquery2.default)(this));
                        } else {
                            console.log(data.datas.counts);
                            (0, _jquery2.default)("#FeedbackRelease .record_accepted").html(data.datas.counts.yes);
                            (0, _jquery2.default)("#FeedbackRelease .record_refused").html(data.datas.counts.no);
                            (0, _jquery2.default)("#FeedbackRelease .record_null").html(data.datas.counts.nul);
                            (0, _jquery2.default)("#FeedbackRelease").modal("show");
                        }
                    }
                }
                if (!data.error) {
                    _releasable = false;
                }

                return;
            }
        });
    }

    function _setRelease(el) {
        (0, _jquery2.default)('.loader', el).css({
            visibility: 'visible'
        });
        _jquery2.default.ajax({
            type: 'POST',
            url: '/lightbox/ajax/SET_RELEASE/' + (0, _jquery2.default)('#navigation').val() + '/',
            dataType: 'json',
            error: function error(data) {
                (0, _jquery2.default)('.loader', el).css({
                    visibility: 'hidden'
                });
            },
            timeout: function timeout(data) {
                (0, _jquery2.default)('.loader', el).css({
                    visibility: 'hidden'
                });
            },
            success: function success(data) {
                (0, _jquery2.default)('.loader', el).css({
                    visibility: 'hidden'
                });
                if (data.datas) {
                    alert(data.datas);
                }
                if (!data.error) {
                    _releasable = false;
                }

                return;
            }
        });
    }

    return {
        initialize: initialize,
        setReleasable: setReleasable
    };
};

exports.default = lightbox;

/***/ }),

/***/ 257:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

var _dialog = __webpack_require__(1);

var _dialog2 = _interopRequireDefault(_dialog);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var humane = __webpack_require__(9);

var download = function download(services) {
    var configService = services.configService,
        localeService = services.localeService,
        appEvents = services.appEvents;

    var url = configService.get('baseUrl');
    var $container = null;

    var initialize = function initialize(options) {
        $container = options.$container;
        $container.on('click', '.basket_downloader', function (event) {
            event.preventDefault();
            _downloadBasket();
        });
    };
    var openModal = function openModal(datas) {
        (0, _jquery2.default)('body').addClass('dialog-open');
        var $dialog = _dialog2.default.create(services, {
            size: 'Medium',
            title: localeService.t('export')
        });

        (0, _jquery2.default)('#DIALOG1').on('dialogclose', function (event) {
            (0, _jquery2.default)('body').removeClass('dialog-open');
        });

        _jquery2.default.ajax({
            type: 'POST',
            data: 'lst=' + datas,
            url: url + 'prod/export/multi-export/',
            success: function success(data) {
                $dialog.setContent(data);
                _onDownloadReady($dialog, window.exportConfig);
            }
        });

        return true;
    };

    var _onDownloadReady = function _onDownloadReady($dialog, dataConfig) {
        (0, _jquery2.default)('.tabs', $dialog.getDomElement()).tabs();

        (0, _jquery2.default)('.close_button', $dialog.getDomElement()).bind('click', function () {
            $dialog.close();
        });

        var tabs = (0, _jquery2.default)('.tabs', $dialog.getDomElement());

        if (dataConfig.haveFtp === true) {
            (0, _jquery2.default)('#ftp_form_selector').bind('change', function () {
                (0, _jquery2.default)('#ftp .ftp_form').hide();
                (0, _jquery2.default)('#ftp .ftp_form_' + (0, _jquery2.default)(this).val()).show();
                (0, _jquery2.default)('.ftp_folder_check', _dialog2.default.get(1).getDomElement()).unbind('change').bind('change', function () {
                    if ((0, _jquery2.default)(this).prop('checked')) {
                        (0, _jquery2.default)(this).next().prop('disabled', false);
                    } else {
                        (0, _jquery2.default)(this).next().prop('disabled', true);
                    }
                });
            }).trigger('change');
        }

        (0, _jquery2.default)('a.TOUview').bind('click', function (event) {
            event.preventDefault();
            var $el = (0, _jquery2.default)(event.currentTarget);
            var options = {
                size: 'Medium',
                closeButton: true,
                title: dataConfig.msg.termOfUseTitle
            };

            var termOfuseDialog = _dialog2.default.create(services, options, 2);

            _jquery2.default.get($el.attr('href'), function (content) {
                termOfuseDialog.setContent(content);
            });
        });

        (0, _jquery2.default)('.close_button').bind('click', function () {
            $dialog.close();
        });

        (0, _jquery2.default)('#download .download_button').bind('click', function () {
            if (!check_subdefs((0, _jquery2.default)('#download'), dataConfig)) {
                return false;
            }

            if (!check_TOU((0, _jquery2.default)('#download'), dataConfig)) {
                return false;
            }

            var total = 0;
            var count = 0;

            (0, _jquery2.default)('input[name="obj[]"]', (0, _jquery2.default)('#download')).each(function () {
                var total_el = (0, _jquery2.default)('#download input[name=download_' + (0, _jquery2.default)(this).val() + ']');
                var count_el = (0, _jquery2.default)('#download input[name=count_' + (0, _jquery2.default)(this).val() + ']');
                if ((0, _jquery2.default)(this).prop('checked')) {
                    total += parseInt((0, _jquery2.default)(total_el).val(), 10);
                    count += parseInt((0, _jquery2.default)(count_el).val(), 10);
                }
            });

            if (count > 1 && total / 1024 / 1024 > dataConfig.maxDownload) {
                if (confirm(dataConfig.msg.fileTooLarge + ' \n ' + dataConfig.msg.fileTooLargeAlt)) {
                    (0, _jquery2.default)('input[name="obj[]"]:checked', (0, _jquery2.default)('#download')).each(function (i, n) {
                        (0, _jquery2.default)('input[name="obj[]"][value="' + (0, _jquery2.default)(n).val() + '"]', (0, _jquery2.default)('#sendmail')).prop('checked', true);
                    });
                    (0, _jquery2.default)(document).find('input[name="taglistdestmail"]').tagsinput('add', dataConfig.user.email);

                    var tabs = (0, _jquery2.default)('.tabs', $dialog.getDomElement());
                    tabs.tabs('option', 'active', 1);
                }

                return false;
            }
            (0, _jquery2.default)('#download form').submit();
            $dialog.close();
        });

        (0, _jquery2.default)('#order .order_button').bind('click', function () {
            var title = '';
            if (!check_TOU((0, _jquery2.default)('#order'), dataConfig)) {
                return false;
            }

            (0, _jquery2.default)('#order .order_button_loader').css('visibility', 'visible');

            var options = (0, _jquery2.default)('#order form').serialize();

            var $this = (0, _jquery2.default)(this);
            $this.prop('disabled', true).addClass('disabled');
            _jquery2.default.post(url + 'prod/order/', options, function (data) {
                $this.prop('disabled', false).removeClass('disabled');

                (0, _jquery2.default)('#order .order_button_loader').css('visibility', 'hidden');

                if (!data.error) {
                    title = dataConfig.msg.success;
                } else {
                    title = dataConfig.msg.warning;
                }

                var options = {
                    size: 'Alert',
                    closeButton: true,
                    title: title
                };

                _dialog2.default.create(services, options, 2).setContent(data.msg);

                if (!data.error) {
                    showHumane(data.msg);

                    $dialog.close();
                } else {
                    alert(data.msg);
                }

                return;
            }, 'json');
        });

        (0, _jquery2.default)('#ftp .ftp_button').bind('click', function () {
            if (!check_subdefs((0, _jquery2.default)('#ftp'), dataConfig)) {
                return false;
            }

            if (!check_TOU((0, _jquery2.default)('#ftp'), dataConfig)) {
                return false;
            }

            (0, _jquery2.default)('#ftp .ftp_button_loader').show();

            (0, _jquery2.default)('#ftp .ftp_form:hidden').remove();

            var $this = (0, _jquery2.default)(this);

            var options_addr = (0, _jquery2.default)('#ftp_form_stock form:visible').serialize();
            var options_join = (0, _jquery2.default)('#ftp_joined').serialize();

            $this.prop('disabled', true);
            _jquery2.default.post(url + 'prod/export/ftp/', options_addr + '&' + options_join, function (data) {
                $this.prop('disabled', false);
                (0, _jquery2.default)('#ftp .ftp_button_loader').hide();

                if (data.success) {
                    showHumane(data.message);
                    $dialog.close();
                } else {
                    var alert = _dialog2.default.create(services, {
                        size: 'Alert',
                        closeOnEscape: true,
                        closeButton: true,
                        title: dataConfig.msg.warning
                    }, 2);

                    alert.setContent(data.message);
                }
                return;
            }, 'json');
        });

        (0, _jquery2.default)('#ftp .tryftp_button').bind('click', function () {
            (0, _jquery2.default)('#ftp .tryftp_button_loader').css('visibility', 'visible');
            var $this = (0, _jquery2.default)(this);
            $this.prop('disabled', true);
            var options_addr = (0, _jquery2.default)('#ftp_form_stock form:visible').serialize();

            _jquery2.default.post(url + 'prod/export/ftp/test/',
            // no need to include 'ftp_joined' checkboxes to test ftp
            options_addr, function (data) {
                (0, _jquery2.default)('#ftp .tryftp_button_loader').css('visibility', 'hidden');

                var options = {
                    size: 'Alert',
                    closeButton: true,
                    title: data.success ? dataConfig.msg.success : dataConfig.msg.warning
                };

                _dialog2.default.create(services, options, 3).setContent(data.message);

                $this.prop('disabled', false);

                return;
            });
        });

        function showHumane(data) {
            (0, _jquery2.default)('body').append('<div class="humane humane-libnotify-info">Email sending request submitted </div>');
            (0, _jquery2.default)('body').find('.humane-libnotify-info').html(data);
            setTimeout(hideHumane, 3000);
        }
        function hideHumane() {
            (0, _jquery2.default)('body').find('.humane').remove();
        }
        (0, _jquery2.default)('#sendmail .sendmail_button').bind('click', function () {
            if (!validEmail((0, _jquery2.default)('input[name="taglistdestmail"]', (0, _jquery2.default)('#sendmail')).val(), dataConfig)) {
                return false;
            }

            if (!check_subdefs((0, _jquery2.default)('#sendmail'), dataConfig)) {
                return false;
            }

            if (!check_TOU((0, _jquery2.default)('#sendmail'), dataConfig)) {
                return false;
            }

            if ((0, _jquery2.default)('iframe[name=""]').length === 0) {
                (0, _jquery2.default)('body').append('<iframe style="display:none;" name="sendmail_target"></iframe>');
            }

            (0, _jquery2.default)('#sendmail form').submit();
            showHumane((0, _jquery2.default)('#export-send-mail-notif').val());
            $dialog.close();
        });

        (0, _jquery2.default)('.datepicker', $dialog.getDomElement()).datepicker({
            changeYear: true,
            changeMonth: true,
            dateFormat: 'yy-mm-dd'
        });

        (0, _jquery2.default)('a.undisposable_link', $dialog.getDomElement()).bind('click', function () {
            (0, _jquery2.default)(this).parent().parent().find('.undisposable').slideToggle();
            return false;
        });

        (0, _jquery2.default)('input[name="obj[]"]', (0, _jquery2.default)('#download, #sendmail, #ftp')).bind('change', function () {
            var $form = (0, _jquery2.default)(this).closest('form');

            if ((0, _jquery2.default)('input.caption[name="obj[]"]:checked', $form).length > 0) {
                (0, _jquery2.default)('div.businessfields', $form).show();
            } else {
                (0, _jquery2.default)('div.businessfields', $form).hide();
            }
        });
    };

    function validateEmail(email) {
        var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(email);
    }

    function validEmail(emailList, dataConfig) {
        //split emailList by ; , or whitespace and filter empty element
        var emails = emailList.split(/[ ,;]+/).filter(Boolean);
        var alert = void 0;
        for (var i = 0; i < emails.length; i++) {
            if (!validateEmail(emails[i])) {

                alert = _dialog2.default.create(services, {
                    size: 'Alert',
                    closeOnEscape: true,
                    closeButton: true,
                    title: dataConfig.msg.warning
                }, 2);

                alert.setContent(dataConfig.msg.invalidEmail);
                return false;
            }
        }
        return true;
    }

    function check_TOU(container, dataConfig) {
        var checkbox = (0, _jquery2.default)('input[name="TOU_accept"]', (0, _jquery2.default)(container));
        var go = checkbox.length === 0 || checkbox.prop('checked');
        var alert = void 0;
        if (!go) {
            alert = _dialog2.default.create(services, {
                size: 'Small',
                closeOnEscape: true,
                closeButton: true,
                title: dataConfig.msg.warning
            }, 2);

            alert.setContent(dataConfig.msg.termOfUseAgree);

            return false;
        }
        return true;
    }

    function check_subdefs(container, dataConfig) {
        var go = false;
        var required = false;
        var alert = void 0;

        (0, _jquery2.default)('input[name="obj[]"]', (0, _jquery2.default)(container)).each(function () {
            if ((0, _jquery2.default)(this).prop('checked')) {
                go = true;
            }
        });

        (0, _jquery2.default)('input.required, textarea.required', container).each(function (i, n) {
            if (_jquery2.default.trim((0, _jquery2.default)(n).val()) === '') {
                required = true;
                (0, _jquery2.default)(n).addClass('error');
            } else {
                (0, _jquery2.default)(n).removeClass('error');
            }
        });

        if (required) {
            alert = _dialog2.default.create(services, {
                size: 'Alert',
                closeOnEscape: true,
                closeButton: true,
                title: dataConfig.msg.warning
            }, 2);

            alert.setContent(dataConfig.msg.requiredFields);

            return false;
        }
        if (!go) {
            alert = _dialog2.default.create(services, {
                size: 'Alert',
                closeOnEscape: true,
                closeButton: true,
                title: dataConfig.msg.warning
            }, 2);

            alert.setContent(dataConfig.msg.missingSubdef);

            return false;
        }

        return true;
    }

    function _downloadBasket() {
        var ids = _jquery2.default.map((0, _jquery2.default)('#sc_container .download_form').toArray(), function (el, i) {
            return (0, _jquery2.default)('input[name="basrec"]', (0, _jquery2.default)(el)).val();
        });
        openModal(ids.join(';'));
    }

    /*function download(value) {
        var $dialog = dialog.create({title: localeService.t('export')});
         $.post('/prod/export/multi-export/', 'lst=' + value, function (data) {
             $dialog.setContent(data);
             $('.tabs', $dialog.getDomElement()).tabs();
             $('.close_button', $dialog.getDomElement()).bind('click', function () {
                $dialog.close();
            });
             return false;
        });
    }*/

    return { initialize: initialize, openModal: openModal };
};

exports.default = download;

/***/ })

},[253]);
});