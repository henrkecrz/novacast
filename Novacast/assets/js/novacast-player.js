(function () {
    'use strict';

    var U = window.NovacastUtils;

    function getWrapper(el) {
        return el ? el.closest('[data-novacast-audio-player]') : null;
    }
    function getAudio(w) { return w ? w.querySelector('.novacast-audio') : null; }
    function q(w, s) { return w ? w.querySelector(s) : null; }

    function syncVolumeUI(w, audio) {
        if (!w || !audio) {
            return;
        }

        var slider = q(w, '[data-novacast-volume]');
        if (slider) {
            slider.value = audio.muted ? 0 : audio.volume;
        }

        w.classList.toggle('is-muted', audio.muted || audio.volume === 0);
    }

    function syncMiniplayer() {
        if (!miniplayerEl) {
            return;
        }

        var activeW = document.querySelector('[data-novacast-audio-player].is-playing');
        if (!activeW) {
            miniplayerEl.classList.remove('is-visible');
            miniplayerEl.setAttribute('aria-hidden', 'true');
            return;
        }

        var activeSection = activeW.closest('[data-novacast-section]');
        if (!activeSection) {
            miniplayerEl.classList.remove('is-visible');
            miniplayerEl.setAttribute('aria-hidden', 'true');
            return;
        }

        var rect = activeSection.getBoundingClientRect();
        var isVisible = rect.bottom > 80 && rect.top < window.innerHeight - 80;
        miniplayerEl.classList.toggle('is-visible', !isVisible);
        miniplayerEl.setAttribute('aria-hidden', String(isVisible));

        var activeCard = activeW.closest('.novacast-player-card, .novacast-episode-row');
        var titleEl = miniplayerEl.querySelector('[data-novacast-mini-title]');
        var timeEl = miniplayerEl.querySelector('[data-novacast-mini-time]');

        if (activeCard) {
            var title = activeCard.querySelector('.novacast-player-title, h4');
            if (titleEl && title) {
                titleEl.textContent = title.textContent;
            }
        }

        var cur = activeW.querySelector('[data-novacast-current]');
        if (timeEl && cur) {
            timeEl.textContent = cur.textContent;
        }
    }

    function highlightActive(w) {
        document.querySelectorAll('.novacast-player-card.is-current, .novacast-episode-row.is-current').forEach(function (el) {
            el.classList.remove('is-current');
        });
        if (w && w.classList.contains('is-playing')) {
            var card = w.closest('.novacast-player-card, .novacast-episode-row');
            if (card) card.classList.add('is-current');
        }
    }

    function updatePlayer(w) {
        if (!w) return;
        var audio = getAudio(w);
        if (!audio) return;

        var total = audio.duration || 0;
        var now = audio.currentTime || 0;
        var percent = total > 0 ? (now / total) * 100 : 0;
        var progress = q(w, '[data-novacast-progress]');
        var current = q(w, '[data-novacast-current]');
        var duration = q(w, '[data-novacast-duration]');
        var playBtn = q(w, '[data-novacast-play]');

        if (progress) progress.style.width = percent + '%';
        if (current) current.textContent = U.formatTime(now);
        if (duration) duration.textContent = U.formatTime(total);
        if (playBtn) {
            playBtn.classList.toggle('is-paused', audio.paused);
            playBtn.classList.toggle('is-playing', !audio.paused);
        }

        var seekEl = q(w, '[data-novacast-seek]');
        if (seekEl) seekEl.setAttribute('aria-valuenow', Math.round(percent));

        var episodeId = U.getEpisodeId(w);
        if (!audio.paused && episodeId) {
            U.savePosition(episodeId, now);
        }

        highlightActive(w);
    }

    function setLoading(w, loading) {
        if (!w) return;
        w.classList.toggle('is-loading', loading);
        var indicator = q(w, '[data-novacast-loading]');
        if (indicator) indicator.setAttribute('aria-hidden', String(!loading));
    }

    function setError(w, msg) {
        if (!w) return;
        var errEl = q(w, '[data-novacast-error]');
        if (!errEl) return;
        w.classList.add('has-error');
        if (msg) {
            errEl.querySelector('span').textContent = msg;
        }
        errEl.hidden = false;
        setLoading(w, false);
    }

    function clearError(w) {
        if (!w) return;
        var errEl = q(w, '[data-novacast-error]');
        if (errEl) errEl.hidden = true;
        w.classList.remove('has-error');
    }

    function showResumeTooltip(w, seconds) {
        var tip = q(w, '[data-novacast-resume-tooltip]');
        if (!tip || seconds <= 3) return;
        tip.textContent = 'Continuar de ' + U.formatTime(seconds);
        tip.classList.add('is-visible');
        setTimeout(function () { tip.classList.remove('is-visible'); }, 4000);
    }

    document.addEventListener('click', function (e) {
        var target = e.target;

        var toggle = target.closest('[data-novacast-theme-toggle]');
        if (toggle) {
            var section = toggle.closest('[data-novacast-section]');
            if (!section) return;
            var isDark = section.classList.toggle('novacast-theme-dark');
            section.classList.toggle('novacast-theme-light', !isDark);
            toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            return;
        }

        var playBtn = target.closest('[data-novacast-play]');
        if (playBtn) {
            var w = getWrapper(playBtn);
            var audio = getAudio(w);
            if (!audio) return;
            U.setLastActiveWrapper(w);
            if (audio.paused) {
                document.querySelectorAll('.novacast-audio').forEach(function (other) {
                    if (other !== audio) other.pause();
                });
                audio.play();
            } else {
                audio.pause();
            }
            return;
        }

        var muteBtn = target.closest('[data-novacast-mute]');
        if (muteBtn) {
            var mw = getWrapper(muteBtn);
            var ma = getAudio(mw);
            if (!ma) return;
            U.setLastActiveWrapper(mw);
            ma.muted = !ma.muted;
            if (mw) {
                mw.classList.toggle('is-muted', ma.muted);
                var volSlider = q(mw, '[data-novacast-volume]');
                if (ma.muted) {
                    if (volSlider) {
                        volSlider.dataset.prevVolume = parseFloat(volSlider.value) > 0 ? volSlider.value : (volSlider.dataset.prevVolume || '1');
                    }
                    if (volSlider) volSlider.value = 0;
                } else {
                    if (volSlider) {
                        var restored = parseFloat(volSlider.dataset.prevVolume || '1');
                        volSlider.value = restored > 0 ? restored : 1;
                    }
                }
                if (volSlider) {
                    volSlider.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
            return;
        }

        var skipBtn = target.closest('[data-novacast-skip]');
        if (skipBtn) {
            var sw = getWrapper(skipBtn);
            var sa = getAudio(sw);
            var secs = parseInt(skipBtn.getAttribute('data-novacast-skip'), 10);
            if (!sa || !sa.duration || !Number.isFinite(secs)) return;
            U.setLastActiveWrapper(sw);
            sa.currentTime = Math.min(Math.max(sa.currentTime + secs, 0), sa.duration);
            updatePlayer(sw);
            return;
        }

        var retryBtn = target.closest('[data-novacast-retry]');
        if (retryBtn) {
            var rw = getWrapper(retryBtn);
            var ra = getAudio(rw);
            if (!ra) return;
            clearError(rw);
            ra.load();
            return;
        }
    });

    document.addEventListener('input', function (e) {
        var volSlider = e.target.closest('[data-novacast-volume]');
        if (!volSlider) return;
        var vw = getWrapper(volSlider);
        var va = getAudio(vw);
        if (!va) return;
        U.setLastActiveWrapper(vw);
        var val = parseFloat(volSlider.value);
        va.volume = val;
        va.muted = val === 0;
        syncVolumeUI(vw, va);
    });

    document.addEventListener('click', function (e) {
        var speedBtn = e.target.closest('[data-novacast-speed]');
        if (!speedBtn) return;
        var sw = getWrapper(speedBtn);
        var sa = getAudio(sw);
        if (!sa) return;
        U.setLastActiveWrapper(sw);
        var speeds = [0.5, 0.75, 1, 1.25, 1.5, 2];
        var current = sa.playbackRate || 1;
        var idx = speeds.indexOf(current);
        var next = speeds[(idx + 1) % speeds.length];
        sa.playbackRate = next;
        speedBtn.textContent = next + 'x';
        U.saveSpeed(next);
    });

    document.addEventListener('click', function (e) {
        var seek = e.target.closest('[data-novacast-seek]');
        if (!seek) return;
        var sw = getWrapper(seek);
        var sa = getAudio(sw);
        if (!sa || !sa.duration) return;
        U.setLastActiveWrapper(sw);
        var rect = seek.getBoundingClientRect();
        var ratio = Math.min(Math.max((e.clientX - rect.left) / rect.width, 0), 1);
        sa.currentTime = ratio * sa.duration;
        updatePlayer(sw);
    });

    document.addEventListener('focusin', function (e) {
        var w = getWrapper(e.target);
        if (w) {
            U.setLastActiveWrapper(w);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) return;

        var w = U.getActiveWrapper();
        if (!w) return;
        var audio = getAudio(w);
        if (!audio) return;

        switch (e.key) {
            case ' ':
                e.preventDefault();
                if (audio.paused) { audio.play(); } else { audio.pause(); }
                break;
            case 'ArrowLeft':
                e.preventDefault();
                audio.currentTime = Math.max(audio.currentTime - 5, 0);
                updatePlayer(w);
                break;
            case 'ArrowRight':
                e.preventDefault();
                if (audio.duration) audio.currentTime = Math.min(audio.currentTime + 5, audio.duration);
                updatePlayer(w);
                break;
            case 'ArrowUp':
                e.preventDefault();
                audio.volume = Math.min(audio.volume + 0.1, 1);
                audio.muted = false;
                syncVolumeUI(w, audio);
                break;
            case 'ArrowDown':
                e.preventDefault();
                audio.volume = Math.max(audio.volume - 0.1, 0);
                audio.muted = audio.volume === 0;
                syncVolumeUI(w, audio);
                break;
            case 'm':
            case 'M':
                e.preventDefault();
                audio.muted = !audio.muted;
                var slider = q(w, '[data-novacast-volume]');
                if (slider) {
                    if (audio.muted) {
                        slider.dataset.prevVolume = parseFloat(slider.value) > 0 ? slider.value : (slider.dataset.prevVolume || '1');
                        slider.value = 0;
                    } else {
                        var restored = parseFloat(slider.dataset.prevVolume || '1');
                        slider.value = restored > 0 ? restored : 1;
                        audio.volume = restored > 0 ? restored : 1;
                    }
                }
                syncVolumeUI(w, audio);
                break;
            case 'Home':
                e.preventDefault();
                audio.currentTime = 0;
                updatePlayer(w);
                break;
            case 'End':
                e.preventDefault();
                if (audio.duration) audio.currentTime = audio.duration;
                updatePlayer(w);
                break;
        }

        if (e.key >= '0' && e.key <= '9' && audio.duration) {
            e.preventDefault();
            audio.currentTime = (parseInt(e.key, 10) / 10) * audio.duration;
            updatePlayer(w);
        }
    });

    document.addEventListener('keydown', function (e) {
        var seek = e.target.closest('[data-novacast-seek]');
        if (!seek) return;
        var sw = getWrapper(seek);
        var sa = getAudio(sw);
        if (!sa || !sa.duration) return;
        U.setLastActiveWrapper(sw);

        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            sa.currentTime = Math.max(sa.currentTime - 5, 0);
            updatePlayer(sw);
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            sa.currentTime = Math.min(sa.currentTime + 5, sa.duration);
            updatePlayer(sw);
        } else if (e.key === 'Home') {
            e.preventDefault();
            sa.currentTime = 0;
            updatePlayer(sw);
        } else if (e.key === 'End') {
            e.preventDefault();
            sa.currentTime = sa.duration;
            updatePlayer(sw);
        }
    });

    function toggleCardPlaying(audio, playing) {
        var w = getWrapper(audio);
        if (!w) return;
        w.classList.toggle('is-playing', playing);
        var card = w.closest('.novacast-player-card, .novacast-episode-row');
        if (card) card.classList.toggle('is-playing', playing);
        if (playing) { clearError(w); }
        updatePlayer(w);
        syncMiniplayer();
    }

    document.addEventListener('play', function (e) {
        var audio = e.target;
        if (!audio.classList || !audio.classList.contains('novacast-audio')) return;
        toggleCardPlaying(audio, true);
    }, true);

    document.addEventListener('pause', function (e) {
        var audio = e.target;
        if (!audio.classList || !audio.classList.contains('novacast-audio')) return;
        toggleCardPlaying(audio, false);
    }, true);

    document.addEventListener('ended', function (e) {
        var audio = e.target;
        if (!audio.classList || !audio.classList.contains('novacast-audio')) return;
        toggleCardPlaying(audio, false);
        var w = getWrapper(audio);
        if (w) {
            U.clearPosition(U.getEpisodeId(w));
            var next = U.getNextWrapper(w);
            if (next) {
                var nextBtn = q(next, '[data-novacast-play]');
                if (nextBtn) nextBtn.click();
            }
        }
    }, true);

    document.addEventListener('timeupdate', function (e) {
        var audio = e.target;
        if (!audio.classList || !audio.classList.contains('novacast-audio')) return;
        var w = getWrapper(audio);
        if (w) updatePlayer(w);
    }, true);

    document.addEventListener('loadedmetadata', function (e) {
        var audio = e.target;
        if (!audio.classList || !audio.classList.contains('novacast-audio')) return;
        var w = getWrapper(audio);
        if (w) {
            updatePlayer(w);
            var speed = U.getSavedSpeed();
            audio.playbackRate = speed;
            var speedBtn = q(w, '[data-novacast-speed]');
            if (speedBtn) speedBtn.textContent = speed + 'x';

            var episodeId = U.getEpisodeId(w);
            var saved = U.getSavedPosition(episodeId);
            if (saved > 3) {
                if (saved < Math.max((audio.duration || 0) - 3, 0)) {
                    audio.currentTime = saved;
                }
                showResumeTooltip(w, saved);
                updatePlayer(w);
            }
        }
    }, true);

    document.addEventListener('waiting', function (e) {
        var audio = e.target;
        if (!audio.classList || !audio.classList.contains('novacast-audio')) return;
        var w = getWrapper(audio);
        if (w) setLoading(w, true);
    }, true);

    document.addEventListener('canplay', function (e) {
        var audio = e.target;
        if (!audio.classList || !audio.classList.contains('novacast-audio')) return;
        var w = getWrapper(audio);
        if (w) {
            setLoading(w, false);
            clearError(w);
        }
    }, true);

    document.addEventListener('error', function (e) {
        var audio = e.target;
        if (!audio.classList || !audio.classList.contains('novacast-audio')) return;
        var w = getWrapper(audio);
        if (w) {
            var msg = audio.error && audio.error.message ? audio.error.message : 'Falha ao carregar audio.';
            setError(w, msg);
        }
    }, true);

    document.addEventListener('loadstart', function (e) {
        var audio = e.target;
        if (!audio.classList || !audio.classList.contains('novacast-audio')) return;
        var w = getWrapper(audio);
        if (w) setLoading(w, true);
    }, true);

    document.querySelectorAll('[data-novacast-audio-player]').forEach(function (w) {
        updatePlayer(w);
    });

    document.querySelectorAll('[data-novacast-section]').forEach(function (section) {
        section.setAttribute('data-novacast-enhanced', '1');

        section.addEventListener('mousemove', function (e) {
            var rect = section.getBoundingClientRect();
            var x = ((e.clientX - rect.left) / rect.width) * 100;
            var y = ((e.clientY - rect.top) / rect.height) * 100;
            section.style.setProperty('--novacast-glow-x', x.toFixed(2) + '%');
            section.style.setProperty('--novacast-glow-y', y.toFixed(2) + '%');
        });
    });

    document.querySelectorAll('.novacast-featured-card').forEach(function (card) {
        card.addEventListener('mousemove', function (e) {
            var rect = card.getBoundingClientRect();
            var px = (e.clientX - rect.left) / rect.width;
            var py = (e.clientY - rect.top) / rect.height;
            var tiltY = (px - 0.5) * 7;
            var tiltX = (0.5 - py) * 7;
            card.style.setProperty('--novacast-tilt-x', tiltX.toFixed(2) + 'deg');
            card.style.setProperty('--novacast-tilt-y', tiltY.toFixed(2) + 'deg');
            card.style.setProperty('--novacast-glow-x', (px * 100).toFixed(2) + '%');
            card.style.setProperty('--novacast-glow-y', (py * 100).toFixed(2) + '%');
        });

        card.addEventListener('mouseleave', function () {
            card.style.setProperty('--novacast-tilt-x', '0deg');
            card.style.setProperty('--novacast-tilt-y', '0deg');
            card.style.setProperty('--novacast-glow-x', '50%');
            card.style.setProperty('--novacast-glow-y', '28%');
        });
    });

    var miniplayerEl = null;
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!miniplayerEl) {
                    miniplayerEl = document.createElement('div');
                    miniplayerEl.className = 'novacast-miniplayer';
                    miniplayerEl.setAttribute('data-novacast-miniplayer', '');
                    miniplayerEl.setAttribute('aria-hidden', 'true');
                    miniplayerEl.innerHTML = '<div class="novacast-miniplayer-inner"><span class="novacast-miniplayer-title" data-novacast-mini-title></span><span class="novacast-miniplayer-time" data-novacast-mini-time></span></div>';
                    document.body.appendChild(miniplayerEl);
                    miniplayerEl.addEventListener('click', function () {
                        var activeW = document.querySelector('[data-novacast-audio-player].is-playing');
                        if (!activeW) {
                            return;
                        }

                        var card = activeW.closest('.novacast-player-card, .novacast-episode-row');
                        if (card && typeof card.scrollIntoView === 'function') {
                            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    });
                }
                syncMiniplayer();
            });
        }, { threshold: 0, rootMargin: '0px 0px 80px 0px' });

        document.querySelectorAll('[data-novacast-section]').forEach(function (section) {
            observer.observe(section);
        });
    }

    setInterval(function () {
        var mp = document.querySelector('[data-novacast-miniplayer]');
        if (!mp || !mp.classList.contains('is-visible')) return;
        var activeW = document.querySelector('[data-novacast-audio-player].is-playing');
        if (!activeW) return;
        var cur = activeW.querySelector('[data-novacast-current]');
        var timeEl = mp.querySelector('[data-novacast-mini-time]');
        if (timeEl && cur) timeEl.textContent = cur.textContent;
    }, 1000);

    window.addEventListener('scroll', syncMiniplayer, { passive: true });
    window.addEventListener('resize', syncMiniplayer);
})();
