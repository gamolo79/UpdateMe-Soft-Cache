<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UpdateMe_Soft_Cache_Admin {

    protected static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_settings_page() {
        add_options_page(
            'UpdateMe Soft Cache',
            'UpdateMe Soft Cache',
            'manage_options',
            'updateme-soft-cache',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'um_soft_cache_options', 'um_soft_cache_enabled', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => 1
        ]);

        register_setting( 'um_soft_cache_options', 'um_soft_cache_ttl', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 600
        ]);
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        if ( isset( $_POST['um_soft_cache_purge_all'] ) ) {
            check_admin_referer( 'um_soft_cache_purge_all_action', 'um_soft_cache_purge_all_nonce' );
            UpdateMe_Soft_Cache::instance()->purge_all();
            echo '<div class="notice notice-success"><p>Caché borrado correctamente.</p></div>';
        }

        if ( isset( $_POST['um_soft_cache_purge_url'] ) ) {
            check_admin_referer( 'um_soft_cache_purge_url_action', 'um_soft_cache_purge_url_nonce' );
            $url = isset( $_POST['um_soft_cache_url'] ) ? esc_url_raw( wp_unslash( $_POST['um_soft_cache_url'] ) ) : '';

            if ( empty( $url ) ) {
                echo '<div class="notice notice-error"><p>Introduce una URL para purgar.</p></div>';
            } else {
                $purged = UpdateMe_Soft_Cache::instance()->purge_url( $url );
                if ( $purged ) {
                    echo '<div class="notice notice-success"><p>La página se purgó del caché.</p></div>';
                } else {
                    echo '<div class="notice notice-warning"><p>No había una copia en caché para esa URL.</p></div>';
                }
            }
        }

        $enabled = (bool) get_option( 'um_soft_cache_enabled', 1 );
        $ttl     = (int) get_option( 'um_soft_cache_ttl', 600 );
        $stats   = UpdateMe_Soft_Cache::instance()->get_cache_stats();
        ?>
        <div class="wrap">
            <h1>UpdateMe Soft Cache</h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'um_soft_cache_options' ); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="um_soft_cache_enabled">Activar caché</label></th>
                        <td>
                            <input type="checkbox" name="um_soft_cache_enabled" id="um_soft_cache_enabled"
                                   value="1" <?php checked( $enabled ); ?> />
                        </td>
                    </tr>

                    <tr>
                        <th><label for="um_soft_cache_ttl">TTL (segundos)</label></th>
                        <td>
                            <input type="number" name="um_soft_cache_ttl" id="um_soft_cache_ttl"
                                   value="<?php echo esc_attr( $ttl ); ?>" min="60" />
                            <p class="description">Tiempo de vida del HTML en caché.</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr>

            <h2>Estado rápido</h2>
            <p><strong>Páginas en caché:</strong> <?php echo intval( $stats['count'] ); ?></p>
            <p><strong>Espacio usado:</strong> <?php echo size_format( $stats['size'] ); ?></p>

            <hr>

            <h2>Purgar caché</h2>
            <form method="post">
                <?php wp_nonce_field( 'um_soft_cache_purge_all_action', 'um_soft_cache_purge_all_nonce' ); ?>
                <?php submit_button( 'Purgar todo el caché', 'delete', 'um_soft_cache_purge_all' ); ?>
            </form>

            <h3>Purgar una página concreta</h3>
            <p>Útil si acabas de actualizar una noticia o landing y quieres que los lectores vean los cambios al instante.</p>
            <form method="post">
                <?php wp_nonce_field( 'um_soft_cache_purge_url_action', 'um_soft_cache_purge_url_nonce' ); ?>
                <input type="url" name="um_soft_cache_url" class="regular-text" placeholder="https://tusitio.com/mi-noticia" required />
                <?php submit_button( 'Purgar esta URL', 'secondary', 'um_soft_cache_purge_url', false ); ?>
            </form>
        </div>
        <?php
    }
}
