function searchEngineConfigurationFormInit(indexExists) {
    $("#dropIndexConfirmDialog").dialog({
        autoOpen: false,
        modal: true,
        title: "Drop index",
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
