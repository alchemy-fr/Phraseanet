import $ from 'jquery';
import notify from '../../../src/components/notify';
import notifyLayout from '../../../src/components/notify/notifyLayout';


describe('Notify Layout Component', () => {

    it('is available', () => {
        expect(notifyLayout).not.to.be.null;
    });

    it('exposes a public API', () => {
        const methods = Object.keys(notifyLayout())
        expect(methods.length).to.eql(2);
        expect(methods).to.contain('bindEvents');
        expect(methods).to.contain('addNotifications');
    });

    describe('append Notification', () => {
        /*
        const $body = $('body');

        before( 'attach dom',() => {
            $body.append('<div id="notification_box"></div>');
        });

        after('cleanup dom', () => {
            $body.remove('#notification_box');
        });

        it('should be able to append notifications into DOM', () => {
            const notifierInstance = notify().appendNotifications('<span class="notification"></span>');
            const hasNotifications = $body.find('.notification');
            expect(hasNotifications.length).to.gte(1);
        });
        */
        /*describe('probe events', () => {
            it('should catch events', () => {
                notify().bindEvents();

                $('#notification_trigger').trigger('mousedown');
                console.log($('#notification_trigger'))
                expect($('#notification_trigger').hasClass('open')).to.true();
            });
        });*/
    });

});
