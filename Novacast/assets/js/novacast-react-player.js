(function (wp) {
    'use strict';

    if (!wp || !wp.element) return;

    var h = wp.element.createElement;
    var useEffect = wp.element.useEffect;
    var useRef = wp.element.useRef;
    var useState = wp.element.useState;
    var useCallback = wp.element.useCallback;
    var createRoot = wp.element.createRoot;
    var render = wp.element.render;
    var mountedRoots = typeof WeakMap === 'function' ? new WeakMap() : null;
    var U = window.NovacastUtils;

    var SPEEDS = [0.5, 0.75, 1, 1.25, 1.5, 2];

    function LoadingDots() {
        return h('div', { className: 'novacast-loading-indicator', 'aria-hidden': true },
            h('span', { className: 'novacast-loading-dot' }),
            h('span', { className: 'novacast-loading-dot' }),
            h('span', { className: 'novacast-loading-dot' })
        );
    }

    function ErrorBanner(props) {
        if (!props.show) return null;
        return h('div', { className: 'novacast-error', role: 'alert' },
            h('span', null, props.message || 'Falha ao carregar audio.'),
            h('button', { type: 'button', onClick: props.onRetry }, 'Tentar novamente')
        );
    }

    function getYoutubeId(url) {
        if (!url) return '';
        var m = String(url).match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{6,})/);
        if (m) return m[1];
        return /^[a-zA-Z0-9_-]{6,}$/.test(url) ? url : '';
    }

    function getSpotifyEpisodeId(url) {
        if (!url) return '';
        var m = String(url).match(/open\.spotify\.com\/(?:embed\/)?episode\/([a-zA-Z0-9]+)/);
        if (m) return m[1];
        return /^[a-zA-Z0-9]+$/.test(url) ? url : '';
    }

    function Waveform(props) {
        var bars = [34, 54, 42, 72, 38, 88, 48, 64, 30, 76, 46, 92, 40, 68, 36, 56, 78, 44, 66, 32, 84, 50, 70, 38];
        var progress = props.progress || 0;
        return h('div', { className: 'novacast-waveform', 'aria-hidden': true },
            bars.map(function (height, index) {
                var active = (index / bars.length) * 100 <= progress;
                return h('span', {
                    key: index,
                    className: active ? 'is-active' : '',
                    style: { height: height + '%' }
                });
            })
        );
    }

    function VolumeSlider(props) {
        var value = props.value;
        var onChange = props.onChange;

        return h('div', { className: 'novacast-volume-wrap' },
            h('button', {
                className: 'novacast-audio-muted',
                type: 'button',
                onClick: props.onToggleMute,
                'aria-label': 'Ativar ou desativar som'
            },
                h('svg', { className: 'novacast-icon-volume', width: 16, height: 16, viewBox: '0 0 24 24', fill: 'currentColor', 'aria-hidden': true },
                    h('path', { d: 'M11 5L6 9H2v6h4l5 4V5z' }),
                    h('path', { className: 'novacast-icon-volume-high', d: 'M19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07' })
                ),
                h('svg', { className: 'novacast-icon-muted', width: 16, height: 16, viewBox: '0 0 24 24', fill: 'currentColor', 'aria-hidden': true },
                    h('path', { d: 'M11 5L6 9H2v6h4l5 4V5z' }),
                    h('line', { x1: 23, y1: 9, x2: 17, y2: 15, stroke: 'currentColor', strokeWidth: 2 }),
                    h('line', { x1: 17, y1: 9, x2: 23, y2: 15, stroke: 'currentColor', strokeWidth: 2 })
                )
            ),
            h('input', {
                className: 'novacast-volume-slider',
                type: 'range',
                min: 0, max: 1, step: 0.05,
                value: value,
                onChange: function (e) { onChange(parseFloat(e.target.value)); },
                'aria-label': 'Volume'
            })
        );
    }

    function SpeedButton(props) {
        var speed = props.speed;
        var onClick = props.onClick;
        return h('button', {
            className: 'novacast-audio-speed',
            type: 'button',
            onClick: onClick,
            'aria-label': 'Velocidade de reproducao'
        }, speed + '\u00D7');
    }

    function CustomAudioPlayer(props) {
        var audioRef = useRef(null);
        var progressRef = useRef(null);
        var episodeId = props.episodeId;

        var _u1 = useState(false);
        var isPlaying = _u1[0];
        var setIsPlaying = _u1[1];

        var _u2 = useState(false);
        var isMuted = _u2[0];
        var setIsMuted = _u2[1];

        var _u3 = useState(0);
        var current = _u3[0];
        var setCurrent = _u3[1];

        var _u4 = useState(0);
        var duration = _u4[0];
        var setDuration = _u4[1];

        var _u5 = useState(false);
        var isLoading = _u5[0];
        var setIsLoading = _u5[1];

        var _u6 = useState(false);
        var hasError = _u6[0];
        var setHasError = _u6[1];

        var _u7 = useState('');
        var errorMsg = _u7[0];
        var setErrorMsg = _u7[1];

        var _u8 = useState(U.getSavedSpeed());
        var speed = _u8[0];
        var setSpeed = _u8[1];

        var _u9 = useState(1);
        var volume = _u9[0];
        var setVolume = _u9[1];

        var _u10 = useState(0);
        var resumeTip = _u10[0];
        var setResumeTip = _u10[1];

        var _u11 = useState(false);
        var wasMuted = _u11[0];
        var setWasMuted = _u11[1];

        var _u12 = useState(1);
        var prevVolume = _u12[0];
        var setPrevVolume = _u12[1];

        useEffect(function () {
            var audio = audioRef.current;
            if (!audio) return undefined;

            function onLoadedMeta() {
                setDuration(audio.duration || 0);
                setIsLoading(false);
                setHasError(false);
                audio.playbackRate = speed;

                var saved = U.getSavedPosition(episodeId);
                if (saved > 3) setResumeTip(saved);
            }

            function onTimeUpdate() {
                var now = audio.currentTime || 0;
                setCurrent(now);
                if (!audio.paused && episodeId) U.savePosition(episodeId, now);
            }

            function onPlay() {
                document.querySelectorAll('.novacast-react-audio').forEach(function (other) {
                    if (other !== audio) other.pause();
                });
                setIsPlaying(true);
                setHasError(false);
            }

            function onPause() { setIsPlaying(false); }

            function onEnded() {
                setIsPlaying(false);
                setCurrent(0);
                if (episodeId) U.clearPosition(episodeId);
            }

            function onWaiting() { setIsLoading(true); }
            function onCanPlay() { setIsLoading(false); }
            function onLoadStart() { setIsLoading(true); }
            function onError() {
                var msg = audio.error && audio.error.message ? audio.error.message : 'Falha ao carregar audio.';
                setHasError(true);
                setErrorMsg(msg);
                setIsLoading(false);
            }

            audio.addEventListener('loadedmetadata', onLoadedMeta);
            audio.addEventListener('timeupdate', onTimeUpdate);
            audio.addEventListener('play', onPlay);
            audio.addEventListener('pause', onPause);
            audio.addEventListener('ended', onEnded);
            audio.addEventListener('waiting', onWaiting);
            audio.addEventListener('canplay', onCanPlay);
            audio.addEventListener('canplaythrough', onCanPlay);
            audio.addEventListener('loadstart', onLoadStart);
            audio.addEventListener('error', onError);

            return function () {
                audio.removeEventListener('loadedmetadata', onLoadedMeta);
                audio.removeEventListener('timeupdate', onTimeUpdate);
                audio.removeEventListener('play', onPlay);
                audio.removeEventListener('pause', onPause);
                audio.removeEventListener('ended', onEnded);
                audio.removeEventListener('waiting', onWaiting);
                audio.removeEventListener('canplay', onCanPlay);
                audio.removeEventListener('canplaythrough', onCanPlay);
                audio.removeEventListener('loadstart', onLoadStart);
                audio.removeEventListener('error', onError);
            };
        }, []);

        function togglePlay() {
            var audio = audioRef.current;
            if (!audio) return;
            if (audio.paused) audio.play(); else audio.pause();
        }

        function toggleMute() {
            var audio = audioRef.current;
            if (!audio) return;
            var willMute = !audio.muted;
            audio.muted = willMute;
            setIsMuted(willMute);
            if (willMute) {
                setPrevVolume(volume);
                setVolume(0);
            } else {
                setVolume(prevVolume);
            }
        }

        function handleVolumeChange(val) {
            var audio = audioRef.current;
            if (!audio) return;
            audio.volume = val;
            audio.muted = val === 0;
            setIsMuted(val === 0);
            setVolume(val);
            if (val > 0) setPrevVolume(val);
        }

        function handleSpeedClick() {
            var audio = audioRef.current;
            if (!audio) return;
            var idx = SPEEDS.indexOf(speed);
            var next = SPEEDS[(idx + 1) % SPEEDS.length];
            audio.playbackRate = next;
            setSpeed(next);
            U.saveSpeed(next);
        }

        function seek(event) {
            var audio = audioRef.current;
            var progress = progressRef.current;
            if (!audio || !progress || !audio.duration) return;
            var rect = progress.getBoundingClientRect();
            var ratio = Math.min(Math.max((event.clientX - rect.left) / rect.width, 0), 1);
            audio.currentTime = ratio * audio.duration;
        }

        function skip(seconds) {
            var audio = audioRef.current;
            if (!audio || !audio.duration) return;
            audio.currentTime = Math.min(Math.max(audio.currentTime + seconds, 0), audio.duration);
        }

        function retry() {
            var audio = audioRef.current;
            if (!audio) return;
            setHasError(false);
            audio.load();
        }

        var percentage = duration > 0 ? (current / duration) * 100 : 0;

        return h('div', {
            className: 'novacast-custom-audio novacast-react-custom-audio' +
                (isPlaying ? ' is-playing' : '') +
                (isMuted ? ' is-muted' : '') +
                (isLoading ? ' is-loading' : '') +
                (hasError ? ' has-error' : '')
        },
            h('audio', {
                ref: audioRef,
                className: 'novacast-audio novacast-react-audio',
                preload: 'metadata',
                src: props.audioUrl
            }),
            h('div', { className: 'novacast-audio-primary' },
                h('button', {
                    className: 'novacast-audio-play' + (isPlaying ? ' is-playing' : ''),
                    type: 'button',
                    onClick: togglePlay,
                    'aria-label': 'Reproduzir ou pausar episodio'
                },
                    h('svg', { className: 'novacast-icon-play', width: 18, height: 18, viewBox: '0 0 24 24', fill: 'currentColor', 'aria-hidden': true },
                        h('polygon', { points: '6,3 20,12 6,21' })
                    ),
                    h('svg', { className: 'novacast-icon-pause', width: 18, height: 18, viewBox: '0 0 24 24', fill: 'currentColor', 'aria-hidden': true },
                        h('rect', { x: 5, y: 3, width: 5, height: 18, rx: 1 }),
                        h('rect', { x: 14, y: 3, width: 5, height: 18, rx: 1 })
                    )
                ),
                h('div', { className: 'novacast-audio-timeline' },
                    h('div', { className: 'novacast-audio-times' },
                        h('span', { className: 'novacast-audio-time' }, U.formatTime(current)),
                        h('span', { className: 'novacast-audio-time' }, U.formatTime(duration))
                    ),
                    h('div', {
                        ref: progressRef,
                        className: 'novacast-progress-wrap',
                        onClick: seek,
                        role: 'slider',
                        tabIndex: 0,
                        'aria-label': 'Barra de progresso do episodio',
                        'aria-valuemin': 0,
                        'aria-valuemax': 100,
                        'aria-valuenow': Math.round(percentage)
                    },
                        h('span', { className: 'novacast-progress-track' },
                            h('span', {
                                className: 'novacast-progress-fill',
                                style: { width: percentage + '%' }
                            })
                        ),
                        resumeTip > 3 ? h('span', { className: 'novacast-progress-resume is-visible' }, 'Continuar de ' + U.formatTime(resumeTip)) : null
                    ),
                    h(Waveform, { progress: percentage })
                )
            ),
            h('div', { className: 'novacast-audio-actions' },
                h('button', { className: 'novacast-audio-mini', type: 'button', onClick: function () { skip(-10); } }, '\u221210'),
                h('button', { className: 'novacast-audio-mini', type: 'button', onClick: function () { skip(30); } }, '+30'),
                h(VolumeSlider, {
                    value: volume,
                    onToggleMute: toggleMute,
                    onChange: handleVolumeChange
                }),
                h(SpeedButton, { speed: speed, onClick: handleSpeedClick }),
                h(LoadingDots, null)
            ),
            h(ErrorBanner, { show: hasError, message: errorMsg, onRetry: retry })
        );
    }

    function EpisodePlayer(props) {
        var episode = props.episode;

        if (episode.source === 'youtube') {
            var youtubeId = getYoutubeId(episode.youtubeUrl);
            if (!youtubeId) return null;
            return h('div', { className: 'novacast-embed novacast-youtube' },
                h('iframe', {
                    src: 'https://www.youtube.com/embed/' + encodeURIComponent(youtubeId),
                    title: 'Player do YouTube',
                    loading: 'lazy',
                    allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share',
                    allowFullScreen: true
                })
            );
        }

        if (episode.source === 'spotify') {
            var spotifyId = getSpotifyEpisodeId(episode.spotifyUrl);
            if (!spotifyId) return null;
            return h('div', { className: 'novacast-embed novacast-spotify' },
                h('iframe', {
                    src: 'https://open.spotify.com/embed/episode/' + encodeURIComponent(spotifyId),
                    title: 'Player do Spotify',
                    loading: 'lazy',
                    allow: 'autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture'
                })
            );
        }

        if (!episode.audioUrl) return null;
        return h(CustomAudioPlayer, { audioUrl: episode.audioUrl, episodeId: episode.id });
    }

    function EpisodeCard(props) {
        var episode = props.episode;
        var featured = props.featured;
        var number = props.number;
        var isCurrent = props.isCurrent;
        var sourceLabel = episode.sourceLabel || 'Audio proprio';

        return h('article', {
            className: 'novacast-player-card ' +
                (featured ? 'novacast-featured-card' : 'novacast-episode-card') +
                ' novacast-source-' + episode.source +
                (isCurrent ? ' is-current' : '')
        },
            episode.cover ? h('div', { className: 'novacast-player-cover-wrap' },
                h('img', { className: 'novacast-player-cover', src: episode.cover, alt: episode.title }),
                h('span', { className: 'novacast-cover-gradient', 'aria-hidden': true }),
                h('span', { className: 'novacast-cover-play', 'aria-hidden': true }, '\u25B6'),
                h('span', { className: 'novacast-cover-wave', 'aria-hidden': true })
            ) : null,
            h('div', { className: 'novacast-player-content' },
                h('div', { className: 'novacast-player-meta' },
                    h('span', { className: 'novacast-episode-number' }, 'Episodio ' + String(number).padStart(2, '0')),
                    h('span', { className: 'novacast-player-badge' }, sourceLabel),
                    episode.date ? h('span', { className: 'novacast-player-date' }, episode.date) : null,
                    episode.duration ? h('span', { className: 'novacast-player-duration' }, episode.duration) : null
                ),
                featured ? h('span', { className: 'novacast-featured-label' }, 'Episodio em destaque') : null,
                h('h3', { className: 'novacast-player-title' }, episode.title),
                episode.description ? h('div', { className: 'novacast-player-description', dangerouslySetInnerHTML: { __html: episode.description } }) : null,
                h('div', { className: 'novacast-player-footer' },
                    h('div', { className: 'novacast-player-control' },
                        h(EpisodePlayer, { episode: episode })
                    )
                )
            )
        );
    }

    function EpisodeRow(props) {
        var episode = props.episode;
        var number = props.number;
        var isCurrent = props.isCurrent;

        return h('article', {
            className: 'novacast-episode-row novacast-source-' + episode.source + (isCurrent ? ' is-current' : '')
        },
            episode.cover ? h('div', { className: 'novacast-row-cover' },
                h('img', { src: episode.cover, alt: episode.title }),
                h('span', { 'aria-hidden': true }, '\u25B6')
            ) : null,
            h('div', { className: 'novacast-row-main' },
                h('div', { className: 'novacast-row-meta' },
                    h('span', null, 'Episodio ' + String(number).padStart(2, '0')),
                    episode.sourceLabel ? h('span', null, episode.sourceLabel) : null,
                    episode.date ? h('span', null, episode.date) : null
                ),
                h('h4', null, episode.title),
                h('div', { className: 'novacast-row-wave', 'aria-hidden': true },
                    [1,2,3,4,5,6,7,8,9,10,11,12,13,14].map(function (item) {
                        return h('span', { key: item, style: { height: (18 + item % 5 * 7) + 'px' } });
                    })
                )
            ),
            episode.duration ? h('span', { className: 'novacast-row-duration' }, episode.duration) : null
        );
    }

    function NovacastApp(props) {
        var data = props.data;
        var _u13 = useState(data.theme === 'dark');
        var isDark = _u13[0];
        var setIsDark = _u13[1];
        var _u14 = useState(false);
        var isPlaying = _u14[0];
        var setIsPlaying = _u14[1];

        var episodes = data.episodes || [];
        var featured = episodes[0];
        var moreEpisodes = episodes.slice(1);

        useEffect(function () {
            function onPlay() {
                var audio = document.querySelector('.novacast-react-audio');
                if (audio && !audio.paused) setIsPlaying(true);
            }
            function onPause() {
                setIsPlaying(false);
            }
            document.addEventListener('play', onPlay, true);
            document.addEventListener('pause', onPause, true);
            return function () {
                document.removeEventListener('play', onPlay, true);
                document.removeEventListener('pause', onPause, true);
            };
        }, []);

        useEffect(function () {
            function onKeyDown(e) {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) return;
                var audio = document.querySelector('.novacast-react-audio');
                if (!audio) return;
                switch (e.key) {
                    case ' ':
                        e.preventDefault();
                        if (audio.paused) audio.play(); else audio.pause();
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        audio.currentTime = Math.max(audio.currentTime - 5, 0);
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        if (audio.duration) audio.currentTime = Math.min(audio.currentTime + 5, audio.duration);
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        audio.volume = Math.min(audio.volume + 0.1, 1);
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        audio.volume = Math.max(audio.volume - 0.1, 0);
                        break;
                    case 'm': case 'M':
                        e.preventDefault();
                        audio.muted = !audio.muted;
                        break;
                }
                if (e.key >= '0' && e.key <= '9' && audio.duration) {
                    e.preventDefault();
                    audio.currentTime = (parseInt(e.key, 10) / 10) * audio.duration;
                }
            }
            document.addEventListener('keydown', onKeyDown);
            return function () { document.removeEventListener('keydown', onKeyDown); };
        }, []);

        if (!featured) {
            return h('p', { className: 'novacast-empty' }, 'Nenhum episodio disponivel no momento.');
        }

        return h('section', {
            className: 'novacast-section ' + (isDark ? 'novacast-theme-dark' : 'novacast-theme-light'),
            'data-novacast-section': true,
            'aria-label': 'Novacast - O Podcast da Novacap'
        },
            h('div', { className: 'novacast-section-hero' },
                h('div', { className: 'novacast-brand-icon', 'aria-hidden': true },
                    h('span', { dangerouslySetInnerHTML: { __html: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>' } })
                ),
                h('div', { className: 'novacast-section-header' },
                    h('span', { className: 'novacast-section-kicker' }, data.kicker || 'Podcast Oficial'),
                    h('h2', { className: 'novacast-section-title' }, data.title || 'Novacast - O Podcast da Novacap'),
                    h('p', { className: 'novacast-section-description' }, data.description || 'Fique atualizado com as noticias diarias sobre o que acontece em nossa cidade.')
                ),
                h('div', { className: 'novacast-section-actions' },
                    h('button', {
                        className: 'novacast-theme-toggle',
                        type: 'button',
                        onClick: function () { setIsDark(!isDark); },
                        'aria-label': 'Alternar tema claro e escuro',
                        'aria-pressed': isDark ? 'true' : 'false'
                    },
                        h('span', { className: 'novacast-theme-icon novacast-theme-icon-sun', 'aria-hidden': true }, '\u2600'),
                        h('span', { className: 'novacast-theme-icon novacast-theme-icon-moon', 'aria-hidden': true }, '\u263E')
                    )
                )
            ),
            h(EpisodeCard, { episode: featured, number: 1, featured: true, isCurrent: isPlaying }),
            moreEpisodes.length ? h('div', { className: 'novacast-list-header' },
                h('h3', null, 'Todos os episodios'),
                data.archiveLink ? h('a', { className: 'novacast-view-all', href: data.archiveLink }, 'Ver todos os episodios') : null
            ) : null,
            moreEpisodes.length ? h('div', { className: 'novacast-episode-rows', 'data-novacast-player-list': true },
                moreEpisodes.map(function (episode, index) {
                    return h(EpisodeRow, {
                        key: episode.id || index,
                        episode: episode,
                        number: index + 2,
                        isCurrent: false
                    });
                })
            ) : null
        );
    }

    function mountNovacastPlayers() {
        document.querySelectorAll('[data-novacast-react-root]').forEach(function (root) {
            if (root.getAttribute('data-novacast-mounted') === '1') return;
            var payload = root.getAttribute('data-novacast-props');
            if (!payload) return;

            try {
                var tree = h(NovacastApp, { data: JSON.parse(payload) });

                if (typeof createRoot === 'function') {
                    var appRoot = mountedRoots && mountedRoots.get(root);
                    if (!appRoot) {
                        appRoot = createRoot(root);
                        if (mountedRoots) mountedRoots.set(root, appRoot);
                    }
                    appRoot.render(tree);
                    root.setAttribute('data-novacast-mounted', '1');
                    return;
                }

                if (typeof render === 'function') {
                    render(tree, root);
                    root.setAttribute('data-novacast-mounted', '1');
                    return;
                }

                throw new Error('No React renderer available');
            } catch (error) {
                root.innerHTML = '<p class="novacast-empty">Nao foi possivel carregar o player Novacast.</p>';
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountNovacastPlayers);
    } else {
        mountNovacastPlayers();
    }

    if (typeof MutationObserver === 'function') {
        new MutationObserver(function () { mountNovacastPlayers(); })
            .observe(document.body, { childList: true, subtree: true });
    }
})(window.wp);
