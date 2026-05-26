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
            'novacast-player',
            NOVACAST_PLUGIN_URL . 'assets/js/novacast-player.js',
            array(),
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
            return '<p class="novacast-empty">' . esc_html__( 'Nenhum episódio disponível no momento.', 'novacast' ) . '</p>';
        }

        $featured_episode = array_shift( $episodes );
        $archive_link     = get_post_type_archive_link( Novacast_Post_Type::POST_TYPE );

        ob_start();
        ?>
        <section class="novacast-section novacast-theme-<?php echo esc_attr( $theme ); ?>" data-novacast-section aria-label="<?php echo esc_attr__( 'Novacast - O Podcast da Novacap', 'novacast' ); ?>">
            <div class="novacast-section-hero">
                <div class="novacast-brand-icon" aria-hidden="true">
                    <span>🎙</span>
                </div>

                <div class="novacast-section-header">
                    <span class="novacast-section-kicker"><?php esc_html_e( 'Podcast Oficial', 'novacast' ); ?></span>
                    <h2 class="novacast-section-title"><?php esc_html_e( 'Novacast - O Podcast da Novacap', 'novacast' ); ?></h2>
                    <p class="novacast-section-description">
                        <?php esc_html_e( 'Fique atualizado com as notícias diárias sobre o que acontece em nossa cidade.', 'novacast' ); ?>
                    </p>
                </div>

                <div class="novacast-section-actions">
                    <button class="novacast-theme-toggle" type="button" data-novacast-theme-toggle aria-label="<?php echo esc_attr__( 'Alternar tema claro e escuro', 'novacast' ); ?>">
                        <span class="novacast-theme-icon novacast-theme-icon-sun" aria-hidden="true">☀</span>
                        <span class="novacast-theme-icon novacast-theme-icon-moon" aria-hidden="true">☾</span>
                    </button>
                </div>
            </div>

            <?php echo self::render_episode_card( $featured_episode, 1, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <?php if ( ! empty( $episodes ) ) : ?>
                <div class="novacast-list-header">
                    <h3><?php esc_html_e( 'Mais episódios', 'novacast' ); ?></h3>
                    <?php if ( $archive_link ) : ?>
                        <a class="novacast-view-all" href="<?php echo esc_url( $archive_link ); ?>"><?php esc_html_e( 'Ver todos os episódios', 'novacast' ); ?></a>
                    <?php endif; ?>
                </div>

                <div class="novacast-player-list" data-novacast-player-list>
                    <?php foreach ( $episodes as $index => $episode ) : ?>
                        <?php echo self::render_episode_card( $episode, $index + 2, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php endforeach; ?>
                </div>
            <?php elseif ( $archive_link ) : ?>
                <div class="novacast-single-action">
                    <a class="novacast-view-all" href="<?php echo esc_url( $archive_link ); ?>"><?php esc_html_e( 'Ver todos os episódios', 'novacast' ); ?></a>
                </div>
            <?php endif; ?>
        </section>
        <?php
        return ob_get_clean();
    }

    private static function render_episode_card( $episode, $episode_number, $featured = false ) {
        $source                 = get_post_meta( $episode->ID, Novacast_Admin::META_SOURCE, true );
        $source                 = $source ? $source : 'audio';
        $audio_url              = get_post_meta( $episode->ID, Novacast_Admin::META_AUDIO_URL, true );
        $youtube_url            = get_post_meta( $episode->ID, Novacast_Admin::META_YOUTUBE_URL, true );
        $spotify_url            = get_post_meta( $episode->ID, Novacast_Admin::META_SPOTIFY_URL, true );
        $external_thumbnail_url = get_post_meta( $episode->ID, Novacast_Admin::META_EXTERNAL_THUMBNAIL_URL, true );
        $duration               = get_post_meta( $episode->ID, Novacast_Admin::META_DURATION, true );
        $cover                  = get_the_post_thumbnail_url( $episode->ID, $featured ? 'large' : 'medium' );
        $cover                  = $cover ? $cover : $external_thumbnail_url;
        $player_markup          = self::render_episode_player( $source, $audio_url, $youtube_url, $spotify_url );
        $source_label           = self::get_source_label( $source );

        if ( empty( $player_markup ) ) {
            return '';
        }

        ob_start();
        ?>
        <article class="novacast-player-card <?php echo $featured ? 'novacast-featured-card' : 'novacast-episode-card'; ?> novacast-source-<?php echo esc_attr( $source ); ?>">
            <?php if ( $cover ) : ?>
                <div class="novacast-player-cover-wrap">
                    <img class="novacast-player-cover" src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( get_the_title( $episode ) ); ?>">
                    <span class="novacast-cover-wave" aria-hidden="true"></span>
                </div>
            <?php endif; ?>

            <div class="novacast-player-content">
                <div class="novacast-player-meta">
                    <span class="novacast-play-mini" aria-hidden="true">▶</span>
                    <span class="novacast-episode-number"><?php echo esc_html( sprintf( __( 'Episódio %02d', 'novacast' ), $episode_number ) ); ?></span>
                    <span class="novacast-player-badge"><?php echo esc_html( $source_label ); ?></span>
                    <span class="novacast-player-date"><?php echo esc_html( get_the_date( 'd/m/Y', $episode ) ); ?></span>
                    <?php if ( $duration ) : ?>
                        <span class="novacast-player-duration"><?php echo esc_html( $duration ); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ( $featured ) : ?>
                    <span class="novacast-featured-label"><?php esc_html_e( 'Episódio mais recente', 'novacast' ); ?></span>
                <?php endif; ?>

                <h3 class="novacast-player-title"><?php echo esc_html( get_the_title( $episode ) ); ?></h3>

                <div class="novacast-player-description">
                    <?php echo wp_kses_post( wpautop( get_the_excerpt( $episode ) ?: wp_trim_words( wp_strip_all_tags( $episode->post_content ), $featured ? 42 : 24 ) ) ); ?>
                </div>

                <div class="novacast-player-footer">
                    <div class="novacast-player-control">
                        <?php echo $player_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                </div>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    private static function render_episode_player( $source, $audio_url, $youtube_url, $spotify_url ) {
        if ( 'youtube' === $source ) {
            $youtube_id = self::extract_youtube_id( $youtube_url );

            if ( empty( $youtube_id ) ) {
                return '';
            }

            return sprintf(
                '<div class="novacast-embed novacast-youtube"><iframe src="%s" title="%s" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>',
                esc_url( 'https://www.youtube.com/embed/' . rawurlencode( $youtube_id ) ),
                esc_attr__( 'Player do YouTube', 'novacast' )
            );
        }

        if ( 'spotify' === $source ) {
            $spotify_id = self::extract_spotify_episode_id( $spotify_url );

            if ( empty( $spotify_id ) ) {
                return '';
            }

            return sprintf(
                '<div class="novacast-embed novacast-spotify"><iframe src="%s" title="%s" loading="lazy" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"></iframe></div>',
                esc_url( 'https://open.spotify.com/embed/episode/' . rawurlencode( $spotify_id ) ),
                esc_attr__( 'Player do Spotify', 'novacast' )
            );
        }

        if ( empty( $audio_url ) ) {
            return '';
        }

        return sprintf(
            '<div class="novacast-custom-audio" data-novacast-audio-player><audio class="novacast-audio" preload="metadata" src="%1$s"></audio><button class="novacast-audio-play" type="button" data-novacast-play aria-label="%2$s"><span class="novacast-play-icon" aria-hidden="true">▶</span><span class="novacast-pause-icon" aria-hidden="true">Ⅱ</span></button><span class="novacast-audio-time" data-novacast-current>0:00</span><div class="novacast-progress-wrap" data-novacast-seek><span class="novacast-progress-track"><span class="novacast-progress-fill" data-novacast-progress></span></span></div><span class="novacast-audio-time" data-novacast-duration>0:00</span><button class="novacast-audio-muted" type="button" data-novacast-mute aria-label="%3$s">♪</button></div>',
            esc_url( $audio_url ),
            esc_attr__( 'Reproduzir ou pausar episódio', 'novacast' ),
            esc_attr__( 'Ativar ou desativar som', 'novacast' )
        );
    }

    private static function get_source_label( $source ) {
        switch ( $source ) {
            case 'youtube':
                return __( 'YouTube', 'novacast' );
            case 'spotify':
                return __( 'Spotify', 'novacast' );
            default:
                return __( 'Áudio próprio', 'novacast' );
        }
    }

    private static function extract_youtube_id( $url ) {
        $url = trim( (string) $url );

        if ( preg_match( '#(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([a-zA-Z0-9_-]{6,})#', $url, $matches ) ) {
            return $matches[1];
        }

        if ( preg_match( '#^[a-zA-Z0-9_-]{6,}$#', $url ) ) {
            return $url;
        }

        return '';
    }

    private static function extract_spotify_episode_id( $url ) {
        $url = trim( (string) $url );

        if ( preg_match( '#open\.spotify\.com/(?:embed/)?episode/([a-zA-Z0-9]+)#', $url, $matches ) ) {
            return $matches[1];
        }

        if ( preg_match( '#^[a-zA-Z0-9]+$#', $url ) ) {
            return $url;
        }

        return '';
    }

    private static function get_episodes( $episode_id, $limit ) {
        $args = array(
            'post_type'      => Novacast_Post_Type::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => $limit > 0 ? $limit : 10,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'     => Novacast_Admin::META_ACTIVE,
                    'value'   => '1',
                    'compare' => '=',
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
