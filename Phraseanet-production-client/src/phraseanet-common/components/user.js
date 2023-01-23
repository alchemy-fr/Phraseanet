// @TODO enable lints
/* eslint-disable no-undef*/
import $ from 'jquery';
const humane = require('humane-js');

humane.info = humane.spawn({addnCls: 'humane-libnotify-info', timeout: 1000});
humane.error = humane.spawn({addnCls: 'humane-libnotify-error', timeout: 1000});
humane.forceNew = true;

function setPref(name, value) {
    const prefName = `pref_${name}`;
    if ($.data[prefName] && $.data[prefName].abort) {
        $.data[prefName].abort();
        $.data[prefName] = false;
    }
    $.data[prefName] = $.ajax({
        type: 'POST',
        url: '/user/preferences/',
        data: {
            prop: name,
            value
        },
        dataType: 'json',
        timeout: $.data[prefName] = false,
        error: $.data[prefName] = false,
        success: (data) => {
            if (data.success) {
                humane.info(data.message);
            } else {
                humane.error(data.message);
            }
            $.data[prefName] = false;
            return data;
        }
    });
    return $.data[prefName];

}

export default {setPref};


