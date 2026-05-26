(function ($) {
    'use strict';

    var frame;

    function formatDuration(seconds) {
        seconds = Math.floor(seconds || 0);

        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var remainingSeconds = seconds % 60;

        if (hours > 0) {
            return [hours, minutes, remainingSeconds].map(function (value) {
                return String(value).padStart(2, '0');
            }).join(':');
        }

        return [minutes, remainingSeconds].map(function (value) {
            return String(value).padStart(2, '0');
        }).join(':');
    }

    function setDurationStatus(message, type) {
        var $status = $('#novacast_duration_status');

        if (!$status.length) {
            return;
        }

        $status.removeClass('is-success is-error is-loading');

        if (type) {
            $status.addClass('is-' + type);
        }

        $status.text(message || '');
    }

    function detectDurationFromUrl(url) {
        if (!url) {
            setDurationStatus(NovacastAdmin.emptyAudioText || 'Selecione ou informe uma URL de áudio para detectar a duração.', 'error');
            return;
        }

        setDurationStatus(NovacastAdmin.detectingText || 'Detectando duração...', 'loading');

        var audio = document.createElement('audio');
        audio.preload = 'metadata';

        audio.addEventListener('loadedmetadata', function () {
            if (Number.isFinite(audio.duration) && audio.duration > 0) {
                $('#novacast_duration').val(formatDuration(audio.duration)).trigger('change');
                setDurationStatus(NovacastAdmin.detectedText || 'Duração detectada automaticamente.', 'success');
            } else {
                setDurationStatus(NovacastAdmin.notDetectedText || 'Não foi possível detectar a duração.', 'error');
            }
        });

        audio.addEventListener('error', function () {
            setDurationStatus(NovacastAdmin.notDetectedText || 'Não foi possível detectar a duração.', 'error');
        });

        audio.src = url;
    }

    function updateAudioPreview(url) {
        var $preview = $('#novacast_audio_preview');

        if (!$preview.length) {
            return;
        }

        if (!url) {
            $preview.empty();
            return;
        }

        $preview.html(
            '<audio controls preload="metadata" style="width:100%;max-width:620px;">' +
            '<source src="' + $('<div>').text(url).html() + '">' +
            '</audio>'
        );
    }

    function setSource(source) {
        source = source || 'audio';
        $('#novacast_source').val(source).trigger('change');

        $('.novacast-source-card').removeClass('is-active');
        $('.novacast-source-card[data-source="' + source + '"]').addClass('is-active');

        $('.novacast-source-panel').removeClass('is-visible').hide();
        $('.novacast-source-panel[data-source-panel="' + source + '"]').addClass('is-visible').show();
    }

    function initSourceCards() {
        setSource($('#novacast_source').val() || 'audio');
    }

    $(document).on('click', '.novacast-source-card', function (event) {
        event.preventDefault();
        setSource($(this).data('source'));
    });

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
            setSource('audio');
            updateAudioPreview(attachment.url);

            if (attachment.fileLength) {
                $('#novacast_duration').val(attachment.fileLength).trigger('change');
                setDurationStatus(NovacastAdmin.detectedText || 'Duração detectada automaticamente.', 'success');
            } else {
                detectDurationFromUrl(attachment.url);
            }
        });

        frame.open();
    });

    $(document).on('click', '#novacast_detect_duration_button', function (event) {
        event.preventDefault();
        detectDurationFromUrl($('#novacast_audio_url').val());
    });

    $(document).on('click', '#novacast_clear_audio_button', function (event) {
        event.preventDefault();
        $('#novacast_audio_url').val('').trigger('change');
        $('#novacast_duration').val('').trigger('change');
        updateAudioPreview('');
        setDurationStatus('', '');
    });

    $(document).on('change blur', '#novacast_audio_url', function () {
        var url = $(this).val();
        updateAudioPreview(url);
    });

    $(function () {
        initSourceCards();
    });
})(jQuery);
