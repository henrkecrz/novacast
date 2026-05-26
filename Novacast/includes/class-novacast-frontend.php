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
            ),
            $atts,
            'novacast_player'
        );

        wp_enqueue_style( 'novacast-frontend' );
        wp_enqueue_script( 'novacast-player' );

        $episodes = self::get_episodes( absint( $atts['id'] ), absint( $atts['limit'] ) );

        if ( empty( $episodes ) ) {
            return '<p class="novacast-empty">' . esc_html__( 'Nenhum episódio disponível no momento.', 'novacast' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="novacast-player-list" data-novacast-player-list>
            <?php foreach ( $episodes as $episode ) : ?>
                <?php
                $audio_url = get_post_meta( $episode->ID, Novacast_Admin::META_AUDIO_URL, true );
                $duration  = get_post_meta( $episode->ID, Novacast_Admin::META_DURATION, true );
                $cover     = get_the_post_thumbnail_url( $episode->ID, 'medium' );

                if ( empty( $audio_url ) ) {
                    continue;
                }
                ?>
                <article class="novacast-player-card">
                    <?php if ( $cover ) : ?>
                        <img class="novacast-player-cover" src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( get_the_title( $episode ) ); ?>">
                    <?php endif; ?>

                    <div class="novacast-player-content">
                        <h3 class="novacast-player-title"><?php echo esc_html( get_the_title( $episode ) ); ?></h3>

                        <?php if ( $duration ) : ?>
                            <p class="novacast-player-duration"><?php echo esc_html( $duration ); ?></p>
                        <?php endif; ?>

                        <div class="novacast-player-description">
                            <?php echo wp_kses_post( wpautop( get_the_excerpt( $episode ) ?: wp_trim_words( wp_strip_all_tags( $episode->post_content ), 28 ) ) ); ?>
                        </div>

                        <audio class="novacast-audio" controls preload="metadata">
                            <source src="<?php echo esc_url( $audio_url ); ?>">
                            <?php esc_html_e( 'Seu navegador não suporta reprodução de áudio.', 'novacast' ); ?>
                        </audio>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function get_episodes( $episode_id, $limit ) {
        $args = array(
            'post_type'      => Novacast_Post_Type::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => $limit > 0 ? $limit : 10,
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
