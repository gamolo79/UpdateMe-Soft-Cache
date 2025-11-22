<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UpdateMe_Soft_Cache {

    protected static $instance = null;
    protected $cache_dir;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->cache_dir = WP_CONTENT_DIR . '/cache/updateme-soft-cache/';
        add_action( 'template_redirect', [ $this, 'maybe_start_buffer' ], 0 );
        add_action( 'save_post', [ $this, 'purge_all_on_content_change' ], 10, 3 );
    }

    public function maybe_start_buffer() {
        $enabled = (bool) get_option( 'um_soft_cache_enabled', 1 );
        if ( ! $enabled ) return;

        if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || defined( 'REST_REQUEST' ) ) return;
        if ( is_user_logged_in() ) return;

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ( strtoupper( $method ) !== 'GET' ) return;

        if ( isset( $_GET['preview'] ) ) return;
        if ( is_404() ) return;

        if ( ! apply_filters( 'um_soft_cache_is_cacheable', true ) ) return;

        if ( $this->serve_from_cache() ) exit;

        ob_start( [ $this, 'store_and_output' ] );
    }

    protected function get_cache_key() {
        $scheme = is_ssl() ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? '';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $ua     = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $device = preg_match( '/(Mobile|Android|iPhone)/i', $ua ) ? 'm' : 'd';

        return md5( "{$scheme}://{$host}{$uri}|{$device}" );
    }

    protected function get_cache_file_path() {
        return $this->cache_dir . $this->get_cache_key() . '.html';
    }

    protected function serve_from_cache() {
        $file = $this->get_cache_file_path();
        if ( ! file_exists( $file ) ) return false;

        $default_ttl = 600;
        $ttl = (int) get_option( 'um_soft_cache_ttl', $default_ttl );
        if ( $ttl <= 0 ) $ttl = $default_ttl;

        if ( ( time() - filemtime( $file ) ) > $ttl ) {
            @unlink( $file );
            return false;
        }

        if ( ! headers_sent() ) header( 'X-UpdateMe-Soft-Cache: HIT' );
        readfile( $file );
        return true;
    }

    public function store_and_output( $html ) {
        if ( ! is_dir( $this->cache_dir ) ) wp_mkdir_p( $this->cache_dir );
        @file_put_contents( $this->get_cache_file_path(), $html );

        if ( ! headers_sent() ) header( 'X-UpdateMe-Soft-Cache: MISS' );
        return $html;
    }

    public function purge_all_on_content_change( $post_id, $post, $update ) {
        if ( wp_is_post_revision( $post_id ) ) return;
        $this->purge_all();
    }

    public function purge_all() {
        if ( ! is_dir( $this->cache_dir ) ) return;
        foreach ( glob( $this->cache_dir . '*.html' ) as $file ) {
            @unlink( $file );
        }
    }
}
