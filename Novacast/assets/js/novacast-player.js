(function () {
    'use strict';

    function formatTime(seconds) {
        if (!Number.isFinite(seconds) || seconds < 0) {
            seconds = 0;
        }

        var minutes = Math.floor(seconds / 60);
        var remainingSeconds = Math.floor(seconds % 60);

        return minutes + ':' + String(remainingSeconds).padStart(2, '0');
    }

    function pauseOtherPlayers(currentAudio) {
        var players = document.querySelectorAll('.novacast-audio');

        players.forEach(function (player) {
            if (player !== currentAudio) {
                player.pause();
                var wrapper = player.closest('[data-novacast-audio-player]');

                if (wrapper) {
                    wrapper.classList.remove('is-playing');
                }
            }
        });
    }

    function updatePlayer(wrapper) {
        var audio = wrapper.querySelector('.novacast-audio');
        var progress = wrapper.querySelector('[data-novacast-progress]');
        var current = wrapper.querySelector('[data-novacast-current]');
        var duration = wrapper.querySelector('[data-novacast-duration]');

        if (!audio) {
            return;
        }

        var total = audio.duration || 0;
        var now = audio.currentTime || 0;
        var percent = total > 0 ? (now / total) * 100 : 0;

        if (progress) {
            progress.style.width = percent + '%';
        }

        if (current) {
            current.textContent = formatTime(now);
        }

        if (duration) {
            duration.textContent = formatTime(total);
        }
    }

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-novacast-theme-toggle]');

        if (toggle) {
            var section = toggle.closest('[data-novacast-section]');

            if (!section) {
                return;
            }

            var isDark = section.classList.toggle('novacast-theme-dark');
            section.classList.toggle('novacast-theme-light', !isDark);
            toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            return;
        }

        var playButton = event.target.closest('[data-novacast-play]');

        if (playButton) {
            var wrapper = playButton.closest('[data-novacast-audio-player]');
            var audio = wrapper ? wrapper.querySelector('.novacast-audio') : null;

            if (!audio) {
                return;
            }

            if (audio.paused) {
                pauseOtherPlayers(audio);
                audio.play();
            } else {
                audio.pause();
            }

            return;
        }

        var muteButton = event.target.closest('[data-novacast-mute]');

        if (muteButton) {
            var muteWrapper = muteButton.closest('[data-novacast-audio-player]');
            var muteAudio = muteWrapper ? muteWrapper.querySelector('.novacast-audio') : null;

            if (!muteAudio) {
                return;
            }

            muteAudio.muted = !muteAudio.muted;
            muteWrapper.classList.toggle('is-muted', muteAudio.muted);
            return;
        }

        var seek = event.target.closest('[data-novacast-seek]');

        if (seek) {
            var seekWrapper = seek.closest('[data-novacast-audio-player]');
            var seekAudio = seekWrapper ? seekWrapper.querySelector('.novacast-audio') : null;

            if (!seekAudio || !seekAudio.duration) {
                return;
            }

            var rect = seek.getBoundingClientRect();
            var ratio = Math.min(Math.max((event.clientX - rect.left) / rect.width, 0), 1);
            seekAudio.currentTime = ratio * seekAudio.duration;
            updatePlayer(seekWrapper);
        }
    });

    document.addEventListener('play', function (event) {
        var target = event.target;

        if (!target.classList || !target.classList.contains('novacast-audio')) {
            return;
        }

        pauseOtherPlayers(target);
        var wrapper = target.closest('[data-novacast-audio-player]');

        if (wrapper) {
            wrapper.classList.add('is-playing');
        }
    }, true);

    document.addEventListener('pause', function (event) {
        var target = event.target;

        if (!target.classList || !target.classList.contains('novacast-audio')) {
            return;
        }

        var wrapper = target.closest('[data-novacast-audio-player]');

        if (wrapper) {
            wrapper.classList.remove('is-playing');
        }
    }, true);

    document.addEventListener('ended', function (event) {
        var target = event.target;

        if (!target.classList || !target.classList.contains('novacast-audio')) {
            return;
        }

        var wrapper = target.closest('[data-novacast-audio-player]');

        if (wrapper) {
            wrapper.classList.remove('is-playing');
            updatePlayer(wrapper);
        }
    }, true);

    document.addEventListener('timeupdate', function (event) {
        var target = event.target;

        if (!target.classList || !target.classList.contains('novacast-audio')) {
            return;
        }

        var wrapper = target.closest('[data-novacast-audio-player]');

        if (wrapper) {
            updatePlayer(wrapper);
        }
    }, true);

    document.addEventListener('loadedmetadata', function (event) {
        var target = event.target;

        if (!target.classList || !target.classList.contains('novacast-audio')) {
            return;
        }

        var wrapper = target.closest('[data-novacast-audio-player]');

        if (wrapper) {
            updatePlayer(wrapper);
        }
    }, true);

    document.querySelectorAll('[data-novacast-audio-player]').forEach(updatePlayer);
})();
