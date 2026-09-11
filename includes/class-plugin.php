<?php
/**
 * Main Plugin Orchestrator (Singleton).
 *
 * @package BP_UserNotes
 */

namespace BP_UserNotes;

defined( 'ABSPATH' ) || exit;

/**
 * Primary plugin class responsible for lifecycle hooks, dependency checks,
 * component registration, and asset management.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Retrieve singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Setup core WordPress and BuddyPress lifecycle hooks.
	 *
	 * @return void
	 */
	private function setup_hooks(): void {
		// Translation loading.
		add_action( 'init', [ $this, 'load_textdomain' ] );

		// Core post type & query layer.
		Post_Type::init();
		Ajax_Handler::init();
		Privacy::init();

		// Admin management.
		if ( is_admin() ) {
			Admin\Admin_Settings::init();
		}

		// BuddyPress component integration.
		add_action( 'bp_loaded', [ $this, 'register_buddypress_component' ], 12 );

		// Asset loading on BuddyPress profile.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
	}

	/**
	 * Load plugin internationalization textdomain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'usernotes-for-buddypress',
			false,
			dirname( BP_USERNOTES_BASENAME ) . '/languages'
		);
	}

	/**
	 * Register the User Notes component into BuddyPress master instance.
	 *
	 * @return void
	 */
	public function register_buddypress_component(): void {
		if ( ! function_exists( 'buddypress' ) ) {
			return;
		}

		$bp = buddypress();
		$bp->notes = new Component();
	}

	/**
	 * Check if the current screen is a BuddyPress member notes page.
	 *
	 * @return bool
	 */
	public function is_notes_screen(): bool {
		if ( ! function_exists( 'bp_is_user' ) || ! bp_is_user() ) {
			return false;
		}

		$slug = get_option( 'bp_usernotes_slug', Component::ID );
		return bp_is_current_component( $slug );
	}

	/**
	 * Enqueue modern frontend CSS and JavaScript on notes screens.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets(): void {
		if ( ! $this->is_notes_screen() ) {
			return;
		}

		// Modern frontend styles.
		wp_enqueue_style(
			'bp-usernotes-frontend',
			BP_USERNOTES_PLUGIN_URL . 'assets/css/frontend.css',
			[],
			BP_USERNOTES_VERSION
		);

		// Modern frontend interactivity.
		wp_enqueue_script(
			'bp-usernotes-frontend',
			BP_USERNOTES_PLUGIN_URL . 'assets/js/frontend.js',
			[],
			BP_USERNOTES_VERSION,
			true
		);

		$displayed_user_id = bp_displayed_user_id();
		$current_user_id   = get_current_user_id();
		$is_owner          = ( $displayed_user_id === $current_user_id && $current_user_id > 0 );
		$can_create        = Security::can_create_notes( $displayed_user_id );
		$public_enabled    = (bool) get_option( 'bp_usernotes_enable_public', 1 );
		$default_vis       = get_option( 'bp_usernotes_default_visibility', 'private' );

		// Localized state and translation strings.
		wp_localize_script(
			'bp-usernotes-frontend',
			'bpUserNotes',
			[
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => Security::create_nonce(),
				'displayedUserId'   => $displayed_user_id,
				'currentUserId'     => $current_user_id,
				'isOwner'           => $is_owner,
				'isAdmin'           => current_user_can( 'manage_options' ),
				'canCreate'         => $can_create,
				'publicEnabled'     => $public_enabled,
				'defaultVisibility' => $default_vis,
				'perPage'           => (int) get_option( 'bp_usernotes_per_page', 10 ),
				'i18n'              => [
					'confirmDelete'     => __( 'Are you sure you want to delete this note? This action cannot be undone.', 'usernotes-for-buddypress' ),
					'deleting'          => __( 'Deleting...', 'usernotes-for-buddypress' ),
					'saving'            => __( 'Saving...', 'usernotes-for-buddypress' ),
					'save'              => __( 'Save Note', 'usernotes-for-buddypress' ),
					'edit'              => __( 'Edit Note', 'usernotes-for-buddypress' ),
					'create'            => __( 'Create Note', 'usernotes-for-buddypress' ),
					'networkError'      => __( 'A network error occurred. Please check your connection and try again.', 'usernotes-for-buddypress' ),
					'noteSaved'         => __( 'Note saved successfully!', 'usernotes-for-buddypress' ),
					'emptyFields'       => __( 'Please enter a title or write some content.', 'usernotes-for-buddypress' ),
					'copied'            => __( 'Link copied to clipboard!', 'usernotes-for-buddypress' ),
					'public'            => __( 'Public', 'usernotes-for-buddypress' ),
					'private'           => __( 'Private', 'usernotes-for-buddypress' ),
					'makePublic'        => __( 'Make Public', 'usernotes-for-buddypress' ),
					'makePrivate'       => __( 'Make Private', 'usernotes-for-buddypress' ),
					'pin'               => __( 'Pin Note', 'usernotes-for-buddypress' ),
					'unpin'             => __( 'Unpin Note', 'usernotes-for-buddypress' ),
					'noNotesFound'      => __( 'No notes found matching your search.', 'usernotes-for-buddypress' ),
				],
			]
		);
	}
}
