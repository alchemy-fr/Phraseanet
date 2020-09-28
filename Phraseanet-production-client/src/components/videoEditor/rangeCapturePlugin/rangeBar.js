import _ from 'underscore';
import videojs from 'video.js';
import noUiSlider from 'nouislider';
/**
 * VideoJs Range bar
 */
const Component = videojs.getComponent('Component');

class RangeBar extends Component {
    rangeBar;
    activeRange;

    constructor(player, settings) {
        super(player, settings);
        this.activeHandlePositions = [];
        this.activeRange = {
            id: 1,
            startPosition: -1,
            endPosition: -1
        };
        this.onUpdatedRange = _.debounce(this.onUpdatedRange, 300);
    }

    /**
     * Create the component's DOM element
     *
     * @return {Element}
     * @method createEl
     */
    createEl() {
        this.rangeBar = super.createEl('div', {
            id: 'connect',
            className: 'vjs-range-bar',
            innerHTML: '<div><span></span></div>'
        });

        noUiSlider.create(this.rangeBar, {
            start: [0, 0], // ((range.startPosition/videoDuration) * 100), ((range.endPosition/videoDuration) * 100)
            behaviour: 'drag',
            connect: true,
            range: {
                min: 0,
                max: 100
            }
        });

        let sliderBar = document.createElement('div');
        let sliderBase = this.rangeBar.querySelector('.noUi-base');

        // Give the bar a class for styling and add it to the slider.
        sliderBar.className += 'connect';
        sliderBase.appendChild(sliderBar);

        this.rangeBar.noUiSlider.on('update', (values, handle, a, b, handlePositions) => {
            let offset = handlePositions[handle];

            // Right offset is 100% - left offset
            if (handle === 1) {
                offset = 100 - offset;
            }

            // Pick left for the first handle, right for the second.
            sliderBar.style[handle ? 'right' : 'left'] = offset + '%';

            this.onUpdatedRange(handlePositions, handle);
        });

        // triggered when drag end - ensure last changed handle is synced with play head
        this.rangeBar.noUiSlider.on('change', (values, handle, a, b, handlePositions) => {
            this.onUpdatedRange(handlePositions, handle);
        });

        return this.rangeBar;
    }

    onUpdatedRange(handlePositions, activeHandle) {

        let videoDuration = this.player_.duration();

        // convert back percent into time:
        if (this.activeRange !== undefined) {
            // checkif changes happened:
            let oldRange = _.extend({}, this.activeRange);
            let newStartPosition = (handlePositions[0] / 100) * videoDuration;
            let newEndPosition = (handlePositions[1] / 100) * videoDuration;
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

    updateRange = (range) => {
        this.activeRange = range;
        let videoDuration = this.player_.duration();
        if (videoDuration > 0) {
            // set left side with percent update
            let left = ((range.startPosition / videoDuration) * 100);
            let right = ((range.endPosition / videoDuration) * 100);

            // set as null if not and handle
            if (this.activeHandlePositions.length > 0) {
                // don't update unchanged handle:
                left = left === this.activeHandlePositions[0] ? null : left;
                right = right === this.activeHandlePositions[1] ? null : right;
            }

            this.rangeBar.noUiSlider.set([left, right]);
        }
    }
}

videojs.registerComponent('RangeBar', RangeBar);

export default RangeBar;
