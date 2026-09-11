/**
 * User Notes for BuddyPress - Admin Dashboard Scripts
 *
 * @package BP_UserNotes
 */

(function ($) {
	'use strict';

	$(document).ready(function () {
		const $deleteCheckbox = $('#bp_usernotes_delete_on_uninstall');
		const $slugInput = $('#bp_usernotes_slug');

		// Confirm destructive action on uninstall data cleanup.
		if ($deleteCheckbox.length) {
			$deleteCheckbox.on('change', function () {
				if ($(this).is(':checked')) {
					const confirmed = window.confirm(
						'Warning: Enabling this option will permanently delete all user notes and database records when this plugin is uninstalled. Are you sure you want to enable this?'
					);
					if (!confirmed) {
						$(this).prop('checked', false);
					}
				}
			});
		}

		// Sanitize slug input on change.
		if ($slugInput.length) {
			$slugInput.on('input', function () {
				let val = $(this).val();
				val = val.toLowerCase().replace(/[^a-z0-9-_]/g, '');
				$(this).val(val);
			});
		}
	});
})(jQuery);
