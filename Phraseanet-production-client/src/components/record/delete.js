import $ from 'jquery';
import dialog from './../../phraseanet-common/components/dialog';

const deleteRecord = (services) => {
    const {configService, localeService, appEvents} = services;
    const url = configService.get('baseUrl');
    let workzoneSelection = [];
    let searchSelection = [];

    const openModal = (datas) => {
        let $dialog = dialog.create(services, {
            size: '480x160',
            title: localeService.t('warning')
        });
        $.ajax({
            type: 'POST',
            url: `${url}prod/records/delete/what/`,
            dataType: 'html',
            data: datas,
            success: function (data) {
                $dialog.setOption('height', 'auto');
                $dialog.setContent(data);

                //reset top position of dialog
                $dialog.getDomElement().offsetParent().css('top', ($(window).height() - $dialog.getDomElement()[0].clientHeight) / 2);
                _onDialogReady();
            }
        });

        return false;
    };
      const _onDialogReady = () => {
    var $dialog = dialog.get(1);
    var $dialogBox = $dialog.getDomElement();
    var $closeButton = $('button.ui-dialog-titlebar-close', $dialogBox.parent());
    var $cancelButton = $('button.cancel', $dialogBox);
    var $delChildren = $("input[name='del_children']", $dialogBox);
    var titleBox = $(".ui-dialog-title", $dialogBox.parent());
    titleBox.prepend('<i class="fa fa-exclamation-triangle" style="margin-right: 10px"></i>');


    /**
     * the checkbox "delete stories children too" changes
     **/
    $delChildren.bind("change", function () {
        fSetDelChildren($(this).is(':checked'));
    });

    $cancelButton.bind('click', function () {
        $dialog.close();
    });

    /**
     * set the dlg content according the "delete children" ckbox
     * the counts (records will be deleted, records rejected...) will update
     */
    var fSetDelChildren = function (delChildren) {
        if (delChildren) {
            $("#delete_records_parent_only").hide();
            $("#delete_records_with_children").show();
        } else {
            $("#delete_records_with_children").hide();
            $("#delete_records_parent_only").show();
        }
    };

    /**
     * click ok : run delete tasks
     */
    $('button.submiter', $dialogBox).bind('click', function () {
        let CHUNKSIZE = 3,
            MAXTASKS = 5;
        let $this = $(this);
        let $form = $(this).closest("form"),
            $counter = $form.find(".to_delete_count"),
            $trash_counter = $form.find(".to_trash_count"),
            $loader = $form.find(".form-action-loader");
        let lst = $("input[name='lst']", $form).val().split(';');

        /**
         *  same parameters for every delete call, except the list of (CHUNKSIZE) records
         *  nb: do NOT ask to delete children since they're included in lst
         */
        let ajaxParms = {
            type: $form.attr("method"),
            url: $form.attr("action"),
            data: {
                'lst': ""      // set in f
            },
            dataType: "json"
        };

        let runningTasks = 0,   // number of running tasks
            canceling = false;
        /**
         * cancel or close dlg will ask tasks to stop
         */
        let fCancel = function () {
            $closeButton.hide();
            $cancelButton.hide();
            $loader.show();
            canceling = true;
        };
        /**
         * task fct : will loop itself while there is job to do
         *
         * @param iTask     (int) The task number 0...MAXTASKS-1, usefull only to debug
         */
        let fTask = function (iTask) {
            if (canceling) {
                return;
            }
            // pop & truncate
            ajaxParms.data.lst = lst.splice(0, CHUNKSIZE).join(';');
            $.ajax(ajaxParms)
                .success(function (data) {     // prod feedback only if result ok
                    $.each(data, function (i, n) {
                        let imgt = $('#IMGT_' + n),
                            chim = $('.CHIM_' + n),
                            stories = $('.STORY_' + n);
                        $('.doc_infos', imgt).remove();
                        try {
                            $imgt.draggable("destroy");
                        } catch (e) {
                            // no-op
                        }
                        imgt.unbind("click")
                            .removeAttr("ondblclick")
                            .removeClass("selected")
                            .removeClass("IMGT")
                            .find("img")
                            .unbind();
                        imgt.find(".thumb img")
                            .attr("src", "/assets/common/images/icons/deleted.png")
                            .css({
                                width: '100%',
                                height: 'auto',
                                margin: '0 10px',
                                top: '0'
                            });
                        chim.parent().slideUp().remove();
                        imgt.find(".status,.title,.bottom").empty();

                        appEvents.emit('search.selection.remove', {records: n});

                        if (stories.length > 0) {
                            appEvents.emit('workzone.refresh');
                        } else {
                            appEvents.emit('workzone.selection.remove', {records: n});
                        }
                    });
                })
                .then(function () {    // go on even in case of error
                    // countdown
                    $counter.html(lst.length);
                    $trash_counter.html(lst.length);
                    if (lst.length === 0 || canceling) {
                        // end of a task
                        if (--runningTasks === 0) {
                            $dialog.close();
                            $('#nbrecsel').empty().append(lst.length);
                        }
                    } else {
                        // don't recurse, give a delay to running fct to end
                        window.setTimeout(fTask, 10, iTask);
                    }
                });
        };

        // cancel or close the dlg is the same : wait
        $dialog.setOption('closeOnEscape', false);
        $closeButton.unbind("click").bind("click", fCancel);
        $cancelButton.unbind("click").bind("click", fCancel);
        // run a bunch of tasks in //
        for (runningTasks = 0; runningTasks < MAXTASKS && lst.length > 0; runningTasks++) {
            fTask(runningTasks);    // pass the task his index to get nice console logs
        }

    });
    fSetDelChildren(false);
     };
    return {openModal};
}

export default deleteRecord;
