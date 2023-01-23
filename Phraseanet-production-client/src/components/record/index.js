import {Observable} from 'rx';
// import {ajax} from 'jquery';
import $ from 'jquery';

// module unused now 06-2021
let recordService = (services) => {
    const {configService} = services;
    const url = configService.get('baseUrl');
    const notificationEndPoint = 'session/notifications/';
    let initialize = () => {
    };

    let getNotification = (data) => {
        let notificationPromise = $.Deferred();
        $.ajax({
            type: 'POST',
            url: `${url}${notificationEndPoint}`,
            data: data,
            dataType: 'json'
        }).done((data) => {
                data.status = data.status || false;
                if (data.status === 'ok') {
                    notificationPromise.resolve(data);
                } else {
                    notificationPromise.reject(data);
                }
            })
            .fail((data) => {
                notificationPromise.reject(data);
            });
        return notificationPromise.promise();
    };

    let stream = Observable.fromPromise(getNotification);
    return {
        initialize,
        getNotification,
        stream
    };
};
export default recordService;
