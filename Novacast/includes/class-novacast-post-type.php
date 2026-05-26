<?php
/**
 * Registro do tipo de post de episódios.
 *
 * @package Novacast
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Novacast_Post_Type {
    const POST_TYPE = 'novacast_episode';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register' ) );
    }

    public static function register() {
        $labels = array(
            'name'                  => __( 'Episódios', 'novacast' ),
            'singular_name'         => __( 'Episódio', 'novacast' ),
            'menu_name'             => __( 'Novacast', 'novacast' ),
            'name_admin_bar'        => __( 'Episódio', 'novacast' ),
            'add_new'               => __( 'Adicionar novo', 'novacast' ),
            'add_new_item'          => __( 'Adicionar novo episódio', 'novacast' ),
            'new_item'              => __( 'Novo episódio', 'novacast' ),
            'edit_item'             => __( 'Editar episódio', 'novacast' ),
            'view_item'             => __( 'Ver episódio', 'novacast' ),
            'all_items'             => __( 'Todos os episódios', 'novacast' ),
            'search_items'          => __( 'Buscar episódios', 'novacast' ),
            'not_found'             => __( 'Nenhum episódio encontrado.', 'novacast' ),
            'not_found_in_trash'    => __( 'Nenhum episódio encontrado na lixeira.', 'novacast' ),
            'featured_image'        => __( 'Capa do episódio', 'novacast' ),
            'set_featured_image'    => __( 'Definir capa', 'novacast' ),
            'remove_featured_image' => __( 'Remover capa', 'novacast' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_icon'          => 'dashicons-microphone',
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
            'has_archive'        => true,
            'rewrite'            => array( 'slug' => 'podcasts' ),
            'show_in_rest'       => true,
        );

        register_post_type( self::POST_TYPE, $args );
    }
}
