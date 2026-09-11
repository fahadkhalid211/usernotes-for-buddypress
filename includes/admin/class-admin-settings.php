<?php
/**
 * Admin Settings Controller using WordPress Settings API.
 *
 * @package BP_UserNotes
 */

namespace BP_UserNotes\Admin;

use BP_UserNotes\Component;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin configuration options, sanitization, and settings UI.
 */
class Admin_Settings {

	/**
	 * Settings group name.
	 *
	 * @var string
	 */
	public const OPTION_GROUP = 'bp_usernotes_settings_group';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	public const PAGE_SLUG = 'bp-usernotes-settings';

	/**
	 * Initialize admin hooks and settings registration.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'register_admin_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
		add_filter( 'plugin_action_links_' . BP_USERNOTES_BASENAME, [ __CLASS__, 'add_settings_link' ] );
	}

	/**
	 * Add Settings action link on plugins list page.
	 *
	 * @param array $links Existing plugin links.
	 * @return array
	 */
	public static function add_settings_link( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) ),
			esc_html__( 'Settings', 'usernotes-for-buddypress' )
		);

		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Register the admin menu page under Settings.
	 *
	 * @return void
	 */
	public static function register_admin_menu(): void {
		add_options_page(
			__( 'User Notes & Journal Settings', 'usernotes-for-buddypress' ),
			__( 'User Notes', 'usernotes-for-buddypress' ),
			'manage_options',
			self::PAGE_SLUG,
			[ __CLASS__, 'render_settings_page' ]
		);
	}

	/**
	 * Register settings, sections, and fields using WordPress Settings API.
	 *
	 * @return void
	 */
	public static function register_settings(): void {
		// Section: General Settings.
		add_settings_section(
			'bp_usernotes_section_general',
			__( 'Display & Navigation', 'usernotes-for-buddypress' ),
			[ __CLASS__, 'section_general_callback' ],
			self::PAGE_SLUG
		);

		register_setting(
			self::OPTION_GROUP,
			'bp_usernotes_tab_label',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => __( 'Journal', 'usernotes-for-buddypress' ),
			]
		);

		add_settings_field(
			'bp_usernotes_tab_label',
			__( 'Profile Tab Label', 'usernotes-for-buddypress' ),
			[ __CLASS__, 'render_field_tab_label' ],
			self::PAGE_SLUG,
			'bp_usernotes_section_general'
		);

		register_setting(
			self::OPTION_GROUP,
			'bp_usernotes_slug',
			[
				'type'              => 'string',
				'sanitize_callback' => [ __CLASS__, 'sanitize_slug' ],
				'default'           => 'journal',
			]
		);

		add_settings_field(
			'bp_usernotes_slug',
			__( 'URL Endpoint Slug', 'usernotes-for-buddypress' ),
			[ __CLASS__, 'render_field_slug' ],
			self::PAGE_SLUG,
			'bp_usernotes_section_general'
		);

		register_setting(
			self::OPTION_GROUP,
			'bp_usernotes_per_page',
			[
				'type'              => 'integer',
				'sanitize_callback' => [ __CLASS__, 'sanitize_per_page' ],
				'default'           => 10,
			]
		);

		add_settings_field(
			'bp_usernotes_per_page',
			__( 'Notes Per Page', 'usernotes-for-buddypress' ),
			[ __CLASS__, 'render_field_per_page' ],
			self::PAGE_SLUG,
			'bp_usernotes_section_general'
		);

		// Section: Privacy & Access Control.
		add_settings_section(
			'bp_usernotes_section_privacy',
			__( 'Privacy & Access Control', 'usernotes-for-buddypress' ),
			[ __CLASS__, 'section_privacy_callback' ],
			self::PAGE_SLUG
		);

		register_setting(
			self::OPTION_GROUP,
			'bp_usernotes_enable_public',
			[
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => 1,
			]
		);

		add_settings_field(
			'bp_usernotes_enable_public',
			__( 'Public Notes Sharing', 'usernotes-for-buddypress' ),
			[ __CLASS__, 'render_field_enable_public' ],
			self::PAGE_SLUG,
			'bp_usernotes_section_privacy'
		);

		register_setting(
			self::OPTION_GROUP,
			'bp_usernotes_default_visibility',
			[
				'type'              => 'string',
				'sanitize_callback' => [ __CLASS__, 'sanitize_default_visibility' ],
				'default'           => 'private',
			]
		);

		add_settings_field(
			'bp_usernotes_default_visibility',
			__( 'Default Visibility', 'usernotes-for-buddypress' ),
			[ __CLASS__, 'render_field_default_visibility' ],
			self::PAGE_SLUG,
			'bp_usernotes_section_privacy'
		);

		register_setting(
			self::OPTION_GROUP,
			'bp_usernotes_allowed_roles',
			[
				'type'              => 'array',
				'sanitize_callback' => [ __CLASS__, 'sanitize_roles' ],
				'default'           => [],
			]
		);

		add_settings_field(
			'bp_usernotes_allowed_roles',
			__( 'Role Access Restrictions', 'usernotes-for-buddypress' ),
			[ __CLASS__, 'render_field_allowed_roles' ],
			self::PAGE_SLUG,
			'bp_usernotes_section_privacy'
		);

		// Section: Data & Lifecycle.
		add_settings_section(
			'bp_usernotes_section_lifecycle',
			__( 'Data Hygiene & Uninstallation', 'usernotes-for-buddypress' ),
			[ __CLASS__, 'section_lifecycle_callback' ],
			self::PAGE_SLUG
		);

		register_setting(
			self::OPTION_GROUP,
			'bp_usernotes_delete_on_uninstall',
			[
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => 0,
			]
		);

		add_settings_field(
			'bp_usernotes_delete_on_uninstall',
			__( 'Uninstall Data Cleanup', 'usernotes-for-buddypress' ),
			[ __CLASS__, 'render_field_delete_on_uninstall' ],
			self::PAGE_SLUG,
			'bp_usernotes_section_lifecycle'
		);
	}

	/**
	 * General section description callback.
	 *
	 * @return void
	 */
	public static function section_general_callback(): void {
		echo '<p class="description">' .
			esc_html__( 'Configure how the notes and journal system appears on member profile navigation.', 'usernotes-for-buddypress' ) .
			'</p>';
	}

	/**
	 * Privacy section description callback.
	 *
	 * @return void
	 */
	public static function section_privacy_callback(): void {
		echo '<p class="description">' .
			esc_html__( 'Control privacy defaults and permissions for creating and viewing notes.', 'usernotes-for-buddypress' ) .
			'</p>';
	}

	/**
	 * Lifecycle section description callback.
	 *
	 * @return void
	 */
	public static function section_lifecycle_callback(): void {
		echo '<p class="description">' .
			esc_html__( 'Manage what happens to stored notes and options if the plugin is uninstalled.', 'usernotes-for-buddypress' ) .
			'</p>';
	}

	/**
	 * Render Tab Label field.
	 *
	 * @return void
	 */
	public static function render_field_tab_label(): void {
		$value = get_option( 'bp_usernotes_tab_label', __( 'Journal', 'usernotes-for-buddypress' ) );
		?>
		<input type="text" name="bp_usernotes_tab_label" id="bp_usernotes_tab_label" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Display name shown on the BuddyPress member profile tab (e.g., Journal, Notes, Notebook, Diary).', 'usernotes-for-buddypress' ); ?></p>
		<?php
	}

	/**
	 * Render Slug field.
	 *
	 * @return void
	 */
	public static function render_field_slug(): void {
		$value = get_option( 'bp_usernotes_slug', 'journal' );
		?>
		<code>/members/[username]/</code><input type="text" name="bp_usernotes_slug" id="bp_usernotes_slug" value="<?php echo esc_attr( $value ); ?>" class="small-text" style="width: 130px;" /><code>/</code>
		<p class="description"><?php esc_html_e( 'URL slug for the notes component. Only lowercase letters, numbers, and hyphens.', 'usernotes-for-buddypress' ); ?></p>
		<?php
	}

	/**
	 * Render Notes Per Page field.
	 *
	 * @return void
	 */
	public static function render_field_per_page(): void {
		$value = (int) get_option( 'bp_usernotes_per_page', 10 );
		?>
		<input type="number" name="bp_usernotes_per_page" id="bp_usernotes_per_page" value="<?php echo esc_attr( (string) $value ); ?>" min="3" max="50" step="1" class="small-text" />
		<p class="description"><?php esc_html_e( 'Number of note cards loaded per page or pagination request (recommended: 6 to 15).', 'usernotes-for-buddypress' ); ?></p>
		<?php
	}

	/**
	 * Render Enable Public Notes field.
	 *
	 * @return void
	 */
	public static function render_field_enable_public(): void {
		$value = (bool) get_option( 'bp_usernotes_enable_public', 1 );
		?>
		<label for="bp_usernotes_enable_public">
			<input type="checkbox" name="bp_usernotes_enable_public" id="bp_usernotes_enable_public" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Allow members to toggle individual notes to Public', 'usernotes-for-buddypress' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'If unchecked, public sharing is completely disabled and all notes will remain strictly private to the author.', 'usernotes-for-buddypress' ); ?></p>
		<?php
	}

	/**
	 * Render Default Visibility field.
	 *
	 * @return void
	 */
	public static function render_field_default_visibility(): void {
		$value = get_option( 'bp_usernotes_default_visibility', 'private' );
		?>
		<select name="bp_usernotes_default_visibility" id="bp_usernotes_default_visibility">
			<option value="private" <?php selected( $value, 'private' ); ?>><?php esc_html_e( 'Private (Author Only) - Recommended', 'usernotes-for-buddypress' ); ?></option>
			<option value="public" <?php selected( $value, 'public' ); ?>><?php esc_html_e( 'Public (Visible on Profile)', 'usernotes-for-buddypress' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'The default visibility state when a member begins composing a new note.', 'usernotes-for-buddypress' ); ?></p>
		<?php
	}

	/**
	 * Render Allowed Roles field.
	 *
	 * @return void
	 */
	public static function render_field_allowed_roles(): void {
		$allowed_roles = get_option( 'bp_usernotes_allowed_roles', [] );
		if ( ! is_array( $allowed_roles ) ) {
			$allowed_roles = [];
		}

		$editable_roles = get_editable_roles();
		?>
		<fieldset>
			<p class="description" style="margin-bottom: 8px;">
				<?php esc_html_e( 'Select which user roles are allowed to create notes. If no roles are checked, all registered members can create notes.', 'usernotes-for-buddypress' ); ?>
			</p>
			<?php foreach ( $editable_roles as $role_key => $role_details ) : ?>
				<label style="display: inline-block; margin-right: 15px; margin-bottom: 5px;">
					<input type="checkbox" name="bp_usernotes_allowed_roles[]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $allowed_roles, true ) ); ?> />
					<?php echo esc_html( translate_user_role( $role_details['name'] ) ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<?php
	}

	/**
	 * Render Delete On Uninstall field.
	 *
	 * @return void
	 */
	public static function render_field_delete_on_uninstall(): void {
		$value = (bool) get_option( 'bp_usernotes_delete_on_uninstall', 0 );
		?>
		<label for="bp_usernotes_delete_on_uninstall" style="color: #b32d2e; font-weight: 500;">
			<input type="checkbox" name="bp_usernotes_delete_on_uninstall" id="bp_usernotes_delete_on_uninstall" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Permanently delete all notes, metadata, and settings upon plugin deletion', 'usernotes-for-buddypress' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'WARNING: Checking this box will purge all user notes permanently from the database when the plugin is deleted via the WordPress Admin.', 'usernotes-for-buddypress' ); ?></p>
		<?php
	}

	/**
	 * Sanitize URL slug input.
	 *
	 * @param string $slug Raw slug.
	 * @return string Sanitized slug.
	 */
	public static function sanitize_slug( string $slug ): string {
		$sanitized = sanitize_title( $slug );
		$final     = ! empty( $sanitized ) ? $sanitized : 'journal';
		flush_rewrite_rules( false );
		return $final;
	}

	/**
	 * Sanitize per-page count.
	 *
	 * @param mixed $per_page Raw input.
	 * @return int Sanitized integer within range.
	 */
	public static function sanitize_per_page( $per_page ): int {
		$val = absint( $per_page );
		return min( 50, max( 3, $val ) );
	}

	/**
	 * Sanitize default visibility.
	 *
	 * @param string $visibility Raw input.
	 * @return string Either 'private' or 'public'.
	 */
	public static function sanitize_default_visibility( string $visibility ): string {
		return ( 'public' === $visibility ) ? 'public' : 'private';
	}

	/**
	 * Sanitize roles array.
	 *
	 * @param mixed $roles Array of role slugs.
	 * @return array Sanitized array of existing role slugs.
	 */
	public static function sanitize_roles( $roles ): array {
		if ( ! is_array( $roles ) ) {
			return [];
		}

		$valid_roles = array_keys( get_editable_roles() );
		return array_values( array_intersect( $roles, $valid_roles ) );
	}

	/**
	 * Enqueue admin styles and scripts specifically on our settings page.
	 *
	 * @param string $hook_suffix Admin screen hook suffix.
	 * @return void
	 */
	public static function enqueue_admin_assets( string $hook_suffix ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'bp-usernotes-admin',
			BP_USERNOTES_PLUGIN_URL . 'assets/css/admin.css',
			[],
			BP_USERNOTES_VERSION
		);

		wp_enqueue_script(
			'bp-usernotes-admin',
			BP_USERNOTES_PLUGIN_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			BP_USERNOTES_VERSION,
			true
		);
	}

	/**
	 * Render the full settings page view.
	 *
	 * @return void
	 */
	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$template_path = BP_USERNOTES_PLUGIN_DIR . 'templates/admin-settings-page.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}
