<?php
/**
 * Plugin Name: Novacast
 * Plugin URI: https://github.com/henrkecrz/novacast
 * Description: Gerencie episódios de podcast e exiba players no frontend com shortcodes.
 * Version: 0.4.0
 * Author: Henrique Vasconcelos
 * Text Domain: novacast
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'NOVACAST_VERSION', '0.4.0' );
define( 'NOVACAST_PLUGIN_FILE', __FILE__ );
define( 'NOVACAST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NOVACAST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once NOVACAST_PLUGIN_DIR . 'includes/class-novacast-post-type.php';
require_once NOVACAST_PLUGIN_DIR . 'includes/class-novacast-admin.php';
require_once NOVACAST_PLUGIN_DIR . 'includes/class-novacast-frontend.php';
require_once NOVACAST_PLUGIN_DIR . 'includes/class-novacast-integrations.php';

/**
 * Inicializa o plugin.
 */
function novacast_bootstrap() {
    Novacast_Post_Type::init();
    Novacast_Admin::init();
    Novacast_Frontend::init();
    Novacast_Integrations::init();
}
add_action( 'plugins_loaded', 'novacast_bootstrap' );

/**
 * Garante que as URLs do Custom Post Type funcionem após ativação.
 */
function novacast_activate() {
    Novacast_Post_Type::register();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'novacast_activate' );

/**
 * Limpa regras de URL na desativação.
 */
function novacast_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'novacast_deactivate' );
