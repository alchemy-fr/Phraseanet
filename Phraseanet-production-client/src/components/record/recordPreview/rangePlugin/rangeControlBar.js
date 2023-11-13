import $ from 'jquery';
import _ from 'underscore';
import videojs from 'video.js';
import {formatMilliseconds, formatTime} from './utils';
/**
 * VideoJs Range Control Bar
 */
const Component = videojs.getComponent('Component');


const icons = `
<svg style="position: absolute; width: 0; height: 0;" width="0" height="0" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
<defs>
<symbol id="icon-loop-range" viewBox="0 0 30 30">
<title>loop-range</title>
<path class="path1" d="M25.707 9.92l-2.133 1.813h1.707c0.107 0 0.32 0.213 0.32 0.213v8.107c0 0.107-0.213 0.213-0.32 0.213h-11.093l-0.853 2.133h11.947c1.067 0 2.453-1.28 2.453-2.347v-8.107c0-1.067-1.067-1.92-2.027-2.027z"></path>
<path class="path2" d="M7.040 22.4l1.92-2.133h-2.24c-0.107 0-0.32-0.213-0.32-0.213v-8.107c0 0 0.213-0.213 0.32-0.213h11.627l0.853-2.133h-12.48c-1.173 0-2.453 1.28-2.453 2.347v8.107c0 1.067 1.28 2.347 2.453 2.347h0.32z"></path>
<path class="path3" d="M17.493 6.827l4.053 3.947-4.053 3.947z"></path>
<path class="path4" d="M14.933 24.96l-3.947-3.84 3.947-3.947z"></path>
</symbol>
<symbol id="icon-prev-forward-frame" viewBox="0 0 30 30">
<title>prev-forward-frame</title>
<path class="path1" d="M25.432 9.942l-9.554 9.554-3.457-3.457 9.554-9.554 3.457 3.457z"></path>
<path class="path2" d="M21.912 25.578l-9.554-9.554 3.457-3.457 9.554 9.554-3.457 3.457z"></path>
<path class="path3" d="M6.578 6.489h2.578v19.111h-2.578v-19.111z"></path>
</symbol>
<symbol id="icon-next-forward-frame" viewBox="0 0 30 30">
<title>next-forward-frame</title>
<path class="path1" d="M10.131 6.462l9.554 9.554-3.457 3.457-9.554-9.554 3.457-3.457z"></path>
<path class="path2" d="M6.611 22.018l9.554-9.554 3.457 3.457-9.554 9.554-3.457-3.457z"></path>
<path class="path3" d="M22.756 6.489h2.578v19.111h-2.578v-19.111z"></path>
</symbol>
<symbol id="icon-prev-frame" viewBox="0 0 30 30">
<title>prev-frame</title>
<path class="path1" d="M22.538 9.962l-9.554 9.554-3.457-3.457 9.554-9.554 3.457 3.457z"></path>
<path class="path2" d="M19.018 25.558l-9.554-9.554 3.457-3.457 9.554 9.554-3.457 3.457z"></path>
</symbol>
<symbol id="icon-next-frame" viewBox="0 0 30 30">
<title>next-frame</title>
<path class="path1" d="M12.984 6.441l9.554 9.554-3.457 3.457-9.554-9.554 3.457-3.457z"></path>
<path class="path2" d="M9.464 22.039l9.554-9.554 3.457 3.457-9.554 9.554-3.457-3.457z"></path>
</symbol>
<symbol id="icon-cue-start" viewBox="0 0 30 30">
<title>cue-start</title>
<path class="path1" d="M20.356 24.089v-15.733c0-0.533-0.356-0.889-0.889-0.889h-8c-0.444 0-0.889 0.356-0.889 0.889v5.067c0 0.533 0.267 1.156 0.622 1.511l8.622 9.422c0.267 0.356 0.533 0.267 0.533-0.267z"></path>
</symbol>
<symbol id="icon-cue-end" viewBox="0 0 30 30">
<title>cue-end</title>
<path class="path1" d="M10.578 24.089v-15.733c0-0.533 0.356-0.889 0.889-0.889h8c0.444 0 0.889 0.356 0.889 0.889v5.067c0 0.533-0.267 1.156-0.622 1.511l-8.622 9.422c-0.267 0.356-0.533 0.267-0.533-0.267z"></path>
</symbol>
<symbol id="icon-trash" viewBox="0 0 30 30">
<title>trash</title>
<path class="path1" d="M22.667 8.978h-3.822v-1.333c0-0.8-0.622-1.422-1.422-1.422h-2.756c-0.8 0-1.422 0.622-1.422 1.422v1.422h-3.822c-0.178 0-0.356 0.178-0.356 0.356v0.711c0 0.178 0.178 0.356 0.356 0.356h13.333c0.178 0 0.356-0.178 0.356-0.356v-0.711c-0.089-0.267-0.267-0.444-0.444-0.444zM14.667 8.978v0-1.422h2.756v1.422h-2.756z"></path>
<path class="path2" d="M21.778 11.111h-11.733c-0.267 0-0.356 0.089-0.356 0.356v14.133c0 0 0.089 0.267 0.356 0.267h11.733c0.267 0 0.533-0.089 0.533-0.356v-14.133c0-0.178-0.267-0.267-0.533-0.267zM13.156 23.378c0 0.178-0.178 0.356-0.356 0.356h-0.711c-0.178 0-0.356-0.178-0.356-0.356v-9.778c0-0.178 0.178-0.356 0.356-0.356h0.711c0.178 0 0.356 0.178 0.356 0.356v9.778zM16.711 23.378c0 0.178-0.178 0.356-0.356 0.356h-0.711c-0.178 0-0.356-0.178-0.356-0.356v-9.778c0-0.178 0.178-0.356 0.356-0.356h0.711c0.178 0 0.356 0.178 0.356 0.356v9.778zM20.178 23.378c0 0.178-0.178 0.356-0.356 0.356h-0.711c-0.178 0-0.356-0.178-0.356-0.356v-9.778c0-0.178 0.178-0.356 0.356-0.356h0.711c0.178 0 0.356 0.178 0.356 0.356v9.778z"></path>
</symbol>
</defs>
</svg>
`;
const defaults = {
    frameRate: 24
};
class RangeControlBar extends Component {
    rangeControlBar;
    frameRate;
    currentRange;

    constructor(player, options) {
        super(player, options);
        const settings = videojs.mergeOptions(defaults, options);

        //this.settings = settings;
        this.looping = false;
        this.loopData = []; // @dprecated
        this.frameStep = 1;

        this.frameRate = settings.frameRates[this.player_.cache_.src];
        this.frameDuration = (1 / this.frameRate);

        this.currentRange = false;
        this.player_.activeRangeStream.subscribe((params) => {
            this.currentRange = params.activeRange;
            this.onRefreshDisplayTime();
        })
    }

    rangeMenuTemplate() {
        return `<div class="range-capture-container">

<button class="control-button" id="start-range" videotip="${this.player_.localize('Start Range')}"><svg class="icon icon-cue-start"><use xlink:href="#icon-cue-start"></use></svg></button>
<button class="control-button" id="end-range" videotip="${this.player_.localize('End Range')}"><svg class="icon icon-cue-end"><use xlink:href="#icon-cue-end"></use></svg><span class="icon-label"> icon-cue-end</span></button>
<button class="control-button" id="delete-range" videotip="${this.player_.localize('Remove current Range')}"><svg class="icon icon-trash"><use xlink:href="#icon-trash"></use></svg><span class="icon-label"> remove</span></button>
<button class="control-button" id="loop-range" videotip="${this.player_.localize('Toggle loop')}"><svg class="icon icon-loop-range"><use xlink:href="#icon-loop-range"></use></svg><span class="icon-label"> loop</span></button>
<button class="control-button" id="prev-forward-frame" videotip="${this.player_.localize('Go to start point')}"><svg class="icon icon-prev-forward-frame"><use xlink:href="#icon-prev-forward-frame"></use></svg><span class="icon-label"> prev forward frame</span></button>
<button class="control-button" id="backward-frame" videotip="${this.player_.localize('Go 1 frame backward')}"><svg class="icon icon-prev-frame"><use xlink:href="#icon-prev-frame"></use></svg><span class="icon-label"> prev frame</span></button>
<span id="display-start" class="display-time">
<input type="text" class="range-input" data-scope="start-range" id="start-range-input-hours" value="00" size="2"/>:
<input type="text" class="range-input" data-scope="start-range" id="start-range-input-minutes" value="00" size="2"/>:
<input type="text" class="range-input" data-scope="start-range" id="start-range-input-seconds" value="00" size="2"/>s
<input type="text" class="range-input" data-scope="start-range" id="start-range-input-frames" value="00" size="2"/>f
</span>
<span id="display-end" class="display-time">
<input type="text" class="range-input" data-scope="end-range" id="end-range-input-hours" value="00" size="2"/>:
<input type="text" class="range-input" data-scope="end-range" id="end-range-input-minutes" value="00" size="2"/>:
<input type="text" class="range-input" data-scope="end-range" id="end-range-input-seconds" value="00" size="2"/>s
<input type="text" class="range-input" data-scope="end-range" id="end-range-input-frames" value="00" size="2"/>f</span>
<button class="control-button" id="forward-frame"  videotip="${this.player_.localize('Go 1 frame forward')}"><svg class="icon icon-next-frame"><use xlink:href="#icon-next-frame"></use></svg><span class="icon-label"> next frame</span></button>
<button class="control-button" id="next-forward-frame"  videotip="${this.player_.localize('Go to end point')}"><svg class="icon icon-next-forward-frame"><use xlink:href="#icon-next-forward-frame"></use></svg><span class="icon-label"> next forward frame</span></button>

<span id="display-current" class="display-time" videotip="${this.player_.localize('Elapsed time')}" data-mode="elapsed">E. 00:00:00s 00f</span>
</div>`;
    }

    /**
     * Create the component's DOM element
     *
     * @return {Element}
     * @method createEl
     */
    createEl() {
        this.rangeControlBar = super.createEl('div', {
            className: 'range-control-bar',
            innerHTML: ''
        });
        $(this.rangeControlBar)
            .on('click', '#start-range', (event) => {
                event.preventDefault();
                this.player_.rangeStream.onNext({
                    action: 'update',
                    handle: 'start',
                    range: this.setStartPositon()
                });
            })
            .on('click', '#end-range', (event) => {
                event.preventDefault();
                this.player_.rangeStream.onNext({
                    action: 'update',
                    handle: 'end',
                    range: this.setEndPosition()
                });
            })
            .on('click', '#delete-range', (event) => {
                event.preventDefault();
                this.player_.rangeStream.onNext({
                    action: 'remove'
                });
            })
            .on('click', '#backward-frame', (event) => {
                event.preventDefault();
                this.setPreviousFrame();
            })
            .on('click', '#forward-frame', (event) => {
                event.preventDefault();
                this.setNextFrame();
            })
            .on('click', '#prev-forward-frame', (event) => {
                event.preventDefault();
                if (!this.player_.paused()) {
                    this.player_.pause();
                }
                this.player_.currentTime(this.getStartPosition())
            })
            .on('click', '#next-forward-frame', (event) => {
                event.preventDefault();
                if (!this.player_.paused()) {
                    this.player_.pause();
                }
                this.player_.currentTime(this.getEndPosition())
            })
            .on('click', '#loop-range', (event) => {
                event.preventDefault();
                this.toggleLoop();
            })
            .on('click', '#display-current', (event) => {
                let $el = $(event.currentTarget);
                let mode = $el.data('mode');

                console.log('mode:', $el.data('mode'))
                switch (mode) {
                    case 'remaining':
                        $el.data('mode', 'elapsed');
                        $el.attr('videotip', this.player_.localize('Elapsed time'))
                        break;
                    case 'elapsed':
                        if (this.currentRange === false) {
                            $el.data('mode', 'remaining');
                            $el.attr('videotip', this.player_.localize('Remaining time'))
                        } else {
                            $el.data('mode', 'duration');
                            $el.attr('videotip', this.player_.localize('Range duration'))
                        }
                        break;
                    case 'duration':
                        $el.data('mode', 'remaining');
                        $el.attr('videotip', this.player_.localize('Remaining time'))
                        break;
                    default:
                        if (this.currentRange === false) {
                            $el.data('mode', 'remaining');
                            $el.attr('videotip', this.player_.localize('Remaining time'))
                        } else {
                            $el.data('mode', 'duration');
                            $el.attr('videotip', this.player_.localize('Range duration'))
                        }
                }
                this.onRefreshDisplayTime();
                /*// toggle mode
                 if ($el.data('mode') === 'remaining') {
                 $el.data('mode', 'current');
                 $el.attr('videotip', this.player_.localize('Elapsed time'))
                 } else {
                 $el.data('mode', 'remaining');
                 $el.attr('videotip', this.player_.localize('Remaining time'))
                 }*/
            })
            .on('keyup', '.range-input', (event) => {
                if (event.keyCode === 13) {
                    $(event.currentTarget).blur();
                }
            })
            .on('focus', '.range-input', (event) => {
                event.currentTarget.setSelectionRange(0, event.currentTarget.value.length)
            })
            .on('blur', '.range-input', (event) => {
                event.preventDefault();
                let $el = $(event.currentTarget);

                if (this.validateScopeInput($el.data('scope'))) {
                    // this.validateScopeInput();
                    let newCurrentTime = this.getScopeInputTime($el.data('scope'));
                    this.player_.currentTime(newCurrentTime);
                    $el.addClass('is-valid');
                    setTimeout(() => $el.removeClass('is-valid'), 500);
                } else {
                    $el.addClass('has-error');
                    setTimeout(() => $el.removeClass('has-error'), 1200);
                }
                // fallback on old values if have errors:
                this.player_.rangeStream.onNext({
                    action: 'update',
                    handle: ($el.data('scope') === 'start-range' ? 'start' : 'end'),
                    range: ($el.data('scope') === 'start-range' ? this.setStartPositon() : this.setEndPosition())
                });

            });

        $(this.rangeControlBar).append(this.rangeMenuTemplate());

        this.player_.on('timeupdate', () => {
            this.onRefreshDisplayTime();
            // if a loop exists
            if (this.looping === true && this.loopData.length > 0) {

                let start = this.currentRange.startPosition; //this.loopData[0];
                let end = this.currentRange.endPosition; //this.loopData[1];

                var current_time = this.player_.currentTime();

                if (current_time < start || end > 0 && current_time >= end) {
                    this.player_.currentTime(start);
                    setTimeout(() => {
                        // Resume play if the element is paused.
                        if (this.player_.paused()) {
                            this.player_.play();
                        }
                    }, 150);
                }

            }
        });
        return this.rangeControlBar;
    }

    refreshRangePosition(range, handle) {
        handle = handle || false;
        this.updateRangeDisplay('start-range', range.startPosition);
        this.updateRangeDisplay('end-range', range.endPosition);

        if (handle === 'start') {
            this.player_.currentTime(range.startPosition)
        } else if (handle === 'end') {
            this.player_.currentTime(range.endPosition)
        }
    }

    setRangePositonToBeginning(range) {
        this.player_.currentTime(range.startPosition);
    }

    updateRangeDisplay(scope, currentTime) {

        let format = formatMilliseconds(currentTime, this.frameRate);

        $(`#${scope}-input-hours`).val(('0' + format.hours).slice(-2));
        $(`#${scope}-input-minutes`).val(('0' + format.minutes).slice(-2));
        $(`#${scope}-input-seconds`).val(('0' + format.seconds).slice(-2));
        $(`#${scope}-input-frames`).val(('0' + format.frames).slice(-2));
    }

    getScopeInputs(scope) {
        return {
            hours: $(`#${scope}-input-hours`).val(),
            minutes: $(`#${scope}-input-minutes`).val(),
            seconds: $(`#${scope}-input-seconds`).val(),
            frames: $(`#${scope}-input-frames`).val()
        }
    }

    validateScopeInput(scope) {
        let scopeInputs = this.getScopeInputs(scope);
        var regex = /^\d+$/;    // allow only numbers [0-9]
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

    getScopeInputTime(scope) {
        let scopeInputs = this.getScopeInputs(scope);
        let hours = parseInt(scopeInputs.hours, 10);
        let minutes = parseInt(scopeInputs.minutes, 10);
        let seconds = parseInt(scopeInputs.seconds, 10);
        let frames = parseInt(scopeInputs.frames, 10);
        let milliseconds = frames === 0 ? 0 : (((1000 / this.frameRate) * frames) / 1000).toFixed(2);

        return (hours * 3600) + (minutes * 60) + (seconds) + parseFloat(milliseconds);
    }

    toggleLoop() {
        let $el = $('#loop-range');
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

    loopBetween(range) {
        range = range || this.player_.activeRange;
        this.loop(range.startPosition, range.endPosition);
    }

    loop(start, end) {
        this.looping = true;
        this.player_.currentTime(start);

        // @deprecated
        this.loopData = [start, end];

        setTimeout(() => {
            // Resume play if the element is paused.
            if (this.player_.paused()) {
                this.player_.play();
            }
        }, 150);
    }

    setStartPositon() {
        if (this.currentRange === false) {
            throw new Error('setStartPositon > no range provided')
        }
        let newRange = _.extend({}, this.currentRange);
        // set start
        newRange.startPosition = this.player_.currentTime();
        newRange.startPositionFormated = formatMilliseconds(this.player_.currentTime(), this.frameRate);
        let firstTime = newRange.startPosition === -1 && newRange.endPosition === -1;
        let startBehindEnd = newRange.startPosition > newRange.endPosition;

        if (firstTime || startBehindEnd) {
            newRange.endPosition = this.player_.duration()
        }
        return newRange;
    }

    getStartPosition() {
        if (this.currentRange === false) {
            throw new Error('getStartPosition > no range provided')
        }
        return this.currentRange.startPosition;
    }

    setEndPosition() {
        if (this.currentRange === false) {
            throw new Error('setEndPositon > no range provided')
        }
        let newRange = _.extend({}, this.currentRange);
        newRange.endPosition = this.player_.currentTime();
        newRange.endPositionFormated = formatMilliseconds(this.player_.currentTime(), this.frameRate);
        let firstTime = newRange.startPosition === -1 && newRange.endPosition === -1;
        let startBehindEnd = newRange.startPosition > newRange.endPosition;
        if (firstTime || startBehindEnd) {
            newRange.startPosition = 0;
        }
        return newRange;
    }


    getEndPosition() {
        if (this.currentRange === false) {
            throw new Error('getEndPosition > no range provided')
        }
        return this.currentRange.endPosition;
    }

    removeActiveRange() {
        this.updateRangeDisplay('start-range', 0);
        this.updateRangeDisplay('end-range', 0);
    }

    /**
     *
     * @param step (frames)
     */
    setNextFrame(step) {
        let position = this.player_.currentTime();
        if (!this.player_.paused()) {
            this.player_.pause();
        }

        if (step !== undefined) {
            this.player_.currentTime(position + step);
        } else {
            this.player_.currentTime(position + (this.frameDuration * this.frameStep));
        }
    }

    /**
     *
     * @param step (frames)
     */
    setPreviousFrame(step) {
        let position = this.player_.currentTime();
        if (!this.player_.paused()) {
            this.player_.pause();
        }

        if (step !== undefined) {
            this.player_.currentTime(position - step);
        } else {
            this.player_.currentTime(position - (this.frameDuration * this.frameStep));
        }
    }

    onRefreshDisplayTime() {
        if (this.$displayCurrent === undefined) {
            this.$displayCurrent = $('#display-current');
        }
        if (this.$displayCurrent.length > 0) {
            switch (this.$displayCurrent.data('mode')) {
                case 'remaining':
                    this.$displayCurrent.html('R. ' + formatTime(this.player_.remainingTime(), '', this.frameRate))
                    break;
                case 'elapsed':
                    this.$displayCurrent.html('E. ' + formatTime(this.player_.currentTime(), '', this.frameRate))
                    break;
                case 'duration':
                    this.$displayCurrent.html('D. ' + formatTime(this.currentRange.endPosition - this.currentRange.startPosition, '', this.frameRate))
                    break;
                default:
            }
        }
    }

}

videojs.registerComponent('RangeControlBar', RangeControlBar);

export default RangeControlBar;
