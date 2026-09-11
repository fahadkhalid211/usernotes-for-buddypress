<?php
/**
 * BuddyPress Component for User Notes & Journal.
 *
 * @package BP_UserNotes
 */

namespace BP_UserNotes;

defined( 'ABSPATH' ) || exit;

/**
 * Extends the official BuddyPress component framework to register profile navigation,
 * sub-navigation, rewrite endpoints, and screen loaders.
 */
class Component extends \BP_Component {

	/**
	 * Component unique identifier.
	 *
	 * @var string
	 */
	public const ID = 'notes';

	/**
	 * Current active sub-screen identifier.
	 *
	 * @var string
	 */
	public static string $current_screen = 'all';

	/**
	 * Constructor for User Notes BuddyPress Component.
	 */
	public function __construct() {
		$name = get_option( 'bp_usernotes_tab_label', __( 'Notes', 'usernotes-for-buddypress' ) );

		parent::__construct(
			self::ID,
			$name,
			BP_USERNOTES_PLUGIN_DIR
		);
	}

	/**
	 * Register component globals such as slug and search string.
	 *
	 * @param array $args Optional global arguments.
	 * @return void
	 */
	public function setup_globals( $args = [] ): void {
		$slug = get_option( 'bp_usernotes_slug', self::ID );
		$slug = sanitize_title( $slug ? $slug : self::ID );

		parent::setup_globals(
			[
				'slug'                  => $slug,
				'root_slug'             => isset( buddypress()->pages->{$slug}->slug ) ? buddypress()->pages->{$slug}->slug : $slug,
				'has_directory'         => false,
				'search_string'         => __( 'Search Notes...', 'usernotes-for-buddypress' ),
				'notification_callback' => '',
			]
		);
	}

	/**
	 * Setup BuddyPress profile navigation and sub-navigation tabs.
	 *
	 * @param array $main_nav Optional main navigation parameters.
	 * @param array $sub_nav  Optional sub navigation parameters.
	 * @return void
	 */
	public function setup_nav( $main_nav = [], $sub_nav = [] ): void {
		if ( ! bp_is_user() ) {
			return;
		}

		$displayed_user_id = bp_displayed_user_id();
		$is_my_profile     = bp_is_my_profile();
		$slug              = $this->slug;
		$user_domain       = bp_displayed_user_domain();
		$component_link    = trailingslashit( $user_domain . $slug );

		// Visibility check: If viewing another member's profile and public notes are disabled, hide tab completely.
		$public_enabled = (bool) get_option( 'bp_usernotes_enable_public', 1 );
		if ( ! $is_my_profile && ! $public_enabled && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tab_label = get_option( 'bp_usernotes_tab_label', __( 'Notes', 'usernotes-for-buddypress' ) );

		// Main navigation item on member profile.
		$main_nav_item = [
			'name'                    => esc_html( $tab_label ),
			'slug'                    => $slug,
			'position'                => 75,
			'screen_function'         => [ __CLASS__, 'screen_loader' ],
			'default_subnav_slug'     => 'all',
			'show_for_displayed_user' => true,
			'item_css_id'             => 'bp-usernotes-nav',
		];

		// Sub-nav: All Notes / My Notes.
		$sub_nav[] = [
			'name'            => $is_my_profile ? __( 'My Notes', 'usernotes-for-buddypress' ) : __( 'Public Notes', 'usernotes-for-buddypress' ),
			'slug'            => 'all',
			'parent_url'      => $component_link,
			'parent_slug'     => $slug,
			'screen_function' => [ __CLASS__, 'screen_loader' ],
			'position'        => 10,
			'user_has_access' => true,
		];

		// Sub-nav: Add Note (only available to profile owner or admins).
		if ( $is_my_profile || current_user_can( 'manage_options' ) ) {
			$sub_nav[] = [
				'name'            => __( 'Add Note', 'usernotes-for-buddypress' ),
				'slug'            => 'new',
				'parent_url'      => $component_link,
				'parent_slug'     => $slug,
				'screen_function' => [ __CLASS__, 'screen_loader' ],
				'position'        => 20,
				'user_has_access' => Security::can_create_notes( $displayed_user_id ),
			];
		}

		parent::setup_nav( $main_nav_item, $sub_nav );
	}

	/**
	 * Setup BuddyPress admin bar items when logged in.
	 *
	 * @param array $wp_admin_nav Admin bar menu arguments.
	 * @return void
	 */
	public function setup_admin_bar( $wp_admin_nav = [] ): void {
		if ( ! is_user_logged_in() || ! bp_use_wp_admin_bar() ) {
			return;
		}

		$user_id     = bp_loggedin_user_id();
		$user_domain = bp_members_get_user_url( $user_id );
		$notes_link  = trailingslashit( $user_domain . $this->slug );
		$tab_label   = get_option( 'bp_usernotes_tab_label', __( 'Notes', 'usernotes-for-buddypress' ) );

		$wp_admin_nav[] = [
			'parent' => buddypress()->my_account_menu_id,
			'id'     => 'my-account-' . self::ID,
			'title'  => esc_html( $tab_label ),
			'href'   => esc_url( $notes_link ),
		];

		$wp_admin_nav[] = [
			'parent' => 'my-account-' . self::ID,
			'id'     => 'my-account-' . self::ID . '-all',
			'title'  => __( 'All Notes', 'usernotes-for-buddypress' ),
			'href'   => esc_url( $notes_link ),
		];

		if ( Security::can_create_notes( $user_id ) ) {
			$wp_admin_nav[] = [
				'parent' => 'my-account-' . self::ID,
				'id'     => 'my-account-' . self::ID . '-new',
				'title'  => __( 'Add Note', 'usernotes-for-buddypress' ),
				'href'   => esc_url( trailingslashit( $notes_link . 'new' ) ),
			];
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Route and handle BuddyPress screen actions.
	 *
	 * @return void
	 */
	public static function screen_loader(): void {
		$action_var = bp_action_variable( 0 );

		// Check if viewing an individual note by ID.
		if ( ! empty( $action_var ) && is_numeric( $action_var ) ) {
			self::$current_screen = 'single';
			add_action( 'bp_template_title', [ __CLASS__, 'screen_title_single' ] );
			add_action( 'bp_template_content', [ __CLASS__, 'render_single_note' ] );
		} elseif ( 'new' === bp_current_action() || 'new' === $action_var ) {
			self::$current_screen = 'new';
			add_action( 'bp_template_title', [ __CLASS__, 'screen_title_new' ] );
			add_action( 'bp_template_content', [ __CLASS__, 'render_new_note' ] );
		} else {
			self::$current_screen = 'all';
			add_action( 'bp_template_title', [ __CLASS__, 'screen_title_list' ] );
			add_action( 'bp_template_content', [ __CLASS__, 'render_notes_list' ] );
		}

		/**
		 * Filter template file to load for member plugins template.
		 *
		 * @param string $template Template path name.
		 */
		$template = apply_filters( 'bp_usernotes_member_template', 'members/single/plugins' );
		bp_core_load_template( $template );
	}

	/**
	 * Title callback for notes listing screen.
	 *
	 * @return void
	 */
	public static function screen_title_list(): void {
		if ( bp_is_my_profile() ) {
			esc_html_e( 'My Personal Notes & Journal', 'usernotes-for-buddypress' );
		} else {
			printf(
				/* translators: %s: Display name of the profile member */
				esc_html__( '%s&#8217;s Public Notes', 'usernotes-for-buddypress' ),
				esc_html( bp_get_displayed_user_display_name() )
			);
		}
	}

	/**
	 * Title callback for new note screen.
	 *
	 * @return void
	 */
	public static function screen_title_new(): void {
		esc_html_e( 'Create New Note', 'usernotes-for-buddypress' );
	}

	/**
	 * Title callback for single note screen.
	 *
	 * @return void
	 */
	public static function screen_title_single(): void {
		$note_id = absint( bp_action_variable( 0 ) );
		$note    = get_post( $note_id );

		if ( $note && Post_Type::POST_TYPE === $note->post_type ) {
			echo esc_html( get_the_title( $note ) );
		} else {
			esc_html_e( 'View Note', 'usernotes-for-buddypress' );
		}
	}

	/**
	 * Render notes list screen content.
	 *
	 * @return void
	 */
	public static function render_notes_list(): void {
		self::load_template( 'notes-container.php' );
	}

	/**
	 * Render create new note screen content.
	 *
	 * @return void
	 */
	public static function render_new_note(): void {
		if ( ! bp_is_my_profile() && ! current_user_can( 'manage_options' ) ) {
			echo '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>' .
				esc_html__( 'You do not have permission to create notes on this profile.', 'usernotes-for-buddypress' ) .
				'</p></div>';
			return;
		}

		self::load_template( 'notes-container.php', [ 'open_new' => true ] );
	}

	/**
	 * Render single note screen content.
	 *
	 * @return void
	 */
	public static function render_single_note(): void {
		$note_id = absint( bp_action_variable( 0 ) );
		$viewer  = get_current_user_id();

		if ( ! Security::can_view_note( $note_id, $viewer ) ) {
			echo '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>' .
				esc_html__( 'This note is private or does not exist.', 'usernotes-for-buddypress' ) .
				'</p></div>';
			return;
		}

		$note_data = Query::get_note( $note_id, $viewer );

		self::load_template( 'single-note.php', [ 'note' => $note_data ] );
	}

	/**
	 * Template locator and loader supporting theme overrides.
	 *
	 * Allows themes to override templates by placing them in:
	 * {theme}/buddypress/usernotes/{template_name} or {theme}/usernotes/{template_name}.
	 *
	 * @param string $template_name Template filename.
	 * @param array  $args          Variables to extract for the template.
	 * @return void
	 */
	public static function load_template( string $template_name, array $args = [] ): void {
		$located = locate_template(
			[
				'buddypress/usernotes/' . $template_name,
				'usernotes/' . $template_name,
			]
		);

		if ( ! $located ) {
			$located = BP_USERNOTES_PLUGIN_DIR . 'templates/' . $template_name;
		}

		if ( file_exists( $located ) ) {
			if ( ! empty( $args ) ) {
				extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			}
			include $located;
		}
	}
}
