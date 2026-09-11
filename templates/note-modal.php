<?php
/**
 * Modern modal dialog template for creating and editing notes.
 *
 * @package BP_UserNotes
 */

namespace BP_UserNotes;

defined( 'ABSPATH' ) || exit;

$public_enabled     = (bool) get_option( 'bp_usernotes_enable_public', 1 );
$default_visibility = get_option( 'bp_usernotes_default_visibility', 'private' );
?>

<div id="bpun-modal" class="bpun-modal" role="dialog" aria-modal="true" aria-labelledby="bpun-modal-title" style="display: none;">
	<div class="bpun-modal-backdrop" tabindex="-1"></div>

	<div class="bpun-modal-container">
		<div class="bpun-modal-content">
			
			<!-- Modal Header -->
			<div class="bpun-modal-header">
				<h3 id="bpun-modal-title" class="bpun-modal-title">
					<?php esc_html_e( 'Create New Note', 'usernotes-for-buddypress' ); ?>
				</h3>
				<button type="button" class="bpun-modal-close" id="bpun-modal-close" aria-label="<?php esc_attr_e( 'Close dialog', 'usernotes-for-buddypress' ); ?>">
					<svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20" aria-hidden="true">
						<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
					</svg>
				</button>
			</div>

			<!-- Modal Body / Form -->
			<form id="bpun-form" class="bpun-form" method="post">
				<input type="hidden" id="bpun-field-id" name="note_id" value="0" />
				<input type="hidden" id="bpun-field-color" name="color" value="" />

				<!-- Title Field -->
				<div class="bpun-form-group">
					<input
						type="text"
						id="bpun-field-title"
						name="title"
						class="bpun-input-title"
						placeholder="<?php esc_attr_e( 'Title of your note...', 'usernotes-for-buddypress' ); ?>"
						maxlength="255"
						autocomplete="off"
					/>
				</div>

				<!-- Rich Formatting Toolbar -->
				<div class="bpun-editor-toolbar" role="toolbar" aria-label="<?php esc_attr_e( 'Editor formatting tools', 'usernotes-for-buddypress' ); ?>">
					<div class="bpun-toolbar-group">
						<button type="button" class="bpun-tool-btn" data-command="bold" title="<?php esc_attr_e( 'Bold (Ctrl+B)', 'usernotes-for-buddypress' ); ?>" aria-label="<?php esc_attr_e( 'Bold', 'usernotes-for-buddypress' ); ?>">
							<strong>B</strong>
						</button>
						<button type="button" class="bpun-tool-btn" data-command="italic" title="<?php esc_attr_e( 'Italic (Ctrl+I)', 'usernotes-for-buddypress' ); ?>" aria-label="<?php esc_attr_e( 'Italic', 'usernotes-for-buddypress' ); ?>">
							<em>I</em>
						</button>
						<button type="button" class="bpun-tool-btn" data-command="underline" title="<?php esc_attr_e( 'Underline (Ctrl+U)', 'usernotes-for-buddypress' ); ?>" aria-label="<?php esc_attr_e( 'Underline', 'usernotes-for-buddypress' ); ?>">
							<span style="text-decoration: underline;">U</span>
						</button>
						<button type="button" class="bpun-tool-btn" data-command="strikeThrough" title="<?php esc_attr_e( 'Strikethrough', 'usernotes-for-buddypress' ); ?>" aria-label="<?php esc_attr_e( 'Strikethrough', 'usernotes-for-buddypress' ); ?>">
							<span style="text-decoration: line-through;">S</span>
						</button>
					</div>

					<div class="bpun-toolbar-divider"></div>

					<div class="bpun-toolbar-group">
						<button type="button" class="bpun-tool-btn" data-command="formatBlock" data-value="h2" title="<?php esc_attr_e( 'Heading 2', 'usernotes-for-buddypress' ); ?>" aria-label="<?php esc_attr_e( 'Heading 2', 'usernotes-for-buddypress' ); ?>">
							H2
						</button>
						<button type="button" class="bpun-tool-btn" data-command="formatBlock" data-value="h3" title="<?php esc_attr_e( 'Heading 3', 'usernotes-for-buddypress' ); ?>" aria-label="<?php esc_attr_e( 'Heading 3', 'usernotes-for-buddypress' ); ?>">
							H3
						</button>
					</div>

					<div class="bpun-toolbar-divider"></div>

					<div class="bpun-toolbar-group">
						<button type="button" class="bpun-tool-btn" data-command="insertUnorderedList" title="<?php esc_attr_e( 'Bullet List', 'usernotes-for-buddypress' ); ?>" aria-label="<?php esc_attr_e( 'Bullet List', 'usernotes-for-buddypress' ); ?>">
							<svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" /></svg>
						</button>
						<button type="button" class="bpun-tool-btn" data-command="insertOrderedList" title="<?php esc_attr_e( 'Numbered List', 'usernotes-for-buddypress' ); ?>" aria-label="<?php esc_attr_e( 'Numbered List', 'usernotes-for-buddypress' ); ?>">
							<span style="font-weight: 600; font-size: 11px;">1.</span>
						</button>
						<button type="button" class="bpun-tool-btn" data-command="formatBlock" data-value="blockquote" title="<?php esc_attr_e( 'Quote', 'usernotes-for-buddypress' ); ?>" aria-label="<?php esc_attr_e( 'Quote', 'usernotes-for-buddypress' ); ?>">
							&ldquo;
						</button>
						<button type="button" class="bpun-tool-btn" data-command="createLink" title="<?php esc_attr_e( 'Insert Link', 'usernotes-for-buddypress' ); ?>" aria-label="<?php esc_attr_e( 'Insert Link', 'usernotes-for-buddypress' ); ?>">
							<svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true"><path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" /></svg>
						</button>
					</div>

					<div class="bpun-toolbar-group bpun-toolbar-meta">
						<span id="bpun-stats" class="bpun-stats">0 words</span>
					</div>
				</div>

				<!-- Content Editable Canvas -->
				<div class="bpun-form-group">
					<div
						id="bpun-editor"
						class="bpun-editor"
						contenteditable="true"
						role="textbox"
						aria-multiline="true"
						aria-label="<?php esc_attr_e( 'Note content', 'usernotes-for-buddypress' ); ?>"
						data-placeholder="<?php esc_attr_e( 'Write your journal entry or thoughts here...', 'usernotes-for-buddypress' ); ?>"
					></div>
				</div>

				<!-- Modal Footer / Meta Controls -->
				<div class="bpun-modal-footer">
					<div class="bpun-footer-meta">
						<!-- Privacy Visibility Setting -->
						<?php if ( $public_enabled ) : ?>
							<div class="bpun-control-visibility">
								<label for="bpun-field-visibility" class="bpun-field-label">
									<?php esc_html_e( 'Visibility:', 'usernotes-for-buddypress' ); ?>
								</label>
								<div class="bpun-vis-select-wrap">
									<select id="bpun-field-visibility" name="visibility" class="bpun-select">
										<option value="private" <?php selected( $default_visibility, 'private' ); ?>>
											🔒 <?php esc_html_e( 'Private (Only You)', 'usernotes-for-buddypress' ); ?>
										</option>
										<option value="public" <?php selected( $default_visibility, 'public' ); ?>>
											🌐 <?php esc_html_e( 'Public (Profile)', 'usernotes-for-buddypress' ); ?>
										</option>
									</select>
								</div>
							</div>
						<?php else : ?>
							<input type="hidden" id="bpun-field-visibility" name="visibility" value="private" />
							<span class="bpun-badge bpun-badge-private">
								🔒 <?php esc_html_e( 'Private', 'usernotes-for-buddypress' ); ?>
							</span>
						<?php endif; ?>

						<!-- Pin Checkbox -->
						<label class="bpun-checkbox-label">
							<input type="checkbox" id="bpun-field-pinned" name="is_pinned" value="1" />
							<span>📌 <?php esc_html_e( 'Pin to top', 'usernotes-for-buddypress' ); ?></span>
						</label>
					</div>

					<div class="bpun-footer-actions">
						<span class="bpun-shortcut-hint" aria-hidden="true">
							<kbd>Ctrl</kbd>+<kbd>Enter</kbd> <?php esc_html_e( 'to save', 'usernotes-for-buddypress' ); ?>
						</span>
						<button type="button" class="bpun-btn bpun-btn-secondary" id="bpun-btn-cancel">
							<?php esc_html_e( 'Cancel', 'usernotes-for-buddypress' ); ?>
						</button>
						<button type="submit" class="bpun-btn bpun-btn-primary" id="bpun-btn-save">
							<span class="bpun-btn-text"><?php esc_html_e( 'Save Note', 'usernotes-for-buddypress' ); ?></span>
							<span class="bpun-btn-spinner" aria-hidden="true" style="display: none;"></span>
						</button>
					</div>
				</div>

			</form>
		</div>
	</div>
</div>
