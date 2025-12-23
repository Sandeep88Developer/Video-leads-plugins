jQuery(document).ready(function ($) {

    // Open popup
    $('#open-video-popup').on('click', function () {
        $('#video-popup').fadeIn();
    });

    // Close popup
    $('.popup-close').on('click', function () {
        $('#video-popup').fadeOut();
        $('#popup-video')[0].pause();
    });

    // Submit form
    $('#custom-video-form').on('submit', function (e) {
        e.preventDefault();

        let name = $('#name').val().trim();
        let email = $('#email').val().trim();

        if (!name || !email) {
            $('#form-message').text('All fields are required');
            return;
        }

        $.ajax({
            url: formAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'submit_custom_form',
                nonce: formAjax.nonce,
                name: name,
                email: email
            },
            success: function (response) {
                if (response.success) {
                    $('#custom-video-form').hide();
                    $('#video-wrapper').fadeIn();

                    let video = $('#popup-video')[0];
                    video.play();
                } else {
                    $('#form-message').text(response.data);
                }
            }
        });
    });

});
