(function (wp) {
    'use strict';

    if (!wp || !wp.element) {
        return;
    }

    var createElement = wp.element.createElement;
    var useEffect = wp.element.useEffect;
    var useRef = wp.element.useRef;
    var useState = wp.element.useState;
    var render = wp.element.render;

    function formatTime(seconds) {
        if (!Number.isFinite(seconds) || seconds < 0) {
            seconds = 0;
        }

        var minutes = Math.floor(seconds / 60);
        var remainingSeconds = Math.floor(seconds % 60);

        return minutes + ':' + String(remainingSeconds).padStart(2, '0');
    }

    function getYoutubeId(url) {
        if (!url) {
            return '';
        }

        var match = String(url).match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{6,})/);

        if (match) {
            return match[1];
        }

        return /^[a-zA-Z0-9_-]{6,}$/.test(url) ? url : '';
    }

    function getSpotifyEpisodeId(url) {
        if (!url) {
            return '';
        }

        var match = String(url).match(/open\.spotify\.com\/(?:embed\/)?episode\/([a-zA-Z0-9]+)/);

        if (match) {
            return match[1];
        }

        return /^[a-zA-Z0-9]+$/.test(url) ? url : '';
    }

    function Waveform(props) {
        var bars = [34, 54, 42, 72, 38, 88, 48, 64, 30, 76, 46, 92, 40, 68, 36, 56, 78, 44, 66, 32, 84, 50, 70, 38];
        var progress = props.progress || 0;

        return createElement('div', { className: 'novacast-waveform', 'aria-hidden': true },
            bars.map(function (height, index) {
                var active = index / bars.length * 100 <= progress;
                return createElement('span', {
                    key: index,
                    className: active ? 'is-active' : '',
                    style: { height: height + '%' }
                });
            })
        );
    }

    function CustomAudioPlayer(props) {
        var audioRef = useRef(null);
        var progressRef = useRef(null);
        var _useState = useState(false);
        var isPlaying = _useState[0];
        var setIsPlaying = _useState[1];
        var _useState2 = useState(false);
        var isMuted = _useState2[0];
        var setIsMuted = _useState2[1];
        var _useState3 = useState(0);
        var current = _useState3[0];
        var setCurrent = _useState3[1];
        var _useState4 = useState(0);
        var duration = _useState4[0];
        var setDuration = _useState4[1];

        useEffect(function () {
            var audio = audioRef.current;

            if (!audio) {
                return undefined;
            }

            function onLoadedMetadata() {
                setDuration(audio.duration || 0);
            }

            function onTimeUpdate() {
                setCurrent(audio.currentTime || 0);
            }

            function onPlay() {
                document.querySelectorAll('.novacast-react-audio').forEach(function (otherAudio) {
                    if (otherAudio !== audio) {
                        otherAudio.pause();
                    }
                });
                setIsPlaying(true);
            }

            function onPause() {
                setIsPlaying(false);
            }

            function onEnded() {
                setIsPlaying(false);
                setCurrent(0);
            }

            audio.addEventListener('loadedmetadata', onLoadedMetadata);
            audio.addEventListener('timeupdate', onTimeUpdate);
            audio.addEventListener('play', onPlay);
            audio.addEventListener('pause', onPause);
            audio.addEventListener('ended', onEnded);

            return function () {
                audio.removeEventListener('loadedmetadata', onLoadedMetadata);
                audio.removeEventListener('timeupdate', onTimeUpdate);
                audio.removeEventListener('play', onPlay);
                audio.removeEventListener('pause', onPause);
                audio.removeEventListener('ended', onEnded);
            };
        }, []);

        function togglePlay() {
            var audio = audioRef.current;

            if (!audio) {
                return;
            }

            if (audio.paused) {
                audio.play();
            } else {
                audio.pause();
            }
        }

        function toggleMute() {
            var audio = audioRef.current;

            if (!audio) {
                return;
            }

            audio.muted = !audio.muted;
            setIsMuted(audio.muted);
        }

        function seek(event) {
            var audio = audioRef.current;
            var progress = progressRef.current;

            if (!audio || !progress || !audio.duration) {
                return;
            }

            var rect = progress.getBoundingClientRect();
            var ratio = Math.min(Math.max((event.clientX - rect.left) / rect.width, 0), 1);
            audio.currentTime = ratio * audio.duration;
        }

        function skip(seconds) {
            var audio = audioRef.current;

            if (!audio || !audio.duration) {
                return;
            }

            audio.currentTime = Math.min(Math.max(audio.currentTime + seconds, 0), audio.duration);
        }

        var percentage = duration > 0 ? current / duration * 100 : 0;

        return createElement('div', {
            className: 'novacast-custom-audio novacast-react-custom-audio' + (isPlaying ? ' is-playing' : '') + (isMuted ? ' is-muted' : '')
        },
            createElement('audio', {
                ref: audioRef,
                className: 'novacast-audio novacast-react-audio',
                preload: 'metadata',
                src: props.audioUrl
            }),
            createElement('div', { className: 'novacast-audio-primary' },
                createElement('button', {
                    className: 'novacast-audio-play',
                    type: 'button',
                    onClick: togglePlay,
                    'aria-label': 'Reproduzir ou pausar episódio'
                }, isPlaying ? 'Ⅱ' : '▶'),
                createElement('div', { className: 'novacast-audio-timeline' },
                    createElement('div', { className: 'novacast-audio-times' },
                        createElement('span', { className: 'novacast-audio-time' }, formatTime(current)),
                        createElement('span', { className: 'novacast-audio-time' }, formatTime(duration))
                    ),
                    createElement('div', {
                        ref: progressRef,
                        className: 'novacast-progress-wrap',
                        onClick: seek,
                        role: 'button',
                        tabIndex: 0
                    }, createElement('span', { className: 'novacast-progress-track' },
                        createElement('span', {
                            className: 'novacast-progress-fill',
                            style: { width: percentage + '%' }
                        })
                    )),
                    createElement(Waveform, { progress: percentage })
                )
            ),
            createElement('div', { className: 'novacast-audio-actions' },
                createElement('button', { className: 'novacast-audio-mini', type: 'button', onClick: function () { skip(-10); } }, '−10'),
                createElement('button', { className: 'novacast-audio-mini', type: 'button', onClick: function () { skip(30); } }, '+30'),
                createElement('button', {
                    className: 'novacast-audio-muted',
                    type: 'button',
                    onClick: toggleMute,
                    'aria-label': 'Ativar ou desativar som'
                }, isMuted ? '×' : '♪')
            )
        );
    }

    function EpisodePlayer(props) {
        var episode = props.episode;

        if (episode.source === 'youtube') {
            var youtubeId = getYoutubeId(episode.youtubeUrl);

            if (!youtubeId) {
                return null;
            }

            return createElement('div', { className: 'novacast-embed novacast-youtube' },
                createElement('iframe', {
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

            if (!spotifyId) {
                return null;
            }

            return createElement('div', { className: 'novacast-embed novacast-spotify' },
                createElement('iframe', {
                    src: 'https://open.spotify.com/embed/episode/' + encodeURIComponent(spotifyId),
                    title: 'Player do Spotify',
                    loading: 'lazy',
                    allow: 'autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture'
                })
            );
        }

        if (!episode.audioUrl) {
            return null;
        }

        return createElement(CustomAudioPlayer, { audioUrl: episode.audioUrl });
    }

    function EpisodeCard(props) {
        var episode = props.episode;
        var featured = props.featured;
        var number = props.number;
        var sourceLabel = episode.sourceLabel || 'Áudio próprio';

        return createElement('article', {
            className: 'novacast-player-card ' + (featured ? 'novacast-featured-card' : 'novacast-episode-card') + ' novacast-source-' + episode.source
        },
            episode.cover ? createElement('div', { className: 'novacast-player-cover-wrap' },
                createElement('img', {
                    className: 'novacast-player-cover',
                    src: episode.cover,
                    alt: episode.title
                }),
                createElement('span', { className: 'novacast-cover-gradient', 'aria-hidden': true }),
                createElement('span', { className: 'novacast-cover-play', 'aria-hidden': true }, '▶'),
                createElement('span', { className: 'novacast-cover-wave', 'aria-hidden': true })
            ) : null,
            createElement('div', { className: 'novacast-player-content' },
                createElement('div', { className: 'novacast-player-meta' },
                    createElement('span', { className: 'novacast-episode-number' }, 'Episódio ' + String(number).padStart(2, '0')),
                    createElement('span', { className: 'novacast-player-badge' }, sourceLabel),
                    episode.date ? createElement('span', { className: 'novacast-player-date' }, episode.date) : null,
                    episode.duration ? createElement('span', { className: 'novacast-player-duration' }, episode.duration) : null
                ),
                featured ? createElement('span', { className: 'novacast-featured-label' }, 'Episódio em destaque') : null,
                createElement('h3', { className: 'novacast-player-title' }, episode.title),
                episode.description ? createElement('div', {
                    className: 'novacast-player-description',
                    dangerouslySetInnerHTML: { __html: episode.description }
                }) : null,
                createElement('div', { className: 'novacast-player-footer' },
                    createElement('div', { className: 'novacast-player-control' },
                        createElement(EpisodePlayer, { episode: episode })
                    )
                )
            )
        );
    }

    function EpisodeRow(props) {
        var episode = props.episode;
        var number = props.number;

        return createElement('article', { className: 'novacast-episode-row novacast-source-' + episode.source },
            episode.cover ? createElement('div', { className: 'novacast-row-cover' },
                createElement('img', { src: episode.cover, alt: episode.title }),
                createElement('span', { 'aria-hidden': true }, '▶')
            ) : null,
            createElement('div', { className: 'novacast-row-main' },
                createElement('div', { className: 'novacast-row-meta' },
                    createElement('span', null, 'Episódio ' + String(number).padStart(2, '0')),
                    episode.sourceLabel ? createElement('span', null, episode.sourceLabel) : null,
                    episode.date ? createElement('span', null, episode.date) : null
                ),
                createElement('h4', null, episode.title),
                createElement('div', { className: 'novacast-row-wave', 'aria-hidden': true },
                    [1,2,3,4,5,6,7,8,9,10,11,12,13,14].map(function (item) {
                        return createElement('span', { key: item, style: { height: (18 + item % 5 * 7) + 'px' } });
                    })
                )
            ),
            episode.duration ? createElement('span', { className: 'novacast-row-duration' }, episode.duration) : null
        );
    }

    function NovacastApp(props) {
        var data = props.data;
        var _useState5 = useState(data.theme === 'dark');
        var isDark = _useState5[0];
        var setIsDark = _useState5[1];
        var episodes = data.episodes || [];
        var featured = episodes[0];
        var moreEpisodes = episodes.slice(1);

        if (!featured) {
            return createElement('p', { className: 'novacast-empty' }, 'Nenhum episódio disponível no momento.');
        }

        return createElement('section', {
            className: 'novacast-section ' + (isDark ? 'novacast-theme-dark' : 'novacast-theme-light'),
            'data-novacast-section': true,
            'aria-label': 'Novacast - O Podcast da Novacap'
        },
            createElement('div', { className: 'novacast-section-hero' },
                createElement('div', { className: 'novacast-brand-icon', 'aria-hidden': true },
                    createElement('span', null, '🎙')
                ),
                createElement('div', { className: 'novacast-section-header' },
                    createElement('span', { className: 'novacast-section-kicker' }, data.kicker || 'Podcast Oficial'),
                    createElement('h2', { className: 'novacast-section-title' }, data.title || 'Novacast - O Podcast da Novacap'),
                    createElement('p', { className: 'novacast-section-description' }, data.description || 'Fique atualizado com as notícias diárias sobre o que acontece em nossa cidade.')
                ),
                createElement('div', { className: 'novacast-section-actions' },
                    createElement('button', {
                        className: 'novacast-theme-toggle',
                        type: 'button',
                        onClick: function () { setIsDark(!isDark); },
                        'aria-label': 'Alternar tema claro e escuro',
                        'aria-pressed': isDark ? 'true' : 'false'
                    },
                        createElement('span', { className: 'novacast-theme-icon novacast-theme-icon-sun', 'aria-hidden': true }, '☀'),
                        createElement('span', { className: 'novacast-theme-icon novacast-theme-icon-moon', 'aria-hidden': true }, '☾')
                    )
                )
            ),
            createElement(EpisodeCard, { episode: featured, number: 1, featured: true }),
            moreEpisodes.length ? createElement('div', { className: 'novacast-list-header' },
                createElement('h3', null, 'Todos os episódios'),
                data.archiveLink ? createElement('a', { className: 'novacast-view-all', href: data.archiveLink }, 'Ver todos os episódios') : null
            ) : null,
            moreEpisodes.length ? createElement('div', { className: 'novacast-episode-rows', 'data-novacast-player-list': true },
                moreEpisodes.map(function (episode, index) {
                    return createElement(EpisodeRow, {
                        key: episode.id || index,
                        episode: episode,
                        number: index + 2
                    });
                })
            ) : null
        );
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-novacast-react-root]').forEach(function (root) {
            var payload = root.getAttribute('data-novacast-props');

            if (!payload) {
                return;
            }

            try {
                render(createElement(NovacastApp, { data: JSON.parse(payload) }), root);
            } catch (error) {
                root.innerHTML = '<p class="novacast-empty">Não foi possível carregar o player Novacast.</p>';
            }
        });
    });
})(window.wp);
