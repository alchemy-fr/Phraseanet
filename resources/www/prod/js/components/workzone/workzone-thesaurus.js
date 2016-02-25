var p4 = p4 || {};

var workzoneThesaurusModule = (function (p4) {
    $(document).ready(function () {

        $('#thesaurus_tab .input-medium').on('keyup', function(){
            if($('#thesaurus_tab .input-medium').val() != ''){
                $('#thesaurus_tab .th_clear').show();
            }else{
                $('#thesaurus_tab .th_clear').hide();
            }
        });

        $('.th_clear').on('click', function(){
            $('#thesaurus_tab .input-medium').val('');
            $('#thesaurus_tab .gform').submit();
            $('#thesaurus_tab .th_clear').hide();
        });

        $('.treeview>li.expandable>.hitarea').on('click', function(){
            if($(this).css('background-position') == '99% 22px'){
                $(this).css('background-position', '99% -28px');
                $(this).addClass('active');
            }else{
                $(this).css('background-position', '99% 22px');
                $(this).removeClass('active');
            }
        });

    });

    return {};
})(p4);
