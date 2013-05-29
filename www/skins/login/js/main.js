// dom ready
$(document).ready(function() {
    $(document).on("click", ".alert .alert-block-close a", function(e){
        e.preventDefault();
        $(this).closest('.alert').alert('close');
        return false;
    });
});
