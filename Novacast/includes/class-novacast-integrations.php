<?php
/**
 * Integrações e sincronização com plataformas externas.
 *
 * @package Novacast
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Novacast_Integrations {
    const OPTION_NAME = 'novacast_integrations_settings';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
        add_action( 'admin_post_novacast_save_integrations', array( __CLASS__, 'save_settings' ) );
        add_action( 'admin_post_novacast_sync_youtube', array( __CLASS__, 'sync_youtube' ) );
        add_action( 'admin_post_novacast_sync_spotify', array( __CLASS__, 'sync_spotify' ) );
    }

    public static function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=' . Novacast_Post_Type::POST_TYPE,
            __( 'Sincronização', 'novacast' ),
            __( 'Sincronização', 'novacast' ),
            'manage_options',
            'novacast-sync',
            array( __CLASS__, 'render_settings_page' )
        );
    }

    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = self::get_settings();
        $message  = isset( $_GET['novacast_message'] ) ? sanitize_text_field( wp_unslash( $_GET['novacast_message'] ) ) : '';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Sincronização Novacast', 'novacast' ); ?></h1>

            <?php if ( $message ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="novacast_save_integrations">
                <?php wp_nonce_field( 'novacast_save_integrations' ); ?>

                <h2><?php esc_html_e( 'YouTube', 'novacast' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="novacast_youtube_api_key"><?php esc_html_e( 'YouTube API Key', 'novacast' ); ?></label></th>
                        <td><input type="password" class="regular-text" id="novacast_youtube_api_key" name="youtube_api_key" value="<?php echo esc_attr( $settings['youtube_api_key'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="novacast_youtube_playlist_id"><?php esc_html_e( 'ID da playlist', 'novacast' ); ?></label></th>
                        <td>
                            <input type="text" class="regular-text" id="novacast_youtube_playlist_id" name="youtube_playlist_id" value="<?php echo esc_attr( $settings['youtube_playlist_id'] ); ?>">
                            <p class="description"><?php esc_html_e( 'Use uma playlist pública do YouTube para importar vídeos como episódios.', 'novacast' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Spotify', 'novacast' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="novacast_spotify_client_id"><?php esc_html_e( 'Spotify Client ID', 'novacast' ); ?></label></th>
                        <td><input type="text" class="regular-text" id="novacast_spotify_client_id" name="spotify_client_id" value="<?php echo esc_attr( $settings['spotify_client_id'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="novacast_spotify_client_secret"><?php esc_html_e( 'Spotify Client Secret', 'novacast' ); ?></label></th>
                        <td><input type="password" class="regular-text" id="novacast_spotify_client_secret" name="spotify_client_secret" value="<?php echo esc_attr( $settings['spotify_client_secret'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="novacast_spotify_show_id"><?php esc_html_e( 'ID ou URL do show', 'novacast' ); ?></label></th>
                        <td>
                            <input type="text" class="regular-text" id="novacast_spotify_show_id" name="spotify_show_id" value="<?php echo esc_attr( $settings['spotify_show_id'] ); ?>">
                            <p class="description"><?php esc_html_e( 'Aceita o ID do show ou uma URL como https://open.spotify.com/show/ID.', 'novacast' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Importação', 'novacast' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="novacast_import_limit"><?php esc_html_e( 'Limite por sincronização', 'novacast' ); ?></label></th>
                        <td><input type="number" min="1" max="50" id="novacast_import_limit" name="import_limit" value="<?php echo esc_attr( $settings['import_limit'] ); ?>"></td>
                    </tr>
                </table>

                <?php submit_button( __( 'Salvar configurações', 'novacast' ) ); ?>
            </form>

            <hr>

            <h2><?php esc_html_e( 'Executar sincronização manual', 'novacast' ); ?></h2>
            <p><?php esc_html_e( 'A sincronização cria ou atualiza episódios usando os metadados públicos das plataformas. A reprodução no frontend usa embeds oficiais.', 'novacast' ); ?></p>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:8px;">
                <input type="hidden" name="action" value="novacast_sync_youtube">
                <?php wp_nonce_field( 'novacast_sync_youtube' ); ?>
                <?php submit_button( __( 'Sincronizar YouTube', 'novacast' ), 'secondary', 'submit', false ); ?>
            </form>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
                <input type="hidden" name="action" value="novacast_sync_spotify">
                <?php wp_nonce_field( 'novacast_sync_spotify' ); ?>
                <?php submit_button( __( 'Sincronizar Spotify', 'novacast' ), 'secondary', 'submit', false ); ?>
            </form>
        </div>
        <?php
    }

    public static function get_settings() {
        $defaults = array(
            'youtube_api_key'       => '',
            'youtube_playlist_id'   => '',
            'spotify_client_id'     => '',
            'spotify_client_secret' => '',
            'spotify_show_id'       => '',
            'import_limit'          => 10,
        );

        $settings = get_option( self::OPTION_NAME, array() );
        return wp_parse_args( is_array( $settings ) ? $settings : array(), $defaults );
    }

    public static function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permissão insuficiente.', 'novacast' ) );
        }

        check_admin_referer( 'novacast_save_integrations' );

        $settings = array(
            'youtube_api_key'       => isset( $_POST['youtube_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['youtube_api_key'] ) ) : '',
            'youtube_playlist_id'   => isset( $_POST['youtube_playlist_id'] ) ? sanitize_text_field( wp_unslash( $_POST['youtube_playlist_id'] ) ) : '',
            'spotify_client_id'     => isset( $_POST['spotify_client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['spotify_client_id'] ) ) : '',
            'spotify_client_secret' => isset( $_POST['spotify_client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['spotify_client_secret'] ) ) : '',
            'spotify_show_id'       => isset( $_POST['spotify_show_id'] ) ? sanitize_text_field( wp_unslash( $_POST['spotify_show_id'] ) ) : '',
            'import_limit'          => isset( $_POST['import_limit'] ) ? max( 1, min( 50, absint( $_POST['import_limit'] ) ) ) : 10,
        );

        update_option( self::OPTION_NAME, $settings );
        self::redirect_with_message( __( 'Configurações salvas.', 'novacast' ) );
    }

    public static function sync_youtube() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permissão insuficiente.', 'novacast' ) );
        }

        check_admin_referer( 'novacast_sync_youtube' );

        $settings = self::get_settings();

        if ( empty( $settings['youtube_api_key'] ) || empty( $settings['youtube_playlist_id'] ) ) {
            self::redirect_with_message( __( 'Informe a YouTube API Key e o ID da playlist antes de sincronizar.', 'novacast' ) );
        }

        $url = add_query_arg(
            array(
                'part'       => 'snippet,contentDetails',
                'maxResults' => $settings['import_limit'],
                'playlistId' => $settings['youtube_playlist_id'],
                'key'        => $settings['youtube_api_key'],
            ),
            'https://www.googleapis.com/youtube/v3/playlistItems'
        );

        $response = wp_remote_get( $url, array( 'timeout' => 20 ) );

        if ( is_wp_error( $response ) ) {
            self::redirect_with_message( $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['items'] ) || ! is_array( $body['items'] ) ) {
            self::redirect_with_message( __( 'Nenhum item retornado pelo YouTube.', 'novacast' ) );
        }

        $count = 0;

        foreach ( $body['items'] as $item ) {
            $snippet  = isset( $item['snippet'] ) ? $item['snippet'] : array();
            $video_id = isset( $snippet['resourceId']['videoId'] ) ? sanitize_text_field( $snippet['resourceId']['videoId'] ) : '';

            if ( empty( $video_id ) ) {
                continue;
            }

            $thumbnail = '';
            if ( isset( $snippet['thumbnails']['medium']['url'] ) ) {
                $thumbnail = esc_url_raw( $snippet['thumbnails']['medium']['url'] );
            } elseif ( isset( $snippet['thumbnails']['default']['url'] ) ) {
                $thumbnail = esc_url_raw( $snippet['thumbnails']['default']['url'] );
            }

            self::create_or_update_episode(
                array(
                    'source'        => 'youtube',
                    'external_id'   => $video_id,
                    'title'         => isset( $snippet['title'] ) ? $snippet['title'] : __( 'Episódio do YouTube', 'novacast' ),
                    'description'   => isset( $snippet['description'] ) ? $snippet['description'] : '',
                    'published_at'  => isset( $snippet['publishedAt'] ) ? $snippet['publishedAt'] : '',
                    'youtube_url'   => 'https://www.youtube.com/watch?v=' . rawurlencode( $video_id ),
                    'spotify_url'   => '',
                    'audio_url'     => '',
                    'duration'      => '',
                    'thumbnail_url' => $thumbnail,
                )
            );
            $count++;
        }

        self::redirect_with_message( sprintf( __( 'YouTube sincronizado. %d episódio(s) processado(s).', 'novacast' ), $count ) );
    }

    public static function sync_spotify() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permissão insuficiente.', 'novacast' ) );
        }

        check_admin_referer( 'novacast_sync_spotify' );

        $settings = self::get_settings();
        $show_id  = self::normalize_spotify_show_id( $settings['spotify_show_id'] );

        if ( empty( $settings['spotify_client_id'] ) || empty( $settings['spotify_client_secret'] ) || empty( $show_id ) ) {
            self::redirect_with_message( __( 'Informe Client ID, Client Secret e o ID/URL do show do Spotify antes de sincronizar.', 'novacast' ) );
        }

        $token = self::get_spotify_access_token( $settings['spotify_client_id'], $settings['spotify_client_secret'] );

        if ( is_wp_error( $token ) ) {
            self::redirect_with_message( $token->get_error_message() );
        }

        $url = add_query_arg(
            array(
                'limit'  => $settings['import_limit'],
                'market' => 'BR',
            ),
            'https://api.spotify.com/v1/shows/' . rawurlencode( $show_id ) . '/episodes'
        );

        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 20,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            self::redirect_with_message( $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['items'] ) || ! is_array( $body['items'] ) ) {
            self::redirect_with_message( __( 'Nenhum episódio retornado pelo Spotify.', 'novacast' ) );
        }

        $count = 0;

        foreach ( $body['items'] as $item ) {
            $episode_id = isset( $item['id'] ) ? sanitize_text_field( $item['id'] ) : '';

            if ( empty( $episode_id ) ) {
                continue;
            }

            $thumbnail = '';
            if ( ! empty( $item['images'][0]['url'] ) ) {
                $thumbnail = esc_url_raw( $item['images'][0]['url'] );
            }

            $spotify_url = isset( $item['external_urls']['spotify'] ) ? esc_url_raw( $item['external_urls']['spotify'] ) : 'https://open.spotify.com/episode/' . rawurlencode( $episode_id );
            $duration    = isset( $item['duration_ms'] ) ? self::format_duration_from_ms( absint( $item['duration_ms'] ) ) : '';

            self::create_or_update_episode(
                array(
                    'source'        => 'spotify',
                    'external_id'   => $episode_id,
                    'title'         => isset( $item['name'] ) ? $item['name'] : __( 'Episódio do Spotify', 'novacast' ),
                    'description'   => isset( $item['description'] ) ? $item['description'] : '',
                    'published_at'  => isset( $item['release_date'] ) ? $item['release_date'] : '',
                    'youtube_url'   => '',
                    'spotify_url'   => $spotify_url,
                    'audio_url'     => '',
                    'duration'      => $duration,
                    'thumbnail_url' => $thumbnail,
                )
            );
            $count++;
        }

        self::redirect_with_message( sprintf( __( 'Spotify sincronizado. %d episódio(s) processado(s).', 'novacast' ), $count ) );
    }

    private static function get_spotify_access_token( $client_id, $client_secret ) {
        $response = wp_remote_post(
            'https://accounts.spotify.com/api/token',
            array(
                'timeout' => 20,
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
                ),
                'body'    => array(
                    'grant_type' => 'client_credentials',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['access_token'] ) ) {
            return new WP_Error( 'novacast_spotify_token_error', __( 'Não foi possível obter token do Spotify. Confira as credenciais.', 'novacast' ) );
        }

        return sanitize_text_field( $body['access_token'] );
    }

    private static function create_or_update_episode( $data ) {
        $existing = get_posts(
            array(
                'post_type'      => Novacast_Post_Type::POST_TYPE,
                'post_status'    => 'any',
                'posts_per_page' => 1,
                'meta_query'     => array(
                    'relation' => 'AND',
                    array(
                        'key'   => Novacast_Admin::META_SOURCE,
                        'value' => $data['source'],
                    ),
                    array(
                        'key'   => Novacast_Admin::META_EXTERNAL_ID,
                        'value' => $data['external_id'],
                    ),
                ),
            )
        );

        $post_date = current_time( 'mysql' );
        if ( ! empty( $data['published_at'] ) ) {
            $timestamp = strtotime( $data['published_at'] );
            if ( $timestamp ) {
                $post_date = gmdate( 'Y-m-d H:i:s', $timestamp );
            }
        }

        $post_data = array(
            'post_title'   => sanitize_text_field( $data['title'] ),
            'post_content' => wp_kses_post( $data['description'] ),
            'post_excerpt' => wp_trim_words( wp_strip_all_tags( $data['description'] ), 35 ),
            'post_status'  => 'publish',
            'post_type'    => Novacast_Post_Type::POST_TYPE,
            'post_date'    => $post_date,
        );

        if ( $existing ) {
            $post_data['ID'] = $existing[0]->ID;
            $post_id         = wp_update_post( $post_data, true );
        } else {
            $post_id = wp_insert_post( $post_data, true );
        }

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        update_post_meta( $post_id, Novacast_Admin::META_SOURCE, sanitize_text_field( $data['source'] ) );
        update_post_meta( $post_id, Novacast_Admin::META_EXTERNAL_ID, sanitize_text_field( $data['external_id'] ) );
        update_post_meta( $post_id, Novacast_Admin::META_AUDIO_URL, esc_url_raw( $data['audio_url'] ) );
        update_post_meta( $post_id, Novacast_Admin::META_YOUTUBE_URL, esc_url_raw( $data['youtube_url'] ) );
        update_post_meta( $post_id, Novacast_Admin::META_SPOTIFY_URL, esc_url_raw( $data['spotify_url'] ) );
        update_post_meta( $post_id, Novacast_Admin::META_DURATION, sanitize_text_field( $data['duration'] ) );
        update_post_meta( $post_id, Novacast_Admin::META_ACTIVE, '1' );
        update_post_meta( $post_id, Novacast_Admin::META_EXTERNAL_THUMBNAIL_URL, esc_url_raw( $data['thumbnail_url'] ) );

        return $post_id;
    }

    private static function normalize_spotify_show_id( $value ) {
        $value = trim( (string) $value );

        if ( preg_match( '#open\.spotify\.com/show/([a-zA-Z0-9]+)#', $value, $matches ) ) {
            return $matches[1];
        }

        return sanitize_text_field( $value );
    }

    private static function format_duration_from_ms( $milliseconds ) {
        $seconds = floor( $milliseconds / 1000 );
        $hours   = floor( $seconds / 3600 );
        $minutes = floor( ( $seconds % 3600 ) / 60 );
        $seconds = $seconds % 60;

        if ( $hours > 0 ) {
            return sprintf( '%02d:%02d:%02d', $hours, $minutes, $seconds );
        }

        return sprintf( '%02d:%02d', $minutes, $seconds );
    }

    private static function redirect_with_message( $message ) {
        wp_safe_redirect(
            add_query_arg(
                array(
                    'post_type'        => Novacast_Post_Type::POST_TYPE,
                    'page'             => 'novacast-sync',
                    'novacast_message' => $message,
                ),
                admin_url( 'edit.php' )
            )
        );
        exit;
    }
}
