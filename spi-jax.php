<?php
/**
 * Plugin Name: SPI JAX
 * Plugin URI: https://example.com/spi-jax
 * Description: A plugin for SPI JAX functionality.
 * Version: 1.0.0
 * Author: Masud Rana
 * Author URI: https://dev-masud-rana.netlify.app/
 * License: GPL2
 * Text Domain: spi-jax
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class SPI_JAX {
    public function __construct() {
        add_action('init', [$this, 'init']);
    }

    public function init() {
        // Register Custom Post Type
        $this->newsletter_custom_post_type();

        // Enqueue CSS and JS
        add_action('wp_enqueue_scripts', [$this, 'plugin_assets_css_js']);

        // Register Shortcode
        add_shortcode('newsletter', [$this, 'newsletter_shortcode_cb']);

        // AJAX Handlers
        add_action('wp_ajax_subscribe_newsletter', [$this, 'handle_subscribe_newsletter']);
        add_action('wp_ajax_nopriv_subscribe_newsletter', [$this, 'handle_subscribe_newsletter']);
    }

    public function handle_subscribe_newsletter(){
        if(!wp_verify_nonce($_POST['newsletter_nonce_field'], 'newsletter_nonce')){
            wp_send_json_error('Security check failed');
        }else{
            // wp_send_json_success("welcome");
            $email_address = isset($_POST['newsletter_email']) ? sanitize_email($_POST['newsletter_email']) : '';

            // Email Validation
            if(empty($email_address)){
                wp_send_json_error('Email address is required');
            }

            // Validate email format
            if(!is_email($email_address)){
                wp_send_json_error('Invalid email address');
            }

            $ip_address = $this->get_user_id();

            $post_data = [
                'post_title'   => "Newsletter Subscription: " . wp_strip_all_tags($email_address),
                'post_content' => "Email: " . $email_address . "\nIP Address: " . $ip_address . "\n Time: " . current_time('mysql'),
                'post_type'    => 'newsletter',
                'post_status'  => 'publish',
            ];

            $post_id = wp_insert_post($post_data);

            wp_send_json_success("Successfully subscribed");
        }
    }
    
    public function newsletter_shortcode_cb($attrs, $content=null){
        $attributes = shortcode_atts([
            'newsletter-title' => 'Subscribe for Updates'
        ], $attrs);

        ob_start();
        ?>  
        <div class="card" role="main" aria-labelledby="title">
            <h1 id="title"><?php echo esc_html($attributes['newsletter-title']); ?></h1>
            <span id="news-message"></span>
            <form action="#" method="post" id="newsletter-form">
                <div class="field">
                    <label for="email">Email Address</label>
                    <input id="newsletter-email" name="newsletter_email" require type="email" inputmode="email" autocomplete="email" placeholder="name@example.com" required aria-describedby="help" />
                </div>
                <div class="actions">
                    <button type="submit">Subscribe</button>
                </div>
                <p id="help" class="note">We only use your email for the newsletter.</p>
            </form>
        </div>
        <?php
        $newsletter_form = ob_get_clean();

        return $newsletter_form;
    }

    public function plugin_assets_css_js(){
        // Enqueue CSS
        wp_enqueue_style( 'spi-jax-style', plugin_dir_url(__FILE__) . 'assets/style.css', 'all', '1.0.0' );

        // Enqueue JS
        wp_enqueue_script( 'spi-jax-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0',  true );

        // Localize Script
        wp_localize_script( 'spi-jax-script', 'ajax_demo', [
            'ajax_url'  => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('newsletter_nonce')
        ]);
    }

    private function newsletter_custom_post_type() {
        $labels = array(
            'name'               => __( 'Newsletters', 'spi-jax' ),
            'singular_name'      => __( 'Newsletter', 'spi-jax' ),
            'menu_name'          => __( 'Newsletters', 'spi-jax' ),
            'name_admin_bar'     => __( 'Newsletter', 'spi-jax' ),
            'add_new'            => __( 'Add New', 'spi-jax' ),
            'add_new_item'       => __( 'Add New Newsletter', 'spi-jax' ),
            'new_item'           => __( 'New Newsletter', 'spi-jax' ),
            'edit_item'          => __( 'Edit Newsletter', 'spi-jax' ),
            'view_item'          => __( 'View Newsletter', 'spi-jax' ),
            'all_items'          => __( 'All Newsletters', 'spi-jax' ),
            'search_items'       => __( 'Search Newsletters', 'spi-jax' ),
            'parent_item_colon'  => __( 'Parent Newsletters:', 'spi-jax' ),
            'not_found'          => __( 'No newsletters found.', 'spi-jax' ),
            'not_found_in_trash' => __( 'No newsletters found in Trash.', 'spi-jax' )
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'newsletter' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-email',
            'supports'           => array( 'title', 'editor' ),
        );

        register_post_type( 'newsletter', $args );
    }

    public function get_user_id() {
        $ip_keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'UNKNOWN';
    }
}

new SPI_JAX();