<?php
/**
 * Plugin Uninstaller.
 *
 * Fired when the plugin is deleted via the WordPress Admin.
 *
 * @package BP_UserNotes
 */

// If uninstall not called from WordPress, exit.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Check if user requested database cleanup on uninstall.
$delete_all_data = (bool) get_option( 'bp_usernotes_delete_on_uninstall', 0 );

if ( $delete_all_data ) {
	// Query and delete note posts in batches to respect performance and memory limits.
	do {
		$note_ids = get_posts(
			[
				'post_type'      => 'bp_note',
				'post_status'    => 'any',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			]
		);

		if ( ! empty( $note_ids ) ) {
			foreach ( $note_ids as $note_id ) {
				// Force delete post and all associated meta.
				wp_delete_post( (int) $note_id, true );
			}
		}
	} while ( ! empty( $note_ids ) );

	// Delete all registered plugin options.
	$options_to_delete = [
		'bp_usernotes_enable_public',
		'bp_usernotes_default_visibility',
		'bp_usernotes_tab_label',
		'bp_usernotes_slug',
		'bp_usernotes_per_page',
		'bp_usernotes_allowed_roles',
		'bp_usernotes_delete_on_uninstall',
	];

	foreach ( $options_to_delete as $option_name ) {
		delete_option( $option_name );
	}
}
