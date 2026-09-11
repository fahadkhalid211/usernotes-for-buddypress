<?php
/**
 * Security and Authorization handler for User Notes for BuddyPress.
 *
 * @package BP_UserNotes
 */

namespace BP_UserNotes;

defined( 'ABSPATH' ) || exit;

/**
 * Handles all permission checks, nonce verifications, and input sanitization.
 */
class Security {

	/**
	 * Default nonce action name.
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'bp_usernotes_action';

	/**
	 * Default nonce field name in requests.
	 *
	 * @var string
	 */
	public const NONCE_NAME = '_wpnonce';

	/**
	 * Check if a user can create notes.
	 *
	 * @param int $user_id User ID. Defaults to current user.
	 * @return bool
	 */
	public static function can_create_notes( int $user_id = 0 ): bool {
		$user_id = $user_id ? $user_id : get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		// Check role restrictions configured in settings.
		$allowed_roles = get_option( 'bp_usernotes_allowed_roles', [] );

		if ( ! empty( $allowed_roles ) && is_array( $allowed_roles ) ) {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				return false;
			}

			$user_roles = (array) $user->roles;
			$matched    = array_intersect( $allowed_roles, $user_roles );

			if ( empty( $matched ) && ! user_can( $user_id, 'manage_options' ) ) {
				return false;
			}
		}

		/**
		 * Filter user creation capability.
		 *
		 * @param bool $can_create Whether user can create notes.
		 * @param int  $user_id    Target user ID.
		 */
		return (bool) apply_filters( 'bp_usernotes_can_create_notes', true, $user_id );
	}

	/**
	 * Check if a user can view a specific note.
	 *
	 * @param int $note_id Note post ID.
	 * @param int $user_id User ID attempting access. Defaults to current user.
	 * @return bool
	 */
	public static function can_view_note( int $note_id, int $user_id = 0 ): bool {
		$note = get_post( $note_id );

		if ( ! $note || Post_Type::POST_TYPE !== $note->post_type || 'trash' === $note->post_status ) {
			return false;
		}

		$user_id = $user_id ? $user_id : get_current_user_id();

		// Author can always view their own note.
		if ( (int) $note->post_author === $user_id && $user_id > 0 ) {
			return true;
		}

		// Site administrator can view if moderation is enabled.
		if ( $user_id > 0 && user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		// Check visibility setting on the note.
		$visibility = get_post_meta( $note_id, '_bp_note_visibility', true );

		// If public notes are globally disabled, notes are strictly private.
		$public_enabled = (bool) get_option( 'bp_usernotes_enable_public', 1 );
		if ( ! $public_enabled ) {
			return false;
		}

		$is_public = ( 'public' === $visibility );

		/**
		 * Filter whether a note can be viewed by the given user.
		 *
		 * @param bool     $can_view Whether note is viewable.
		 * @param \WP_Post $note     Note object.
		 * @param int      $user_id  Requesting user ID.
		 */
		return (bool) apply_filters( 'bp_usernotes_can_view_note', $is_public, $note, $user_id );
	}

	/**
	 * Check if a user can edit a specific note.
	 *
	 * @param int $note_id Note post ID.
	 * @param int $user_id User ID attempting to edit. Defaults to current user.
	 * @return bool
	 */
	public static function can_edit_note( int $note_id, int $user_id = 0 ): bool {
		$note = get_post( $note_id );

		if ( ! $note || Post_Type::POST_TYPE !== $note->post_type ) {
			return false;
		}

		$user_id = $user_id ? $user_id : get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		// Author has edit permissions.
		if ( (int) $note->post_author === $user_id ) {
			return true;
		}

		// Administrators can edit for moderation if permitted.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		/**
		 * Filter whether a note can be edited by the given user.
		 *
		 * @param bool     $can_edit Whether note is editable.
		 * @param \WP_Post $note     Note object.
		 * @param int      $user_id  Requesting user ID.
		 */
		return (bool) apply_filters( 'bp_usernotes_can_edit_note', false, $note, $user_id );
	}

	/**
	 * Check if a user can delete a specific note.
	 *
	 * @param int $note_id Note post ID.
	 * @param int $user_id User ID attempting deletion. Defaults to current user.
	 * @return bool
	 */
	public static function can_delete_note( int $note_id, int $user_id = 0 ): bool {
		$note = get_post( $note_id );

		if ( ! $note || Post_Type::POST_TYPE !== $note->post_type ) {
			return false;
		}

		$user_id = $user_id ? $user_id : get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		// Author can delete their own note.
		if ( (int) $note->post_author === $user_id ) {
			return true;
		}

		// Administrators can delete.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		/**
		 * Filter whether a note can be deleted by the given user.
		 *
		 * @param bool     $can_delete Whether note can be deleted.
		 * @param \WP_Post $note       Note object.
		 * @param int      $user_id    Requesting user ID.
		 */
		return (bool) apply_filters( 'bp_usernotes_can_delete_note', false, $note, $user_id );
	}

	/**
	 * Sanitize note title.
	 *
	 * @param string $title Raw title.
	 * @return string
	 */
	public static function sanitize_title( string $title ): string {
		$sanitized = sanitize_text_field( wp_unslash( $title ) );
		$max_len   = (int) apply_filters( 'bp_usernotes_max_title_length', 255 );

		if ( mb_strlen( $sanitized ) > $max_len ) {
			$sanitized = mb_substr( $sanitized, 0, $max_len );
		}

		return $sanitized;
	}

	/**
	 * Get allowed HTML tags for note rich content.
	 *
	 * @return array
	 */
	public static function get_allowed_tags(): array {
		$allowed_tags = [
			'p'          => [ 'class' => true ],
			'br'         => [],
			'strong'     => [],
			'b'          => [],
			'em'         => [],
			'i'          => [],
			'u'          => [],
			's'          => [],
			'strike'     => [],
			'del'        => [],
			'h1'         => [ 'class' => true ],
			'h2'         => [ 'class' => true ],
			'h3'         => [ 'class' => true ],
			'h4'         => [ 'class' => true ],
			'h5'         => [ 'class' => true ],
			'h6'         => [ 'class' => true ],
			'ul'         => [ 'class' => true ],
			'ol'         => [ 'class' => true, 'start' => true ],
			'li'         => [ 'class' => true ],
			'blockquote' => [ 'class' => true, 'cite' => true ],
			'pre'        => [ 'class' => true ],
			'code'       => [ 'class' => true ],
			'hr'         => [],
			'a'          => [
				'href'   => true,
				'title'  => true,
				'target' => true,
				'rel'    => true,
			],
			'span'       => [ 'class' => true, 'style' => true ],
			'mark'       => [ 'class' => true ],
		];

		/**
		 * Filter the allowed HTML tags for note content.
		 *
		 * @param array $allowed_tags Array of allowed tags.
		 */
		return (array) apply_filters( 'bp_usernotes_allowed_html_tags', $allowed_tags );
	}

	/**
	 * Sanitize note rich content using safe KSES tags.
	 *
	 * @param string $content Raw content.
	 * @return string
	 */
	public static function sanitize_content( string $content ): string {
		return wp_kses( wp_unslash( $content ), self::get_allowed_tags() );
	}

	/**
	 * Sanitize visibility value (defaults strictly to 'private').
	 *
	 * @param string $visibility Raw visibility input.
	 * @return string Either 'private' or 'public'.
	 */
	public static function sanitize_visibility( string $visibility ): string {
		$visibility = strtolower( trim( $visibility ) );

		// If public notes are disabled globally, force private.
		$public_enabled = (bool) get_option( 'bp_usernotes_enable_public', 1 );
		if ( ! $public_enabled ) {
			return 'private';
		}

		return in_array( $visibility, [ 'private', 'public' ], true ) ? $visibility : 'private';
	}

	/**
	 * Sanitize color hex code.
	 *
	 * @param string $color Raw color.
	 * @return string Hex color string or empty string.
	 */
	public static function sanitize_color( string $color ): string {
		$sanitized = sanitize_hex_color( wp_unslash( $color ) );
		return $sanitized ? $sanitized : '';
	}

	/**
	 * Verify an AJAX or form nonce.
	 *
	 * @param string $nonce  Nonce token.
	 * @param string $action Nonce action name.
	 * @return bool
	 */
	public static function verify_nonce( string $nonce, string $action = self::NONCE_ACTION ): bool {
		return (bool) wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Create a security nonce for note operations.
	 *
	 * @param string $action Nonce action name.
	 * @return string
	 */
	public static function create_nonce( string $action = self::NONCE_ACTION ): string {
		return wp_create_nonce( $action );
	}
}
