<?php
/**
 * WordPress Core GDPR Privacy, Data Exporter, and Eraser integration.
 *
 * @package BP_UserNotes
 */

namespace BP_UserNotes;

defined( 'ABSPATH' ) || exit;

/**
 * Handles WordPress privacy tools integration for exporting and erasing personal note data.
 */
class Privacy {

	/**
	 * Register privacy hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'wp_privacy_personal_data_exporters', [ __CLASS__, 'register_exporter' ] );
		add_filter( 'wp_privacy_personal_data_erasers', [ __CLASS__, 'register_eraser' ] );
	}

	/**
	 * Register personal data exporter for notes.
	 *
	 * @param array $exporters Existing exporters.
	 * @return array
	 */
	public static function register_exporter( array $exporters ): array {
		$exporters['bp-usernotes'] = [
			'exporter_friendly_name' => __( 'BuddyPress User Notes & Journal', 'usernotes-for-buddypress' ),
			'callback'               => [ __CLASS__, 'export_user_notes' ],
		];

		return $exporters;
	}

	/**
	 * Exporter callback: export notes for a specific user email.
	 *
	 * @param string $email_address Email address of the user.
	 * @param int    $page          Page number.
	 * @return array
	 */
	public static function export_user_notes( string $email_address, int $page = 1 ): array {
		$user = get_user_by( 'email', $email_address );

		if ( ! $user ) {
			return [
				'data' => [],
				'done' => true,
			];
		}

		$number = 50;
		$notes  = get_posts(
			[
				'post_type'      => Post_Type::POST_TYPE,
				'post_status'    => [ 'publish', 'draft', 'trash' ],
				'author'         => $user->ID,
				'posts_per_page' => $number,
				'paged'          => $page,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			]
		);

		$export_items = [];

		foreach ( $notes as $note ) {
			$visibility = get_post_meta( $note->ID, Post_Type::META_VISIBILITY, true );
			$is_pinned  = get_post_meta( $note->ID, Post_Type::META_PINNED, true );

			$item_data = [
				[
					'name'  => __( 'Note ID', 'usernotes-for-buddypress' ),
					'value' => $note->ID,
				],
				[
					'name'  => __( 'Title', 'usernotes-for-buddypress' ),
					'value' => $note->post_title,
				],
				[
					'name'  => __( 'Content', 'usernotes-for-buddypress' ),
					'value' => $note->post_content,
				],
				[
					'name'  => __( 'Visibility', 'usernotes-for-buddypress' ),
					'value' => $visibility ? $visibility : 'private',
				],
				[
					'name'  => __( 'Is Pinned', 'usernotes-for-buddypress' ),
					'value' => $is_pinned ? __( 'Yes', 'usernotes-for-buddypress' ) : __( 'No', 'usernotes-for-buddypress' ),
				],
				[
					'name'  => __( 'Date Created', 'usernotes-for-buddypress' ),
					'value' => $note->post_date,
				],
				[
					'name'  => __( 'Last Modified', 'usernotes-for-buddypress' ),
					'value' => $note->post_modified,
				],
			];

			$export_items[] = [
				'group_id'    => 'bp-usernotes',
				'group_label' => __( 'Personal Notes & Journal', 'usernotes-for-buddypress' ),
				'item_id'     => 'bp-note-' . $note->ID,
				'data'        => $item_data,
			];
		}

		$done = count( $notes ) < $number;

		return [
			'data' => $export_items,
			'done' => $done,
		];
	}

	/**
	 * Register personal data eraser for notes.
	 *
	 * @param array $erasers Existing erasers.
	 * @return array
	 */
	public static function register_eraser( array $erasers ): array {
		$erasers['bp-usernotes'] = [
			'eraser_friendly_name' => __( 'BuddyPress User Notes & Journal', 'usernotes-for-buddypress' ),
			'callback'             => [ __CLASS__, 'erase_user_notes' ],
		];

		return $erasers;
	}

	/**
	 * Eraser callback: delete notes for a specific user email.
	 *
	 * @param string $email_address Email address of the user.
	 * @param int    $page          Page number.
	 * @return array
	 */
	public static function erase_user_notes( string $email_address, int $page = 1 ): array {
		$user = get_user_by( 'email', $email_address );

		if ( ! $user ) {
			return [
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => [],
				'done'           => true,
			];
		}

		$number = 50;
		$notes  = get_posts(
			[
				'post_type'      => Post_Type::POST_TYPE,
				'post_status'    => [ 'publish', 'draft', 'trash' ],
				'author'         => $user->ID,
				'posts_per_page' => $number,
				'paged'          => $page,
				'fields'         => 'ids',
			]
		);

		$items_removed = false;

		foreach ( $notes as $note_id ) {
			$deleted = wp_delete_post( $note_id, true );
			if ( $deleted ) {
				$items_removed = true;
			}
		}

		$done = count( $notes ) < $number;

		return [
			'items_removed'  => $items_removed,
			'items_retained' => false,
			'messages'       => [],
			'done'           => $done,
		];
	}
}
