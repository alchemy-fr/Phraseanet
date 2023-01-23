import videojs from 'video.js';
import RangeBar from './rangeBar';
/**
 * VideoJs Range Bar Collection
 */
const Component = videojs.getComponent('Component');

class RangeBarCollection extends Component {
    activeRangeItem;

    constructor(player, settings) {
        super(player, settings);
        this.settings = settings;
    }

    /**
     * Create the component's DOM element
     *
     * @return {Element}
     * @method createEl
     */
    createEl() {
        return super.createEl('div', {
            className: 'vjs-range-container',
            innerHTML: ''
        });
    }

    refreshRangeSliderPosition = (range) => {
        if (range.startPosition === -1 && range.endPosition === -1) {
            this.removeActiveRange(range)
            return;
        }

        if (this.activeRangeItem === undefined) {
            this.activeRangeItem = new RangeBar(this.player_, this.settings);//this.addChild('RangeBar', [this.player_, this.settings]);
        }
        this.activeRangeItem.updateRange(range);

        this.addChild(this.activeRangeItem);
    }

    removeActiveRange = (range) => {
        if (this.activeRangeItem !== undefined) {
            this.removeChild(this.activeRangeItem);
        }
    }
}

videojs.registerComponent('RangeBarCollection', RangeBarCollection);

export default RangeBarCollection;
