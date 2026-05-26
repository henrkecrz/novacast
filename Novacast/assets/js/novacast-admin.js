(function ($) {
    'use strict';

    var frame;

    $(document).on('click', '#novacast_select_audio_button', function (event) {
        event.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: window.NovacastAdmin ? NovacastAdmin.mediaTitle : 'Selecionar áudio do episódio',
            button: {
                text: window.NovacastAdmin ? NovacastAdmin.mediaButton : 'Usar este áudio'
            },
            library: {
                type: 'audio'
            },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();

            if (!attachment || !attachment.url) {
                return;
            }

            $('#novacast_audio_url').val(attachment.url).trigger('change');
            $('#novacast_source').val('audio').trigger('change');
            $('#novacast_audio_preview').html(
                '<audio controls preload="metadata" style="width:100%;max-width:520px;">' +
                '<source src="' + attachment.url + '">' +
                '</audio>'
            );
        });

        frame.open();
    });

    $(document).on('click', '#novacast_clear_audio_button', function (event) {
        event.preventDefault();
        $('#novacast_audio_url').val('').trigger('change');
        $('#novacast_audio_preview').empty();
    });
})(jQuery);
