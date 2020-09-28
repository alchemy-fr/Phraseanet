import $ from 'jquery';
import _ from 'underscore';
import videojs from 'video.js';
import RangeItem from './rangeItem';
import {formatTime, formatToFixedDecimals} from './utils';
import Alerts from '../../utils/alert';
import dialog from './../../../phraseanet-common/components/dialog';

const humane = require('humane-js');

/**
 * VideoJs Range Collection
 */
const Component = videojs.getComponent('Component');

class RangeCollection extends Component {
    uid = 0;
    defaultRange = {
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
    rangeCollection = [];
    rangeItemComponentCollection = [];
    currentRange = false;
    isHoverChapterSelected = false;

    constructor(player, settings) {
        super(player, settings);
        this.settings = settings;
        this.$el = this.renderElContent();

        this.player_.activeRangeStream.subscribe((params) => {
            this.currentRange = params.activeRange;
            this.refreshRangeCollection()
        });

        this.isHoverChapterSelected = settings.preferences.overlapChapters == 1 ? true : false;
    }

    initDefaultRange() {
        // init collection with a new range if nothing specified:
        let newRange = this.addRange(this.defaultRange);
        this.player_.rangeStream.onNext({
            action: 'create',
            range: newRange
        });
    }

    addRangeEvent() {
        let newRange = this.addNewRange(this.defaultRange);
        this.player_.rangeStream.onNext({
            action: 'create',
            range: newRange
        });
    }

    exportRangeEvent() {
        this.player_.rangeStream.onNext({
            action: 'export-ranges',
            ranges: this.exportRanges()
        });
    }

    exportVTTRangeEvent() {
        this.player_.rangeStream.onNext({
            action: 'export-vtt-ranges',
            data: this.exportVttRanges()
        });
    }

    setHoverChapter(isChecked) {
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
    createEl() {
        return super.createEl('div', {
            className: 'range-collection-container',
            innerHTML: ''
        });
    }

    renderElContent() {
        return $(this.el());
    }

    update(range) {
        let updatedRange;

        if (!this.isExist(range)) {
            updatedRange = this.addNewRange(range);
        } else {
            updatedRange = this.updateRange(range);
        }
        return updatedRange;
    }

    updatingByDragging(range) {
        if(this.isHoverChapterSelected) { 
            this.syncRange(range); 
        }else { 
            this.updateRange(range); 
        }
    }

    isExist(range) {
        if (range.id === undefined) {
            return false;
        }
        for (let i = 0; i < this.rangeCollection.length; i++) {
            if (this.rangeCollection[i].id === range.id) {
                return true;
            }
        }
        return false;
    }

    remove(range) {
        let cleanedColl = _.filter(this.rangeCollection, (rangeData, index) => {
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
                })
            }

        }
        this.refreshRangeCollection();
    }

    addRange(range) {
        let lastId = this.uid = this.uid + 1;
        let newRange = _.extend({}, this.defaultRange, range, {id: lastId});
        newRange = this.setHandlePositions(newRange);
        this.rangeCollection.push(newRange);
        this.refreshRangeCollection();
        return newRange;
    }

    addNewRange(range) {
        let lastId = this.uid = this.uid + 1;
        let newRange = _.extend({}, this.defaultRange, range, {id: lastId});
        newRange.startPosition = this.getStartingPosition();
        newRange.endPosition = this.getEndPosition(newRange.startPosition);
        newRange = this.setHandlePositions(newRange);
        this.rangeCollection.push(newRange);
        this.refreshRangeCollection();
        return newRange;
    }

    getStartingPosition() {
        //tracker is at ending of previous range
        let gap = _.first(this.settings.record.sources).framerate * 0.001;
        let lastKnownPosition = this.player_.currentTime();

        if((lastKnownPosition + gap) < this.player_.duration()) {
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

    getEndPosition(startPosition) {
        let rangeDuration = this.player_.duration()/10;
        let endPosition = startPosition + rangeDuration;
        if(endPosition >= this.player_.duration()) {
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

    updateRange(range) {
        if (range.id !== undefined) {
            this.rangeCollection = _.map(this.rangeCollection, (rangeData, index) => {
                if (range.id === rangeData.id) {
                    range = this.setHandlePositions(range);
                    return range;
                }
                return rangeData;
            });
        }
        this.refreshRangeCollection();
        return range;
    }

    syncRange(range) { 
        let gap = _.first(this.settings.record.sources).framerate * 0.001;
        if (range.id !== undefined) {
            let index = _.findIndex(this.rangeCollection, (rangeData) => {
                return rangeData.id == range.id;
            });

            if(index !== null) {
                if(index < this.rangeCollection.length-1) {
                    //update next range
                    let rangeToUpdate = this.rangeCollection[index+1];
                    rangeToUpdate.startPosition = range.endPosition + gap <= rangeToUpdate.endPosition
                        ? range.endPosition + gap : rangeToUpdate.endPosition;
                    let newRange = this.setHandlePositions(rangeToUpdate);
                    this.rangeCollection[index+1] = newRange;
                }
                if (index > 0) {
                    //update previous range
                    let rangeToUpdate = this.rangeCollection[index-1];
                    rangeToUpdate.endPosition = range.startPosition - gap >= rangeToUpdate.startPosition
                        ? range.startPosition - gap : rangeToUpdate.startPosition;
                    let newRange = this.setHandlePositions(rangeToUpdate);
                    this.rangeCollection[index-1] = newRange;
                }

                let newRange = this.setHandlePositions(range);
                this.rangeCollection[index] = newRange;
            }
        }
        this.refreshRangeCollection();
        return range;
     }


    setHandlePositions(range) {
        let videoDuration = this.player_.duration();
        if (videoDuration > 0) {
            let left = ((range.startPosition / videoDuration) * 100);
            let right = ((range.endPosition / videoDuration) * 100);

            range.handlePositions = {left, right};
        }
        return range;
    }

    getRangeById(id) {
        let foundRange = {};
        for (let i = 0; i < this.rangeCollection.length; i++) {
            if (this.rangeCollection[i].id === id) {
                foundRange = this.rangeCollection[i];
            }
        }
        return foundRange;
    }

    exportRanges = () => {
        let exportedRanges = [];
        for (let i = 0; i < this.rangeCollection.length; i++) {
            exportedRanges.push({
                startPosition: this.rangeCollection[i].startPosition,
                endPosition: this.rangeCollection[i].endPosition
            })
        }
        return exportedRanges;
    }
    exportVttRanges = () => {
        let exportedRanges = [`WEBVTT
`];
        let titleValue= document.getElementById("default-video-chapter-label").value;
        for (let i = 0; i < this.rangeCollection.length; i++) {
            let exportableData = {
                title: this.rangeCollection[i].title != "" ? this.rangeCollection[i].title : titleValue
            };

            if (this.rangeCollection[i].image.src !== '') {
                exportableData.image = this.rangeCollection[i].image.src;
                exportableData.manualSnapShot = this.rangeCollection[i].manualSnapShot || false;
            }

            exportedRanges.push(`${i + 1}
${formatTime(this.rangeCollection[i].startPosition, 'hh:mm:ss.mmm')} --> ${formatTime(this.rangeCollection[i].endPosition, 'hh:mm:ss.mmm')}
${JSON.stringify(exportableData)}
`)
        }
        return exportedRanges.join('\n');
    }

    get = (model) => {
        if (model === undefined) {
            return this.rangeCollection;
        }
        return this.getRangeById(model.id);
    }

    splice = (...args) => {
        return Array.prototype.splice.apply(this.rangeCollection, args);
    }

    getIndex = (model) => {
        let index = {};
        for (let i = 0; i < this.rangeCollection.length; i++) {
            if (this.rangeCollection[i].id === model.id) {
                index = i;
            }
        }
        return index;
    }

    getSelection = () => {
        let selectedRanges = [];
        for (let i = 0; i < this.rangeCollection.length; i++) {
            if (this.rangeCollection[i].selected === true) {
                selectedRanges.push(this.rangeCollection[i]);
            }
        }
        return selectedRanges;
    }

    resetSelection = () => {
        for (let i = 0; i < this.rangeCollection.length; i++) {
            this.rangeCollection[i].selected = false;
        }
    }

    addToSelection = (model) => {
        for (let i = 0; i < this.rangeCollection.length; i++) {
            if (this.rangeCollection[i].id === model.id) {
                this.rangeCollection[i].selected = true;
            }
        }
    }

    removeFromSelection = (model) => {
        for (let i = 0; i < this.rangeCollection.length; i++) {
            if (this.rangeCollection[i].id === model.id) {
                this.rangeCollection[i].selected = false;
            }
        }
    }

    getFirstSelected = () => {
        let firstModel = false;
        for (let i = 0; i < this.rangeCollection.length; i++) {
            if (this.rangeCollection[i].selected === true && firstModel === false) {
                firstModel = this.rangeCollection[i];
            }
        }
        return firstModel;
    }

    getLastSelected = () => {
        let lastModel = false;
        for (let i = 0; i < this.rangeCollection.length; i++) {
            if (this.rangeCollection[i].selected === true) {
                lastModel = this.rangeCollection[i];
            }
        }
        return lastModel;
    }

    reset = (collection) => {
        this.rangeCollection = collection;
        // refresh internal indexes:
        for (let i = 0; i < this.rangeCollection.length; i++) {
            this.rangeCollection[i].index = i;
        }
        this.refreshRangeCollection();
    }

    setActiveRange = (direction) => {
        if (this.currentRange === false) {
            return;
        }
        let toIndex = this.currentRange.index - 1;

        if (direction === 'down') {
            toIndex = this.currentRange.index + 1;
        }

        if (this.rangeCollection[toIndex] !== undefined) {

            this.player_.rangeStream.onNext({
                action: 'change',
                range: this.rangeCollection[toIndex]
            });
        }
    }

    moveRange = (direction) => {
        if (this.currentRange === false) {
            return;
        }
        let collection = this.get();
        let toIndex = this.currentRange.index - 1;

        if (direction === 'down') {
            toIndex = this.currentRange.index + 1;
        }
        this.addToSelection(this.currentRange);
        let selectedModels = this.getSelection();

        for (let i = selectedModels.length; i--;) {
            let fromIndex = this.getIndex(this.get(selectedModels[i]));

            collection.splice(toIndex, 0, this.splice(fromIndex, 1)[0]);
        }

        this.reset(collection);
    }

    refreshRangeCollection = () => {
        // remove any existing items
        for (let i = 0; i < this.rangeItemComponentCollection.length; i++) {
            this.rangeItemComponentCollection[i].dispose();
            this.removeChild(this.rangeItemComponentCollection[i]);
        }
        this.rangeItemComponentCollection = [];

        let activeId = 0;
        if (this.currentRange !== false) {
            activeId = this.currentRange.id;
        }

        for (let i = 0; i < this.rangeCollection.length; i++) {
            let model = _.extend({}, this.rangeCollection[i], {index: i});
            let item = new RangeItem(this.player_, {
                model: model,
                collection: this,
                isActive: this.rangeCollection[i].id === activeId ? true : false
            }, this.settings);

            this.rangeItemComponentCollection.push(item);
            this.addChild(item);


        }
    }

    exportRangesData = (rangeData) => {
        var title = this.settings.translations.alertTitle;
        var message = this.settings.translations.updateTitle;
        var services = this.settings.services;
        $.ajax({
            type: 'POST',
            url: `${this.settings.baseUrl}prod/tools/metadata/save/`,
            data: {
                databox_id: this.settings.databoxId,
                record_id: this.settings.recordId,
                meta_struct_id: this.settings.meta_struct_id,
                value: rangeData
            },
            success: function (data) {
                if (!data.success) {
                    humane.error(data.message);
                } else {
                    humane.info(message);
                }
            }
        });
};
}

videojs.registerComponent('RangeCollection', RangeCollection);

export default RangeCollection;
