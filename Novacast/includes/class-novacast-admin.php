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
    const META_AUDIO_URL              = '_novacast_audio_url';
    const META_DURATION               = '_novacast_duration';
    const META_ACTIVE                 = '_novacast_active';
    const META_SOURCE                 = '_novacast_source';
    const META_EXTERNAL_ID            = '_novacast_external_id';
    const META_YOUTUBE_URL            = '_novacast_youtube_url';
    const META_SPOTIFY_URL            = '_novacast_spotify_url';
    const META_EXTERNAL_THUMBNAIL_URL = '_novacast_external_thumbnail_url';

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
        wp_enqueue_script(
            'novacast-admin',
            NOVACAST_PLUGIN_URL . 'assets/js/novacast-admin.js',
            array( 'jquery' ),
            NOVACAST_VERSION,
            true
        );

        wp_localize_script(
            'novacast-admin',
            'NovacastAdmin',
            array(
                'mediaTitle'  => __( 'Selecionar áudio do episódio', 'novacast' ),
                'mediaButton' => __( 'Usar este áudio', 'novacast' ),
            )
        );
    }

    public static function render_episode_details_meta_box( $post ) {
        wp_nonce_field( 'novacast_save_episode_meta', 'novacast_episode_meta_nonce' );

        $source                 = get_post_meta( $post->ID, self::META_SOURCE, true );
        $source                 = $source ? $source : 'audio';
        $audio_url              = get_post_meta( $post->ID, self::META_AUDIO_URL, true );
        $youtube_url            = get_post_meta( $post->ID, self::META_YOUTUBE_URL, true );
        $spotify_url            = get_post_meta( $post->ID, self::META_SPOTIFY_URL, true );
        $external_id            = get_post_meta( $post->ID, self::META_EXTERNAL_ID, true );
        $external_thumbnail_url = get_post_meta( $post->ID, self::META_EXTERNAL_THUMBNAIL_URL, true );
        $duration               = get_post_meta( $post->ID, self::META_DURATION, true );
        $active                 = get_post_meta( $post->ID, self::META_ACTIVE, true );
        $active                 = '' === $active ? '1' : $active;
        ?>
        <p>
            <label for="novacast_source"><strong><?php esc_html_e( 'Fonte de reprodução', 'novacast' ); ?></strong></label><br>
            <select id="novacast_source" name="novacast_source">
                <option value="audio" <?php selected( $source, 'audio' ); ?>><?php esc_html_e( 'Áudio próprio', 'novacast' ); ?></option>
                <option value="youtube" <?php selected( $source, 'youtube' ); ?>><?php esc_html_e( 'YouTube Embed', 'novacast' ); ?></option>
                <option value="spotify" <?php selected( $source, 'spotify' ); ?>><?php esc_html_e( 'Spotify Embed', 'novacast' ); ?></option>
            </select>
            <span class="description"><?php esc_html_e( 'O frontend usa áudio HTML5 para arquivos próprios e embeds oficiais para YouTube/Spotify.', 'novacast' ); ?></span>
        </p>

        <div class="novacast-admin-audio-field">
            <label for="novacast_audio_url"><strong><?php esc_html_e( 'URL do áudio próprio', 'novacast' ); ?></strong></label>
            <input type="url" id="novacast_audio_url" name="novacast_audio_url" value="<?php echo esc_attr( $audio_url ); ?>" class="widefat" placeholder="https://exemplo.com/audio.mp3">
            <p>
                <button type="button" class="button button-secondary" id="novacast_select_audio_button">
                    <?php esc_html_e( 'Carregar ou escolher áudio da galeria', 'novacast' ); ?>
                </button>
                <button type="button" class="button" id="novacast_clear_audio_button">
                    <?php esc_html_e( 'Limpar áudio', 'novacast' ); ?>
                </button>
            </p>
            <p class="description">
                <?php esc_html_e( 'Você pode colar uma URL manualmente ou usar a biblioteca de mídia do WordPress para enviar/selecionar MP3, WAV, OGG ou outro formato aceito pelo navegador.', 'novacast' ); ?>
            </p>
            <div id="novacast_audio_preview" class="novacast-audio-preview">
                <?php if ( $audio_url ) : ?>
                    <audio controls preload="metadata" style="width:100%;max-width:520px;">
                        <source src="<?php echo esc_url( $audio_url ); ?>">
                    </audio>
                <?php endif; ?>
            </div>
        </div>

        <p>
            <label for="novacast_youtube_url"><strong><?php esc_html_e( 'URL do YouTube', 'novacast' ); ?></strong></label><br>
            <input type="url" id="novacast_youtube_url" name="novacast_youtube_url" value="<?php echo esc_attr( $youtube_url ); ?>" class="widefat" placeholder="https://www.youtube.com/watch?v=VIDEO_ID">
        </p>

        <p>
            <label for="novacast_spotify_url"><strong><?php esc_html_e( 'URL do Spotify', 'novacast' ); ?></strong></label><br>
            <input type="url" id="novacast_spotify_url" name="novacast_spotify_url" value="<?php echo esc_attr( $spotify_url ); ?>" class="widefat" placeholder="https://open.spotify.com/episode/EPISODE_ID">
        </p>

        <p>
            <label for="novacast_external_id"><strong><?php esc_html_e( 'ID externo', 'novacast' ); ?></strong></label><br>
            <input type="text" id="novacast_external_id" name="novacast_external_id" value="<?php echo esc_attr( $external_id ); ?>" class="regular-text" placeholder="ID do vídeo ou episódio importado">
        </p>

        <p>
            <label for="novacast_external_thumbnail_url"><strong><?php esc_html_e( 'URL de imagem externa', 'novacast' ); ?></strong></label><br>
            <input type="url" id="novacast_external_thumbnail_url" name="novacast_external_thumbnail_url" value="<?php echo esc_attr( $external_thumbnail_url ); ?>" class="widefat" placeholder="https://exemplo.com/capa.jpg">
            <span class="description"><?php esc_html_e( 'Usada quando o episódio vem de uma sincronização e não há imagem destacada no WordPress.', 'novacast' ); ?></span>
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

        $allowed_sources         = array( 'audio', 'youtube', 'spotify' );
        $source                  = isset( $_POST['novacast_source'] ) ? sanitize_key( wp_unslash( $_POST['novacast_source'] ) ) : 'audio';
        $source                  = in_array( $source, $allowed_sources, true ) ? $source : 'audio';
        $audio_url               = isset( $_POST['novacast_audio_url'] ) ? esc_url_raw( wp_unslash( $_POST['novacast_audio_url'] ) ) : '';
        $youtube_url             = isset( $_POST['novacast_youtube_url'] ) ? esc_url_raw( wp_unslash( $_POST['novacast_youtube_url'] ) ) : '';
        $spotify_url             = isset( $_POST['novacast_spotify_url'] ) ? esc_url_raw( wp_unslash( $_POST['novacast_spotify_url'] ) ) : '';
        $external_id             = isset( $_POST['novacast_external_id'] ) ? sanitize_text_field( wp_unslash( $_POST['novacast_external_id'] ) ) : '';
        $external_thumbnail_url  = isset( $_POST['novacast_external_thumbnail_url'] ) ? esc_url_raw( wp_unslash( $_POST['novacast_external_thumbnail_url'] ) ) : '';
        $duration                = isset( $_POST['novacast_duration'] ) ? sanitize_text_field( wp_unslash( $_POST['novacast_duration'] ) ) : '';
        $active                  = isset( $_POST['novacast_active'] ) ? '1' : '0';

        update_post_meta( $post_id, self::META_SOURCE, $source );
        update_post_meta( $post_id, self::META_AUDIO_URL, $audio_url );
        update_post_meta( $post_id, self::META_YOUTUBE_URL, $youtube_url );
        update_post_meta( $post_id, self::META_SPOTIFY_URL, $spotify_url );
        update_post_meta( $post_id, self::META_EXTERNAL_ID, $external_id );
        update_post_meta( $post_id, self::META_EXTERNAL_THUMBNAIL_URL, $external_thumbnail_url );
        update_post_meta( $post_id, self::META_DURATION, $duration );
        update_post_meta( $post_id, self::META_ACTIVE, $active );
    }
}
