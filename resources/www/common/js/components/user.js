
var userModule = (function(){
    function setPref(name, value) {
        if (jQuery.data['pref_' + name] && jQuery.data['pref_' + name].abort) {
            jQuery.data['pref_' + name].abort();
            jQuery.data['pref_' + name] = false;
        }

        jQuery.data['pref_' + name] = $.ajax({
            type: "POST",
            url: "/user/preferences/",
            data: {
                prop: name,
                value: value
            },
            dataType: 'json',
            timeout: function () {
                jQuery.data['pref_' + name] = false;
            },
            error: function () {
                jQuery.data['pref_' + name] = false;
            },
            success: function (data) {
                if (data.success) {
                    humane.info(data.message);
                }
                else {
                    humane.error(data.message);
                }
                jQuery.data['pref_' + name] = false;
                return;
            }
        });
    }

    return {setPref: setPref}
})();


