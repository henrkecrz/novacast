<?php
/**
 * Campos administrativos dos episódios.
 *
 * @package Novacast
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Novacast_Admin {
    const META_AUDIO_URL = '_novacast_audio_url';
    const META_DURATION  = '_novacast_duration';
    const META_ACTIVE    = '_novacast_active';

    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_' . Novacast_Post_Type::POST_TYPE, array( __CLASS__, 'save_episode_meta' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'novacast_episode_details',
            __( 'Detalhes do episódio', 'novacast' ),
            array( __CLASS__, 'render_episode_details_meta_box' ),
            Novacast_Post_Type::POST_TYPE,
            'normal',
            'high'
        );
    }

    public static function enqueue_admin_assets( $hook ) {
        global $post_type;

        if ( Novacast_Post_Type::POST_TYPE !== $post_type ) {
            return;
        }

        wp_enqueue_media();
    }

    public static function render_episode_details_meta_box( $post ) {
        wp_nonce_field( 'novacast_save_episode_meta', 'novacast_episode_meta_nonce' );

        $audio_url = get_post_meta( $post->ID, self::META_AUDIO_URL, true );
        $duration  = get_post_meta( $post->ID, self::META_DURATION, true );
        $active    = get_post_meta( $post->ID, self::META_ACTIVE, true );
        $active    = '' === $active ? '1' : $active;
        ?>
        <p>
            <label for="novacast_audio_url"><strong><?php esc_html_e( 'URL do áudio', 'novacast' ); ?></strong></label><br>
            <input type="url" id="novacast_audio_url" name="novacast_audio_url" value="<?php echo esc_attr( $audio_url ); ?>" class="widefat" placeholder="https://exemplo.com/audio.mp3">
            <span class="description"><?php esc_html_e( 'Informe a URL do arquivo MP3, WAV, OGG ou outro formato aceito pelo navegador.', 'novacast' ); ?></span>
        </p>

        <p>
            <label for="novacast_duration"><strong><?php esc_html_e( 'Duração', 'novacast' ); ?></strong></label><br>
            <input type="text" id="novacast_duration" name="novacast_duration" value="<?php echo esc_attr( $duration ); ?>" class="regular-text" placeholder="00:45:30">
        </p>

        <p>
            <label>
                <input type="checkbox" name="novacast_active" value="1" <?php checked( $active, '1' ); ?>>
                <?php esc_html_e( 'Exibir este episódio no frontend', 'novacast' ); ?>
            </label>
        </p>
        <?php
    }

    public static function save_episode_meta( $post_id ) {
        if ( ! isset( $_POST['novacast_episode_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['novacast_episode_meta_nonce'] ) ), 'novacast_save_episode_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $audio_url = isset( $_POST['novacast_audio_url'] ) ? esc_url_raw( wp_unslash( $_POST['novacast_audio_url'] ) ) : '';
        $duration  = isset( $_POST['novacast_duration'] ) ? sanitize_text_field( wp_unslash( $_POST['novacast_duration'] ) ) : '';
        $active    = isset( $_POST['novacast_active'] ) ? '1' : '0';

        update_post_meta( $post_id, self::META_AUDIO_URL, $audio_url );
        update_post_meta( $post_id, self::META_DURATION, $duration );
        update_post_meta( $post_id, self::META_ACTIVE, $active );
    }
}
