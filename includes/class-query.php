<?php
/**
 * Query repository and data formatting for notes.
 *
 * @package BP_UserNotes
 */

namespace BP_UserNotes;

defined( 'ABSPATH' ) || exit;

/**
 * Handles database queries and data presentation with guaranteed privacy isolation.
 */
class Query {

	/**
	 * Retrieve a paginated list of notes for a specified user.
	 *
	 * Guarantees privacy: if the viewer is not the note author and not an administrator,
	 * queries are unconditionally restricted to public notes only.
	 *
	 * @param array $args Query filter arguments.
	 * @return array {
	 *     @type array $notes     Array of formatted note data.
	 *     @type int   $total     Total matching notes.
	 *     @type int   $max_pages Total number of pages.
	 *     @type int   $page      Current page number.
	 * }
	 */
	public static function get_notes( array $args = [] ): array {
		$defaults = [
			'author_id'  => 0,
			'viewer_id'  => 0,
			'visibility' => 'all', // 'all', 'private', 'public'.
			'search'     => '',
			'page'       => 1,
			'per_page'   => 10,
			'orderby'    => 'date',
			'order'      => 'DESC',
		];

		$params = wp_parse_args( $args, $defaults );

		$author_id = absint( $params['author_id'] );
		$viewer_id = $params['viewer_id'] ? absint( $params['viewer_id'] ) : get_current_user_id();

		if ( ! $author_id ) {
			return [
				'notes'     => [],
				'total'     => 0,
				'max_pages' => 0,
				'page'      => 1,
			];
		}

		$is_owner = ( $viewer_id > 0 && $viewer_id === $author_id );
		$is_admin = ( $viewer_id > 0 && user_can( $viewer_id, 'manage_options' ) );

		// Query building.
		$query_args = [
			'post_type'              => Post_Type::POST_TYPE,
			'post_status'            => 'publish',
			'author'                 => $author_id,
			'paged'                  => max( 1, absint( $params['page'] ) ),
			'posts_per_page'         => min( 50, max( 1, absint( $params['per_page'] ) ) ),
			'no_found_rows'          => false,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		];

		// Keyword search.
		if ( ! empty( $params['search'] ) ) {
			$query_args['s'] = sanitize_text_field( $params['search'] );
		}

		// Ordering: Pinned notes always surface first, followed by date/order.
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Scoped strictly to post_type and single author.
		$query_args['meta_key'] = Post_Type::META_PINNED;
		$query_args['orderby']  = [
			'meta_value_num' => 'DESC',
			'date'           => strtoupper( $params['order'] ) === 'ASC' ? 'ASC' : 'DESC',
		];

		// Strict visibility handling.
		$meta_query = [];

		if ( ! $is_owner && ! $is_admin ) {
			// External visitors can ONLY ever see public notes.
			$meta_query[] = [
				'key'     => Post_Type::META_VISIBILITY,
				'value'   => 'public',
				'compare' => '=',
			];
		} else {
			// Owner or admin: apply requested visibility filter.
			if ( 'public' === $params['visibility'] ) {
				$meta_query[] = [
					'key'     => Post_Type::META_VISIBILITY,
					'value'   => 'public',
					'compare' => '=',
				];
			} elseif ( 'private' === $params['visibility'] ) {
				$meta_query[] = [
					'relation' => 'OR',
					[
						'key'     => Post_Type::META_VISIBILITY,
						'value'   => 'private',
						'compare' => '=',
					],
					[
						'key'     => Post_Type::META_VISIBILITY,
						'compare' => 'NOT EXISTS',
					],
				];
			}
		}

		if ( ! empty( $meta_query ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Scoped strictly to post_type and single author for visibility isolation.
			$query_args['meta_query'] = $meta_query;
		}

		/**
		 * Filter the WP_Query arguments for notes retrieval.
		 *
		 * @param array $query_args WP_Query arguments.
		 * @param array $params     Original request parameters.
		 */
		$query_args = apply_filters( 'bp_usernotes_query_args', $query_args, $params );

		$query = new \WP_Query( $query_args );

		$formatted_notes = [];
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$formatted_notes[] = self::format_note( $post, $viewer_id );
			}
		}

		return [
			'notes'     => $formatted_notes,
			'total'     => (int) $query->found_posts,
			'max_pages' => (int) $query->max_num_pages,
			'page'      => max( 1, absint( $params['page'] ) ),
		];
	}

	/**
	 * Retrieve a single note by ID with permission verification.
	 *
	 * @param int $note_id   Note post ID.
	 * @param int $viewer_id User ID viewing the note.
	 * @return array|null Formatted note array or null if not found or unauthorized.
	 */
	public static function get_note( int $note_id, int $viewer_id = 0 ): ?array {
		$note = get_post( $note_id );

		if ( ! $note || Post_Type::POST_TYPE !== $note->post_type ) {
			return null;
		}

		if ( ! Security::can_view_note( $note_id, $viewer_id ) ) {
			return null;
		}

		return self::format_note( $note, $viewer_id );
	}

	/**
	 * Format a note post into a standardized, presentation-ready array.
	 *
	 * @param \WP_Post $post      WP_Post object.
	 * @param int      $viewer_id Requesting user ID.
	 * @return array Formatted note details.
	 */
	public static function format_note( \WP_Post $post, int $viewer_id = 0 ): array {
		$note_id    = $post->ID;
		$author_id  = (int) $post->post_author;
		$visibility = get_post_meta( $note_id, Post_Type::META_VISIBILITY, true );
		$is_pinned  = (bool) get_post_meta( $note_id, Post_Type::META_PINNED, true );
		$color      = get_post_meta( $note_id, Post_Type::META_COLOR, true );

		if ( empty( $visibility ) ) {
			$visibility = 'private';
		}

		$raw_content = $post->post_content;
		$clean_html  = wp_kses_post( wpautop( $raw_content ) );
		$word_count  = str_word_count( wp_strip_all_tags( $raw_content ) );
		$read_min    = max( 1, (int) ceil( $word_count / 200 ) );

		$author_data = get_userdata( $author_id );
		$author_name = $author_data ? $author_data->display_name : __( 'Unknown', 'usernotes-for-buddypress' );

		return [
			'id'           => $note_id,
			'title'        => get_the_title( $post ),
			'content_raw'  => $raw_content,
			'content_html' => $clean_html,
			'visibility'   => $visibility,
			'is_private'   => ( 'private' === $visibility ),
			'is_public'    => ( 'public' === $visibility ),
			'is_pinned'    => $is_pinned,
			'color'        => $color ? $color : '',
			'date'         => get_the_date( '', $post ),
			'date_iso'     => get_the_date( 'c', $post ),
			'time_ago'     => sprintf(
				/* translators: %s: human-readable relative time interval */
				__( '%s ago', 'usernotes-for-buddypress' ),
				human_time_diff( get_post_timestamp( $post ), current_time( 'timestamp' ) )
			),
			'author_id'    => $author_id,
			'author_name'  => $author_name,
			'author_url'   => function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $author_id ) : '',
			'word_count'   => $word_count,
			'reading_time' => sprintf(
				/* translators: %d: reading time in minutes */
				_n( '%d min read', '%d min read', $read_min, 'usernotes-for-buddypress' ),
				$read_min
			),
			'can_edit'     => Security::can_edit_note( $note_id, $viewer_id ),
			'can_delete'   => Security::can_delete_note( $note_id, $viewer_id ),
		];
	}

	/**
	 * Calculate note metrics and counts for a user's notebook.
	 *
	 * @param int $author_id Author user ID.
	 * @param int $viewer_id Viewing user ID.
	 * @return array Counts of total, private, and public notes.
	 */
	public static function get_counts( int $author_id, int $viewer_id = 0 ): array {
		$viewer_id = $viewer_id ? absint( $viewer_id ) : get_current_user_id();
		$is_owner  = ( $viewer_id > 0 && $viewer_id === $author_id );
		$is_admin  = ( $viewer_id > 0 && user_can( $viewer_id, 'manage_options' ) );

		if ( ! $is_owner && ! $is_admin ) {
			// External visitors can only count public notes.
			$public_count = self::count_by_visibility( $author_id, 'public' );
			return [
				'total'   => $public_count,
				'public'  => $public_count,
				'private' => 0,
			];
		}

		$public_count  = self::count_by_visibility( $author_id, 'public' );
		$private_count = self::count_by_visibility( $author_id, 'private' );

		return [
			'total'   => $public_count + $private_count,
			'public'  => $public_count,
			'private' => $private_count,
		];
	}

	/**
	 * Count notes for an author by specific visibility status.
	 *
	 * @param int    $author_id   Author user ID.
	 * @param string $visibility  'public' or 'private'.
	 * @return int Total count.
	 */
	private static function count_by_visibility( int $author_id, string $visibility ): int {
		$args = [
			'post_type'      => Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'author'         => $author_id,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
		];

		if ( 'public' === $visibility ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Scoped strictly to post_type and single author.
			$args['meta_query'] = [
				[
					'key'     => Post_Type::META_VISIBILITY,
					'value'   => 'public',
					'compare' => '=',
				],
			];
		} else {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Scoped strictly to post_type and single author.
			$args['meta_query'] = [
				'relation' => 'OR',
				[
					'key'     => Post_Type::META_VISIBILITY,
					'value'   => 'private',
					'compare' => '=',
				],
				[
					'key'     => Post_Type::META_VISIBILITY,
					'compare' => 'NOT EXISTS',
				],
			];
		}

		$query = new \WP_Query( $args );
		return (int) $query->found_posts;
	}
}
