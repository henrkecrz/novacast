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
})();
