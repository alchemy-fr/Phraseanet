import * as _ from 'underscore';
import $ from 'jquery';

class SortableComponent {
    tagName;
    template;
    customEvents;
    parent;
    options;
    dragTooltipContainer;
    isSortingEnabled;
    defaultOptions = {
        selectionClass: 'drag-selected',
        overClass: 'drag-over',
    }

    constructor(options, $el) {
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
        this.$el
            .on('mousedown', (event) => this.onMouseDown(event))
            .on('mouseup', (event) => this.onMouseUp(event))
            .on('drag', (event) => this.onDrag(event))
            .on('dragstart', (event) => this.onDragStart(event))
            .on('dragenter', (event) => this.onDragEnter(event))
            .on('dragleave', (event) => this.onDragLeave(event))
            .on('dragover', (event) => this.onDragOver(event))
            .on('drop', (event) => this.drop(event));

        this.dragTooltipContainer = false;

        // create tooltip container if not existing:
        if ($('.drag-mousemove-container').length === 0) {
            $('body').append('<div class="drag-mousemove-container" style="position:absolute"></div>');
        }
        this.dragTooltipContainer = $('.drag-mousemove-container');
        this.enableSorting();
    }

    dispose() {
        this.$el.off();
    }

    disableSorting() {
        this.isSortingEnabled = false;
    }

    enableSorting() {
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

    onMouseDown(e) {
    }

    onMouseUp(e) {
        if (!this.isSortingEnabled) return;

        let isSelected = this.$el.hasClass(this.options.selectionClass);
        // if selection has more than one item, then user drag

        if (!this.isMultipleModifier(e)) {
            let selectedModels = this.options.collection.getSelection();

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
                let collection = this.options.collection;
                let currentIndex = collection.getIndex(this.model);
                let firstModel = collection.getFirstSelected();
                let firstIndex = collection.getIndex(firstModel);
                let lastModel = collection.getLastSelected();
                let lastIndex = collection.getIndex(lastModel);
                // get first selection offset
                // get last selected offset
                // get current
                this.clearSelection();
                let models = collection.get()
                if (firstIndex < currentIndex) {
                    for (let i = firstIndex; i < currentIndex; i++) {
                        this.addSelection(models[i]);
                    }
                } else {
                    for (let i = lastIndex; i > currentIndex; i--) {
                        this.addSelection(models[i]);
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

    onDrag() {
        if (!this.isSortingEnabled) return;
    }

    onDragStart(e) {
        if (!this.isSortingEnabled) return;

        let isSelected = this.$el.hasClass(this.selectionClass);

        // jquery ui sortable: http://jsfiddle.net/hQnWG/614/
        //if the element's parent is not the owner, then block this event
        if (
            this.isMultipleModifier(e)
            &&
            e.target.getAttribute('aria-grabbed') === 'false'
        ) {
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

    onDragEnter(e) {
        if (!this.isSortingEnabled) return;
        e.preventDefault();
        this.$el.addClass(this.options.overClass);
    }

    onDragLeave(e) {
        if (!this.isSortingEnabled) return;
        e.preventDefault();
        this.$el.removeClass(this.options.overClass);
    }

    onDragOver(e) {
        if (!this.isSortingEnabled) return;
        e.preventDefault();

        return false;
    }

    drop(e) {
        if (!this.isSortingEnabled) return;
        e.preventDefault();
        this.onDragLeave(e);
        let selectedModels = [];
        let collection = this.options.collection.get();
        let toIndex = this.$el.index();

        selectedModels = this.options.collection.getSelection();
        
        for (let i = selectedModels.length; i--;) {
            let fromIndex = this.options.collection.getIndex(this.options.collection.get(selectedModels[i]));

            collection.splice(toIndex, 0, this.options.collection.splice(fromIndex, 1)[0]);
        }
        
        this.options.collection.reset(collection);
    }

    /**
     * trigger multiple selection with keyboard modifier
     */
    isMultipleModifier(e) {
        return (e.ctrlKey || e.metaKey || e.shiftKey);
    }

    clearSelection() {
        this.options.collection.resetSelection();
    }

    addSelection(model) {
        this.options.collection.addToSelection(model);
    }

    removeSelection(model) {
        this.options.collection.removeFromSelection(model);
    }

    selectionChange() {
        //this.triggerMethod('selection:changed', this.options.collection.getSelection());
    }
}

export default SortableComponent;
