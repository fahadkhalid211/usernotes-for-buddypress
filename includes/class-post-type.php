<?php
/**
 * Post Type registration and metadata management.
 *
 * @package BP_UserNotes
 */

namespace BP_UserNotes;

defined( 'ABSPATH' ) || exit;

/**
 * Handles registration and metadata schema for the 'bp_note' custom post type.
 */
class Post_Type {

	/**
	 * Custom post type identifier.
	 *
	 * @var string
	 */
	public const POST_TYPE = 'bp_note';

	/**
	 * Meta key for note privacy visibility.
	 *
	 * @var string
	 */
	public const META_VISIBILITY = '_bp_note_visibility';

	/**
	 * Meta key for pinned status.
	 *
	 * @var string
	 */
	public const META_PINNED = '_bp_note_is_pinned';

	/**
	 * Meta key for note accent color.
	 *
	 * @var string
	 */
	public const META_COLOR = '_bp_note_color';

	/**
	 * Initialize post type registration hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_post_type' ], 5 );
		add_action( 'init', [ __CLASS__, 'register_post_meta' ], 10 );
	}

	/**
	 * Register the 'bp_note' custom post type.
	 *
	 * @return void
	 */
	public static function register_post_type(): void {
		$labels = [
			'name'               => _x( 'Notes', 'post type general name', 'usernotes-for-buddypress' ),
			'singular_name'      => _x( 'Note', 'post type singular name', 'usernotes-for-buddypress' ),
			'menu_name'          => _x( 'User Notes', 'admin menu', 'usernotes-for-buddypress' ),
			'name_admin_bar'     => _x( 'Note', 'add new on admin bar', 'usernotes-for-buddypress' ),
			'add_new'            => _x( 'Add New', 'note', 'usernotes-for-buddypress' ),
			'add_new_item'       => __( 'Add New Note', 'usernotes-for-buddypress' ),
			'new_item'           => __( 'New Note', 'usernotes-for-buddypress' ),
			'edit_item'          => __( 'Edit Note', 'usernotes-for-buddypress' ),
			'view_item'          => __( 'View Note', 'usernotes-for-buddypress' ),
			'all_items'          => __( 'All Notes', 'usernotes-for-buddypress' ),
			'search_items'       => __( 'Search Notes', 'usernotes-for-buddypress' ),
			'not_found'          => __( 'No notes found.', 'usernotes-for-buddypress' ),
			'not_found_in_trash' => __( 'No notes found in Trash.', 'usernotes-for-buddypress' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'query_var'           => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'supports'            => [ 'title', 'editor', 'author' ],
			'can_export'          => true,
			'delete_with_user'    => true,
		];

		/**
		 * Filter the arguments used to register the 'bp_note' post type.
		 *
		 * @param array $args Post type registration arguments.
		 */
		$args = apply_filters( 'bp_usernotes_post_type_args', $args );

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register custom post meta fields with schema definitions.
	 *
	 * @return void
	 */
	public static function register_post_meta(): void {
		register_post_meta(
			self::POST_TYPE,
			self::META_VISIBILITY,
			[
				'type'              => 'string',
				'description'       => __( 'Note visibility: private or public', 'usernotes-for-buddypress' ),
				'single'            => true,
				'default'           => 'private',
				'sanitize_callback' => [ Security::class, 'sanitize_visibility' ],
				'auth_callback'     => function( $allowed, $meta_key, $post_id, $user_id ) {
					return Security::can_edit_note( $post_id, $user_id );
				},
				'show_in_rest'      => false,
			]
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_PINNED,
			[
				'type'              => 'boolean',
				'description'       => __( 'Whether note is pinned to top', 'usernotes-for-buddypress' ),
				'single'            => true,
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => function( $allowed, $meta_key, $post_id, $user_id ) {
					return Security::can_edit_note( $post_id, $user_id );
				},
				'show_in_rest'      => false,
			]
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_COLOR,
			[
				'type'              => 'string',
				'description'       => __( 'Note accent color hex code', 'usernotes-for-buddypress' ),
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => [ Security::class, 'sanitize_color' ],
				'auth_callback'     => function( $allowed, $meta_key, $post_id, $user_id ) {
					return Security::can_edit_note( $post_id, $user_id );
				},
				'show_in_rest'      => false,
			]
		);
	}
}
