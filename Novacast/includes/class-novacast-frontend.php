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

        wp_register_script(
            'novacast-react-player',
            NOVACAST_PLUGIN_URL . 'assets/js/novacast-react-player.js',
            array( 'wp-element' ),
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
        wp_enqueue_script( 'novacast-react-player' );

        $theme    = 'dark' === sanitize_key( $atts['theme'] ) ? 'dark' : 'light';
        $episodes = self::get_episodes( absint( $atts['id'] ), absint( $atts['limit'] ) );

        if ( empty( $episodes ) ) {
            return '<p class="novacast-empty">' . esc_html__( 'Nenhum episódio disponível no momento.', 'novacast' ) . '</p>';
        }

        $payload = array(
            'theme'       => $theme,
            'kicker'      => __( 'Podcast Oficial', 'novacast' ),
            'title'       => __( 'Novacast - O Podcast da Novacap', 'novacast' ),
            'description' => __( 'Fique atualizado com as notícias diárias sobre o que acontece em nossa cidade.', 'novacast' ),
            'archiveLink' => get_post_type_archive_link( Novacast_Post_Type::POST_TYPE ),
            'episodes'    => array_map( array( __CLASS__, 'format_episode_for_react' ), $episodes ),
        );

        return sprintf(
            '<div class="novacast-react-root" data-novacast-react-root data-novacast-props="%s"></div>',
            esc_attr( wp_json_encode( $payload ) )
        );
    }

    private static function format_episode_for_react( $episode ) {
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
