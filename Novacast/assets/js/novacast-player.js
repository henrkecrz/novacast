(function () {
    'use strict';

    document.addEventListener('play', function (event) {
        var target = event.target;

        if (!target.classList || !target.classList.contains('novacast-audio')) {
            return;
        }

        var players = document.querySelectorAll('.novacast-audio');

        players.forEach(function (player) {
            if (player !== target) {
                player.pause();
            }
        });
    }, true);

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-novacast-theme-toggle]');

        if (!toggle) {
            return;
        }

        var section = toggle.closest('[data-novacast-section]');
        var label = toggle.querySelector('[data-novacast-theme-label]');

        if (!section) {
            return;
        }

        var isDark = section.classList.toggle('novacast-theme-dark');
        section.classList.toggle('novacast-theme-light', !isDark);

        if (label) {
            label.textContent = isDark ? 'Tema claro' : 'Tema escuro';
        }
    });
})();
