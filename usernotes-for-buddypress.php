<?php
/**
 * Plugin Name:       User Notes for BuddyPress
 * Plugin URI:        https://wordpress.org/plugins/usernotes-for-buddypress/
 * Description:       A modern, private-by-default personal notes and journaling system for BuddyPress members.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Fahad Khalid
 * Author URI:        https://profiles.wordpress.org/fahadkhalid211/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       usernotes-for-buddypress
 * Domain Path:       /languages
 *
 * @package BP_UserNotes
 */

defined( 'ABSPATH' ) || exit;

// Plugin core constants.
define( 'BP_USERNOTES_VERSION', '1.0.0' );
define( 'BP_USERNOTES_FILE', __FILE__ );
define( 'BP_USERNOTES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BP_USERNOTES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BP_USERNOTES_BASENAME', plugin_basename( __FILE__ ) );

// Load class autoloader.
require_once BP_USERNOTES_PLUGIN_DIR . 'includes/class-autoloader.php';
\BP_UserNotes\Autoloader::register( BP_USERNOTES_PLUGIN_DIR . 'includes' );

/**
 * BuddyPress dependency check and initialization routine.
 *
 * @return void
 */
function bp_usernotes_init(): void {
	// Verify BuddyPress or BuddyBoss Platform availability.
	if ( ! function_exists( 'buddypress' ) && ! class_exists( 'BuddyPress' ) && ! class_exists( 'BuddyBoss_Platform' ) ) {
		add_action( 'admin_notices', 'bp_usernotes_buddypress_missing_notice' );
		return;
	}

	// Bootstrap the plugin singleton.
	\BP_UserNotes\Plugin::instance();
}
add_action( 'plugins_loaded', 'bp_usernotes_init', 10 );

/**
 * Display an admin notice when BuddyPress is not active.
 *
 * @return void
 */
function bp_usernotes_buddypress_missing_notice(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<strong><?php esc_html_e( 'User Notes for BuddyPress', 'usernotes-for-buddypress' ); ?></strong>:
			<?php esc_html_e( 'This plugin requires BuddyPress (or BuddyBoss Platform) to be installed and activated.', 'usernotes-for-buddypress' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Plugin activation routine.
 *
 * @return void
 */
function bp_usernotes_activate(): void {
	// Set default options if not already configured.
	if ( false === get_option( 'bp_usernotes_enable_public' ) ) {
		add_option( 'bp_usernotes_enable_public', 1 );
	}

	if ( false === get_option( 'bp_usernotes_default_visibility' ) ) {
		add_option( 'bp_usernotes_default_visibility', 'private' );
	}

	if ( false === get_option( 'bp_usernotes_tab_label' ) ) {
		add_option( 'bp_usernotes_tab_label', __( 'Journal', 'usernotes-for-buddypress' ) );
	}

	if ( false === get_option( 'bp_usernotes_slug' ) ) {
		add_option( 'bp_usernotes_slug', 'journal' );
	}

	if ( false === get_option( 'bp_usernotes_per_page' ) ) {
		add_option( 'bp_usernotes_per_page', 10 );
	}

	// Register post type on activation so rewrite rules can be flushed cleanly.
	\BP_UserNotes\Post_Type::register_post_type();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'bp_usernotes_activate' );

/**
 * Plugin deactivation routine.
 *
 * @return void
 */
function bp_usernotes_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'bp_usernotes_deactivate' );
