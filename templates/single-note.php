<?php
/**
 * Single note view template.
 *
 * @package BP_UserNotes
 */

namespace BP_UserNotes;

defined( 'ABSPATH' ) || exit;

if ( empty( $note ) ) {
	return;
}

$displayed_user_id = bp_displayed_user_id();
$user_domain       = bp_displayed_user_domain();
$slug              = get_option( 'bp_usernotes_slug', Component::ID );
$back_url          = trailingslashit( $user_domain . $slug );
$public_enabled    = (bool) get_option( 'bp_usernotes_enable_public', 1 );
?>

<div class="bpun-single-wrapper">
	<div class="bpun-single-nav">
		<a href="<?php echo esc_url( $back_url ); ?>" class="bpun-back-link">
			<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true">
				<path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
			</svg>
			<span><?php esc_html_e( 'Back to Notes', 'usernotes-for-buddypress' ); ?></span>
		</a>
	</div>

	<article class="bpun-single-article" data-note-id="<?php echo esc_attr( (string) $note['id'] ); ?>">
		
		<header class="bpun-single-header">
			<div class="bpun-single-badges">
				<?php if ( $note['is_pinned'] ) : ?>
					<span class="bpun-badge bpun-badge-pinned">
						📌 <?php esc_html_e( 'Pinned', 'usernotes-for-buddypress' ); ?>
					</span>
				<?php endif; ?>

				<?php if ( $note['is_public'] ) : ?>
					<span class="bpun-badge bpun-badge-public">
						🌐 <?php esc_html_e( 'Public', 'usernotes-for-buddypress' ); ?>
					</span>
				<?php else : ?>
					<span class="bpun-badge bpun-badge-private">
						🔒 <?php esc_html_e( 'Private', 'usernotes-for-buddypress' ); ?>
					</span>
				<?php endif; ?>

				<span class="bpun-single-readtime"><?php echo esc_html( $note['reading_time'] ); ?></span>
			</div>

			<h1 class="bpun-single-title">
				<?php echo esc_html( $note['title'] ); ?>
			</h1>

			<div class="bpun-single-meta">
				<div class="bpun-meta-author">
					<span class="bpun-author-name"><?php echo esc_html( $note['author_name'] ); ?></span>
					<span class="bpun-meta-sep">&bull;</span>
					<time class="bpun-note-date" datetime="<?php echo esc_attr( $note['date_iso'] ); ?>" title="<?php echo esc_attr( $note['date'] ); ?>">
						<?php echo esc_html( $note['time_ago'] ); ?>
					</time>
				</div>

				<?php if ( $note['can_edit'] ) : ?>
					<div class="bpun-single-actions">
						<?php if ( $public_enabled ) : ?>
							<button
								type="button"
								class="bpun-btn bpun-btn-sm bpun-btn-secondary bpun-action-toggle-vis"
								data-note-id="<?php echo esc_attr( (string) $note['id'] ); ?>"
								title="<?php echo $note['is_public'] ? esc_attr__( 'Make Private', 'usernotes-for-buddypress' ) : esc_attr__( 'Make Public', 'usernotes-for-buddypress' ); ?>"
							>
								<?php echo $note['is_public'] ? esc_html__( 'Make Private', 'usernotes-for-buddypress' ) : esc_html__( 'Make Public', 'usernotes-for-buddypress' ); ?>
							</button>
						<?php endif; ?>

						<button
							type="button"
							class="bpun-btn bpun-btn-sm bpun-btn-secondary bpun-action-edit"
							data-note-id="<?php echo esc_attr( (string) $note['id'] ); ?>"
						>
							<?php esc_html_e( 'Edit', 'usernotes-for-buddypress' ); ?>
						</button>

						<button
							type="button"
							class="bpun-btn bpun-btn-sm bpun-btn-danger bpun-action-delete"
							data-note-id="<?php echo esc_attr( (string) $note['id'] ); ?>"
						>
							<?php esc_html_e( 'Delete', 'usernotes-for-buddypress' ); ?>
						</button>
					</div>
				<?php endif; ?>
			</div>
		</header>

		<div class="bpun-single-content bpun-prose">
			<?php echo wp_kses_post( $note['content_html'] ); ?>
		</div>

	</article>

	<!-- Modal Template for Edit Action on Single Note Screen -->
	<?php Component::load_template( 'note-modal.php' ); ?>
</div>
