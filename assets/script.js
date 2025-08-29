(function($){
    $(document).ready(function(){
        $('#newsletter-form').on('submit', function(e){
            e.preventDefault();
            const $message = $('#news-message');
            const $emailInput = $('#newsletter-email');

            const formData = {
                action: 'subscribe_newsletter',
                newsletter_nonce_field: ajax_demo.nonce,
                newsletter_email: $emailInput.val()
            }

            $.post(ajax_demo.ajax_url, formData, function(response) {
                if (response.success) {
                    $message.text('Thank you for subscribing!').css('color', 'green');
                    $emailInput.val('');
                } else {
                    $message.text('There was an error: ' + response.data).css('color', 'red');
                }
            })
        });
    });
})(jQuery)