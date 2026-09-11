<?php
/**
 * AJAX Controller for User Notes.
 *
 * @package BP_UserNotes
 */

namespace BP_UserNotes;

defined( 'ABSPATH' ) || exit;

/**
 * Handles all AJAX endpoints for note operations with nonce and capability verification.
 */
class Ajax_Handler {

	/**
	 * Register all AJAX action hooks for logged-in and guest users.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Logged in users.
		add_action( 'wp_ajax_bp_usernotes_get_notes', [ __CLASS__, 'get_notes' ] );
		add_action( 'wp_ajax_bp_usernotes_save_note', [ __CLASS__, 'save_note' ] );
		add_action( 'wp_ajax_bp_usernotes_delete_note', [ __CLASS__, 'delete_note' ] );
		add_action( 'wp_ajax_bp_usernotes_toggle_visibility', [ __CLASS__, 'toggle_visibility' ] );
		add_action( 'wp_ajax_bp_usernotes_toggle_pin', [ __CLASS__, 'toggle_pin' ] );

		// Public/guest viewing (read-only).
		add_action( 'wp_ajax_nopriv_bp_usernotes_get_notes', [ __CLASS__, 'get_notes' ] );
	}

	/**
	 * AJAX endpoint to retrieve notes.
	 *
	 * @return void
	 */
	public static function get_notes(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		$author_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
		if ( ! $author_id && function_exists( 'bp_displayed_user_id' ) ) {
			$author_id = bp_displayed_user_id();
		}
		if ( ! $author_id ) {
			$author_id = get_current_user_id();
		}

		$visibility = isset( $_GET['visibility'] ) ? sanitize_key( wp_unslash( $_GET['visibility'] ) ) : 'all';
		$search     = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
		$page       = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
		$per_page   = (int) get_option( 'bp_usernotes_per_page', 10 );

		$results = Query::get_notes(
			[
				'author_id'  => $author_id,
				'viewer_id'  => get_current_user_id(),
				'visibility' => $visibility,
				'search'     => $search,
				'page'       => $page,
				'per_page'   => $per_page,
			]
		);

		$counts = Query::get_counts( $author_id, get_current_user_id() );

		wp_send_json_success(
			[
				'notes'     => $results['notes'],
				'total'     => $results['total'],
				'max_pages' => $results['max_pages'],
				'page'      => $results['page'],
				'counts'    => $counts,
			]
		);
	}

	/**
	 * AJAX endpoint to create or update a note.
	 *
	 * @return void
	 */
	public static function save_note(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'You must be logged in to save notes.', 'usernotes-for-buddypress' ) ], 401 );
		}

		$current_user_id = get_current_user_id();
		$note_id         = isset( $_POST['note_id'] ) ? absint( $_POST['note_id'] ) : 0;
		$title           = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$content         = isset( $_POST['content'] ) ? wp_kses( wp_unslash( $_POST['content'] ), Security::get_allowed_tags() ) : '';
		$visibility      = isset( $_POST['visibility'] ) ? Security::sanitize_visibility( sanitize_key( wp_unslash( $_POST['visibility'] ) ) ) : 'private';
		$color           = isset( $_POST['color'] ) ? sanitize_hex_color( wp_unslash( $_POST['color'] ) ) : '';
		$color           = $color ? $color : '';
		$is_pinned       = ! empty( $_POST['is_pinned'] );

		// Permission verification.
		if ( $note_id > 0 ) {
			if ( ! Security::can_edit_note( $note_id, $current_user_id ) ) {
				wp_send_json_error( [ 'message' => __( 'You do not have permission to edit this note.', 'usernotes-for-buddypress' ) ], 403 );
			}
		} else {
			if ( ! Security::can_create_notes( $current_user_id ) ) {
				wp_send_json_error( [ 'message' => __( 'You do not have permission to create notes.', 'usernotes-for-buddypress' ) ], 403 );
			}
		}

		// Validation: At least title or content must be provided.
		if ( '' === trim( $title ) && '' === trim( wp_strip_all_tags( $content ) ) ) {
			wp_send_json_error( [ 'message' => __( 'A note must have a title or some content.', 'usernotes-for-buddypress' ) ], 422 );
		}

		// Fallback title if only content was supplied.
		if ( '' === trim( $title ) ) {
			$title = wp_trim_words( wp_strip_all_tags( $content ), 6, '...' );
			if ( '' === trim( $title ) ) {
				$title = sprintf(
					/* translators: %s: current date */
					__( 'Note - %s', 'usernotes-for-buddypress' ),
					wp_date( get_option( 'date_format' ) )
				);
			}
		}

		$post_data = [
			'post_type'    => Post_Type::POST_TYPE,
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $content,
		];

		if ( $note_id > 0 ) {
			$post_data['ID'] = $note_id;
			$saved_id        = wp_update_post( $post_data, true );
		} else {
			$post_data['post_author'] = $current_user_id;
			$saved_id                 = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $saved_id ) ) {
			wp_send_json_error( [ 'message' => $saved_id->get_error_message() ], 500 );
		}

		// Update note meta attributes.
		update_post_meta( $saved_id, Post_Type::META_VISIBILITY, $visibility );
		update_post_meta( $saved_id, Post_Type::META_PINNED, $is_pinned ? 1 : 0 );
		update_post_meta( $saved_id, Post_Type::META_COLOR, $color );

		/**
		 * Action hook after a note is saved.
		 *
		 * @param int  $saved_id Note post ID.
		 * @param bool $is_new   True if note was created, false if updated.
		 */
		do_action( 'bp_usernotes_after_save_note', $saved_id, ( 0 === $note_id ) );

		$formatted = Query::get_note( $saved_id, $current_user_id );
		$counts    = Query::get_counts( $current_user_id, $current_user_id );

		wp_send_json_success(
			[
				'message' => $note_id > 0
					? __( 'Note updated successfully.', 'usernotes-for-buddypress' )
					: __( 'Note created successfully.', 'usernotes-for-buddypress' ),
				'note'    => $formatted,
				'counts'  => $counts,
			]
		);
	}

	/**
	 * AJAX endpoint to delete or trash a note.
	 *
	 * @return void
	 */
	public static function delete_note(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'You must be logged in to delete notes.', 'usernotes-for-buddypress' ) ], 401 );
		}

		$current_user_id = get_current_user_id();
		$note_id         = isset( $_POST['note_id'] ) ? absint( $_POST['note_id'] ) : 0;

		if ( ! $note_id || ! Security::can_delete_note( $note_id, $current_user_id ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to delete this note.', 'usernotes-for-buddypress' ) ], 403 );
		}

		/**
		 * Action hook before a note is deleted.
		 *
		 * @param int $note_id Note post ID.
		 */
		do_action( 'bp_usernotes_before_delete_note', $note_id );

		// Move to trash or force delete based on settings.
		$result = wp_trash_post( $note_id );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Could not delete note. Please try again.', 'usernotes-for-buddypress' ) ], 500 );
		}

		$counts = Query::get_counts( $current_user_id, $current_user_id );

		wp_send_json_success(
			[
				'message' => __( 'Note moved to Trash.', 'usernotes-for-buddypress' ),
				'note_id' => $note_id,
				'counts'  => $counts,
			]
		);
	}

	/**
	 * AJAX endpoint to toggle note visibility between private and public.
	 *
	 * @return void
	 */
	public static function toggle_visibility(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized access.', 'usernotes-for-buddypress' ) ], 401 );
		}

		$current_user_id = get_current_user_id();
		$note_id         = isset( $_POST['note_id'] ) ? absint( $_POST['note_id'] ) : 0;

		if ( ! $note_id || ! Security::can_edit_note( $note_id, $current_user_id ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to modify this note.', 'usernotes-for-buddypress' ) ], 403 );
		}

		$public_enabled = (bool) get_option( 'bp_usernotes_enable_public', 1 );
		$current_vis    = get_post_meta( $note_id, Post_Type::META_VISIBILITY, true );

		if ( 'public' === $current_vis ) {
			$new_vis = 'private';
		} else {
			if ( ! $public_enabled ) {
				wp_send_json_error( [ 'message' => __( 'Public notes are disabled by administrator.', 'usernotes-for-buddypress' ) ], 400 );
			}
			$new_vis = 'public';
		}

		update_post_meta( $note_id, Post_Type::META_VISIBILITY, $new_vis );

		$counts = Query::get_counts( $current_user_id, $current_user_id );

		wp_send_json_success(
			[
				'note_id'       => $note_id,
				'visibility'    => $new_vis,
				'is_private'    => ( 'private' === $new_vis ),
				'is_public'     => ( 'public' === $new_vis ),
				'badge_label'   => ( 'public' === $new_vis ) ? __( 'Public', 'usernotes-for-buddypress' ) : __( 'Private', 'usernotes-for-buddypress' ),
				'toggle_label'  => ( 'public' === $new_vis ) ? __( 'Make Private', 'usernotes-for-buddypress' ) : __( 'Make Public', 'usernotes-for-buddypress' ),
				'message'       => ( 'public' === $new_vis )
					? __( 'Note is now public and visible on your profile.', 'usernotes-for-buddypress' )
					: __( 'Note is now private and only visible to you.', 'usernotes-for-buddypress' ),
				'counts'        => $counts,
			]
		);
	}

	/**
	 * AJAX endpoint to toggle pinned status of a note.
	 *
	 * @return void
	 */
	public static function toggle_pin(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized access.', 'usernotes-for-buddypress' ) ], 401 );
		}

		$current_user_id = get_current_user_id();
		$note_id         = isset( $_POST['note_id'] ) ? absint( $_POST['note_id'] ) : 0;

		if ( ! $note_id || ! Security::can_edit_note( $note_id, $current_user_id ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to modify this note.', 'usernotes-for-buddypress' ) ], 403 );
		}

		$current_pinned = (bool) get_post_meta( $note_id, Post_Type::META_PINNED, true );
		$new_pinned     = ! $current_pinned;

		update_post_meta( $note_id, Post_Type::META_PINNED, $new_pinned ? 1 : 0 );

		wp_send_json_success(
			[
				'note_id'   => $note_id,
				'is_pinned' => $new_pinned,
				'message'   => $new_pinned
					? __( 'Note pinned to top.', 'usernotes-for-buddypress' )
					: __( 'Note unpinned.', 'usernotes-for-buddypress' ),
			]
		);
	}
}
