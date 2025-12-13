<?php
/**
 * Plugin Name: Ultimate SEO Checklist
 * Plugin URI: [Your Plugin URI, e.g., https://yourwebsite.com/ultimate-seo-checklist]
 * Description: A real-time, lightweight on-page SEO checklist for the WordPress editor, focusing on core ranking signals and E-E-A-T.
 * Version: 1.0.0
 * Author: [Your Name/Company Name]
 * Author URI: [Your Website URI]
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ultimate-seo-checklist
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'USC_VERSION', '1.0.0' );
define( 'USC_PATH', plugin_dir_path( __FILE__ ) );
define( 'USC_URL', plugin_dir_url( __FILE__ ) );

// Load core classes
require_once USC_PATH . 'includes/class-usc-scoring-engine.php';


/**
 * 1. Setup Internationalization (i18n)
 */
function usc_load_textdomain() {
    load_plugin_textdomain( 'ultimate-seo-checklist', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'usc_load_textdomain' );


/**
 * 2. Enqueue Assets (Scripts and Styles)
 */
function usc_enqueue_assets( $hook ) {
    global $post;

    // Only enqueue on post/page edit screen
    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
        return;
    }

    // Enqueue styles
    wp_enqueue_style( 'usc-admin-styles', USC_URL . 'assets/css/admin-styles.css', array(), USC_VERSION );

    // Enqueue script
    wp_enqueue_script( 'usc-admin-script', USC_URL . 'assets/js/admin-script.js', array( 'jquery' ), USC_VERSION, true );
    
    // Pass data and security nonces to the JavaScript file
    wp_localize_script( 'usc-admin-script', 'usc_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        // Nonce for security validation in the AJAX call
        'nonce' => wp_create_nonce( 'usc_audit_nonce' ), 
        // Localize strings for the score box title
        'score_title' => esc_html__( 'Overall Score:', 'ultimate-seo-checklist' ),
        'focus_keyword_label' => esc_html__( 'Focus Keyword:', 'ultimate-seo-checklist' ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'usc_enqueue_assets' );


/**
 * 3. Add the Meta Box to the Editor
 */
function usc_add_meta_box() {
    add_meta_box(
        'usc_checklist_box',
        esc_html__( 'SEO Checklist', 'ultimate-seo-checklist' ),
        'usc_render_meta_box',
        array( 'post', 'page' ), // Post types where the meta box appears
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes', 'usc_add_meta_box' );

/**
 * Render the meta box HTML structure
 */
function usc_render_meta_box( $post ) {
    ?>
    <div id="usc-container">
        <div class="usc-input-group">
            <label for="usc_focus_keyword"><?php echo esc_html__( 'Focus Keyword:', 'ultimate-seo-checklist' ); ?></label>
            <input type="text" id="usc_focus_keyword" name="usc_focus_keyword" value="" placeholder="<?php echo esc_attr__( 'Enter your primary keyword here...', 'ultimate-seo-checklist' ); ?>">
        </div>
        
        <div id="usc-score-output" class="usc-score-box">
            <h3 class="usc-score-header"><?php echo esc_html__( 'Overall Score:', 'ultimate-seo-checklist' ); ?> <span id="usc-score-value">--</span>/100</h3>
            <ul id="usc-checklist">
                <li><?php echo esc_html__( 'Enter a keyword to begin.', 'ultimate-seo-checklist' ); ?></li>
            </ul>
        </div>
        
        <button type="button" id="usc-recalculate-btn" class="button button-primary button-large"><?php echo esc_html__( 'Recalculate Score', 'ultimate-seo-checklist' ); ?></button>
    </div>
    <?php
}

/**
 * 4. Register and Execute the AJAX Endpoint
 */
add_action( 'wp_ajax_usc_run_audit', 'USC_Scoring_Engine::run_audit_via_ajax' );
