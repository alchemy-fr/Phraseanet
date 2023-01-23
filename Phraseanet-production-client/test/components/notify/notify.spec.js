import $ from 'jquery';
//let jsdom = require('mocha-jsdom');
import notify from '../../../src/components/notify';


describe('Notify Component', () => {
/*
    var $;
    jsdom();

    before(function () {
        $ = require('jquery')
    });*/
    it('should exposes a public API', () => {
        const methods = Object.keys(notify({appConfig: {}}))
        expect(methods.length).to.eql(5);
        expect(methods).to.contain('bindEvents');
        expect(methods).to.contain('appendNotifications');
        expect(methods).to.contain('createNotifier');
        expect(methods).to.contain('isValid');
        expect(methods).to.contain('poll');
    });
    /*

    it('should exposes a public API', () => {
        const methods = Object.keys(notify())
        expect(methods.length).to.eql(5);
        expect(methods).to.contain('bindEvents');
        expect(methods).to.contain('appendNotifications');
        expect(methods).to.contain('createNotifier');
        expect(methods).to.contain('isValid');
        expect(methods).to.contain('poll');
    });


    describe('create notification', () => {
        it('should fail to create an invalid notification object', () => {
            const notifierInstance = notify().createNotifier();
            expect(notifierInstance).to.eql( {
                url: null,
                moduleId: null,
                userId: null,
                _isValid: false
            });
        });
        it('should be able to create a valid notification object', () => {
            const notifierInstance = notify().createNotifier({
                url: 'url',
                moduleId: 1,
                userId: 1
            });
            expect(notifierInstance.url).to.eql('url');
            expect(notifierInstance.moduleId).to.eql(1);
            expect(notifierInstance.userId).to.eql(1);
            expect(notify().isValid(notifierInstance)).to.eql(true);
        });

    });
    describe('append Notification', () => {
        const $body = $('body');

        before( 'attach dom',() => {
            $body.append('<div id="notification_box"></div>');
        });

        after('cleanup dom', () => {
            $body.remove('#notification_box');
        });

        it('should be able to append notifications', () => {
            const notifierInstance = notify().appendNotifications('<span class="notification"></span>');
            const hasNotifications = $body.find('.notification');
            expect(hasNotifications.length).to.gte(1);
        });
    });
    */

});
