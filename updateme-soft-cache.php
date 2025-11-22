<?php
/**
 * Plugin Name: UpdateMe Soft Cache
 * Description: Caché de página sencilla y no agresiva para mejorar tiempos de carga sin romper el diseño.
 * Version: 0.1.0
 * Author: Horizonte Media Group
 * Text Domain: updateme-soft-cache
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'UM_SOFT_CACHE_VERSION', '0.1.0' );
define( 'UM_SOFT_CACHE_PATH', plugin_dir_path( __FILE__ ) );
define( 'UM_SOFT_CACHE_URL', plugin_dir_url( __FILE__ ) );

require_once UM_SOFT_CACHE_PATH . 'includes/class-updateme-soft-cache.php';
require_once UM_SOFT_CACHE_PATH . 'includes/class-updateme-soft-cache-admin.php';

function um_soft_cache_init() {
    UpdateMe_Soft_Cache::instance();
    if ( is_admin() ) {
        UpdateMe_Soft_Cache_Admin::instance();
    }
}
add_action( 'plugins_loaded', 'um_soft_cache_init' );
