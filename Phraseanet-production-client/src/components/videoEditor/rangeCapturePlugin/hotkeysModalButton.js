import $ from 'jquery';
import videojs from 'video.js';
const Button = videojs.getComponent('Button');
const Component = videojs.getComponent('Component');
import HotkeyModal from './hotkeysModal';

class HotkeysModalButton extends Button {

    constructor(player, settings) {
        super(player, settings);
        this.settings = settings;
    }

    /**
     * Allow sub components to stack CSS class names
     *
     * @return {String} The constructed class name
     * @method buildCSSClass
     */
    buildCSSClass() {
        return 'vjs-hotkeys-modal-button vjs-button';
    }

    createEl(tag = 'button', props = {}, attributes = {}) {
        let el = super.createEl(tag, props, attributes);
        el.innerHTML = '<span class="fa-stack"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-info fa-stack-1x fa-inverse"></i></span>';
        return el;
    }

    /**
     * Handles click for keyboard shortcuts modal
     *
     * @method handleClick
     */
    handleClick() {
        this.hotkeysModal = this.player_.addChild('HotkeyModal', this.settings);
        this.hotkeysModal.initialize();
        this.hotkeysModal.open();
        this.hotkeysModal.on('beforemodalclose', () => {
            $(this.el()).show();
        });
        $(this.el()).hide();
    }

}

// HotkeysModalButton.prototype.controlText_ = `<span class="fa-stack">
//                               <i class="fa fa-circle fa-stack-2x"></i>
//                               <i class="fa fa-info fa-stack-1x fa-inverse"></i>
//                             </span>`;

Component.registerComponent('HotkeysModalButton', HotkeysModalButton);
export default HotkeysModalButton;
