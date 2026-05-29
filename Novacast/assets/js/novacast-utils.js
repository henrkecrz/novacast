(function () {
    'use strict';

    var lastActiveWrapper = null;

    window.NovacastUtils = {
        formatTime: function (seconds) {
            if (!Number.isFinite(seconds) || seconds < 0) seconds = 0;
            var m = Math.floor(seconds / 60);
            var s = Math.floor(seconds % 60);
            return m + ':' + String(s).padStart(2, '0');
        },

        getEpisodeId: function (wrapper) {
            return wrapper ? wrapper.getAttribute('data-novacast-episode-id') || '' : '';
        },

        getActiveWrapper: function () {
            var el = document.querySelector('[data-novacast-audio-player].is-playing');
            return el || lastActiveWrapper || null;
        },

        setLastActiveWrapper: function (wrapper) {
            lastActiveWrapper = wrapper || null;
        },

        getAllWrappers: function () {
            return document.querySelectorAll('[data-novacast-audio-player]');
        },

        getActiveListItem: function () {
            return document.querySelector('.novacast-episode-row.is-current, .novacast-player-card.is-current');
        },

        savePosition: function (episodeId, currentTime) {
            if (!episodeId || !window.localStorage) return;
            try {
                var positions = JSON.parse(localStorage.getItem('novacast_positions') || '{}');
                positions[episodeId] = currentTime;
                localStorage.setItem('novacast_positions', JSON.stringify(positions));
            } catch (e) {}
        },

        getSavedPosition: function (episodeId) {
            if (!episodeId || !window.localStorage) return 0;
            try {
                var positions = JSON.parse(localStorage.getItem('novacast_positions') || '{}');
                return positions[episodeId] || 0;
            } catch (e) { return 0; }
        },

        clearPosition: function (episodeId) {
            if (!episodeId || !window.localStorage) return;
            try {
                var positions = JSON.parse(localStorage.getItem('novacast_positions') || '{}');
                delete positions[episodeId];
                localStorage.setItem('novacast_positions', JSON.stringify(positions));
            } catch (e) {}
        },

        saveSpeed: function (speed) {
            if (!window.localStorage) return;
            try { localStorage.setItem('novacast_speed', String(speed)); } catch (e) {}
        },

        getSavedSpeed: function () {
            if (!window.localStorage) return 1;
            try { return parseFloat(localStorage.getItem('novacast_speed')) || 1; } catch (e) { return 1; }
        },

        getNextWrapper: function (currentWrapper) {
            var wrappers = this.getAllWrappers();
            var found = false;
            for (var i = 0; i < wrappers.length; i++) {
                if (found) return wrappers[i];
                if (wrappers[i] === currentWrapper) found = true;
            }
            return null;
        }
    };
})();
