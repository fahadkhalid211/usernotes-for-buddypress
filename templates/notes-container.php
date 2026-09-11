<?php
/**
 * Main container template for BuddyPress member profile notes screen.
 *
 * @package BP_UserNotes
 */

namespace BP_UserNotes;

defined( 'ABSPATH' ) || exit;

$displayed_user_id = bp_displayed_user_id();
$viewer_id         = get_current_user_id();
$is_owner          = ( $viewer_id > 0 && $viewer_id === $displayed_user_id );
$tab_label         = get_option( 'bp_usernotes_tab_label', __( 'Journal', 'usernotes-for-buddypress' ) );
$can_create        = Security::can_create_notes( $displayed_user_id );
$public_enabled    = (bool) get_option( 'bp_usernotes_enable_public', 1 );
$counts            = Query::get_counts( $displayed_user_id, $viewer_id );
$open_new          = ! empty( $open_new );
?>

<div id="bpun-app" class="bpun-container" data-user-id="<?php echo esc_attr( (string) $displayed_user_id ); ?>" data-is-owner="<?php echo $is_owner ? '1' : '0'; ?>" data-auto-open="<?php echo $open_new ? '1' : '0'; ?>">
	
	<!-- App Header & Statistics -->
	<header class="bpun-header">
		<div class="bpun-header-info">
			<h2 class="bpun-title">
				<?php if ( $is_owner ) : ?>
					<?php
					printf(
						/* translators: %s: tab label */
						esc_html__( 'My %s', 'usernotes-for-buddypress' ),
						esc_html( $tab_label )
					);
					?>
				<?php else : ?>
					<?php
					printf(
						/* translators: 1: User display name, 2: tab label */
						esc_html__( '%1$s&#8217;s Public %2$s', 'usernotes-for-buddypress' ),
						esc_html( bp_get_displayed_user_display_name() ),
						esc_html( $tab_label )
					);
					?>
				<?php endif; ?>
			</h2>
			<p class="bpun-subtitle">
				<?php if ( $is_owner ) : ?>
					<?php esc_html_e( 'Your private space. Entries stay private to you unless you explicitly choose to share them.', 'usernotes-for-buddypress' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Public entries and shared thoughts.', 'usernotes-for-buddypress' ); ?>
				<?php endif; ?>
			</p>
		</div>

		<?php if ( $is_owner && $can_create ) : ?>
			<div class="bpun-header-actions">
				<button type="button" class="bpun-btn bpun-btn-primary" id="bpun-btn-new-note" aria-haspopup="dialog">
					<svg class="bpun-icon" viewBox="0 0 20 20" fill="currentColor" width="18" height="18" aria-hidden="true">
						<path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
					</svg>
					<span><?php esc_html_e( 'New Note', 'usernotes-for-buddypress' ); ?></span>
				</button>
			</div>
		<?php endif; ?>
	</header>

	<!-- Filter & Search Toolbar -->
	<div class="bpun-toolbar">
		<div class="bpun-filter-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Filter Notes', 'usernotes-for-buddypress' ); ?>">
			<button type="button" class="bpun-tab bpun-tab-active" data-visibility="all" role="tab" aria-selected="true">
				<span><?php esc_html_e( 'All', 'usernotes-for-buddypress' ); ?></span>
				<span class="bpun-counter" id="bpun-count-all"><?php echo esc_html( (string) $counts['total'] ); ?></span>
			</button>

			<?php if ( $is_owner ) : ?>
				<button type="button" class="bpun-tab" data-visibility="private" role="tab" aria-selected="false">
					<svg class="bpun-icon-sm" viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true">
						<path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
					</svg>
					<span><?php esc_html_e( 'Private', 'usernotes-for-buddypress' ); ?></span>
					<span class="bpun-counter" id="bpun-count-private"><?php echo esc_html( (string) $counts['private'] ); ?></span>
				</button>
			<?php endif; ?>

			<?php if ( $public_enabled ) : ?>
				<button type="button" class="bpun-tab" data-visibility="public" role="tab" aria-selected="false">
					<svg class="bpun-icon-sm" viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true">
						<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-.2a2 2 0 00-1.664-1.972l-.17-.028A2 2 0 017 11.8v-.472a2 2 0 00-1.4-1.914l-.4-.134a2 2 0 01-.868-.753z" clip-rule="evenodd" />
					</svg>
					<span><?php esc_html_e( 'Public', 'usernotes-for-buddypress' ); ?></span>
					<span class="bpun-counter" id="bpun-count-public"><?php echo esc_html( (string) $counts['public'] ); ?></span>
				</button>
			<?php endif; ?>
		</div>

		<div class="bpun-search-view-wrap">
			<!-- Search Form -->
			<div class="bpun-search-box">
				<svg class="bpun-search-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true">
					<path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
				</svg>
				<input type="search" id="bpun-search-input" class="bpun-search-input" placeholder="<?php esc_attr_e( 'Search notes...', 'usernotes-for-buddypress' ); ?>" aria-label="<?php esc_attr_e( 'Search notes', 'usernotes-for-buddypress' ); ?>" />
				<button type="button" id="bpun-search-clear" class="bpun-search-clear" aria-label="<?php esc_attr_e( 'Clear search', 'usernotes-for-buddypress' ); ?>" style="display: none;">&times;</button>
			</div>

			<!-- Layout Toggle (Grid / List) -->
			<div class="bpun-layout-toggle" role="group" aria-label="<?php esc_attr_e( 'View Layout', 'usernotes-for-buddypress' ); ?>">
				<button type="button" class="bpun-layout-btn bpun-layout-active" data-layout="grid" aria-label="<?php esc_attr_e( 'Grid view', 'usernotes-for-buddypress' ); ?>" title="<?php esc_attr_e( 'Grid view', 'usernotes-for-buddypress' ); ?>">
					<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true">
						<path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
					</svg>
				</button>
				<button type="button" class="bpun-layout-btn" data-layout="list" aria-label="<?php esc_attr_e( 'List view', 'usernotes-for-buddypress' ); ?>" title="<?php esc_attr_e( 'List view', 'usernotes-for-buddypress' ); ?>">
					<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true">
						<path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
					</svg>
				</button>
			</div>
		</div>
	</div>

	<!-- Notes Grid / List Container -->
	<div id="bpun-notes-wrapper" class="bpun-notes-wrapper bpun-layout-grid" aria-live="polite">
		<div class="bpun-loader-skeleton">
			<div class="bpun-skeleton-card"></div>
			<div class="bpun-skeleton-card"></div>
			<div class="bpun-skeleton-card"></div>
		</div>
	</div>

	<!-- Pagination Controls -->
	<nav id="bpun-pagination" class="bpun-pagination" aria-label="<?php esc_attr_e( 'Notes Pagination', 'usernotes-for-buddypress' ); ?>" style="display: none;">
		<button type="button" id="bpun-page-prev" class="bpun-page-btn" disabled>
			&larr; <?php esc_html_e( 'Previous', 'usernotes-for-buddypress' ); ?>
		</button>
		<span id="bpun-page-info" class="bpun-page-info"></span>
		<button type="button" id="bpun-page-next" class="bpun-page-btn" disabled>
			<?php esc_html_e( 'Next', 'usernotes-for-buddypress' ); ?> &rarr;
		</button>
	</nav>

	<!-- Modal Dialog for Creating & Editing Notes -->
	<?php Component::load_template( 'note-modal.php' ); ?>

	<!-- Empty State Template -->
	<template id="bpun-empty-template">
		<?php Component::load_template( 'empty-state.php' ); ?>
	</template>
</div>
