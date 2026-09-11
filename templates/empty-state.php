<?php
/**
 * Empty state template for User Notes.
 *
 * @package BP_UserNotes
 */

namespace BP_UserNotes;

defined( 'ABSPATH' ) || exit;

$displayed_user_id = bp_displayed_user_id();
$is_owner          = ( get_current_user_id() > 0 && get_current_user_id() === $displayed_user_id );
$can_create        = Security::can_create_notes( $displayed_user_id );
?>

<div class="bpun-empty-state">
	<div class="bpun-empty-icon" aria-hidden="true">
		<svg viewBox="0 0 64 64" fill="none" width="64" height="64">
			<rect x="12" y="8" width="40" height="48" rx="8" fill="var(--bpun-card-bg, #ffffff)" stroke="var(--bpun-border, #e2e8f0)" stroke-width="2" />
			<path d="M20 20H44" stroke="var(--bpun-primary, #4f46e5)" stroke-width="2.5" stroke-linecap="round" />
			<path d="M20 28H44" stroke="var(--bpun-muted, #94a3b8)" stroke-width="2" stroke-linecap="round" />
			<path d="M20 36H34" stroke="var(--bpun-muted, #94a3b8)" stroke-width="2" stroke-linecap="round" />
			<circle cx="42" cy="42" r="10" fill="var(--bpun-accent-bg, #eef2ff)" stroke="var(--bpun-primary, #4f46e5)" stroke-width="2" />
			<path d="M42 38V46M38 42H46" stroke="var(--bpun-primary, #4f46e5)" stroke-width="2" stroke-linecap="round" />
		</svg>
	</div>

	<h3 class="bpun-empty-title">
		<?php if ( $is_owner ) : ?>
			<?php esc_html_e( 'Your Notebook is Empty', 'usernotes-for-buddypress' ); ?>
		<?php else : ?>
			<?php esc_html_e( 'No Public Notes Found', 'usernotes-for-buddypress' ); ?>
		<?php endif; ?>
	</h3>

	<p class="bpun-empty-desc">
		<?php if ( $is_owner ) : ?>
			<?php esc_html_e( 'Capture personal thoughts, ideas, or journal reflections. Notes are completely private to you by default.', 'usernotes-for-buddypress' ); ?>
		<?php else : ?>
			<?php esc_html_e( 'This member hasn&#8217;t published any public notes or journal entries yet.', 'usernotes-for-buddypress' ); ?>
		<?php endif; ?>
	</p>

	<?php if ( $is_owner && $can_create ) : ?>
		<div class="bpun-empty-action">
			<button type="button" class="bpun-btn bpun-btn-primary bpun-btn-empty-create">
				<svg class="bpun-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true">
					<path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
				</svg>
				<span><?php esc_html_e( 'Write Your First Note', 'usernotes-for-buddypress' ); ?></span>
			</button>
		</div>
	<?php endif; ?>
</div>
