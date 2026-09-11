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
 * component registration, rewrite rules, and asset management.
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

		// BuddyPress component integration across all lifecycle hooks.
		add_action( 'bp_include', [ $this, 'register_buddypress_component' ], 6 );
		add_action( 'bp_setup_components', [ $this, 'register_buddypress_component' ], 6 );
		add_action( 'bp_loaded', [ $this, 'register_buddypress_component' ], 6 );
		add_filter( 'bp_active_components', [ $this, 'filter_active_components' ] );

		// Direct nav setup hook to ensure navigation is registered even if BP_Component is bypassed.
		add_action( 'bp_setup_nav', [ $this, 'setup_nav_fallback' ], 10 );

		// WordPress Rewrites & fail-safe URL routing.
		add_action( 'init', [ $this, 'register_rewrite_rules' ], 2 );
		add_filter( 'redirect_canonical', [ $this, 'filter_redirect_canonical' ], 10, 2 );
		add_action( 'template_redirect', [ $this, 'catch_notes_screen' ], 1 );
		add_filter( 'template_include', [ $this, 'filter_template_include_fallback' ], 99 );

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

		if ( ! isset( $bp->notes ) || ! ( $bp->notes instanceof Component ) ) {
			$bp->notes = new Component();
		}

		// Mark component as active in BuddyPress.
		$bp->active_components[ Component::ID ] = 1;

		$slug = get_option( 'bp_usernotes_slug', 'journal' );
		$bp->active_components[ $slug ] = 1;
		$bp->{$slug} = $bp->notes;
	}

	/**
	 * Ensure the notes component is reported as active by BuddyPress.
	 *
	 * @param array $components Active component array.
	 * @return array
	 */
	public function filter_active_components( array $components ): array {
		$components[ Component::ID ] = 1;
		$slug                        = get_option( 'bp_usernotes_slug', 'journal' );
		$components[ $slug ]         = 1;
		return $components;
	}

	/**
	 * Direct fallback to ensure navigation item is registered.
	 *
	 * @return void
	 */
	public function setup_nav_fallback(): void {
		if ( isset( buddypress()->notes ) && method_exists( buddypress()->notes, 'setup_nav' ) ) {
			buddypress()->notes->setup_nav();
		}
	}

	/**
	 * Register WordPress rewrite rules for members notes/journal endpoint.
	 *
	 * @return void
	 */
	public function register_rewrite_rules(): void {
		$members_slug = function_exists( 'bp_get_members_root_slug' ) ? bp_get_members_root_slug() : 'members';
		$slug         = get_option( 'bp_usernotes_slug', 'journal' );
		$slugs        = array_unique( [ $slug, 'journal', 'notes' ] );
		$slug_regex   = implode( '|', array_map( 'preg_quote', $slugs ) );

		// Single note/entry: members/{username}/journal/{id}/
		add_rewrite_rule(
			'^' . $members_slug . '/([^/]+)/(' . $slug_regex . ')/([0-9]+)/?$',
			'index.php?' . $members_slug . '=$matches[1]&bp_action=$matches[3]',
			'top'
		);

		// Sub-actions: members/{username}/journal/(all|new)/
		add_rewrite_rule(
			'^' . $members_slug . '/([^/]+)/(' . $slug_regex . ')/([^/]+)/?$',
			'index.php?' . $members_slug . '=$matches[1]&bp_action=$matches[3]',
			'top'
		);

		// Main component: members/{username}/journal/
		add_rewrite_rule(
			'^' . $members_slug . '/([^/]+)/(' . $slug_regex . ')/?$',
			'index.php?' . $members_slug . '=$matches[1]',
			'top'
		);

		$this->maybe_flush_rewrite_rules();
	}

	/**
	 * Auto-flush rewrite rules if our rule is not yet stored in the database.
	 *
	 * @return void
	 */
	public function maybe_flush_rewrite_rules(): void {
		$rules = get_option( 'rewrite_rules' );
		$slug  = get_option( 'bp_usernotes_slug', 'journal' );

		if ( ! empty( $rules ) && is_array( $rules ) ) {
			$found = false;
			foreach ( array_keys( $rules ) as $pattern ) {
				if ( strpos( $pattern, $slug ) !== false || strpos( $pattern, 'journal' ) !== false ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				flush_rewrite_rules( false );
			}
		}
	}

	/**
	 * Prevent WordPress from 301-redirecting members journal/notes URLs to homepage.
	 *
	 * @param string|bool $redirect_url Redirect URL.
	 * @param string      $requested_url Requested URL.
	 * @return string|false
	 */
	public function filter_redirect_canonical( $redirect_url, string $requested_url ) {
		$slug  = get_option( 'bp_usernotes_slug', 'journal' );
		$slugs = array_unique( [ $slug, 'journal', 'notes' ] );
		$regex = implode( '|', array_map( 'preg_quote', $slugs ) );

		if ( preg_match( '#/members/[^/]+/(' . $regex . ')(/|$)#i', $requested_url ) ) {
			return false; // Prevent canonical 301 redirect.
		}

		return $redirect_url;
	}

	/**
	 * Fail-safe template router catching journal/notes URLs before WordPress errors.
	 *
	 * @return void
	 */
	public function catch_notes_screen(): void {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$slug        = get_option( 'bp_usernotes_slug', 'journal' );
		$slugs       = array_unique( [ $slug, 'journal', 'notes' ] );
		$regex       = implode( '|', array_map( 'preg_quote', $slugs ) );

		if ( ! preg_match( '#/members/([^/]+)/(' . $regex . ')(?:/([^/?]+))?#i', $request_uri, $matches ) ) {
			return;
		}

		global $wp_query;

		$member_username = sanitize_title( $matches[1] );
		$sub_action      = isset( $matches[3] ) ? sanitize_title( $matches[3] ) : '';

		// Verify member user exists.
		$user = get_user_by( 'slug', $member_username );
		if ( ! $user ) {
			$user = get_user_by( 'login', $member_username );
		}

		if ( ! $user ) {
			return;
		}

		// Set BuddyPress displayed user if not already configured.
		if ( ! function_exists( 'bp_is_user' ) || ! bp_is_user() ) {
			$bp = buddypress();
			if ( ! isset( $bp->displayed_user ) ) {
				$bp->displayed_user = new \stdClass();
			}
			$bp->displayed_user->id       = (int) $user->ID;
			$bp->displayed_user->userdata = $user->data;
			$bp->is_single_user           = true;
			$bp->current_component        = $slug;
			$bp->current_action           = ! empty( $sub_action ) ? $sub_action : 'all';
		}

		// Clear 404 flags.
		$wp_query->is_404    = false;
		$wp_query->is_page   = false;
		$wp_query->is_home   = false;
		$wp_query->is_single = false;
		status_header( 200 );

		// Load template if not already dispatched.
		if ( ! has_action( 'bp_template_content' ) && ! did_action( 'bp_template_content' ) ) {
			Component::screen_loader( $sub_action );
		}
	}

	/**
	 * Template include fallback ensuring a 404 template is never served for member journal/notes screens.
	 *
	 * @param string $template Path to template file.
	 * @return string
	 */
	public function filter_template_include_fallback( string $template ): string {
		if ( $this->is_notes_screen() && ( is_404() || empty( $template ) || false !== strpos( $template, '404.php' ) ) ) {
			$located = locate_template( [ 'buddypress.php', 'page.php', 'single.php', 'index.php' ] );
			if ( ! empty( $located ) ) {
				return $located;
			}
		}

		return $template;
	}

	/**
	 * Check if the current screen is a BuddyPress member notes or journal page.
	 *
	 * @return bool
	 */
	public function is_notes_screen(): bool {
		$slug = get_option( 'bp_usernotes_slug', 'journal' );

		if ( function_exists( 'bp_is_current_component' ) ) {
			if ( bp_is_current_component( $slug ) || bp_is_current_component( 'journal' ) || bp_is_current_component( 'notes' ) ) {
				return true;
			}
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		return (bool) preg_match( '#/members/[^/]+/(journal|notes)(/|$)#i', $request_uri );
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
				'slug'              => get_option( 'bp_usernotes_slug', 'journal' ),
				'authorUrl'         => function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $displayed_user_id ) : '',
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
