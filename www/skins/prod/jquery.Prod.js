(function () {
    $(document).ready(function () {
        humane.info = humane.spawn({addnCls: 'humane-libnotify-info', timeout: 1000});
        humane.error = humane.spawn({addnCls: 'humane-libnotify-error', timeout: 1000});

        $('a.dialog').live('click', function (event) {
            var $this = $(this), size = 'Medium';

            if ($this.hasClass('small-dialog')) {
                size = 'Small';
            } else if ($this.hasClass('full-dialog')) {
                size = 'Full';
            }

            var options = {
                size: size,
                loading: true,
                title: $this.attr('title'),
                closeOnEscape: true
            };

            $dialog = p4.Dialog.Create(options);

            $.ajax({
                type: "GET",
                url: $this.attr('href'),
                dataType: 'html',
                success: function (data) {
                    $dialog.setContent(data);
                    return;
                }
            });

            return false;
        });
    });
}());
