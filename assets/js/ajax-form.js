$(function () {
    var form = $('#contact-form');
    var formMessages = $('.ajax-response');

    $(form).submit(function (e) {
        e.preventDefault();

        var formData = $(form).serialize();

        $.ajax({
            type: 'POST',
            url: $(form).attr('action'),
            data: formData,
            dataType: 'json', // tell jQuery to expect JSON
            beforeSend: function () {
                $(formMessages).removeClass('success error').text('Sending...');
            },
        })
        .done(function (response) {
            if (response.status === 'success') {
                $(formMessages)
                    .removeClass('error')
                    .addClass('success')
                    .text(response.message);
                $('#contact-form input,#contact-form textarea').val('');
            } else {
                $(formMessages)
                    .removeClass('success')
                    .addClass('error')
                    .text(response.message || 'Something went wrong. Please try again.');
            }
        })
        .fail(function () {
            $(formMessages)
                .removeClass('success')
                .addClass('error')
                .text('Oops! An error occurred, and your message could not be sent.');
        });
    });
});
