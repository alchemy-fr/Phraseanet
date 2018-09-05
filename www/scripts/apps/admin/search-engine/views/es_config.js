function searchEngineConfigurationFormInit(indexExists) {
    var dropIndexDialog = $("#dropIndexConfirmDialog").dialog({
        autoOpen: false,
        modal: true,
        title: "Drop index",
        width: 382,
        buttons: [
            {
                text: "Ok",
                click: function () {
                    $("#ElasticSearchDropIndexForm").submit();
                    $("#dropIndexConfirmDialog").dialog("close");
                }
            },
            {
                text: "Cancel",
                click: function () {
                    $("#dropIndexConfirmDialog").dialog("close");
                }
            }
        ]
    }).closest('.ui-dialog').addClass('dialog_container');
    dropIndexDialog.find('.ui-dialog-buttonpane button').css({
        float: 'right',
        marginRight: '23px'
    });

    if(indexExists) {
        $("BUTTON[data-id=esSettingsCreateIndexButton]").hide();
        $("BUTTON[data-id=esSettingsDropIndexButton]").show().bind("click", function (event) {
            event.preventDefault();
            $("#dropIndexConfirmDialog").dialog("open");
            return false;
        });
    }
    else {
        $("BUTTON[data-id=esSettingsDropIndexButton]").hide();
        $("BUTTON[data-id=esSettingsCreateIndexButton]").show().bind("click", function (event) {
            event.preventDefault();
            $("#ElasticSearchCreateIndexForm").submit();
            return false;
        });
    }
}

$("input[data-class='inline']").parent('div').css('display','inline-block');
$("button[data-class='inline']").parent('div').css({'display':'inline-block'});
$("button[data-class='inline']").css({'margin-left':'10px', 'margin-bottom': '10px'});
//Get setting from index
function esSettingFromIndex() {
    $('#elasticsearch_settings_dumpField').removeClass('hide');
    var data = {};
    data.index = $('#elasticsearch_settings_indexName').val();
    var url = pathGetIndexSettings;
    $.ajax({
        type: "GET",
        url: url,
        dataType: 'json',
        data : data,
        success: function (data) {
            if (data.success) {
                $('#elasticsearch_settings_dumpField').text(JSON.stringify(data.response));
            } else {
                $('#elasticsearch_settings_dumpField').text(data.message);
            }
        }
        , error: function (jqXHR, textStatus, errorThrown) {
            alert("Error XML:\n\n" + jqXHR.responseText);
        }
    });

    return false;
}
