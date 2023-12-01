webpackJsonpapp([1],{

/***/ 105:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _underscore = __webpack_require__(1);

var _underscore2 = _interopRequireDefault(_underscore);

var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

var _index = __webpack_require__(399);

var _index2 = _interopRequireDefault(_index);

var _video = __webpack_require__(12);

var _video2 = _interopRequireDefault(_video);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var hotkeys = __webpack_require__(107);

// require('video.js').default;

var rangeCapture = function rangeCapture(services, datas) {
    var activeTab = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
    var configService = services.configService,
        localeService = services.localeService,
        appEvents = services.appEvents;

    var $container = null;
    var initData = {};
    var options = {};
    var defaultOptions = {
        playbackRates: [],
        fluid: true,
        controlBar: {
            muteToggle: false
        },
        baseUrl: configService.get('baseUrl')
    };
    var videoPlayer = void 0;
    var initialize = function initialize(params, userOptions) {
        //{$container} = params;
        $container = params.$container;
        initData = params.data;
        options = _underscore2.default.extend(defaultOptions, userOptions, { $container: $container });
        dispose();
        render(initData);
    };

    var render = function render(initData) {
        var record = initData.records[0];
        if (record.type !== 'video') {
            return;
        }
        options.frameRates = {};
        options.ratios = {};
        var generateSourcesTpl = function generateSourcesTpl(record) {
            var recordSources = [];
            _underscore2.default.each(record.sources, function (s, i) {
                recordSources.push('<source src="' + s.src + '" type="' + s.type + '" data-frame-rate="' + s.framerate + '">');
                options.frameRates[s.src] = s.framerate;
                options.ratios[s.src] = s.ratio;
            });

            return recordSources.join(' ');
        };

        var sources = generateSourcesTpl(record);
        $container.append('<video id="embed-video" class="embed-resource video-js vjs-default-skin vjs-big-play-centered" controls\n               preload="none" width="100%" height="100%"  data-setup=\'{"language":"' + localeService.getLocale() + '"}\'>' + sources + ' </video>');

        // window.videojs = videojs;
        _video2.default.addLanguage(localeService.getLocale(), localeService.getTranslations());
        videoPlayer = (0, _video2.default)('embed-video', options, function () {});
        //group video elements together
        videoPlayer.rangeCapturePlugin(options);
        (0, _jquery2.default)(videoPlayer.el_).children().not('.range-item-container').wrapAll('<div class="video-player-container"></div>');
        videoPlayer.ready(function () {
            var hotkeyOptions = _underscore2.default.extend({
                alwaysCaptureHotkeys: true,
                enableNumbers: false,
                enableVolumeScroll: false,
                volumeStep: 0.1,
                seekStep: 1,
                customKeys: videoPlayer.getRangeCaptureHotkeys()
            }, videoPlayer.getRangeCaptureOverridedHotkeys());

            videoPlayer.hotkeys(hotkeyOptions);
        });
    };

    var dispose = function dispose() {
        try {
            if (_video2.default.getPlayers()['embed-video']) {
                delete _video2.default.getPlayers()['embed-video'];
            }
        } catch (e) {}
    };

    var getPlayer = function getPlayer() {
        return videoPlayer;
    };

    return { initialize: initialize, getPlayer: getPlayer };
};

exports.default = rangeCapture;

/***/ }),

/***/ 363:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});
var formatMilliseconds = function formatMilliseconds(currentTime, frameRate) {
    var hours = 0;
    var minutes = 0;
    var seconds = 0;
    var currentFrames = 0;
    if (currentTime > 0) {
        hours = Math.floor(currentTime / 3600);
        var s = currentTime - hours * 3600;
        minutes = Math.floor(s / 60);
        seconds = Math.floor(s - minutes * 60);
        var currentRest = currentTime - Math.floor(currentTime);
        currentFrames = Math.round(frameRate * currentRest);
    }
    return {
        hours: hours,
        minutes: minutes,
        seconds: seconds,
        frames: currentFrames
    };
};

var formatTime = function formatTime(currentTime, format, frameRate) {
    frameRate = frameRate || 24;
    var hours = 0;
    var minutes = 0;
    var seconds = 0;
    var milliseconds = 0;
    var frames = 0;
    if (currentTime > 0) {
        hours = Math.floor(currentTime / 3600);
        var s = currentTime - hours * 3600;
        minutes = Math.floor(s / 60);
        seconds = Math.floor(s - minutes * 60);
        // keep only milliseconds rest ()
        milliseconds = (currentTime - Math.floor(currentTime)).toFixed(3);
        frames = Math.round(frameRate * milliseconds);
        // if( currentFrames >= )
    }
    switch (format) {
        // standard vtt format
        case 'hh:mm:ss.mmm':
            return ('0' + hours).slice(-2) + ':' + ('0' + minutes).slice(-2) + ':' + ('0' + seconds).slice(-2) + '.' + ('00' + milliseconds).slice(-3) + '';
        case 'hms':
            var formatedOutput = [];
            if (hours > 0) {
                formatedOutput.push(('0' + hours).slice(-2) + 'h');
            }

            formatedOutput.push(('0' + minutes).slice(-2) + 'm');
            formatedOutput.push(('0' + seconds).slice(-2) + 's');

            return formatedOutput.join(' ');
        case '':
        default:
            return ('0' + hours).slice(-2) + ':' + ('0' + minutes).slice(-2) + ':' + ('0' + seconds).slice(-2) + 's ' + ('0' + frames).slice(-2) + 'f';

    }
};

var formatToFixedDecimals = function formatToFixedDecimals(currentTime) {
    var decimalsPoints = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 2;

    return parseFloat(currentTime.toFixed(decimalsPoints));
};

exports.formatMilliseconds = formatMilliseconds;
exports.formatTime = formatTime;
exports.formatToFixedDecimals = formatToFixedDecimals;

/***/ }),

/***/ 373:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _video = __webpack_require__(12);

var _video2 = _interopRequireDefault(_video);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

/**
 * VideoJs Hotkeys Modal
 */

var ModalDialog = _video2.default.getComponent('ModalDialog');

var HotkeyModal = function (_ModalDialog) {
    _inherits(HotkeyModal, _ModalDialog);

    function HotkeyModal(player, settings) {
        _classCallCheck(this, HotkeyModal);

        var _this = _possibleConstructorReturn(this, (HotkeyModal.__proto__ || Object.getPrototypeOf(HotkeyModal)).call(this, player, settings));

        _this.modalTemplate = function () {
            return '<div class="vjs-hotkeys-modal video-tools-help"><h1>' + _this.player_.localize('Keyboard shortcuts') + '</h1>\n            <dl class="dl-horizontal">\n            <dt>' + _this.player_.localize('Play') + '</dt><dd><span class="shortcut-label">' + _this.player_.localize('Space bar') + '</span> ' + _this.player_.localize('or') + ' <span class="shortcut-label">L</span></dd>\n            <dt>' + _this.player_.localize('Change play speed') + '</dt><dd><span class="shortcut-label">L</span> &nbsp;...&nbsp; <span class="shortcut-label">L</span> <span class="shortcut-label">L</span> &nbsp;...&nbsp; <span class="shortcut-label">L</span> <span class="shortcut-label">L</span> <span class="shortcut-label">L</span> ...</dd>\n            <dt>' + _this.player_.localize('Pause') + '</dt><dd><span class="shortcut-label">' + _this.player_.localize('Space bar') + '</span> ' + _this.player_.localize('or') + ' <span class="shortcut-label">K</span></dd>\n            <dt>' + _this.player_.localize('One frame forward') + '</dt><dd><span class="shortcut-label">&gt;</span></dd>\n            <dt>' + _this.player_.localize('One frame backward') + '</dt><dd><span class="shortcut-label">&lt;</span></dd>\n            <dt>' + _this.player_.localize('Add an entry point') + '</dt><dd><span class="shortcut-label">I</span></dd>\n            <dt>' + _this.player_.localize('Add an end point') + '</dt><dd><span class="shortcut-label">O</span></dd>\n            <dt>' + _this.player_.localize('Navigate to entry point') + '</dt><dd><span class="shortcut-label">' + _this.player_.localize('Shift') + '</span> + <span class="shortcut-label">I</span></dd>\n            <dt>' + _this.player_.localize('Navigate to end point') + '</dt><dd><span class="shortcut-label">' + _this.player_.localize('Shift') + '</span> + <span class="shortcut-label">O</span></dd>\n            <dt>' + _this.player_.localize('Add new range') + '</dt><dd><span class="shortcut-label">' + _this.player_.localize('Ctrl') + '</span> + <span class="shortcut-label">N</span> ' + _this.player_.localize('or') + '  <span class="shortcut-label">' + _this.player_.localize('Shift') + '</span> + <span class="shortcut-label">+</span></dd>\n            <dt>' + _this.player_.localize('Delete current') + '</dt><dd><span class="shortcut-label">' + _this.player_.localize('Shift') + '</span> + <span class="shortcut-label">' + _this.player_.localize('Suppr') + '</span></dd>\n            <dt>' + _this.player_.localize('Toggle loop') + '</dt><dd><span class="shortcut-label">' + _this.player_.localize('Ctrl') + '</span> + <span class="shortcut-label">L</span></dd>\n            <dt>' + _this.player_.localize('Go 1 frame backward') + '</dt><dd><span class="shortcut-label">' + _this.player_.localize('Ctrl') + '</span> + <span class="shortcut-label">&larr;</span></dd>\n            <dt>' + _this.player_.localize('Go 1 frame forward') + '</dt><dd><span class="shortcut-label">' + _this.player_.localize('Ctrl') + '</span> + <span class="shortcut-label">&rarr;</span></dd>\n            <dt>' + _this.player_.localize('Move up range') + '</dt><dd><span class="shortcut-label">&uarr;</span></dd>\n            <dt>' + _this.player_.localize('Move down range') + '</dt><dd><span class="shortcut-label">&darr;</span></dd>\n            </dl>\n            </div>';
        };

        return _this;
    }

    _createClass(HotkeyModal, [{
        key: 'initialize',
        value: function initialize() {
            var domTpl = document.createElement('div');
            domTpl.innerHTML = this.modalTemplate();
            this.fillWith(domTpl);
        }
    }]);

    return HotkeyModal;
}(ModalDialog);

_video2.default.registerComponent('HotkeyModal', HotkeyModal);

exports.default = HotkeyModal;

/***/ }),

/***/ 374:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _get = function get(object, property, receiver) { if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { return get(parent, property, receiver); } } else if ("value" in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } };

var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

var _underscore = __webpack_require__(1);

var _underscore2 = _interopRequireDefault(_underscore);

var _video = __webpack_require__(12);

var _video2 = _interopRequireDefault(_video);

var _rangeItem = __webpack_require__(403);

var _rangeItem2 = _interopRequireDefault(_rangeItem);

var _utils = __webpack_require__(363);

var _alert = __webpack_require__(48);

var _alert2 = _interopRequireDefault(_alert);

var _dialog = __webpack_require__(2);

var _dialog2 = _interopRequireDefault(_dialog);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var humane = __webpack_require__(8);

/**
 * VideoJs Range Collection
 */
var Component = _video2.default.getComponent('Component');

var RangeCollection = function (_Component) {
    _inherits(RangeCollection, _Component);

    function RangeCollection(player, settings) {
        _classCallCheck(this, RangeCollection);

        var _this = _possibleConstructorReturn(this, (RangeCollection.__proto__ || Object.getPrototypeOf(RangeCollection)).call(this, player, settings));

        _this.uid = 0;
        _this.defaultRange = {
            startPosition: -1,
            endPosition: -1,
            title: '',
            handlePositions: [],
            selected: false,
            image: {
                src: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFkAAAAyCAYAAAA3OHc2AAAAuElEQVR4Xu3UwQkAIAwEwdh/0Qr2kH2NBWRhODwzc8dbFTiQV33/ccj7xpADY8iQC4Gg4U+GHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgECUuGHAgEiQftoTIBhrHr1wAAAABJRU5ErkJggg==',
                width: 89,
                height: 50
            },
            manualSnapShot: false
        };
        _this.rangeCollection = [];
        _this.rangeItemComponentCollection = [];
        _this.currentRange = false;
        _this.isHoverChapterSelected = false;

        _this.exportRanges = function () {
            var exportedRanges = [];
            for (var i = 0; i < _this.rangeCollection.length; i++) {
                exportedRanges.push({
                    startPosition: _this.rangeCollection[i].startPosition,
                    endPosition: _this.rangeCollection[i].endPosition
                });
            }
            return exportedRanges;
        };

        _this.exportVttRanges = function () {
            var exportedRanges = ['WEBVTT\n'];
            var titleValue = document.getElementById("default-video-chapter-label").value;
            for (var i = 0; i < _this.rangeCollection.length; i++) {
                var exportableData = {
                    title: _this.rangeCollection[i].title != "" ? _this.rangeCollection[i].title : titleValue
                };

                if (_this.rangeCollection[i].image.src !== '') {
                    exportableData.image = _this.rangeCollection[i].image.src;
                    exportableData.manualSnapShot = _this.rangeCollection[i].manualSnapShot || false;
                }

                exportedRanges.push(i + 1 + '\n' + (0, _utils.formatTime)(_this.rangeCollection[i].startPosition, 'hh:mm:ss.mmm') + ' --> ' + (0, _utils.formatTime)(_this.rangeCollection[i].endPosition, 'hh:mm:ss.mmm') + '\n' + JSON.stringify(exportableData) + '\n');
            }
            return exportedRanges.join('\n');
        };

        _this.get = function (model) {
            if (model === undefined) {
                return _this.rangeCollection;
            }
            return _this.getRangeById(model.id);
        };

        _this.splice = function () {
            for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
                args[_key] = arguments[_key];
            }

            return Array.prototype.splice.apply(_this.rangeCollection, args);
        };

        _this.getIndex = function (model) {
            var index = {};
            for (var i = 0; i < _this.rangeCollection.length; i++) {
                if (_this.rangeCollection[i].id === model.id) {
                    index = i;
                }
            }
            return index;
        };

        _this.getSelection = function () {
            var selectedRanges = [];
            for (var i = 0; i < _this.rangeCollection.length; i++) {
                if (_this.rangeCollection[i].selected === true) {
                    selectedRanges.push(_this.rangeCollection[i]);
                }
            }
            return selectedRanges;
        };

        _this.resetSelection = function () {
            for (var i = 0; i < _this.rangeCollection.length; i++) {
                _this.rangeCollection[i].selected = false;
            }
        };

        _this.addToSelection = function (model) {
            for (var i = 0; i < _this.rangeCollection.length; i++) {
                if (_this.rangeCollection[i].id === model.id) {
                    _this.rangeCollection[i].selected = true;
                }
            }
        };

        _this.removeFromSelection = function (model) {
            for (var i = 0; i < _this.rangeCollection.length; i++) {
                if (_this.rangeCollection[i].id === model.id) {
                    _this.rangeCollection[i].selected = false;
                }
            }
        };

        _this.getFirstSelected = function () {
            var firstModel = false;
            for (var i = 0; i < _this.rangeCollection.length; i++) {
                if (_this.rangeCollection[i].selected === true && firstModel === false) {
                    firstModel = _this.rangeCollection[i];
                }
            }
            return firstModel;
        };

        _this.getLastSelected = function () {
            var lastModel = false;
            for (var i = 0; i < _this.rangeCollection.length; i++) {
                if (_this.rangeCollection[i].selected === true) {
                    lastModel = _this.rangeCollection[i];
                }
            }
            return lastModel;
        };

        _this.reset = function (collection) {
            _this.rangeCollection = collection;
            // refresh internal indexes:
            for (var i = 0; i < _this.rangeCollection.length; i++) {
                _this.rangeCollection[i].index = i;
            }
            _this.refreshRangeCollection();
        };

        _this.setActiveRange = function (direction) {
            if (_this.currentRange === false) {
                return;
            }
            var toIndex = _this.currentRange.index - 1;

            if (direction === 'down') {
                toIndex = _this.currentRange.index + 1;
            }

            if (_this.rangeCollection[toIndex] !== undefined) {

                _this.player_.rangeStream.onNext({
                    action: 'change',
                    range: _this.rangeCollection[toIndex]
                });
            }
        };

        _this.moveRange = function (direction) {
            if (_this.currentRange === false) {
                return;
            }
            var collection = _this.get();
            var toIndex = _this.currentRange.index - 1;

            if (direction === 'down') {
                toIndex = _this.currentRange.index + 1;
            }
            _this.addToSelection(_this.currentRange);
            var selectedModels = _this.getSelection();

            for (var i = selectedModels.length; i--;) {
                var fromIndex = _this.getIndex(_this.get(selectedModels[i]));

                collection.splice(toIndex, 0, _this.splice(fromIndex, 1)[0]);
            }

            _this.reset(collection);
        };

        _this.refreshRangeCollection = function () {
            // remove any existing items
            for (var i = 0; i < _this.rangeItemComponentCollection.length; i++) {
                _this.rangeItemComponentCollection[i].dispose();
                _this.removeChild(_this.rangeItemComponentCollection[i]);
            }
            _this.rangeItemComponentCollection = [];

            var activeId = 0;
            if (_this.currentRange !== false) {
                activeId = _this.currentRange.id;
            }

            for (var _i = 0; _i < _this.rangeCollection.length; _i++) {
                var model = _underscore2.default.extend({}, _this.rangeCollection[_i], { index: _i });
                var item = new _rangeItem2.default(_this.player_, {
                    model: model,
                    collection: _this,
                    isActive: _this.rangeCollection[_i].id === activeId ? true : false
                }, _this.settings);

                _this.rangeItemComponentCollection.push(item);
                _this.addChild(item);
            }
        };

        _this.exportRangesData = function (rangeData) {
            var title = _this.settings.translations.alertTitle;
            var message = _this.settings.translations.updateTitle;
            var services = _this.settings.services;
            _jquery2.default.ajax({
                type: 'POST',
                url: _this.settings.baseUrl + 'prod/tools/metadata/save/',
                data: {
                    databox_id: _this.settings.databoxId,
                    record_id: _this.settings.recordId,
                    meta_struct_id: _this.settings.meta_struct_id,
                    value: rangeData
                },
                success: function success(data) {
                    if (!data.success) {
                        humane.error(data.message);
                    } else {
                        humane.info(message);
                    }
                }
            });
        };

        _this.settings = settings;
        _this.$el = _this.renderElContent();

        _this.player_.activeRangeStream.subscribe(function (params) {
            _this.currentRange = params.activeRange;
            _this.refreshRangeCollection();
        });

        _this.isHoverChapterSelected = settings.preferences.overlapChapters == 1 ? true : false;
        return _this;
    }

    _createClass(RangeCollection, [{
        key: 'initDefaultRange',
        value: function initDefaultRange() {
            // init collection with a new range if nothing specified:
            var newRange = this.addRange(this.defaultRange);
            this.player_.rangeStream.onNext({
                action: 'create',
                range: newRange
            });
        }
    }, {
        key: 'addRangeEvent',
        value: function addRangeEvent() {
            var newRange = this.addNewRange(this.defaultRange);
            this.player_.rangeStream.onNext({
                action: 'create',
                range: newRange
            });
        }
    }, {
        key: 'exportRangeEvent',
        value: function exportRangeEvent() {
            this.player_.rangeStream.onNext({
                action: 'export-ranges',
                ranges: this.exportRanges()
            });
        }
    }, {
        key: 'exportVTTRangeEvent',
        value: function exportVTTRangeEvent() {
            this.player_.rangeStream.onNext({
                action: 'export-vtt-ranges',
                data: this.exportVttRanges()
            });
        }
    }, {
        key: 'setHoverChapter',
        value: function setHoverChapter(isChecked) {
            this.isHoverChapterSelected = isChecked;
            this.player_.rangeStream.onNext({

                action: 'saveRangeCollectionPref',

                data: isChecked

            });
        }

        /**
         * Create the component's DOM element
         *
         * @return {Element}
         * @method createEl
         */

    }, {
        key: 'createEl',
        value: function createEl() {
            return _get(RangeCollection.prototype.__proto__ || Object.getPrototypeOf(RangeCollection.prototype), 'createEl', this).call(this, 'div', {
                className: 'range-collection-container',
                innerHTML: ''
            });
        }
    }, {
        key: 'renderElContent',
        value: function renderElContent() {
            return (0, _jquery2.default)(this.el());
        }
    }, {
        key: 'update',
        value: function update(range) {
            var updatedRange = void 0;

            if (!this.isExist(range)) {
                updatedRange = this.addNewRange(range);
            } else {
                updatedRange = this.updateRange(range);
            }
            return updatedRange;
        }
    }, {
        key: 'updatingByDragging',
        value: function updatingByDragging(range) {
            if (this.isHoverChapterSelected) {

                this.syncRange(range);
            } else {

                this.updateRange(range);
            }
        }
    }, {
        key: 'isExist',
        value: function isExist(range) {
            if (range.id === undefined) {
                return false;
            }
            for (var i = 0; i < this.rangeCollection.length; i++) {
                if (this.rangeCollection[i].id === range.id) {
                    return true;
                }
            }
            return false;
        }
    }, {
        key: 'remove',
        value: function remove(range) {
            var cleanedColl = _underscore2.default.filter(this.rangeCollection, function (rangeData, index) {
                if (range.id === rangeData.id) {
                    return false;
                }
                return true;
            });
            this.rangeCollection = cleanedColl;
            // if removed range is active one, activate another one
            if (range.id === this.currentRange.id) {
                if (this.rangeCollection.length > 0) {

                    //let lastRange = this.rangeCollection.length-1;
                    this.player_.rangeStream.onNext({
                        action: 'select',
                        range: this.rangeCollection[this.rangeCollection.length - 1]
                    });
                }
            }
            this.refreshRangeCollection();
        }
    }, {
        key: 'addRange',
        value: function addRange(range) {
            var lastId = this.uid = this.uid + 1;
            var newRange = _underscore2.default.extend({}, this.defaultRange, range, { id: lastId });
            newRange = this.setHandlePositions(newRange);
            this.rangeCollection.push(newRange);
            this.refreshRangeCollection();
            return newRange;
        }
    }, {
        key: 'addNewRange',
        value: function addNewRange(range) {
            var lastId = this.uid = this.uid + 1;
            var newRange = _underscore2.default.extend({}, this.defaultRange, range, { id: lastId });
            newRange.startPosition = this.getStartingPosition();
            newRange.endPosition = this.getEndPosition(newRange.startPosition);
            newRange = this.setHandlePositions(newRange);
            this.rangeCollection.push(newRange);
            this.refreshRangeCollection();
            return newRange;
        }
    }, {
        key: 'getStartingPosition',
        value: function getStartingPosition() {
            //tracker is at ending of previous range
            var gap = _underscore2.default.first(this.settings.record.sources).framerate * 0.001;
            var lastKnownPosition = this.player_.currentTime();

            if (lastKnownPosition + gap < this.player_.duration()) {
                lastKnownPosition += gap;
                return lastKnownPosition;
            }
            return lastKnownPosition;
            // let gap = 0.01;
            // let lastRange = this.rangeCollection.length > 0 ?
            //     this.rangeCollection[this.rangeCollection.length -1] : null;
            //
            // if(lastRange != null ||
            //     formatToFixedDecimals(this.player_.currentTime()) < formatToFixedDecimals(lastRange.endPosition)) {
            //     lastKnownPosition = lastRange.endPosition + gap <= this.player_.duration()
            //         ? lastRange.endPosition + gap
            //         : this.player_.duration();
            // }else {
            //     lastKnownPosition = this.player_.currentTime() + gap <= this.player_.duration()
            //         ? this.player_.currentTime() + gap
            //         : this.player_.duration();
            // }
            // return lastKnownPosition;
        }
    }, {
        key: 'getEndPosition',
        value: function getEndPosition(startPosition) {
            var rangeDuration = this.player_.duration() / 10;
            var endPosition = startPosition + rangeDuration;
            if (endPosition >= this.player_.duration()) {
                endPosition == this.player_.duration();
            }
            return endPosition;
            // let gap = 0.01;
            // let rangeDuration = this.player_.duration()/10;
            // let endPosition = null;
            // if(formatToFixedDecimals(startPosition) >= formatToFixedDecimals(this.player_.currentTime() + gap)) {
            //     endPosition = startPosition + rangeDuration <= this.player_.duration()
            //         ? startPosition + rangeDuration
            //         : this.player_.duration();
            // }else {
            //     endPosition = this.player_.currentTime() + gap;
            // }
            // return endPosition;
        }
    }, {
        key: 'updateRange',
        value: function updateRange(range) {
            var _this2 = this;

            if (range.id !== undefined) {
                this.rangeCollection = _underscore2.default.map(this.rangeCollection, function (rangeData, index) {
                    if (range.id === rangeData.id) {
                        range = _this2.setHandlePositions(range);
                        return range;
                    }
                    return rangeData;
                });
            }
            this.refreshRangeCollection();
            return range;
        }
    }, {
        key: 'syncRange',
        value: function syncRange(range) {

            var gap = _underscore2.default.first(this.settings.record.sources).framerate * 0.001;
            if (range.id !== undefined) {
                var index = _underscore2.default.findIndex(this.rangeCollection, function (rangeData) {
                    return rangeData.id == range.id;
                });

                if (index !== null) {
                    if (index < this.rangeCollection.length - 1) {
                        //update next range
                        var rangeToUpdate = this.rangeCollection[index + 1];
                        rangeToUpdate.startPosition = range.endPosition + gap <= rangeToUpdate.endPosition ? range.endPosition + gap : rangeToUpdate.endPosition;
                        var _newRange = this.setHandlePositions(rangeToUpdate);
                        this.rangeCollection[index + 1] = _newRange;
                    }
                    if (index > 0) {
                        //update previous range
                        var _rangeToUpdate = this.rangeCollection[index - 1];
                        _rangeToUpdate.endPosition = range.startPosition - gap >= _rangeToUpdate.startPosition ? range.startPosition - gap : _rangeToUpdate.startPosition;
                        var _newRange2 = this.setHandlePositions(_rangeToUpdate);
                        this.rangeCollection[index - 1] = _newRange2;
                    }

                    var newRange = this.setHandlePositions(range);
                    this.rangeCollection[index] = newRange;
                }
            }
            this.refreshRangeCollection();
            return range;
        }
    }, {
        key: 'setHandlePositions',
        value: function setHandlePositions(range) {
            var videoDuration = this.player_.duration();
            if (videoDuration > 0) {
                var left = range.startPosition / videoDuration * 100;
                var right = range.endPosition / videoDuration * 100;

                range.handlePositions = { left: left, right: right };
            }
            return range;
        }
    }, {
        key: 'getRangeById',
        value: function getRangeById(id) {
            var foundRange = {};
            for (var i = 0; i < this.rangeCollection.length; i++) {
                if (this.rangeCollection[i].id === id) {
                    foundRange = this.rangeCollection[i];
                }
            }
            return foundRange;
        }
    }]);

    return RangeCollection;
}(Component);

_video2.default.registerComponent('RangeCollection', RangeCollection);

exports.default = RangeCollection;

/***/ }),

/***/ 399:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

var _rx = __webpack_require__(7);

var Rx = _interopRequireWildcard(_rx);

var _video = __webpack_require__(12);

var _video2 = _interopRequireDefault(_video);

var _hotkeysModal = __webpack_require__(373);

var _hotkeysModal2 = _interopRequireDefault(_hotkeysModal);

var _hotkeysModalButton = __webpack_require__(400);

var _hotkeysModalButton2 = _interopRequireDefault(_hotkeysModalButton);

var _rangeBarCollection = __webpack_require__(401);

var _rangeBarCollection2 = _interopRequireDefault(_rangeBarCollection);

var _rangeCollection = __webpack_require__(374);

var _rangeCollection2 = _interopRequireDefault(_rangeCollection);

var _rangeControlBar = __webpack_require__(405);

var _rangeControlBar2 = _interopRequireDefault(_rangeControlBar);

var _videojsVtt = __webpack_require__(63);

var _hotkeys = __webpack_require__(406);

var _rangeItemContainer = __webpack_require__(407);

var _rangeItemContainer2 = _interopRequireDefault(_rangeItemContainer);

var _phraseanetCommon = __webpack_require__(11);

var appCommons = _interopRequireWildcard(_phraseanetCommon);

function _interopRequireWildcard(obj) { if (obj && obj.__esModule) { return obj; } else { var newObj = {}; if (obj != null) { for (var key in obj) { if (Object.prototype.hasOwnProperty.call(obj, key)) newObj[key] = obj[key]; } } newObj.default = obj; return newObj; } }

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/* eslint-disable quotes */
__webpack_require__(408);


// import rangeControls from './oldControlBar';

var icons = '\n<svg style="position: absolute; width: 0; height: 0;" width="0" height="0" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">\n<defs>\n<symbol id="icon-loop-range" viewBox="0 0 30 30">\n<title>loop-range</title>\n<path class="path1" d="M25.707 9.92l-2.133 1.813h1.707c0.107 0 0.32 0.213 0.32 0.213v8.107c0 0.107-0.213 0.213-0.32 0.213h-11.093l-0.853 2.133h11.947c1.067 0 2.453-1.28 2.453-2.347v-8.107c0-1.067-1.067-1.92-2.027-2.027z"></path>\n<path class="path2" d="M7.040 22.4l1.92-2.133h-2.24c-0.107 0-0.32-0.213-0.32-0.213v-8.107c0 0 0.213-0.213 0.32-0.213h11.627l0.853-2.133h-12.48c-1.173 0-2.453 1.28-2.453 2.347v8.107c0 1.067 1.28 2.347 2.453 2.347h0.32z"></path>\n<path class="path3" d="M17.493 6.827l4.053 3.947-4.053 3.947z"></path>\n<path class="path4" d="M14.933 24.96l-3.947-3.84 3.947-3.947z"></path>\n</symbol>\n<symbol id="icon-prev-forward-frame" viewBox="0 0 30 30">\n<title>prev-forward-frame</title>\n<path class="path1" d="M25.432 9.942l-9.554 9.554-3.457-3.457 9.554-9.554 3.457 3.457z"></path>\n<path class="path2" d="M21.912 25.578l-9.554-9.554 3.457-3.457 9.554 9.554-3.457 3.457z"></path>\n<path class="path3" d="M6.578 6.489h2.578v19.111h-2.578v-19.111z"></path>\n</symbol>\n<symbol id="icon-next-forward-frame" viewBox="0 0 30 30">\n<title>next-forward-frame</title>\n<path class="path1" d="M10.131 6.462l9.554 9.554-3.457 3.457-9.554-9.554 3.457-3.457z"></path>\n<path class="path2" d="M6.611 22.018l9.554-9.554 3.457 3.457-9.554 9.554-3.457-3.457z"></path>\n<path class="path3" d="M22.756 6.489h2.578v19.111h-2.578v-19.111z"></path>\n</symbol>\n<symbol id="icon-prev-frame" viewBox="0 0 30 30">\n<title>prev-frame</title>\n<path class="path1" d="M22.538 9.962l-9.554 9.554-3.457-3.457 9.554-9.554 3.457 3.457z"></path>\n<path class="path2" d="M19.018 25.558l-9.554-9.554 3.457-3.457 9.554 9.554-3.457 3.457z"></path>\n</symbol>\n<symbol id="icon-next-frame" viewBox="0 0 30 30">\n<title>next-frame</title>\n<path class="path1" d="M12.984 6.441l9.554 9.554-3.457 3.457-9.554-9.554 3.457-3.457z"></path>\n<path class="path2" d="M9.464 22.039l9.554-9.554 3.457 3.457-9.554 9.554-3.457-3.457z"></path>\n</symbol>\n<symbol id="icon-cue-start" viewBox="0 0 30 30">\n<title>cue-start</title>\n<path class="path1" d="M20.356 24.089v-15.733c0-0.533-0.356-0.889-0.889-0.889h-8c-0.444 0-0.889 0.356-0.889 0.889v5.067c0 0.533 0.267 1.156 0.622 1.511l8.622 9.422c0.267 0.356 0.533 0.267 0.533-0.267z"></path>\n</symbol>\n<symbol id="icon-cue-end" viewBox="0 0 30 30">\n<title>cue-end</title>\n<path class="path1" d="M10.578 24.089v-15.733c0-0.533 0.356-0.889 0.889-0.889h8c0.444 0 0.889 0.356 0.889 0.889v5.067c0 0.533-0.267 1.156-0.622 1.511l-8.622 9.422c-0.267 0.356-0.533 0.267-0.533-0.267z"></path>\n</symbol>\n<symbol id="icon-trash" viewBox="0 0 30 30">\n<title>trash</title>\n<path class="path1" d="M22.667 8.978h-3.822v-1.333c0-0.8-0.622-1.422-1.422-1.422h-2.756c-0.8 0-1.422 0.622-1.422 1.422v1.422h-3.822c-0.178 0-0.356 0.178-0.356 0.356v0.711c0 0.178 0.178 0.356 0.356 0.356h13.333c0.178 0 0.356-0.178 0.356-0.356v-0.711c-0.089-0.267-0.267-0.444-0.444-0.444zM14.667 8.978v0-1.422h2.756v1.422h-2.756z"></path>\n<path class="path2" d="M21.778 11.111h-11.733c-0.267 0-0.356 0.089-0.356 0.356v14.133c0 0 0.089 0.267 0.356 0.267h11.733c0.267 0 0.533-0.089 0.533-0.356v-14.133c0-0.178-0.267-0.267-0.533-0.267zM13.156 23.378c0 0.178-0.178 0.356-0.356 0.356h-0.711c-0.178 0-0.356-0.178-0.356-0.356v-9.778c0-0.178 0.178-0.356 0.356-0.356h0.711c0.178 0 0.356 0.178 0.356 0.356v9.778zM16.711 23.378c0 0.178-0.178 0.356-0.356 0.356h-0.711c-0.178 0-0.356-0.178-0.356-0.356v-9.778c0-0.178 0.178-0.356 0.356-0.356h0.711c0.178 0 0.356 0.178 0.356 0.356v9.778zM20.178 23.378c0 0.178-0.178 0.356-0.356 0.356h-0.711c-0.178 0-0.356-0.178-0.356-0.356v-9.778c0-0.178 0.178-0.356 0.356-0.356h0.711c0.178 0 0.356 0.178 0.356 0.356v9.778z"></path>\n</symbol>\n</defs>\n</svg>\n';

var defaults = {
    seekBackwardStep: 1000,
    seekForwardStep: 1000,
    align: 'top-left',
    class: '',
    content: 'This overlay will show up while the video is playing',
    debug: false,
    overlays: [{
        start: 'playing',
        end: 'paused'
    }]
};

var Component = _video2.default.getComponent('Component');

var plugin = function plugin(options) {
    var _this = this;

    var settings = _video2.default.mergeOptions(defaults, options);
    this.looping = false;
    this.loopData = [];
    this.activeRange = {};
    this.activeRangeStream = new Rx.Subject(); //new Rx.Observable.ofObjectChanges(this.activeRange);
    this.rangeStream = new Rx.Subject();
    this.rangeBarCollection = this.controlBar.getChild('progressControl').getChild('seekBar').addChild('RangeBarCollection', settings);
    this.rangeControlBar = this.addChild('RangeControlBar', settings);
    this.rangeItemContainer = this.addChild('RangeItemContainer', settings);
    this.rangeCollection = this.rangeItemContainer.getChild('RangeCollection');

    this.hotkeysModalButton = this.addChild('HotkeysModalButton', settings);

    (0, _jquery2.default)(this.el()).prepend(icons);

    this.setEditorWidth = function () {
        var editorWidth = _this.currentWidth();

        if (editorWidth < 672) {
            (0, _jquery2.default)(_this.el()).addClass('vjs-mini-screen');
        } else {
            (0, _jquery2.default)(_this.el()).removeClass('vjs-mini-screen');
        }
    };

    this.setEditorHeight = function () {
        // gather components sizes
        var editorHeight = _this.currentHeight() + (0, _jquery2.default)(_this.rangeControlBar.el()).height() + (0, _jquery2.default)(_this.rangeCollection.el()).height();

        if (editorHeight > 0) {
            options.$container.height(editorHeight + 'px');
        }
    };
    // range actions:
    this.rangeStream.subscribe(function (params) {
        params.handle = params.handle || false;
        console.log('RANGE EVENT ===========', params.action, '========>>>');
        switch (params.action) {
            case 'initialize':
                _this.rangeCollection.update(params.range);
                break;
            case 'select':
            case 'change':
                params.range = _this.shouldTakeSnapShot(params.range, false);
                _this.activeRange = _this.rangeCollection.update(params.range);

                _this.activeRangeStream.onNext({
                    activeRange: _this.activeRange
                });

                _this.rangeBarCollection.refreshRangeSliderPosition(_this.activeRange);
                _this.rangeControlBar.refreshRangePosition(_this.activeRange, params.handle);
                setTimeout(function () {
                    _this.rangeControlBar.setRangePositonToBeginning(params.range);
                }, 300);
                break;
            // flow through update:
            case 'create':
            case 'update':
                params.range = _this.shouldTakeSnapShot(params.range, false);
                _this.activeRange = _this.rangeCollection.update(params.range);

                _this.activeRangeStream.onNext({
                    activeRange: _this.activeRange
                });

                _this.rangeBarCollection.refreshRangeSliderPosition(_this.activeRange);
                _this.rangeControlBar.refreshRangePosition(_this.activeRange, params.handle);
                break;
            case 'remove':
                // if a range is specified remove it from collection:
                if (params.range !== undefined) {
                    _this.rangeCollection.remove(params.range);
                    if (params.range.id === _this.activeRange.id) {
                        // remove from controls components too if active:
                        _this.rangeBarCollection.removeActiveRange();
                        _this.rangeControlBar.removeActiveRange();
                    }
                } else {
                    _this.rangeBarCollection.removeActiveRange();
                    _this.rangeControlBar.removeActiveRange();
                    _this.rangeCollection.remove(_this.activeRange);
                }

                break;
            case 'drag-update':
                // if changes come from range bar
                _this.rangeControlBar.refreshRangePosition(params.range, params.handle);
                _this.rangeCollection.updatingByDragging(params.range);

                // setting currentTime may take some additionnal time,
                // so let's wait:
                setTimeout(function () {
                    if (params.handle === 'start') {
                        params.range = _this.shouldTakeSnapShot(params.range, false);
                        _this.rangeCollection.update(params.range);
                    }
                }, 900);
                break;
            case 'export-ranges':
                break;
            case 'export-vtt-ranges':
                _this.rangeCollection.exportRangesData(params.data);
                break;
            case 'resize':
                _this.setEditorWidth();
                break;
            case 'saveRangeCollectionPref':

                _this.saveRangeCollectionPref(params.data);
                break;
            case 'capture':
                // if a range is specified remove it from collection:
                if (params.range !== undefined) {
                    params.range = _this.shouldTakeSnapShot(params.range, true);
                    _this.rangeCollection.update(params.range);
                }
                break;
            default:
        }
        console.log('<<< =================== RANGE EVENT COMPLETE');
    });

    this.shouldTakeSnapShot = function (range, atCurrentPosition) {
        if (atCurrentPosition) {
            _this.takeSnapshot(range);
            range.manualSnapShot = true;
            return range;
        } else if (Math.round(range.startPosition) == Math.round(_this.currentTime()) && !range.manualSnapShot) {
            _this.takeSnapshot(range);
            return range;
        } else {
            return range;
        }
    };

    this.takeSnapshot = function (range) {
        var video = _this.el().querySelector('video');
        var canvas = document.createElement('canvas');
        var ratio = settings.ratios[_this.cache_.src];
        canvas.width = 50 * ratio;
        canvas.height = 50;
        var context = canvas.getContext('2d');

        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        var dataURI = canvas.toDataURL('image/jpeg');

        range.image = {
            src: dataURI,
            ratio: settings.ratios[_this.cache_.src],
            width: canvas.width,
            height: canvas.height
        };

        return range;
    };

    this.setVTT = function () {
        if (settings.vttFieldValue !== false) {
            // reset existing collection
            _this.rangeCollection.reset([]);

            // prefill chapters with vtt data
            var parser = new _videojsVtt.WebVTT.Parser(window, window.vttjs, _videojsVtt.WebVTT.StringDecoder());

            var errors = [];

            parser.oncue = function (cue) {

                // try to parse text:
                var parsedCue = false;
                try {
                    parsedCue = JSON.parse(cue.text || '{}');
                } catch (e) {
                    console.error('failed to parse cue text', e);
                }
                if (parsedCue === false) {
                    parsedCue = {
                        title: cue.text
                    };
                }

                var newRange = _this.rangeCollection.addRange({
                    startPosition: cue.startTime,
                    endPosition: cue.endTime,
                    title: parsedCue.title,
                    image: {
                        src: parsedCue.image || ''
                    },
                    manualSnapShot: parsedCue.manualSnapShot

                });

                _this.rangeStream.onNext({
                    action: 'initialize',
                    range: newRange
                });
            };

            parser.onparsingerror = function (error) {
                errors.push(error);
            };

            parser.parse(settings.vttFieldValue);
            if (errors.length > 0) {
                if (console.groupCollapsed) {
                    console.groupCollapsed('Text Track parsing errors');
                }
                errors.forEach(function (error) {
                    return console.error(error);
                });
                if (console.groupEnd) {
                    console.groupEnd();
                }
            }

            parser.flush();
        }
    };

    this.ready(function () {

        /*resize video*/
        var videoChapterH = (0, _jquery2.default)('#rangeExtractor').height();
        (0, _jquery2.default)('#rangeExtractor .video-player-container').css('max-height', videoChapterH);
        (0, _jquery2.default)('#rangeExtractor .range-collection-container').css('height', videoChapterH - 100);
        (0, _jquery2.default)('#rangeExtractor .video-range-editor-container').css('max-height', videoChapterH).css('overflow', 'hidden');

        _this.setVTT();
        // if we have to load existing chapters, let's trigger loadedmetadata:
        if (settings.vttFieldValue !== false) {
            var playPromise = null;
            if (_this.paused()) {
                playPromise = _this.play().then(function () {
                    _this.pause();
                }).catch(function (error) {});
            }
            if (!_this.paused()) {
                if (playPromise !== undefined) {
                    playPromise.then(function () {
                        _this.pause();
                    }).catch(function (error) {});
                }
            }
        }
    });

    this.one('loadedmetadata', function () {
        //this.setEditorHeight();
        _this.setEditorWidth();
        if (settings.vttFieldValue !== false) {
            //this.currentTime(0);
        }
    });

    // ensure control bar is always visible by simulating user activity:
    this.on('timeupdate', function () {
        _this.userActive(true);
    });

    // override existing hotkeys:
    this.getRangeCaptureOverridedHotkeys = function () {
        return (0, _hotkeys.overrideHotkeys)(settings);
    };

    // set new hotkeys
    this.getRangeCaptureHotkeys = function () {
        return (0, _hotkeys.hotkeys)(_this, settings);
    };

    // init a default range once every components are ready:
    this.rangeCollection.initDefaultRange();

    this.saveRangeCollectionPref = function (isChecked) {

        appCommons.userModule.setPref('overlapChapters', isChecked ? '1' : '0');
    };
};
_video2.default.plugin('rangeCapturePlugin', plugin);
exports.default = plugin;

/***/ }),

/***/ 400:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _get = function get(object, property, receiver) { if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { return get(parent, property, receiver); } } else if ("value" in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } };

var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

var _video = __webpack_require__(12);

var _video2 = _interopRequireDefault(_video);

var _hotkeysModal = __webpack_require__(373);

var _hotkeysModal2 = _interopRequireDefault(_hotkeysModal);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var Button = _video2.default.getComponent('Button');
var Component = _video2.default.getComponent('Component');

var HotkeysModalButton = function (_Button) {
    _inherits(HotkeysModalButton, _Button);

    function HotkeysModalButton(player, settings) {
        _classCallCheck(this, HotkeysModalButton);

        var _this = _possibleConstructorReturn(this, (HotkeysModalButton.__proto__ || Object.getPrototypeOf(HotkeysModalButton)).call(this, player, settings));

        _this.settings = settings;
        return _this;
    }

    /**
     * Allow sub components to stack CSS class names
     *
     * @return {String} The constructed class name
     * @method buildCSSClass
     */


    _createClass(HotkeysModalButton, [{
        key: 'buildCSSClass',
        value: function buildCSSClass() {
            return 'vjs-hotkeys-modal-button vjs-button';
        }
    }, {
        key: 'createEl',
        value: function createEl() {
            var tag = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'button';
            var props = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
            var attributes = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};

            var el = _get(HotkeysModalButton.prototype.__proto__ || Object.getPrototypeOf(HotkeysModalButton.prototype), 'createEl', this).call(this, tag, props, attributes);
            el.innerHTML = '<span class="fa-stack"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-info fa-stack-1x fa-inverse"></i></span>';
            return el;
        }

        /**
         * Handles click for keyboard shortcuts modal
         *
         * @method handleClick
         */

    }, {
        key: 'handleClick',
        value: function handleClick() {
            var _this2 = this;

            this.hotkeysModal = this.player_.addChild('HotkeyModal', this.settings);
            this.hotkeysModal.initialize();
            this.hotkeysModal.open();
            this.hotkeysModal.on('beforemodalclose', function () {
                (0, _jquery2.default)(_this2.el()).show();
            });
            (0, _jquery2.default)(this.el()).hide();
        }
    }]);

    return HotkeysModalButton;
}(Button);

// HotkeysModalButton.prototype.controlText_ = `<span class="fa-stack">
//                               <i class="fa fa-circle fa-stack-2x"></i>
//                               <i class="fa fa-info fa-stack-1x fa-inverse"></i>
//                             </span>`;

Component.registerComponent('HotkeysModalButton', HotkeysModalButton);
exports.default = HotkeysModalButton;

/***/ }),

/***/ 401:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _get = function get(object, property, receiver) { if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { return get(parent, property, receiver); } } else if ("value" in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } };

var _video = __webpack_require__(12);

var _video2 = _interopRequireDefault(_video);

var _rangeBar = __webpack_require__(402);

var _rangeBar2 = _interopRequireDefault(_rangeBar);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

/**
 * VideoJs Range Bar Collection
 */
var Component = _video2.default.getComponent('Component');

var RangeBarCollection = function (_Component) {
    _inherits(RangeBarCollection, _Component);

    function RangeBarCollection(player, settings) {
        _classCallCheck(this, RangeBarCollection);

        var _this = _possibleConstructorReturn(this, (RangeBarCollection.__proto__ || Object.getPrototypeOf(RangeBarCollection)).call(this, player, settings));

        _this.refreshRangeSliderPosition = function (range) {
            if (range.startPosition === -1 && range.endPosition === -1) {
                _this.removeActiveRange(range);
                return;
            }

            if (_this.activeRangeItem === undefined) {
                _this.activeRangeItem = new _rangeBar2.default(_this.player_, _this.settings); //this.addChild('RangeBar', [this.player_, this.settings]);
            }
            _this.activeRangeItem.updateRange(range);

            _this.addChild(_this.activeRangeItem);
        };

        _this.removeActiveRange = function (range) {
            if (_this.activeRangeItem !== undefined) {
                _this.removeChild(_this.activeRangeItem);
            }
        };

        _this.settings = settings;
        return _this;
    }

    /**
     * Create the component's DOM element
     *
     * @return {Element}
     * @method createEl
     */


    _createClass(RangeBarCollection, [{
        key: 'createEl',
        value: function createEl() {
            return _get(RangeBarCollection.prototype.__proto__ || Object.getPrototypeOf(RangeBarCollection.prototype), 'createEl', this).call(this, 'div', {
                className: 'vjs-range-container',
                innerHTML: ''
            });
        }
    }]);

    return RangeBarCollection;
}(Component);

_video2.default.registerComponent('RangeBarCollection', RangeBarCollection);

exports.default = RangeBarCollection;

/***/ }),

/***/ 402:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _get = function get(object, property, receiver) { if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { return get(parent, property, receiver); } } else if ("value" in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } };

var _underscore = __webpack_require__(1);

var _underscore2 = _interopRequireDefault(_underscore);

var _video = __webpack_require__(12);

var _video2 = _interopRequireDefault(_video);

var _nouislider = __webpack_require__(106);

var _nouislider2 = _interopRequireDefault(_nouislider);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

/**
 * VideoJs Range bar
 */
var Component = _video2.default.getComponent('Component');

var RangeBar = function (_Component) {
    _inherits(RangeBar, _Component);

    function RangeBar(player, settings) {
        _classCallCheck(this, RangeBar);

        var _this = _possibleConstructorReturn(this, (RangeBar.__proto__ || Object.getPrototypeOf(RangeBar)).call(this, player, settings));

        _this.updateRange = function (range) {
            _this.activeRange = range;
            var videoDuration = _this.player_.duration();
            if (videoDuration > 0) {
                // set left side with percent update
                var left = range.startPosition / videoDuration * 100;
                var right = range.endPosition / videoDuration * 100;

                // set as null if not and handle
                if (_this.activeHandlePositions.length > 0) {
                    // don't update unchanged handle:
                    left = left === _this.activeHandlePositions[0] ? null : left;
                    right = right === _this.activeHandlePositions[1] ? null : right;
                }

                _this.rangeBar.noUiSlider.set([left, right]);
            }
        };

        _this.activeHandlePositions = [];
        _this.activeRange = {
            id: 1,
            startPosition: -1,
            endPosition: -1
        };
        _this.onUpdatedRange = _underscore2.default.debounce(_this.onUpdatedRange, 300);
        return _this;
    }

    /**
     * Create the component's DOM element
     *
     * @return {Element}
     * @method createEl
     */


    _createClass(RangeBar, [{
        key: 'createEl',
        value: function createEl() {
            var _this2 = this;

            this.rangeBar = _get(RangeBar.prototype.__proto__ || Object.getPrototypeOf(RangeBar.prototype), 'createEl', this).call(this, 'div', {
                id: 'connect',
                className: 'vjs-range-bar',
                innerHTML: '<div><span></span></div>'
            });

            _nouislider2.default.create(this.rangeBar, {
                start: [0, 0], // ((range.startPosition/videoDuration) * 100), ((range.endPosition/videoDuration) * 100)
                behaviour: 'drag',
                connect: true,
                range: {
                    min: 0,
                    max: 100
                }
            });

            var sliderBar = document.createElement('div');
            var sliderBase = this.rangeBar.querySelector('.noUi-base');

            // Give the bar a class for styling and add it to the slider.
            sliderBar.className += 'connect';
            sliderBase.appendChild(sliderBar);

            this.rangeBar.noUiSlider.on('update', function (values, handle, a, b, handlePositions) {
                var offset = handlePositions[handle];

                // Right offset is 100% - left offset
                if (handle === 1) {
                    offset = 100 - offset;
                }

                // Pick left for the first handle, right for the second.
                sliderBar.style[handle ? 'right' : 'left'] = offset + '%';

                _this2.onUpdatedRange(handlePositions, handle);
            });

            // triggered when drag end - ensure last changed handle is synced with play head
            this.rangeBar.noUiSlider.on('change', function (values, handle, a, b, handlePositions) {
                _this2.onUpdatedRange(handlePositions, handle);
            });

            return this.rangeBar;
        }
    }, {
        key: 'onUpdatedRange',
        value: function onUpdatedRange(handlePositions, activeHandle) {

            var videoDuration = this.player_.duration();

            // convert back percent into time:
            if (this.activeRange !== undefined) {
                // checkif changes happened:
                var oldRange = _underscore2.default.extend({}, this.activeRange);
                var newStartPosition = handlePositions[0] / 100 * videoDuration;
                var newEndPosition = handlePositions[1] / 100 * videoDuration;
                this.activeRange.startPosition = newStartPosition;
                this.activeRange.endPosition = newEndPosition;
                this.activeHandlePositions = handlePositions;
                this.player_.rangeStream.onNext({
                    action: 'drag-update',
                    handle: activeHandle === 1 ? 'end' : 'start',
                    range: this.activeRange
                });
            }
        }
    }]);

    return RangeBar;
}(Component);

_video2.default.registerComponent('RangeBar', RangeBar);

exports.default = RangeBar;

/***/ }),

/***/ 403:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _get = function get(object, property, receiver) { if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { return get(parent, property, receiver); } } else if ("value" in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } };

var _underscore = __webpack_require__(1);

var _underscore2 = _interopRequireDefault(_underscore);

var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

var _video = __webpack_require__(12);

var _video2 = _interopRequireDefault(_video);

var _utils = __webpack_require__(363);

var _sortableComponent = __webpack_require__(404);

var _sortableComponent2 = _interopRequireDefault(_sortableComponent);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

/**
 * VideoJs Range bar
 */
var Component = _video2.default.getComponent('Component');
var chapterLabel = document.getElementById("default-video-chapter-label").value;
var rangeItemTemplate = function rangeItemTemplate(model, frameRate) {
    var image = '';
    if (model.image.src !== '') {
        image = '<div class="range-item-screenshot">\n<div>\n<div id="capture-thumbnail-icon"/>\n<img src="' + model.image.src + '" style="height: 60px;width:auto;">\n</div>\n</div>';
    }

    return '\n    <div class="range-item-index-div">\n<span class="range-item-index">' + (model.index + 1) + '</span>\n</div>\n' + image + '\n<div class="range-item-time-data">\n    <span class="range-item-title">\n     <input class="range-title range-input" type="text" value="' + (model.title != chapterLabel ? model.title : '') + '" placeholder="' + chapterLabel + '">\n    </span>\n    <div class="display-time-container">\n      <span class="icon-container small-icon"><svg class="icon icon-cue-start"><use xlink:href="#icon-cue-start"></use></svg></span>\n      <span class="display-time">' + (0, _utils.formatTime)(model.startPosition, 'hms', frameRate) + '</span>\n      <span class="display-time">' + (0, _utils.formatTime)(model.endPosition, 'hms', frameRate) + '</span>\n      <span class="icon-container small-icon"><svg class="icon icon-cue-end"><use xlink:href="#icon-cue-end"></use></svg></span>\n    </div>\n    <div class="progress-container">\n    <div class="progress-bar" style="left:' + model.handlePositions.left + '%;width:' + (model.handlePositions.right - model.handlePositions.left) + '%; height: 100%"></div>\n    <div class="progress-value">' + (0, _utils.formatTime)(model.endPosition - model.startPosition, 'hms', frameRate) + '</div>\n    </div>\n</div>\n<div class="range-item-close">\n    <div class="remove-range"></div>\n</div>\n';
    // <button class="control-button remove-range"><svg class="icon icon-trash"><use xlink:href="#icon-trash"></use></svg><span class="icon-label"> remove</span></button>
};

var RangeItem = function (_Component) {
    _inherits(RangeItem, _Component);

    function RangeItem(player, rangeOptions, settings) {
        _classCallCheck(this, RangeItem);

        var _this = _possibleConstructorReturn(this, (RangeItem.__proto__ || Object.getPrototypeOf(RangeItem)).call(this, player, rangeOptions));

        _this.frameRate = settings.frameRates[_this.player_.cache_.src];
        _this.settings = settings;
        _this.$el = _this.renderElContent();

        _this.$el.on('click', '#capture-thumbnail-icon', function (event) {
            event.preventDefault();
            _this.player_.rangeStream.onNext({
                action: 'capture',
                range: rangeOptions.model
            });
            // don't trigger other events
            event.stopPropagation();
        });

        _this.$el.on('click', function (event) {
            // event.preventDefault();
            var $el = (0, _jquery2.default)(event.currentTarget);
            if (rangeOptions.isActive === false) {
                // broadcast active state:
                _this.player_.rangeStream.onNext({
                    action: 'change',
                    range: rangeOptions.model
                });
            }
        });
        _this.$el.on('click', '.remove-range', function (event) {
            event.preventDefault();
            _this.player_.rangeStream.onNext({
                action: 'remove',
                range: rangeOptions.model
            });
            // don't trigger other events
            event.stopPropagation();
        });
        _this.$el.on('click focus', '.range-title', function (event) {
            event.stopPropagation(); // stop unfocus
        });
        _this.$el.on('keydown', '.range-title', function (event) {
            event.stopPropagation();
        });
        _this.$el.on('keyup', '.range-title', function (event) {
            if (event.keyCode === 13) {
                (0, _jquery2.default)(event.currentTarget).blur();
            }
        });
        _this.$el.on('blur', '.range-title', function (event) {
            event.preventDefault();
            var $el = (0, _jquery2.default)(event.currentTarget);
            _this.player_.rangeStream.onNext({
                action: 'update',
                range: _underscore2.default.extend(rangeOptions.model, {
                    title: $el.val()
                })
            });
            // don't trigger other events
            event.stopPropagation();
        });

        _this.sortable = new _sortableComponent2.default(rangeOptions, _this.$el);

        return _this;
    }

    /**
     * Create the component's DOM element
     *
     * @return {Element}
     * @method createEl
     */


    _createClass(RangeItem, [{
        key: 'createEl',
        value: function createEl() {
            this.rangeOptions = _get(RangeItem.prototype.__proto__ || Object.getPrototypeOf(RangeItem.prototype), 'createEl', this).call(this, 'div', {
                className: 'range-collection-item',
                innerHTML: ''
            }, {
                draggable: true
            });

            return this.rangeOptions;
        }
    }, {
        key: 'renderElContent',
        value: function renderElContent() {
            (0, _jquery2.default)(this.el_).append(rangeItemTemplate(this.options_.model, this.frameRate));
            if (this.options_.isActive) {
                (0, _jquery2.default)(this.el_).addClass('active');
            }
            return (0, _jquery2.default)(this.el_);
        }
    }, {
        key: 'dispose',
        value: function dispose() {
            this.$el.off();
            this.sortable.dispose();
        }
    }]);

    return RangeItem;
}(Component);

_video2.default.registerComponent('RangeItem', RangeItem);

exports.default = RangeItem;

/***/ }),

/***/ 404:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _underscore = __webpack_require__(1);

var _ = _interopRequireWildcard(_underscore);

var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _interopRequireWildcard(obj) { if (obj && obj.__esModule) { return obj; } else { var newObj = {}; if (obj != null) { for (var key in obj) { if (Object.prototype.hasOwnProperty.call(obj, key)) newObj[key] = obj[key]; } } newObj.default = obj; return newObj; } }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var SortableComponent = function () {
    function SortableComponent(options, $el) {
        var _this = this;

        _classCallCheck(this, SortableComponent);

        this.defaultOptions = {
            selectionClass: 'drag-selected',
            overClass: 'drag-over'
        };

        if (!options) {
            options = {};
        }
        options.attributes = {
            draggable: true
        };
        this.options = _.extend(this.defaultOptions, options);
        this.$el = $el;
        this.model = options.model;
        if (!this.options.events) {
            this.options.events = {};
        }
        this.$el.on('mousedown', function (event) {
            return _this.onMouseDown(event);
        }).on('mouseup', function (event) {
            return _this.onMouseUp(event);
        }).on('drag', function (event) {
            return _this.onDrag(event);
        }).on('dragstart', function (event) {
            return _this.onDragStart(event);
        }).on('dragenter', function (event) {
            return _this.onDragEnter(event);
        }).on('dragleave', function (event) {
            return _this.onDragLeave(event);
        }).on('dragover', function (event) {
            return _this.onDragOver(event);
        }).on('drop', function (event) {
            return _this.drop(event);
        });

        this.dragTooltipContainer = false;

        // create tooltip container if not existing:
        if ((0, _jquery2.default)('.drag-mousemove-container').length === 0) {
            (0, _jquery2.default)('body').append('<div class="drag-mousemove-container" style="position:absolute"></div>');
        }
        this.dragTooltipContainer = (0, _jquery2.default)('.drag-mousemove-container');
        this.enableSorting();
    }

    _createClass(SortableComponent, [{
        key: 'dispose',
        value: function dispose() {
            this.$el.off();
        }
    }, {
        key: 'disableSorting',
        value: function disableSorting() {
            this.isSortingEnabled = false;
        }
    }, {
        key: 'enableSorting',
        value: function enableSorting() {
            this.isSortingEnabled = true;
        }

        /*initialize(options) {
         this.listenTo(this.model, 'change:_selected', this.onModelChanged);
         }
         onModelChanged() {
         if( this.model.get('_selected') === true) {
         this.$el.addClass(this.options.selectionClass);
         } else {
         this.$el.removeClass(this.options.selectionClass);
         }
         }*/

    }, {
        key: 'onMouseDown',
        value: function onMouseDown(e) {}
    }, {
        key: 'onMouseUp',
        value: function onMouseUp(e) {
            if (!this.isSortingEnabled) return;

            var isSelected = this.$el.hasClass(this.options.selectionClass);
            // if selection has more than one item, then user drag

            if (!this.isMultipleModifier(e)) {
                var selectedModels = this.options.collection.getSelection();

                // if selection is 1 or 0, then do something - else user is dragging
                if (selectedModels.length < 2) {
                    // remove previous selection if multpile modifier not active
                    this.clearSelection();
                    if (isSelected) {
                        // remove selection:
                        this.removeSelection(this.model);
                    } else {
                        // is not already selected:
                        this.addSelection(this.model);
                    }
                } else {
                    // if there is a multiselection and modifier is not active
                    this.clearSelection();
                    // then select clicked one:
                    this.addSelection(this.model);
                }
            } else {
                if (e.shiftKey) {
                    var collection = this.options.collection;
                    var currentIndex = collection.getIndex(this.model);
                    var firstModel = collection.getFirstSelected();
                    var firstIndex = collection.getIndex(firstModel);
                    var lastModel = collection.getLastSelected();
                    var lastIndex = collection.getIndex(lastModel);
                    // get first selection offset
                    // get last selected offset
                    // get current
                    this.clearSelection();
                    var models = collection.get();
                    if (firstIndex < currentIndex) {
                        for (var i = firstIndex; i < currentIndex; i++) {
                            this.addSelection(models[i]);
                        }
                    } else {
                        for (var _i = lastIndex; _i > currentIndex; _i--) {
                            this.addSelection(models[_i]);
                        }
                    }
                }
                // with multiple modifier
                if (isSelected) {
                    // remove from selection
                    this.removeSelection(this.model);
                } else {
                    // add to selection
                    this.addSelection(this.model);
                }
            }
            this.selectionChange();
        }
    }, {
        key: 'onDrag',
        value: function onDrag() {
            if (!this.isSortingEnabled) return;
        }
    }, {
        key: 'onDragStart',
        value: function onDragStart(e) {
            if (!this.isSortingEnabled) return;

            var isSelected = this.$el.hasClass(this.selectionClass);

            // jquery ui sortable: http://jsfiddle.net/hQnWG/614/
            //if the element's parent is not the owner, then block this event
            if (this.isMultipleModifier(e) && e.target.getAttribute('aria-grabbed') === 'false') {
                this.clearSelection();
                //add this additional selection
                this.addSelection(this.model); //e.target
            } else {
                // if start drag a non selected model, add the model to selection:
                if (!isSelected) {
                    this.clearSelection();
                    this.$el.addClass(this.options.selectionClass);
                    this.addSelection(this.model);
                }
            }

            this.options.draggedModel = this.model;
            this.$el.attr('aria-grabbed', 'true');
            this.selectionChange();
        }
    }, {
        key: 'onDragEnter',
        value: function onDragEnter(e) {
            if (!this.isSortingEnabled) return;
            e.preventDefault();
            this.$el.addClass(this.options.overClass);
        }
    }, {
        key: 'onDragLeave',
        value: function onDragLeave(e) {
            if (!this.isSortingEnabled) return;
            e.preventDefault();
            this.$el.removeClass(this.options.overClass);
        }
    }, {
        key: 'onDragOver',
        value: function onDragOver(e) {
            if (!this.isSortingEnabled) return;
            e.preventDefault();

            return false;
        }
    }, {
        key: 'drop',
        value: function drop(e) {
            if (!this.isSortingEnabled) return;
            e.preventDefault();
            this.onDragLeave(e);
            var selectedModels = [];
            var collection = this.options.collection.get();
            var toIndex = this.$el.index();

            selectedModels = this.options.collection.getSelection();

            for (var i = selectedModels.length; i--;) {
                var fromIndex = this.options.collection.getIndex(this.options.collection.get(selectedModels[i]));

                collection.splice(toIndex, 0, this.options.collection.splice(fromIndex, 1)[0]);
            }

            this.options.collection.reset(collection);
        }

        /**
         * trigger multiple selection with keyboard modifier
         */

    }, {
        key: 'isMultipleModifier',
        value: function isMultipleModifier(e) {
            return e.ctrlKey || e.metaKey || e.shiftKey;
        }
    }, {
        key: 'clearSelection',
        value: function clearSelection() {
            this.options.collection.resetSelection();
        }
    }, {
        key: 'addSelection',
        value: function addSelection(model) {
            this.options.collection.addToSelection(model);
        }
    }, {
        key: 'removeSelection',
        value: function removeSelection(model) {
            this.options.collection.removeFromSelection(model);
        }
    }, {
        key: 'selectionChange',
        value: function selectionChange() {
            //this.triggerMethod('selection:changed', this.options.collection.getSelection());
        }
    }]);

    return SortableComponent;
}();

exports.default = SortableComponent;

/***/ }),

/***/ 405:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _get = function get(object, property, receiver) { if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { return get(parent, property, receiver); } } else if ("value" in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } };

var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

var _underscore = __webpack_require__(1);

var _underscore2 = _interopRequireDefault(_underscore);

var _video = __webpack_require__(12);

var _video2 = _interopRequireDefault(_video);

var _utils = __webpack_require__(363);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

/**
 * VideoJs Range Control Bar
 */
var Component = _video2.default.getComponent('Component');

var icons = '\n<svg style="position: absolute; width: 0; height: 0;" width="0" height="0" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">\n<defs>\n<symbol id="icon-loop-range" viewBox="0 0 30 30">\n<title>loop-range</title>\n<path class="path1" d="M25.707 9.92l-2.133 1.813h1.707c0.107 0 0.32 0.213 0.32 0.213v8.107c0 0.107-0.213 0.213-0.32 0.213h-11.093l-0.853 2.133h11.947c1.067 0 2.453-1.28 2.453-2.347v-8.107c0-1.067-1.067-1.92-2.027-2.027z"></path>\n<path class="path2" d="M7.040 22.4l1.92-2.133h-2.24c-0.107 0-0.32-0.213-0.32-0.213v-8.107c0 0 0.213-0.213 0.32-0.213h11.627l0.853-2.133h-12.48c-1.173 0-2.453 1.28-2.453 2.347v8.107c0 1.067 1.28 2.347 2.453 2.347h0.32z"></path>\n<path class="path3" d="M17.493 6.827l4.053 3.947-4.053 3.947z"></path>\n<path class="path4" d="M14.933 24.96l-3.947-3.84 3.947-3.947z"></path>\n</symbol>\n<symbol id="icon-prev-forward-frame" viewBox="0 0 30 30">\n<title>prev-forward-frame</title>\n<path class="path1" d="M25.432 9.942l-9.554 9.554-3.457-3.457 9.554-9.554 3.457 3.457z"></path>\n<path class="path2" d="M21.912 25.578l-9.554-9.554 3.457-3.457 9.554 9.554-3.457 3.457z"></path>\n<path class="path3" d="M6.578 6.489h2.578v19.111h-2.578v-19.111z"></path>\n</symbol>\n<symbol id="icon-next-forward-frame" viewBox="0 0 30 30">\n<title>next-forward-frame</title>\n<path class="path1" d="M10.131 6.462l9.554 9.554-3.457 3.457-9.554-9.554 3.457-3.457z"></path>\n<path class="path2" d="M6.611 22.018l9.554-9.554 3.457 3.457-9.554 9.554-3.457-3.457z"></path>\n<path class="path3" d="M22.756 6.489h2.578v19.111h-2.578v-19.111z"></path>\n</symbol>\n<symbol id="icon-prev-frame" viewBox="0 0 30 30">\n<title>prev-frame</title>\n<path class="path1" d="M22.538 9.962l-9.554 9.554-3.457-3.457 9.554-9.554 3.457 3.457z"></path>\n<path class="path2" d="M19.018 25.558l-9.554-9.554 3.457-3.457 9.554 9.554-3.457 3.457z"></path>\n</symbol>\n<symbol id="icon-next-frame" viewBox="0 0 30 30">\n<title>next-frame</title>\n<path class="path1" d="M12.984 6.441l9.554 9.554-3.457 3.457-9.554-9.554 3.457-3.457z"></path>\n<path class="path2" d="M9.464 22.039l9.554-9.554 3.457 3.457-9.554 9.554-3.457-3.457z"></path>\n</symbol>\n<symbol id="icon-cue-start" viewBox="0 0 30 30">\n<title>cue-start</title>\n<path class="path1" d="M20.356 24.089v-15.733c0-0.533-0.356-0.889-0.889-0.889h-8c-0.444 0-0.889 0.356-0.889 0.889v5.067c0 0.533 0.267 1.156 0.622 1.511l8.622 9.422c0.267 0.356 0.533 0.267 0.533-0.267z"></path>\n</symbol>\n<symbol id="icon-cue-end" viewBox="0 0 30 30">\n<title>cue-end</title>\n<path class="path1" d="M10.578 24.089v-15.733c0-0.533 0.356-0.889 0.889-0.889h8c0.444 0 0.889 0.356 0.889 0.889v5.067c0 0.533-0.267 1.156-0.622 1.511l-8.622 9.422c-0.267 0.356-0.533 0.267-0.533-0.267z"></path>\n</symbol>\n<symbol id="icon-trash" viewBox="0 0 30 30">\n<title>trash</title>\n<path class="path1" d="M22.667 8.978h-3.822v-1.333c0-0.8-0.622-1.422-1.422-1.422h-2.756c-0.8 0-1.422 0.622-1.422 1.422v1.422h-3.822c-0.178 0-0.356 0.178-0.356 0.356v0.711c0 0.178 0.178 0.356 0.356 0.356h13.333c0.178 0 0.356-0.178 0.356-0.356v-0.711c-0.089-0.267-0.267-0.444-0.444-0.444zM14.667 8.978v0-1.422h2.756v1.422h-2.756z"></path>\n<path class="path2" d="M21.778 11.111h-11.733c-0.267 0-0.356 0.089-0.356 0.356v14.133c0 0 0.089 0.267 0.356 0.267h11.733c0.267 0 0.533-0.089 0.533-0.356v-14.133c0-0.178-0.267-0.267-0.533-0.267zM13.156 23.378c0 0.178-0.178 0.356-0.356 0.356h-0.711c-0.178 0-0.356-0.178-0.356-0.356v-9.778c0-0.178 0.178-0.356 0.356-0.356h0.711c0.178 0 0.356 0.178 0.356 0.356v9.778zM16.711 23.378c0 0.178-0.178 0.356-0.356 0.356h-0.711c-0.178 0-0.356-0.178-0.356-0.356v-9.778c0-0.178 0.178-0.356 0.356-0.356h0.711c0.178 0 0.356 0.178 0.356 0.356v9.778zM20.178 23.378c0 0.178-0.178 0.356-0.356 0.356h-0.711c-0.178 0-0.356-0.178-0.356-0.356v-9.778c0-0.178 0.178-0.356 0.356-0.356h0.711c0.178 0 0.356 0.178 0.356 0.356v9.778z"></path>\n</symbol>\n</defs>\n</svg>\n';
var defaults = {
    frameRate: 24
};

var RangeControlBar = function (_Component) {
    _inherits(RangeControlBar, _Component);

    function RangeControlBar(player, options) {
        _classCallCheck(this, RangeControlBar);

        var _this = _possibleConstructorReturn(this, (RangeControlBar.__proto__ || Object.getPrototypeOf(RangeControlBar)).call(this, player, options));

        var settings = _video2.default.mergeOptions(defaults, options);

        //this.settings = settings;
        _this.looping = false;
        _this.loopData = []; // @dprecated
        _this.frameStep = 1;

        _this.frameRate = settings.frameRates[_this.player_.cache_.src];
        _this.frameDuration = 1 / _this.frameRate;

        _this.currentRange = false;
        _this.player_.activeRangeStream.subscribe(function (params) {
            _this.currentRange = params.activeRange;
            _this.onRefreshDisplayTime();
        });
        return _this;
    }

    _createClass(RangeControlBar, [{
        key: 'rangeMenuTemplate',
        value: function rangeMenuTemplate() {
            return '<div class="range-capture-container">\n\n<button class="control-button" id="start-range" videotip="' + this.player_.localize('Start Range') + '"><svg class="icon icon-cue-start"><use xlink:href="#icon-cue-start"></use></svg></button>\n<button class="control-button" id="end-range" videotip="' + this.player_.localize('End Range') + '"><svg class="icon icon-cue-end"><use xlink:href="#icon-cue-end"></use></svg><span class="icon-label"> icon-cue-end</span></button>\n<button class="control-button" id="delete-range" videotip="' + this.player_.localize('Remove current Range') + '"><svg class="icon icon-trash"><use xlink:href="#icon-trash"></use></svg><span class="icon-label"> remove</span></button>\n<button class="control-button" id="loop-range" videotip="' + this.player_.localize('Toggle loop') + '"><svg class="icon icon-loop-range"><use xlink:href="#icon-loop-range"></use></svg><span class="icon-label"> loop</span></button>\n<button class="control-button" id="prev-forward-frame" videotip="' + this.player_.localize('Go to start point') + '"><svg class="icon icon-prev-forward-frame"><use xlink:href="#icon-prev-forward-frame"></use></svg><span class="icon-label"> prev forward frame</span></button>\n<button class="control-button" id="backward-frame" videotip="' + this.player_.localize('Go 1 frame backward') + '"><svg class="icon icon-prev-frame"><use xlink:href="#icon-prev-frame"></use></svg><span class="icon-label"> prev frame</span></button>\n<span id="display-start" class="display-time">\n<input type="text" class="range-input" data-scope="start-range" id="start-range-input-hours" value="00" size="2"/>:\n<input type="text" class="range-input" data-scope="start-range" id="start-range-input-minutes" value="00" size="2"/>:\n<input type="text" class="range-input" data-scope="start-range" id="start-range-input-seconds" value="00" size="2"/>s\n<input type="text" class="range-input" data-scope="start-range" id="start-range-input-frames" value="00" size="2"/>f\n</span>\n<span id="display-end" class="display-time">\n<input type="text" class="range-input" data-scope="end-range" id="end-range-input-hours" value="00" size="2"/>:\n<input type="text" class="range-input" data-scope="end-range" id="end-range-input-minutes" value="00" size="2"/>:\n<input type="text" class="range-input" data-scope="end-range" id="end-range-input-seconds" value="00" size="2"/>s\n<input type="text" class="range-input" data-scope="end-range" id="end-range-input-frames" value="00" size="2"/>f</span>\n<button class="control-button" id="forward-frame"  videotip="' + this.player_.localize('Go 1 frame forward') + '"><svg class="icon icon-next-frame"><use xlink:href="#icon-next-frame"></use></svg><span class="icon-label"> next frame</span></button>\n<button class="control-button" id="next-forward-frame"  videotip="' + this.player_.localize('Go to end point') + '"><svg class="icon icon-next-forward-frame"><use xlink:href="#icon-next-forward-frame"></use></svg><span class="icon-label"> next forward frame</span></button>\n\n<span id="display-current" class="display-time" videotip="' + this.player_.localize('Elapsed time') + '" data-mode="elapsed">E. 00:00:00s 00f</span>\n</div>';
        }

        /**
         * Create the component's DOM element
         *
         * @return {Element}
         * @method createEl
         */

    }, {
        key: 'createEl',
        value: function createEl() {
            var _this2 = this;

            this.rangeControlBar = _get(RangeControlBar.prototype.__proto__ || Object.getPrototypeOf(RangeControlBar.prototype), 'createEl', this).call(this, 'div', {
                className: 'range-control-bar',
                innerHTML: ''
            });
            (0, _jquery2.default)(this.rangeControlBar).on('click', '#start-range', function (event) {
                event.preventDefault();
                _this2.player_.rangeStream.onNext({
                    action: 'update',
                    handle: 'start',
                    range: _this2.setStartPositon()
                });
            }).on('click', '#end-range', function (event) {
                event.preventDefault();
                _this2.player_.rangeStream.onNext({
                    action: 'update',
                    handle: 'end',
                    range: _this2.setEndPosition()
                });
            }).on('click', '#delete-range', function (event) {
                event.preventDefault();
                _this2.player_.rangeStream.onNext({
                    action: 'remove'
                });
            }).on('click', '#backward-frame', function (event) {
                event.preventDefault();
                _this2.setPreviousFrame();
            }).on('click', '#forward-frame', function (event) {
                event.preventDefault();
                _this2.setNextFrame();
            }).on('click', '#prev-forward-frame', function (event) {
                event.preventDefault();
                if (!_this2.player_.paused()) {
                    _this2.player_.pause();
                }
                _this2.player_.currentTime(_this2.getStartPosition());
            }).on('click', '#next-forward-frame', function (event) {
                event.preventDefault();
                if (!_this2.player_.paused()) {
                    _this2.player_.pause();
                }
                _this2.player_.currentTime(_this2.getEndPosition());
            }).on('click', '#loop-range', function (event) {
                event.preventDefault();
                _this2.toggleLoop();
            }).on('click', '#display-current', function (event) {
                var $el = (0, _jquery2.default)(event.currentTarget);
                var mode = $el.data('mode');

                console.log('mode:', $el.data('mode'));
                switch (mode) {
                    case 'remaining':
                        $el.data('mode', 'elapsed');
                        $el.attr('videotip', _this2.player_.localize('Elapsed time'));
                        break;
                    case 'elapsed':
                        if (_this2.currentRange === false) {
                            $el.data('mode', 'remaining');
                            $el.attr('videotip', _this2.player_.localize('Remaining time'));
                        } else {
                            $el.data('mode', 'duration');
                            $el.attr('videotip', _this2.player_.localize('Range duration'));
                        }
                        break;
                    case 'duration':
                        $el.data('mode', 'remaining');
                        $el.attr('videotip', _this2.player_.localize('Remaining time'));
                        break;
                    default:
                        if (_this2.currentRange === false) {
                            $el.data('mode', 'remaining');
                            $el.attr('videotip', _this2.player_.localize('Remaining time'));
                        } else {
                            $el.data('mode', 'duration');
                            $el.attr('videotip', _this2.player_.localize('Range duration'));
                        }
                }
                _this2.onRefreshDisplayTime();
                /*// toggle mode
                 if ($el.data('mode') === 'remaining') {
                 $el.data('mode', 'current');
                 $el.attr('videotip', this.player_.localize('Elapsed time'))
                 } else {
                 $el.data('mode', 'remaining');
                 $el.attr('videotip', this.player_.localize('Remaining time'))
                 }*/
            }).on('keyup', '.range-input', function (event) {
                if (event.keyCode === 13) {
                    (0, _jquery2.default)(event.currentTarget).blur();
                }
            }).on('focus', '.range-input', function (event) {
                event.currentTarget.setSelectionRange(0, event.currentTarget.value.length);
            }).on('blur', '.range-input', function (event) {
                event.preventDefault();
                var $el = (0, _jquery2.default)(event.currentTarget);

                if (_this2.validateScopeInput($el.data('scope'))) {
                    // this.validateScopeInput();
                    var newCurrentTime = _this2.getScopeInputTime($el.data('scope'));
                    _this2.player_.currentTime(newCurrentTime);
                    $el.addClass('is-valid');
                    setTimeout(function () {
                        return $el.removeClass('is-valid');
                    }, 500);
                } else {
                    $el.addClass('has-error');
                    setTimeout(function () {
                        return $el.removeClass('has-error');
                    }, 1200);
                }
                // fallback on old values if have errors:
                _this2.player_.rangeStream.onNext({
                    action: 'update',
                    handle: $el.data('scope') === 'start-range' ? 'start' : 'end',
                    range: $el.data('scope') === 'start-range' ? _this2.setStartPositon() : _this2.setEndPosition()
                });
            });

            (0, _jquery2.default)(this.rangeControlBar).append(this.rangeMenuTemplate());

            this.player_.on('timeupdate', function () {
                _this2.onRefreshDisplayTime();
                // if a loop exists
                if (_this2.looping === true && _this2.loopData.length > 0) {

                    var start = _this2.currentRange.startPosition; //this.loopData[0];
                    var end = _this2.currentRange.endPosition; //this.loopData[1];

                    var current_time = _this2.player_.currentTime();

                    if (current_time < start || end > 0 && current_time >= end) {
                        _this2.player_.currentTime(start);
                        setTimeout(function () {
                            // Resume play if the element is paused.
                            if (_this2.player_.paused()) {
                                _this2.player_.play();
                            }
                        }, 150);
                    }
                }
            });
            return this.rangeControlBar;
        }
    }, {
        key: 'refreshRangePosition',
        value: function refreshRangePosition(range, handle) {
            handle = handle || false;
            this.updateRangeDisplay('start-range', range.startPosition);
            this.updateRangeDisplay('end-range', range.endPosition);

            if (handle === 'start') {
                this.player_.currentTime(range.startPosition);
            } else if (handle === 'end') {
                this.player_.currentTime(range.endPosition);
            }
        }
    }, {
        key: 'setRangePositonToBeginning',
        value: function setRangePositonToBeginning(range) {
            this.player_.currentTime(range.startPosition);
        }
    }, {
        key: 'updateRangeDisplay',
        value: function updateRangeDisplay(scope, currentTime) {

            var format = (0, _utils.formatMilliseconds)(currentTime, this.frameRate);

            (0, _jquery2.default)('#' + scope + '-input-hours').val(('0' + format.hours).slice(-2));
            (0, _jquery2.default)('#' + scope + '-input-minutes').val(('0' + format.minutes).slice(-2));
            (0, _jquery2.default)('#' + scope + '-input-seconds').val(('0' + format.seconds).slice(-2));
            (0, _jquery2.default)('#' + scope + '-input-frames').val(('0' + format.frames).slice(-2));
        }
    }, {
        key: 'getScopeInputs',
        value: function getScopeInputs(scope) {
            return {
                hours: (0, _jquery2.default)('#' + scope + '-input-hours').val(),
                minutes: (0, _jquery2.default)('#' + scope + '-input-minutes').val(),
                seconds: (0, _jquery2.default)('#' + scope + '-input-seconds').val(),
                frames: (0, _jquery2.default)('#' + scope + '-input-frames').val()
            };
        }
    }, {
        key: 'validateScopeInput',
        value: function validateScopeInput(scope) {
            var scopeInputs = this.getScopeInputs(scope);
            var regex = /^\d+$/; // allow only numbers [0-9]
            if (regex.test(scopeInputs.hours) && regex.test(scopeInputs.minutes) && regex.test(scopeInputs.seconds) && regex.test(scopeInputs.frames)) {
                if (scopeInputs.minutes < 0 || scopeInputs.minutes > 59) {
                    return false;
                }
                if (scopeInputs.seconds < 0 || scopeInputs.seconds > 59) {
                    return false;
                }
                if (scopeInputs.frames < 0 || scopeInputs.frames > this.frameRate) {
                    return false;
                }
                return true;
            }
            return false;
        }
    }, {
        key: 'getScopeInputTime',
        value: function getScopeInputTime(scope) {
            var scopeInputs = this.getScopeInputs(scope);
            var hours = parseInt(scopeInputs.hours, 10);
            var minutes = parseInt(scopeInputs.minutes, 10);
            var seconds = parseInt(scopeInputs.seconds, 10);
            var frames = parseInt(scopeInputs.frames, 10);
            var milliseconds = frames === 0 ? 0 : (1000 / this.frameRate * frames / 1000).toFixed(2);

            return hours * 3600 + minutes * 60 + seconds + parseFloat(milliseconds);
        }
    }, {
        key: 'toggleLoop',
        value: function toggleLoop() {
            var $el = (0, _jquery2.default)('#loop-range');
            if (!this.player_.paused()) {
                this.player_.pause();
            }
            this.looping = !this.looping;

            if (this.looping) {
                $el.addClass('active');
                this.loopBetween();
            } else {
                $el.removeClass('active');
            }
        }
    }, {
        key: 'loopBetween',
        value: function loopBetween(range) {
            range = range || this.player_.activeRange;
            this.loop(range.startPosition, range.endPosition);
        }
    }, {
        key: 'loop',
        value: function loop(start, end) {
            var _this3 = this;

            this.looping = true;
            this.player_.currentTime(start);

            // @deprecated
            this.loopData = [start, end];

            setTimeout(function () {
                // Resume play if the element is paused.
                if (_this3.player_.paused()) {
                    _this3.player_.play();
                }
            }, 150);
        }
    }, {
        key: 'setStartPositon',
        value: function setStartPositon() {
            if (this.currentRange === false) {
                throw new Error('setStartPositon > no range provided');
            }
            var newRange = _underscore2.default.extend({}, this.currentRange);
            // set start
            newRange.startPosition = this.player_.currentTime();
            newRange.startPositionFormated = (0, _utils.formatMilliseconds)(this.player_.currentTime(), this.frameRate);
            var firstTime = newRange.startPosition === -1 && newRange.endPosition === -1;
            var startBehindEnd = newRange.startPosition > newRange.endPosition;

            if (firstTime || startBehindEnd) {
                newRange.endPosition = this.player_.duration();
            }
            return newRange;
        }
    }, {
        key: 'getStartPosition',
        value: function getStartPosition() {
            if (this.currentRange === false) {
                throw new Error('getStartPosition > no range provided');
            }
            return this.currentRange.startPosition;
        }
    }, {
        key: 'setEndPosition',
        value: function setEndPosition() {
            if (this.currentRange === false) {
                throw new Error('setEndPositon > no range provided');
            }
            var newRange = _underscore2.default.extend({}, this.currentRange);
            newRange.endPosition = this.player_.currentTime();
            newRange.endPositionFormated = (0, _utils.formatMilliseconds)(this.player_.currentTime(), this.frameRate);
            var firstTime = newRange.startPosition === -1 && newRange.endPosition === -1;
            var startBehindEnd = newRange.startPosition > newRange.endPosition;
            if (firstTime || startBehindEnd) {
                newRange.startPosition = 0;
            }
            return newRange;
        }
    }, {
        key: 'getEndPosition',
        value: function getEndPosition() {
            if (this.currentRange === false) {
                throw new Error('getEndPosition > no range provided');
            }
            return this.currentRange.endPosition;
        }
    }, {
        key: 'removeActiveRange',
        value: function removeActiveRange() {
            this.updateRangeDisplay('start-range', 0);
            this.updateRangeDisplay('end-range', 0);
        }

        /**
         *
         * @param step (frames)
         */

    }, {
        key: 'setNextFrame',
        value: function setNextFrame(step) {
            var position = this.player_.currentTime();
            if (!this.player_.paused()) {
                this.player_.pause();
            }

            if (step !== undefined) {
                this.player_.currentTime(position + step);
            } else {
                this.player_.currentTime(position + this.frameDuration * this.frameStep);
            }
        }

        /**
         *
         * @param step (frames)
         */

    }, {
        key: 'setPreviousFrame',
        value: function setPreviousFrame(step) {
            var position = this.player_.currentTime();
            if (!this.player_.paused()) {
                this.player_.pause();
            }

            if (step !== undefined) {
                this.player_.currentTime(position - step);
            } else {
                this.player_.currentTime(position - this.frameDuration * this.frameStep);
            }
        }
    }, {
        key: 'onRefreshDisplayTime',
        value: function onRefreshDisplayTime() {
            if (this.$displayCurrent === undefined) {
                this.$displayCurrent = (0, _jquery2.default)('#display-current');
            }
            if (this.$displayCurrent.length > 0) {
                switch (this.$displayCurrent.data('mode')) {
                    case 'remaining':
                        this.$displayCurrent.html('R. ' + (0, _utils.formatTime)(this.player_.remainingTime(), '', this.frameRate));
                        break;
                    case 'elapsed':
                        this.$displayCurrent.html('E. ' + (0, _utils.formatTime)(this.player_.currentTime(), '', this.frameRate));
                        break;
                    case 'duration':
                        this.$displayCurrent.html('D. ' + (0, _utils.formatTime)(this.currentRange.endPosition - this.currentRange.startPosition, '', this.frameRate));
                        break;
                    default:
                }
            }
        }
    }]);

    return RangeControlBar;
}(Component);

_video2.default.registerComponent('RangeControlBar', RangeControlBar);

exports.default = RangeControlBar;

/***/ }),

/***/ 406:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});
exports.hotkeys = exports.overrideHotkeys = undefined;

var _rx = __webpack_require__(7);

var Rx = _interopRequireWildcard(_rx);

function _interopRequireWildcard(obj) { if (obj && obj.__esModule) { return obj; } else { var newObj = {}; if (obj != null) { for (var key in obj) { if (Object.prototype.hasOwnProperty.call(obj, key)) newObj[key] = obj[key]; } } newObj.default = obj; return newObj; } }

var overrideHotkeys = function overrideHotkeys(settings) {
    // override existing keys
    return {
        volumeUpKey: function volumeUpKey(event, player) {
            // disable existing one
            return false;
        },
        volumeDownKey: function volumeDownKey(event, player) {
            // disable existing one
            return false;
        },
        rewindKey: function rewindKey(event, player) {
            // disable existing one
            return false;
        },
        forwardKey: function forwardKey(event, player) {
            // disable existing one
            return false;
        }
    };
};

var tapSequenceHotKey = function tapSequenceHotKey(keyStream, eventKey) {

    return keyStream.filter(function (e) {
        if (e.which === eventKey) {
            return true;
        }
        return false;
    }).buffer(function () {
        return keyStream.debounce(250);
    }).map(function (list) {
        return list.length;
    }).filter(function (x) {
        return x >= 1;
    });
};

var hotkeys = function hotkeys(player, settings) {

    var keyStream = Rx.Observable.fromEvent(settings.$container.get(0), 'keyup');
    var rates = settings.playbackRates;

    // L key speed 1x 2x 3x ...
    tapSequenceHotKey(keyStream, 76).subscribe(function (numclicks) {
        var rate = rates[numclicks - 1];
        if (rate !== undefined) {
            player.playbackRate(rate);
        }
    });

    var hotkeys = {
        rewindKey: {
            key: function key(e) {
                // Backward Arrow Key
                return !e.ctrlKey && e.which === 37;
            },
            handler: function handler(player, options) {
                player.rangeControlBar.setPreviousFrame(parseInt(settings.seekBackwardStep, 10) / 1000);
            }
        },
        forwardKey: {
            key: function key(e) {
                // forward Arrow Key
                return !e.ctrlKey && e.which === 39;
            },
            handler: function handler(player, options) {
                player.rangeControlBar.setNextFrame(parseInt(settings.seekForwardStep, 10) / 1000);
            }
        },
        rewindFrameKey: {
            key: function key(e) {
                // Backward Arrow Key
                return e.ctrlKey && e.which === 37;
            },
            handler: function handler(player, options) {
                player.rangeControlBar.setPreviousFrame();
            }
        },
        forwardFrameKey: {
            key: function key(e) {
                // forward Arrow Key
                return e.ctrlKey && e.which === 39;
            },
            handler: function handler(player, options) {
                player.rangeControlBar.setNextFrame();
            }
        },
        playOnlyKey: {
            key: function key(e) {
                // L Key
                return !e.ctrlKey && e.which === 76;
            },
            handler: function handler(player, options) {
                if (player.paused()) {
                    player.play();
                }
            }
        },
        pauseOnlyKey: {
            key: function key(e) {
                // K Key
                return e.which === 75;
            },
            handler: function handler(player, options) {
                if (!player.paused()) {
                    player.pause();
                }
            }
        },
        frameBackward: {
            key: function key(e) {
                // < Key
                return e.which === 188;
            },
            handler: function handler(player, options) {
                player.rangeControlBar.setPreviousFrame();
            }
        },
        frameForward: {
            key: function key(e) {
                // MAJ + < = > Key
                return e.which === 190;
            },
            handler: function handler(player, options) {
                player.rangeControlBar.setNextFrame();
            }
        },
        moveDownRange: {
            key: function key(e) {
                // K Key
                return e.which === 40;
            },
            handler: function handler(player, options) {
                player.rangeCollection.setActiveRange('down');
            }
        },
        moveUpRange: {
            key: function key(e) {
                // K Key
                return e.which === 38;
            },
            handler: function handler(player, options) {
                player.rangeCollection.setActiveRange('up');
            }
        },
        entryCuePoint: {
            key: function key(e) {
                // I Key
                return !e.shiftKey && e.which === 73;
            },
            handler: function handler(player, options) {
                player.rangeStream.onNext({
                    action: 'update',
                    range: player.rangeControlBar.setStartPositon()
                });
            }
        },
        endCuePoint: {
            key: function key(e) {
                // O Key
                return !e.shiftKey && e.which === 79;
            },
            handler: function handler(player, options) {
                player.rangeStream.onNext({
                    action: 'update',
                    range: player.rangeControlBar.setEndPosition()
                });
            }
        },
        PlayAtEntryCuePoint: {
            key: function key(e) {
                // I Key
                return e.shiftKey && e.which === 73;
            },
            handler: function handler(player, options) {
                if (!player.paused()) {
                    player.pause();
                }
                player.currentTime(player.rangeControlBar.getStartPosition());
            }
        },
        PlayAtEndCuePoint: {
            key: function key(e) {
                // O Key
                return e.shiftKey && e.which === 79;
            },
            handler: function handler(player, options) {
                if (!player.paused()) {
                    player.pause();
                }
                player.currentTime(player.rangeControlBar.getEndPosition());
            }
        },
        toggleLoop: {
            key: function key(e) {
                // ctrl+ L Key
                return e.ctrlKey && e.which === 76;
            },
            handler: function handler(player, options) {
                player.rangeControlBar.toggleLoop();
            }
        },
        addRange: {
            key: function key(e) {
                // ctrl + N or  shift + "+"
                return e.ctrlKey && e.which === 78 || e.shiftKey && e.which === 107;
            },
            handler: function handler(player, options) {
                player.rangeControlBar.setNextFrame(parseInt(settings.seekForwardStep, 10) / 1000);
                var newRange = player.rangeCollection.addRange({});
                player.rangeStream.onNext({
                    action: 'create',
                    range: newRange
                });
            }
        },
        deleteRange: {
            key: function key(e) {
                // MAJ+SUPPR Key
                return e.shiftKey && e.which === 46;
            },
            handler: function handler(player, options) {
                player.rangeStream.onNext({
                    action: 'remove'
                });
            }
        }
    };

    return hotkeys;
};

exports.overrideHotkeys = overrideHotkeys;
exports.hotkeys = hotkeys;

/***/ }),

/***/ 407:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _get = function get(object, property, receiver) { if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { return get(parent, property, receiver); } } else if ("value" in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } };

var _underscore = __webpack_require__(1);

var _underscore2 = _interopRequireDefault(_underscore);

var _jquery = __webpack_require__(0);

var _jquery2 = _interopRequireDefault(_jquery);

var _video = __webpack_require__(12);

var _video2 = _interopRequireDefault(_video);

var _rangeCollection = __webpack_require__(374);

var _rangeCollection2 = _interopRequireDefault(_rangeCollection);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var Component = _video2.default.getComponent('Component');

var RangeItemContainer = function (_Component) {
    _inherits(RangeItemContainer, _Component);

    function RangeItemContainer(player, settings) {
        _classCallCheck(this, RangeItemContainer);

        var _this = _possibleConstructorReturn(this, (RangeItemContainer.__proto__ || Object.getPrototypeOf(RangeItemContainer)).call(this, player));

        _this.settings = settings;
        _this.$el = _this.renderHeaderContent();
        _this.rangeCollection = _this.addChild('RangeCollection', settings);
        _this.$el = _this.renderButtonsContent();

        _this.$el.on('click', '.add-range', function (event) {
            event.preventDefault();
            _this.rangeCollection.addRangeEvent();
        });

        _this.$el.on('click', '.export-ranges', function (event) {
            event.preventDefault();
            _this.rangeCollection.exportRangeEvent();
        });

        if (_this.settings.ChapterVttFieldName == false || _this.settings.meta_struct_id == undefined) {
            _this.$el.find('.export-vtt-ranges').prop('disabled', true);
        } else {
            _this.$el.on('click', '.export-vtt-ranges', function (event) {
                event.preventDefault();
                _this.rangeCollection.exportVTTRangeEvent();
            });
        }

        _this.$el.on('click', 'input[name=hover-chapters]', function (event) {
            var $el = (0, _jquery2.default)(event.currentTarget);
            _this.rangeCollection.setHoverChapter($el.is(':checked'));
        });
        return _this;
    }

    /**
     * Create the component's DOM element
     *
     * @return {Element}
     * @method createEl
     */


    _createClass(RangeItemContainer, [{
        key: 'createEl',
        value: function createEl() {
            this.container = _get(RangeItemContainer.prototype.__proto__ || Object.getPrototypeOf(RangeItemContainer.prototype), 'createEl', this).call(this, 'div', {
                className: 'range-item-container',
                innerHTML: ''
            });

            return this.container;
        }
    }, {
        key: 'renderHeaderContent',
        value: function renderHeaderContent() {
            var checkedValue = this.settings.preferences.overlapChapters == 1 ? 'checked' : '';
            (0, _jquery2.default)(this.el()).append('\n        <div class="header-chapters">\n            <h4>\n            ' + this.player_.localize('Chapters') + '\n</h2>\n<span class="checkbox-chapters"><input type="checkbox" name="hover-chapters" ' + checkedValue + ' value="hover"><span>' + this.player_.localize('No hover to chapter') + '</span></span>\n</div>');
            return (0, _jquery2.default)(this.el_);
        }
    }, {
        key: 'renderButtonsContent',
        value: function renderButtonsContent() {
            (0, _jquery2.default)(this.el()).append('\n<div class="btn-container">\n    <button class="btn add-range" type="button"><i class="fa fa-plus" aria-hidden="true"></i> ' + this.player_.localize('Add new range') + '</button>\n    <button class="btn export-vtt-ranges" type="button"><i class="fa fa-hdd" aria-hidden="true"></i> ' + this.player_.localize('Save as VTT') + '</button>\n    <!--<button class="button button-primary export-ranges" type="button"><i class="fa fa-arrow-circle-o-down" aria-hidden="true"></i> ' + this.player_.localize('Export video ranges') + '</button>-->\n</div>');
            return (0, _jquery2.default)(this.el_);
        }
    }, {
        key: 'dispose',
        value: function dispose() {
            this.$el.off();
        }
    }]);

    return RangeItemContainer;
}(Component);

_video2.default.registerComponent('RangeItemContainer', RangeItemContainer);

exports.default = RangeItemContainer;

/***/ }),

/***/ 408:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ })

});