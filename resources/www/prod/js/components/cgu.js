var p4 = p4 || {};

var cguModule = (function (p4) {
    function acceptCgus(name, value) {
        userModule.setPref(name, value);
    }

    function cancelCgus(id) {

        $.ajax({
            type: "POST",
            url: "../prod/TOU/deny/" + id + "/",
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    alert(language.cgusRelog);
                    self.location.replace(self.location.href);
                }
                else {
                    humane.error(data.message);
                }
            }
        });

    }

    function activateCgus() {
        var $this = $('.cgu-dialog:first');
        $this.dialog({
            autoOpen: true,
            closeOnEscape: false,
            draggable: false,
            modal: true,
            resizable: false,
            width: 800,
            height: 500,
            open: function () {
                $this.parents(".ui-dialog:first").find(".ui-dialog-titlebar-close").remove();
                $('.cgus-accept', $(this)).bind('click', function () {
                    acceptCgus($('.cgus-accept', $this).attr('id'), $('.cgus-accept', $this).attr('date'));
                    $this.dialog('close').remove();
                    activateCgus();
                });
                $('.cgus-cancel', $(this)).bind('click', function () {
                    if (confirm(language.warningDenyCgus)) {
                        cancelCgus($('.cgus-cancel', $this).attr('id').split('_').pop());
                    }
                });
            }
        });
    }

    return {
        activateCgus: activateCgus
    };
}(p4));
