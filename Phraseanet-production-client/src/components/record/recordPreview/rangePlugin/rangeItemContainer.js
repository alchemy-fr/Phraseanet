import _ from 'underscore';
import $ from 'jquery';
import videojs from 'video.js';
import RangeCollection from './rangeCollection';

const Component = videojs.getComponent('Component');

class RangeItemContainer extends Component {
    container;
    settings;
    rangeCollection;

    constructor(player, settings) {
        super(player);
        this.settings = settings;
        this.$el = this.renderHeaderContent();
        this.rangeCollection = this.addChild('RangeCollection', settings);
        this.$el = this.renderButtonsContent();

        this.$el.on('click', '.add-range', (event) => {
            event.preventDefault();
            this.rangeCollection.addRangeEvent();
        });

        this.$el.on('click', '.export-ranges', (event) => {
            event.preventDefault();
            this.rangeCollection.exportRangeEvent();
        });

        if(this.settings.ChapterVttFieldName == false
            || this.settings.meta_struct_id == undefined) {
            this.$el.find('.export-vtt-ranges').prop('disabled', true);
        }else {
            this.$el.on('click', '.export-vtt-ranges', (event) => {
                event.preventDefault();
                this.rangeCollection.exportVTTRangeEvent();
            });
        }

        this.$el.on('click', 'input[name=hover-chapters]', (event) => {
            let $el = $(event.currentTarget);
            this.rangeCollection.setHoverChapter($el.is(':checked'));
        });
    }

    /**
     * Create the component's DOM element
     *
     * @return {Element}
     * @method createEl
     */
    createEl() {
        this.container = super.createEl('div', {
            className: 'range-item-container',
            innerHTML: ''
        });

        return this.container;
    }

    renderHeaderContent() {
        var checkedValue = this.settings.preferences.overlapChapters == 1 ? 'checked' : '';
        $(this.el()).append(`
        <div class="header-chapters">
            <h4>
            ${this.player_.localize('Chapters')}
</h2>
<span class="checkbox-chapters"><input type="checkbox" name="hover-chapters" ${checkedValue} value="hover"><span>${this.player_.localize('No hover to chapter')}</span></span>
</div>`);
        return $(this.el_);
    }

    renderButtonsContent() {
        $(this.el()).append(`
<div class="btn-container">
    <button class="btn add-range" type="button"><i class="fa fa-plus" aria-hidden="true"></i> ${this.player_.localize('Add new range')}</button>
    <button class="btn export-vtt-ranges" type="button"><i class="fa fa-hdd" aria-hidden="true"></i> ${this.player_.localize('Save as VTT')}</button>
    <!--<button class="button button-primary export-ranges" type="button"><i class="fa fa-arrow-circle-o-down" aria-hidden="true"></i> ${this.player_.localize('Export video ranges')}</button>-->
</div>`);
        return $(this.el_);
    }

    dispose() {
        this.$el.off();
    }
}

videojs.registerComponent('RangeItemContainer', RangeItemContainer);

export default RangeItemContainer;
