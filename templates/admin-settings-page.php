<?php
/**
 * Admin Settings Dashboard Template.
 *
 * @package BP_UserNotes
 */

namespace BP_UserNotes\Admin;

defined( 'ABSPATH' ) || exit;

// Fetch aggregate system statistics.
$total_notes_count = wp_count_posts( \BP_UserNotes\Post_Type::POST_TYPE );
$published_notes   = isset( $total_notes_count->publish ) ? (int) $total_notes_count->publish : 0;
$trashed_notes     = isset( $total_notes_count->trash ) ? (int) $total_notes_count->trash : 0;
$bp_version        = function_exists( 'bp_get_version' ) ? bp_get_version() : ( defined( 'BP_VERSION' ) ? BP_VERSION : 'Unknown' );
?>

<div class="wrap bpun-admin-wrap">
	<div class="bpun-admin-header">
		<div class="bpun-admin-branding">
			<h1>
				<?php esc_html_e( 'User Notes for BuddyPress', 'usernotes-for-buddypress' ); ?>
				<span class="bpun-version-tag">v<?php echo esc_html( BP_USERNOTES_VERSION ); ?></span>
			</h1>
			<p class="bpun-admin-tagline">
				<?php esc_html_e( 'A modern, private-by-default personal notes and journaling suite for your BuddyPress community.', 'usernotes-for-buddypress' ); ?>
			</p>
		</div>
	</div>

	<div class="bpun-admin-layout">
		<!-- Main Form Area -->
		<div class="bpun-admin-main">
			<div class="bpun-card">
				<form method="post" action="options.php">
					<?php
					settings_fields( Admin_Settings::OPTION_GROUP );
					do_settings_sections( Admin_Settings::PAGE_SLUG );
					submit_button( __( 'Save Settings', 'usernotes-for-buddypress' ), 'primary', 'submit', true );
					?>
				</form>
			</div>
		</div>

		<!-- Sidebar Information -->
		<div class="bpun-admin-sidebar">
			<!-- System Overview Card -->
			<div class="bpun-card bpun-info-card">
				<h3 class="bpun-card-title"><?php esc_html_e( 'System Overview', 'usernotes-for-buddypress' ); ?></h3>
				<ul class="bpun-status-list">
					<li>
						<span class="bpun-status-label"><?php esc_html_e( 'Active Notes:', 'usernotes-for-buddypress' ); ?></span>
						<strong class="bpun-status-value"><?php echo esc_html( (string) $published_notes ); ?></strong>
					</li>
					<li>
						<span class="bpun-status-label"><?php esc_html_e( 'Trashed Notes:', 'usernotes-for-buddypress' ); ?></span>
						<span class="bpun-status-value"><?php echo esc_html( (string) $trashed_notes ); ?></span>
					</li>
					<li>
						<span class="bpun-status-label"><?php esc_html_e( 'BuddyPress:', 'usernotes-for-buddypress' ); ?></span>
						<span class="bpun-status-badge bpun-badge-active">v<?php echo esc_html( $bp_version ); ?></span>
					</li>
					<li>
						<span class="bpun-status-label"><?php esc_html_e( 'Privacy Standard:', 'usernotes-for-buddypress' ); ?></span>
						<span class="bpun-status-badge bpun-badge-private"><?php esc_html_e( 'Private by Default', 'usernotes-for-buddypress' ); ?></span>
					</li>
					<li>
						<span class="bpun-status-label"><?php esc_html_e( 'GDPR Exporter:', 'usernotes-for-buddypress' ); ?></span>
						<span class="bpun-status-badge bpun-badge-active"><?php esc_html_e( 'Compliant', 'usernotes-for-buddypress' ); ?></span>
					</li>
				</ul>
			</div>

			<!-- Quick Guidance Card -->
			<div class="bpun-card bpun-guide-card">
				<h3 class="bpun-card-title"><?php esc_html_e( 'Privacy Architecture', 'usernotes-for-buddypress' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'All notes created by members stay 100% private to the author by default. Even site administrators cannot query private notes through standard member endpoints unless moderation access is explicitly invoked.', 'usernotes-for-buddypress' ); ?>
				</p>
				<p class="description">
					<?php esc_html_e( 'When a member chooses to toggle a note to "Public", it becomes visible on their BuddyPress profile to visitors and other community members.', 'usernotes-for-buddypress' ); ?>
				</p>
			</div>
		</div>
	</div>
</div>
