jQuery(document).ready(function($) {
    $('#wish_card_checkbox').change(function() {
        if ($(this).is(':checked')) {
            $('#wish_card_message').show();
        } else {
            $('#wish_card_message').hide();
        }
    });
});