import $ from 'jquery';
// import user from '../user/index.js';
import notifyLayout from './notifyLayout';
import notifyService from './notifyService';
import * as Rx from 'rx';
import merge from 'lodash.merge';
const notify = (services) => {

    const { configService, localeService, appEvents } = services;
    const defaultPollingTime = 10000;
    const defaultConfig = {
        url: null,
        moduleId: null,
        userId: null,
        _isValid: false
    };

    const initialize = () => {
        notifyLayout(services).initialize();
    };

    const createNotifier = (state) => {
        if (state === undefined) {
            return defaultConfig;
        }
        if (state.url === undefined) {
            return defaultConfig;
        }

        return merge({}, defaultConfig, {
            url: state.url,
            moduleId: state.moduleId,
            userId: state.userId,
            _isValid: true
        });
    };

    //const appendNotifications = (content) => notifyUiComponent().addNotifications(content);

    const isValid = (notificationInstance) => notificationInstance._isValid || false;

    const poll = (notificationInstance) => {

        let notificationSource = Rx.Observable
            .fromPromise(notifyService({
                configService: configService
            }).getNotification({
                module: notificationInstance.moduleId,
                usr: notificationInstance.userId
            }));

        notificationSource.subscribe(
            x => onPollSuccess(x, notificationInstance),
            e => onPollError(e, notificationInstance),
            () => {}
        );
    };
    const onPollSuccess = (data, notificationInstance) => {
        // broadcast session refresh event
        appEvents.emit('session.refresh', data);
        // broadcast notification refresh event
        if (data.changed.length > 0) {
            appEvents.emit('notification.refresh', data);
        }
        // append notification content
        notifyLayout(services).addNotifications(data.notifications);

        let t = 120000;
        if (data.apps && parseInt(data.apps, 10) > 1) {
            t = Math.round((Math.sqrt(parseInt(data.apps, 10) - 1) * 1.3 * 60000));
        }

        window.setTimeout(poll, t, notificationInstance);

        return true;
    };

    const onPollError = (data, notificationInstance) => {
        if (data.status === 'disconnected' || data.status === 'session') {
            appEvents.emit('user.disconnected', data);
            return false;
        }
        window.setTimeout(poll, defaultPollingTime, notificationInstance);
    };


    return {
        initialize,
        /*appendNotifications: (content) => {
            notifyLayout().addNotifications(content)
        },*/
        createNotifier,
        isValid,
        poll
    };
};

export default notify;
