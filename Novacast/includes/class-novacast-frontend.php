<?php
/**
 * Recursos de frontend e shortcodes.
 *
 * @package Novacast
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Novacast_Frontend {
    public static function init() {
        add_shortcode( 'novacast_player', array( __CLASS__, 'render_player_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
    }

    public static function register_assets() {
        wp_register_style(
            'novacast-frontend',
            NOVACAST_PLUGIN_URL . 'assets/css/novacast-frontend.css',
            array(),
            NOVACAST_VERSION
        );

        wp_register_script(
            'novacast-utils',
            NOVACAST_PLUGIN_URL . 'assets/js/novacast-utils.js',
            array(),
            NOVACAST_VERSION,
            true
        );

        wp_register_script(
            'novacast-player',
            NOVACAST_PLUGIN_URL . 'assets/js/novacast-player.js',
            array( 'novacast-utils' ),
            NOVACAST_VERSION,
            true
        );
    }

    public static function render_player_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'id'    => 0,
                'limit' => 10,
                'theme' => 'light',
            ),
            $atts,
            'novacast_player'
        );

        wp_enqueue_style( 'novacast-frontend' );
        wp_enqueue_script( 'novacast-player' );

        $theme    = 'dark' === sanitize_key( $atts['theme'] ) ? 'dark' : 'light';
        $episodes = self::get_episodes( absint( $atts['id'] ), absint( $atts['limit'] ) );

        if ( empty( $episodes ) ) {
            return '<p class="novacast-empty">' . esc_html__( 'Nenhum episodio disponivel no momento.', 'novacast' ) . '</p>';
        }

        $episodes     = array_map( array( __CLASS__, 'format_episode' ), $episodes );
        $featured     = array_shift( $episodes );
        $archive_link = get_post_type_archive_link( Novacast_Post_Type::POST_TYPE );

        ob_start();
        ?>
        <div class="novacast-backdrop" aria-hidden="true">
            <div class="novacast-backdrop-orb"></div>
            <div class="novacast-backdrop-orb"></div>
            <div class="novacast-backdrop-orb"></div>
        </div>
        <section class="novacast-section novacast-style-apple novacast-theme-<?php echo esc_attr( $theme ); ?>" data-novacast-section aria-label="<?php esc_attr_e( 'Novacast - O Podcast da Novacap', 'novacast' ); ?>">
            <div class="novacast-section-hero">
                <div class="novacast-brand-icon" aria-hidden="true"><?php echo wp_kses( self::get_brand_icon_markup(), self::allowed_svg_tags() ); ?></div>
                <div class="novacast-section-header">
                    <span class="novacast-section-kicker"><?php esc_html_e( 'Agora tocando', 'novacast' ); ?></span>
                    <h2 class="novacast-section-title"><?php esc_html_e( 'Novacast - O Podcast da Novacap', 'novacast' ); ?></h2>
                    <p class="novacast-section-description"><?php esc_html_e( 'Boletins com cara de streaming: mais atmosfera, mais foco no episodio e controles mais refinados.', 'novacast' ); ?></p>
                </div>
                <div class="novacast-section-actions">
                    <button class="novacast-theme-toggle" type="button" data-novacast-theme-toggle aria-label="<?php esc_attr_e( 'Alternar tema claro e escuro', 'novacast' ); ?>" aria-pressed="<?php echo 'dark' === $theme ? 'true' : 'false'; ?>">
                        <span class="novacast-theme-icon novacast-theme-icon-sun" aria-hidden="true">&#9728;</span>
                        <span class="novacast-theme-icon novacast-theme-icon-moon" aria-hidden="true">&#9790;</span>
                        <span class="novacast-theme-label"><?php esc_html_e( 'Tema escuro', 'novacast' ); ?></span>
                    </button>
                </div>
            </div>

            <?php echo self::render_episode_card( $featured, 1, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <?php if ( ! empty( $episodes ) ) : ?>
                <div class="novacast-list-header">
                    <h3><?php esc_html_e( 'Todos os episodios', 'novacast' ); ?></h3>
                    <?php if ( $archive_link ) : ?>
                        <a class="novacast-view-all" href="<?php echo esc_url( $archive_link ); ?>"><?php esc_html_e( 'Ver todos os episodios', 'novacast' ); ?></a>
                    <?php endif; ?>
                </div>
                <div class="novacast-episode-rows" data-novacast-player-list>
                    <?php foreach ( $episodes as $index => $episode ) : ?>
                        <?php echo self::render_episode_row( $episode, $index + 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php

        return ob_get_clean();
    }

    private static function format_episode( $episode ) {
        $source                 = get_post_meta( $episode->ID, Novacast_Admin::META_SOURCE, true );
        $source                 = $source ? $source : 'audio';
        $external_thumbnail_url = get_post_meta( $episode->ID, Novacast_Admin::META_EXTERNAL_THUMBNAIL_URL, true );
        $cover                  = get_the_post_thumbnail_url( $episode->ID, 'large' );
        $cover                  = $cover ? $cover : $external_thumbnail_url;
        $description            = get_the_excerpt( $episode );
        $description            = $description ? $description : wp_trim_words( wp_strip_all_tags( $episode->post_content ), 42 );

        return array(
            'id'          => $episode->ID,
            'title'       => get_the_title( $episode ),
            'description' => wp_kses_post( wpautop( $description ) ),
            'date'        => get_the_date( 'd/m/Y', $episode ),
            'source'      => $source,
            'sourceLabel' => self::get_source_label( $source ),
            'duration'    => get_post_meta( $episode->ID, Novacast_Admin::META_DURATION, true ),
            'cover'       => $cover,
            'audioUrl'    => get_post_meta( $episode->ID, Novacast_Admin::META_AUDIO_URL, true ),
            'youtubeUrl'  => get_post_meta( $episode->ID, Novacast_Admin::META_YOUTUBE_URL, true ),
            'spotifyUrl'  => get_post_meta( $episode->ID, Novacast_Admin::META_SPOTIFY_URL, true ),
        );
    }

    private static function render_episode_card( $episode, $number, $featured = false ) {
        if ( empty( $episode ) || ! is_array( $episode ) ) {
            return '';
        }

        ob_start();
        ?>
        <article class="novacast-player-card <?php echo $featured ? 'novacast-featured-card' : 'novacast-episode-card'; ?> novacast-source-<?php echo esc_attr( $episode['source'] ); ?>">
            <?php if ( ! empty( $episode['cover'] ) ) : ?>
                <div class="novacast-player-cover-wrap">
                    <img class="novacast-player-cover" src="<?php echo esc_url( $episode['cover'] ); ?>" alt="<?php echo esc_attr( $episode['title'] ); ?>">
                    <span class="novacast-cover-gradient" aria-hidden="true"></span>
                    <span class="novacast-cover-play" aria-hidden="true">▶</span>
                    <span class="novacast-cover-wave" aria-hidden="true"></span>
                    <?php if ( $featured ) : ?>
                    <span class="novacast-vinyl-ring" aria-hidden="true"></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="novacast-player-content">
                <div class="novacast-player-meta">
                    <span class="novacast-episode-number"><?php echo esc_html( sprintf( __( 'Episodio %02d', 'novacast' ), $number ) ); ?></span>
                    <?php if ( ! empty( $episode['sourceLabel'] ) ) : ?>
                        <span class="novacast-player-badge"><?php echo esc_html( $episode['sourceLabel'] ); ?></span>
                    <?php endif; ?>
                    <?php if ( ! empty( $episode['date'] ) ) : ?>
                        <span class="novacast-player-date"><?php echo esc_html( $episode['date'] ); ?></span>
                    <?php endif; ?>
                    <?php if ( ! empty( $episode['duration'] ) ) : ?>
                        <span class="novacast-player-duration"><?php echo esc_html( $episode['duration'] ); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ( $featured ) : ?>
                    <span class="novacast-featured-label"><?php esc_html_e( 'Episodio em destaque', 'novacast' ); ?></span>
                <?php endif; ?>
                <h3 class="novacast-player-title"><?php echo esc_html( $episode['title'] ); ?></h3>
                <?php if ( ! empty( $episode['description'] ) ) : ?>
                    <div class="novacast-player-description"><?php echo wp_kses_post( $episode['description'] ); ?></div>
                <?php endif; ?>
                <div class="novacast-player-footer">
                    <div class="novacast-player-control">
                        <?php echo self::render_episode_player( $episode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                </div>
            </div>
        </article>
        <?php

        return ob_get_clean();
    }

    private static function render_episode_row( $episode, $number ) {
        if ( empty( $episode ) || ! is_array( $episode ) ) {
            return '';
        }

        ob_start();
        ?>
        <article class="novacast-episode-row novacast-source-<?php echo esc_attr( $episode['source'] ); ?>">
            <?php if ( ! empty( $episode['cover'] ) ) : ?>
                <div class="novacast-row-cover">
                    <img src="<?php echo esc_url( $episode['cover'] ); ?>" alt="<?php echo esc_attr( $episode['title'] ); ?>">
                    <span aria-hidden="true">▶</span>
                </div>
            <?php endif; ?>
            <div class="novacast-row-main">
                <div class="novacast-row-meta">
                    <span><?php echo esc_html( sprintf( __( 'Episodio %02d', 'novacast' ), $number ) ); ?></span>
                    <?php if ( ! empty( $episode['sourceLabel'] ) ) : ?>
                        <span><?php echo esc_html( $episode['sourceLabel'] ); ?></span>
                    <?php endif; ?>
                    <?php if ( ! empty( $episode['date'] ) ) : ?>
                        <span><?php echo esc_html( $episode['date'] ); ?></span>
                    <?php endif; ?>
                </div>
                <h4><?php echo esc_html( $episode['title'] ); ?></h4>
                <div class="novacast-row-wave" aria-hidden="true"><?php echo self::render_waveform( array( 18, 25, 31, 22, 29, 34, 21, 27, 19, 33, 24, 30, 20, 28 ), false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                <div class="novacast-row-player">
                    <?php echo self::render_episode_player( $episode, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>
            <?php if ( ! empty( $episode['duration'] ) ) : ?>
                <span class="novacast-row-duration"><?php echo esc_html( $episode['duration'] ); ?></span>
            <?php endif; ?>
        </article>
        <?php

        return ob_get_clean();
    }

    private static function render_episode_player( $episode, $compact = false ) {
        switch ( $episode['source'] ) {
            case 'youtube':
                $youtube_id = self::get_youtube_id( $episode['youtubeUrl'] );

                if ( ! $youtube_id ) {
                    return '';
                }

                if ( $compact ) {
                    return '<a class="novacast-inline-link novacast-inline-link-youtube" href="https://www.youtube.com/watch?v=' . esc_attr( rawurlencode( $youtube_id ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Ouvir no YouTube', 'novacast' ) . '</a>';
                }

                return '<div class="novacast-embed novacast-youtube"><iframe src="https://www.youtube.com/embed/' . esc_attr( rawurlencode( $youtube_id ) ) . '" title="' . esc_attr__( 'Player do YouTube', 'novacast' ) . '" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';

            case 'spotify':
                $spotify_id = self::get_spotify_episode_id( $episode['spotifyUrl'] );

                if ( ! $spotify_id ) {
                    return '';
                }

                if ( $compact ) {
                    return '<a class="novacast-inline-link novacast-inline-link-spotify" href="https://open.spotify.com/episode/' . esc_attr( rawurlencode( $spotify_id ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Ouvir no Spotify', 'novacast' ) . '</a>';
                }

                return '<div class="novacast-embed novacast-spotify"><iframe src="https://open.spotify.com/embed/episode/' . esc_attr( rawurlencode( $spotify_id ) ) . '" title="' . esc_attr__( 'Player do Spotify', 'novacast' ) . '" loading="lazy" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"></iframe></div>';

            default:
                return self::render_audio_player( $episode, $compact );
        }
    }

    private static function render_audio_player( $episode, $compact = false ) {
        if ( empty( $episode['audioUrl'] ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="novacast-custom-audio<?php echo $compact ? ' novacast-custom-audio-compact' : ''; ?>" data-novacast-audio-player data-novacast-episode-id="<?php echo esc_attr( $episode['id'] ); ?>">
            <audio class="novacast-audio" preload="metadata" src="<?php echo esc_url( $episode['audioUrl'] ); ?>"></audio>
            <div class="novacast-audio-primary">
                <button class="novacast-audio-play" type="button" data-novacast-play aria-label="<?php esc_attr_e( 'Reproduzir ou pausar episodio', 'novacast' ); ?>">
                    <svg class="novacast-icon-play" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="6,3 20,12 6,21"/></svg>
                    <svg class="novacast-icon-pause" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="5" y="3" width="5" height="18" rx="1"/><rect x="14" y="3" width="5" height="18" rx="1"/></svg>
                </button>
                <div class="novacast-audio-timeline">
                    <div class="novacast-audio-times">
                        <span class="novacast-audio-time" data-novacast-current>0:00</span>
                        <span class="novacast-audio-time" data-novacast-duration><?php echo esc_html( ! empty( $episode['duration'] ) ? $episode['duration'] : '0:00' ); ?></span>
                    </div>
                    <div class="novacast-progress-wrap" data-novacast-seek role="slider" tabindex="0" aria-label="<?php esc_attr_e( 'Barra de progresso do episodio', 'novacast' ); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                        <span class="novacast-progress-track"><span class="novacast-progress-fill" data-novacast-progress></span></span>
                        <span class="novacast-progress-resume" data-novacast-resume-tooltip></span>
                    </div>
                    <div class="novacast-waveform" aria-hidden="true"><?php echo self::render_waveform( array( 34, 54, 42, 72, 38, 88, 48, 64, 30, 76, 46, 92, 40, 68, 36, 56, 78, 44, 66, 32, 84, 50, 70, 38 ), true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                </div>
            </div>
            <div class="novacast-audio-actions">
                <button class="novacast-audio-mini" type="button" data-novacast-skip="-10">-10</button>
                <button class="novacast-audio-mini" type="button" data-novacast-skip="30">+30</button>
                <div class="novacast-volume-wrap">
                    <button class="novacast-audio-muted" type="button" data-novacast-mute aria-label="<?php esc_attr_e( 'Ativar ou desativar som', 'novacast' ); ?>">
                        <svg class="novacast-icon-volume" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path class="novacast-icon-volume-high" d="M19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07"/></svg>
                        <svg class="novacast-icon-muted" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11 5L6 9H2v6h4l5 4V5z"/><line x1="23" y1="9" x2="17" y2="15" stroke="currentColor" stroke-width="2"/><line x1="17" y1="9" x2="23" y2="15" stroke="currentColor" stroke-width="2"/></svg>
                    </button>
                    <input class="novacast-volume-slider" type="range" min="0" max="1" step="0.05" value="1" data-novacast-volume aria-label="<?php esc_attr_e( 'Volume', 'novacast' ); ?>">
                </div>
                <button class="novacast-audio-speed" type="button" data-novacast-speed aria-label="<?php esc_attr_e( 'Velocidade de reproducao', 'novacast' ); ?>">1x</button>
                <div class="novacast-loading-indicator" data-novacast-loading aria-hidden="true">
                    <span class="novacast-loading-dot"></span>
                    <span class="novacast-loading-dot"></span>
                    <span class="novacast-loading-dot"></span>
                </div>
            </div>
            <div class="novacast-error" data-novacast-error role="alert" hidden>
                <span><?php esc_html_e( 'Falha ao carregar audio.', 'novacast' ); ?></span>
                <button type="button" data-novacast-retry><?php esc_html_e( 'Tentar novamente', 'novacast' ); ?></button>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    private static function render_waveform( $bars, $percent_height = false ) {
        $markup = '';

        foreach ( $bars as $i => $height ) {
            $style   = $percent_height ? 'height:' . absint( $height ) . '%;' : 'height:' . absint( $height ) . 'px;';
            $style  .= '--i:' . absint( $i ) . ';';
            $markup .= '<span style="' . esc_attr( $style ) . '"></span>';
        }

        return $markup;
    }

    private static function get_source_label( $source ) {
        switch ( $source ) {
            case 'youtube':
                return __( 'YouTube', 'novacast' );
            case 'spotify':
                return __( 'Spotify', 'novacast' );
            default:
                return __( 'Audio proprio', 'novacast' );
        }
    }

    private static function get_youtube_id( $url ) {
        if ( empty( $url ) ) {
            return '';
        }

        if ( preg_match( '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{6,})/', (string) $url, $matches ) ) {
            return $matches[1];
        }

        return preg_match( '/^[a-zA-Z0-9_-]{6,}$/', (string) $url ) ? $url : '';
    }

    private static function get_spotify_episode_id( $url ) {
        if ( empty( $url ) ) {
            return '';
        }

        if ( preg_match( '/open\.spotify\.com\/(?:embed\/)?episode\/([a-zA-Z0-9]+)/', (string) $url, $matches ) ) {
            return $matches[1];
        }

        return preg_match( '/^[a-zA-Z0-9]+$/', (string) $url ) ? $url : '';
    }

    private static function get_brand_icon_markup() {
        return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a3 3 0 0 0-3 3v6a3 3 0 1 0 6 0V6a3 3 0 0 0-3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><path d="M12 19v2"></path><path d="M8 21h8"></path></svg>';
    }

    private static function allowed_svg_tags() {
        return array(
            'svg'  => array(
                'width'             => true,
                'height'            => true,
                'viewbox'           => true,
                'viewBox'           => true,
                'fill'              => true,
                'stroke'            => true,
                'stroke-width'      => true,
                'stroke-linecap'    => true,
                'stroke-linejoin'   => true,
                'xmlns'             => true,
            ),
            'path' => array(
                'd' => true,
            ),
        );
    }

    private static function get_episodes( $episode_id, $limit ) {
        $args = array(
            'post_type'      => Novacast_Post_Type::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => $limit > 0 ? $limit : 10,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => Novacast_Admin::META_ACTIVE,
                    'value'   => '1',
                    'compare' => '=',
                ),
                array(
                    'key'     => Novacast_Admin::META_ACTIVE,
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );

        if ( $episode_id > 0 ) {
            $args['p']              = $episode_id;
            $args['posts_per_page'] = 1;
        }

        return get_posts( $args );
    }
}
