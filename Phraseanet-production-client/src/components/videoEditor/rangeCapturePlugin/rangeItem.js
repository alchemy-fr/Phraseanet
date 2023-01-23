import _ from 'underscore';
import $ from 'jquery';
import videojs from 'video.js';
import {formatTime} from './utils';
import SortableComponent from './sortableComponent';
/**
 * VideoJs Range bar
 */
const Component = videojs.getComponent('Component');
let chapterLabel = document.getElementById("default-video-chapter-label").value;
let rangeItemTemplate = (model, frameRate) => {
    let image = '';
    if (model.image.src !== '') {
        image = `<div class="range-item-screenshot">
<div>
<div id="capture-thumbnail-icon"/>
<img src="${model.image.src}" style="height: 60px;width:auto;">
</div>
</div>`;
    }

    return `
    <div class="range-item-index-div">
<span class="range-item-index">${model.index + 1}</span>
</div>
${image}
<div class="range-item-time-data">
    <span class="range-item-title">
     <input class="range-title range-input" type="text" value="${model.title != chapterLabel ? model.title : ''}" placeholder="${chapterLabel}">
    </span>
    <div class="display-time-container">
      <span class="icon-container small-icon"><svg class="icon icon-cue-start"><use xlink:href="#icon-cue-start"></use></svg></span>
      <span class="display-time">${formatTime(model.startPosition, 'hms', frameRate)}</span>
      <span class="display-time">${formatTime(model.endPosition, 'hms', frameRate)}</span>
      <span class="icon-container small-icon"><svg class="icon icon-cue-end"><use xlink:href="#icon-cue-end"></use></svg></span>
    </div>
    <div class="progress-container">
    <div class="progress-bar" style="left:${model.handlePositions.left}%;width:${model.handlePositions.right - model.handlePositions.left}%; height: 100%"></div>
    <div class="progress-value">${formatTime(model.endPosition - model.startPosition, 'hms', frameRate)}</div>
    </div>
</div>
<div class="range-item-close">
    <div class="remove-range"></div>
</div>
`;
    // <button class="control-button remove-range"><svg class="icon icon-trash"><use xlink:href="#icon-trash"></use></svg><span class="icon-label"> remove</span></button>
};
class RangeItem extends Component {
    rangeOptions;
    settings;
    item;

    constructor(player, rangeOptions, settings) {
        super(player, rangeOptions);
        this.frameRate = settings.frameRates[this.player_.cache_.src];
        this.settings = settings;
        this.$el = this.renderElContent();

        this.$el.on('click', '#capture-thumbnail-icon', (event) => {
            event.preventDefault();
            this.player_.rangeStream.onNext({
                action: 'capture',
                range: rangeOptions.model
            });
            // don't trigger other events
            event.stopPropagation();
        });

        this.$el.on('click', (event) => {
            // event.preventDefault();
            let $el = $(event.currentTarget);
            if (rangeOptions.isActive === false) {
                // broadcast active state:
                this.player_.rangeStream.onNext({
                    action: 'change',
                    range: rangeOptions.model
                });
            }
        })
        this.$el.on('click', '.remove-range', (event) => {
            event.preventDefault();
            this.player_.rangeStream.onNext({
                action: 'remove',
                range: rangeOptions.model
            });
            // don't trigger other events
            event.stopPropagation();
        })
        this.$el.on('click focus', '.range-title', (event) => {
            event.stopPropagation(); // stop unfocus
        });
        this.$el.on('keydown', '.range-title', (event) => {
            event.stopPropagation();
        });
        this.$el.on('keyup', '.range-title', (event) => {
            if (event.keyCode === 13) {
                $(event.currentTarget).blur();
            }
        })
        this.$el.on('blur', '.range-title', (event) => {
            event.preventDefault();
            let $el = $(event.currentTarget);
            this.player_.rangeStream.onNext({
                action: 'update',
                range: _.extend(rangeOptions.model, {
                    title: $el.val()
                })
            });
            // don't trigger other events
            event.stopPropagation();
        })

        this.sortable = new SortableComponent(rangeOptions, this.$el);

    }

    /**
     * Create the component's DOM element
     *
     * @return {Element}
     * @method createEl
     */
    createEl() {
        this.rangeOptions = super.createEl('div', {
            className: 'range-collection-item',
            innerHTML: ''
        }, {
            draggable: true
        });

        return this.rangeOptions;
    }

    renderElContent() {
        $(this.el_).append(rangeItemTemplate(this.options_.model, this.frameRate));
        if (this.options_.isActive) {
            $(this.el_).addClass('active')
        }
        return $(this.el_);
    }

    dispose() {
        this.$el.off();
        this.sortable.dispose();
    }
}

videojs.registerComponent('RangeItem', RangeItem);

export default RangeItem;
