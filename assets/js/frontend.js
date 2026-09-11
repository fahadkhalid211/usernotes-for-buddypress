/**
 * User Notes for BuddyPress - Modern Frontend Controller
 *
 * @package BP_UserNotes
 */

(function () {
	'use strict';

	// Verify localized configuration availability.
	if (typeof window.bpUserNotes === 'undefined') {
		return;
	}

	const config = window.bpUserNotes;

	/**
	 * State Manager.
	 */
	const state = {
		userId: parseInt(config.displayedUserId, 10) || 0,
		page: 1,
		maxPages: 1,
		visibility: 'all',
		search: '',
		layout: 'grid',
		isLoading: false,
		editingNote: null,
		notes: [],
	};

	// DOM Elements Cache.
	const dom = {
		app: null,
		notesWrapper: null,
		searchInput: null,
		searchClear: null,
		filterTabs: null,
		layoutBtns: null,
		btnNewNote: null,
		pagination: null,
		btnPrev: null,
		btnNext: null,
		pageInfo: null,
		countAll: null,
		countPrivate: null,
		countPublic: null,
		// Modal Elements.
		modal: null,
		modalBackdrop: null,
		modalClose: null,
		modalTitle: null,
		form: null,
		fieldId: null,
		fieldTitle: null,
		fieldVisibility: null,
		fieldPinned: null,
		editor: null,
		btnSave: null,
		btnCancel: null,
		stats: null,
		toolbar: null,
	};

	/**
	 * Initialize Frontend Application.
	 */
	function init() {
		dom.app = document.getElementById('bpun-app');
		if (!dom.app) {
			// Check if single note view exists.
			initSingleNoteView();
			return;
		}

		cacheElements();
		bindEvents();

		// Auto-open new note modal if requested via URL/template.
		if (dom.app.getAttribute('data-auto-open') === '1') {
			openModal();
		}

		// Initial notes retrieval.
		fetchNotes(1);
	}

	/**
	 * Cache DOM element references.
	 */
	function cacheElements() {
		dom.notesWrapper = document.getElementById('bpun-notes-wrapper');
		dom.searchInput = document.getElementById('bpun-search-input');
		dom.searchClear = document.getElementById('bpun-search-clear');
		dom.filterTabs = dom.app.querySelectorAll('.bpun-tab');
		dom.layoutBtns = dom.app.querySelectorAll('.bpun-layout-btn');
		dom.btnNewNote = document.getElementById('bpun-btn-new-note');
		dom.pagination = document.getElementById('bpun-pagination');
		dom.btnPrev = document.getElementById('bpun-page-prev');
		dom.btnNext = document.getElementById('bpun-page-next');
		dom.pageInfo = document.getElementById('bpun-page-info');
		dom.countAll = document.getElementById('bpun-count-all');
		dom.countPrivate = document.getElementById('bpun-count-private');
		dom.countPublic = document.getElementById('bpun-count-public');

		// Modal.
		dom.modal = document.getElementById('bpun-modal');
		if (dom.modal) {
			dom.modalBackdrop = dom.modal.querySelector('.bpun-modal-backdrop');
			dom.modalClose = document.getElementById('bpun-modal-close');
			dom.modalTitle = document.getElementById('bpun-modal-title');
			dom.form = document.getElementById('bpun-form');
			dom.fieldId = document.getElementById('bpun-field-id');
			dom.fieldTitle = document.getElementById('bpun-field-title');
			dom.fieldVisibility = document.getElementById('bpun-field-visibility');
			dom.fieldPinned = document.getElementById('bpun-field-pinned');
			dom.editor = document.getElementById('bpun-editor');
			dom.btnSave = document.getElementById('bpun-btn-save');
			dom.btnCancel = document.getElementById('bpun-btn-cancel');
			dom.stats = document.getElementById('bpun-stats');
			dom.toolbar = dom.modal.querySelector('.bpun-editor-toolbar');
		}
	}

	/**
	 * Bind UI Event Listeners.
	 */
	function bindEvents() {
		// New Note trigger.
		if (dom.btnNewNote) {
			dom.btnNewNote.addEventListener('click', function () {
				openModal();
			});
		}

		// Search Input with Debounce.
		if (dom.searchInput) {
			let searchDebounceTimer = null;
			dom.searchInput.addEventListener('input', function (e) {
				const val = e.target.value.trim();
				if (dom.searchClear) {
					dom.searchClear.style.display = val.length > 0 ? 'block' : 'none';
				}
				clearTimeout(searchDebounceTimer);
				searchDebounceTimer = setTimeout(function () {
					state.search = val;
					fetchNotes(1);
				}, 300);
			});
		}

		// Clear Search.
		if (dom.searchClear) {
			dom.searchClear.addEventListener('click', function () {
				dom.searchInput.value = '';
				dom.searchClear.style.display = 'none';
				state.search = '';
				fetchNotes(1);
			});
		}

		// Filter Tabs.
		if (dom.filterTabs) {
			dom.filterTabs.forEach(function (tab) {
				tab.addEventListener('click', function () {
					dom.filterTabs.forEach(function (t) {
						t.classList.remove('bpun-tab-active');
						t.setAttribute('aria-selected', 'false');
					});
					tab.classList.add('bpun-tab-active');
					tab.setAttribute('aria-selected', 'true');
					state.visibility = tab.getAttribute('data-visibility') || 'all';
					fetchNotes(1);
				});
			});
		}

		// Layout Switch (Grid vs List).
		if (dom.layoutBtns) {
			dom.layoutBtns.forEach(function (btn) {
				btn.addEventListener('click', function () {
					const targetLayout = btn.getAttribute('data-layout');
					dom.layoutBtns.forEach(function (b) {
						b.classList.remove('bpun-layout-active');
					});
					btn.classList.add('bpun-layout-active');
					state.layout = targetLayout;

					if (dom.notesWrapper) {
						dom.notesWrapper.classList.remove('bpun-layout-grid', 'bpun-layout-list');
						dom.notesWrapper.classList.add('bpun-layout-' + targetLayout);
					}
				});
			});
		}

		// Pagination Buttons.
		if (dom.btnPrev) {
			dom.btnPrev.addEventListener('click', function () {
				if (state.page > 1) {
					fetchNotes(state.page - 1);
				}
			});
		}

		if (dom.btnNext) {
			dom.btnNext.addEventListener('click', function () {
				if (state.page < state.maxPages) {
					fetchNotes(state.page + 1);
				}
			});
		}

		// Modal Interactions.
		if (dom.modal) {
			if (dom.modalClose) {
				dom.modalClose.addEventListener('click', closeModal);
			}
			if (dom.modalBackdrop) {
				dom.modalBackdrop.addEventListener('click', closeModal);
			}
			if (dom.btnCancel) {
				dom.btnCancel.addEventListener('click', closeModal);
			}
			if (dom.form) {
				dom.form.addEventListener('submit', handleFormSubmit);
			}

			// Editor Live Stats.
			if (dom.editor) {
				dom.editor.addEventListener('input', updateEditorStats);
			}

			// Formatting Toolbar Buttons.
			if (dom.toolbar) {
				dom.toolbar.querySelectorAll('.bpun-tool-btn').forEach(function (btn) {
					btn.addEventListener('click', function (e) {
						e.preventDefault();
						const command = btn.getAttribute('data-command');
						const value = btn.getAttribute('data-value') || null;
						executeFormat(command, value);
					});
				});
			}

			// Keyboard shortcuts.
			document.addEventListener('keydown', function (e) {
				if (dom.modal && dom.modal.style.display !== 'none') {
					if (e.key === 'Escape') {
						closeModal();
					} else if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
						e.preventDefault();
						if (dom.form) {
							dom.form.dispatchEvent(new Event('submit', { cancelable: true }));
						}
					}
				}
			});
		}

		// Global delegated click listener for dynamic note card actions.
		document.addEventListener('click', handleGlobalClicks);
	}

	/**
	 * Fetch Notes via AJAX.
	 *
	 * @param {number} page Target page number.
	 */
	function fetchNotes(page) {
		if (state.isLoading) {
			return;
		}

		state.isLoading = true;
		showLoadingSkeleton();

		const params = new URLSearchParams({
			action: 'bp_usernotes_get_notes',
			nonce: config.nonce,
			user_id: state.userId,
			visibility: state.visibility,
			search: state.search,
			page: page,
		});

		fetch(config.ajaxUrl + '?' + params.toString())
			.then(function (response) {
				return response.json();
			})
			.then(function (res) {
				state.isLoading = false;
				if (res && res.success && res.data) {
					state.notes = res.data.notes || [];
					state.page = res.data.page || 1;
					state.maxPages = res.data.max_pages || 1;

					updateCounters(res.data.counts);
					renderNotesList(state.notes);
					updatePagination(res.data.page, res.data.max_pages, res.data.total);
				} else {
					renderEmptyState();
				}
			})
			.catch(function () {
				state.isLoading = false;
				if (dom.notesWrapper) {
					dom.notesWrapper.innerHTML = '<div class="bp-feedback error"><p>' + escapeHtml(config.i18n.networkError) + '</p></div>';
				}
			});
	}

	/**
	 * Render Notes Cards into Wrapper.
	 *
	 * @param {Array} notes Array of note objects.
	 */
	function renderNotesList(notes) {
		if (!dom.notesWrapper) {
			return;
		}

		if (!notes || notes.length === 0) {
			renderEmptyState();
			return;
		}

		let html = '';
		notes.forEach(function (note) {
			html += buildNoteCardHtml(note);
		});

		dom.notesWrapper.innerHTML = html;
	}

	/**
	 * Generate Note Card HTML string.
	 *
	 * @param {Object} note Formatted note object.
	 * @returns {string} HTML markup.
	 */
	function buildNoteCardHtml(note) {
		const isOwner = Boolean(config.isOwner);
		const canEdit = Boolean(note.can_edit);
		const pinnedClass = note.is_pinned ? ' bpun-is-pinned' : '';

		let badgesHtml = '';
		if (note.is_pinned) {
			badgesHtml += '<span class="bpun-badge bpun-badge-pinned">📌 ' + escapeHtml(config.i18n.pin) + '</span>';
		}

		if (note.is_public) {
			badgesHtml += '<span class="bpun-badge bpun-badge-public">🌐 ' + escapeHtml(config.i18n.public) + '</span>';
		} else {
			badgesHtml += '<span class="bpun-badge bpun-badge-private">🔒 ' + escapeHtml(config.i18n.private) + '</span>';
		}

		let menuHtml = '';
		if (canEdit) {
			menuHtml = `
				<div class="bpun-card-menu-wrap">
					<button type="button" class="bpun-menu-trigger" aria-label="Note actions" data-note-id="${note.id}">
						<svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" /></svg>
					</button>
					<div class="bpun-dropdown-menu" id="bpun-menu-${note.id}">
						<button type="button" class="bpun-menu-item bpun-action-edit" data-note-id="${note.id}">
							✏️ ${escapeHtml(config.i18n.edit)}
						</button>
						${
							config.publicEnabled
								? `<button type="button" class="bpun-menu-item bpun-action-toggle-vis" data-note-id="${note.id}">
									${note.is_public ? '🔒 ' + escapeHtml(config.i18n.makePrivate) : '🌐 ' + escapeHtml(config.i18n.makePublic)}
								</button>`
								: ''
						}
						<button type="button" class="bpun-menu-item bpun-action-toggle-pin" data-note-id="${note.id}">
							📌 ${note.is_pinned ? escapeHtml(config.i18n.unpin) : escapeHtml(config.i18n.pin)}
						</button>
						<button type="button" class="bpun-menu-item bpun-menu-item-danger bpun-action-delete" data-note-id="${note.id}">
							🗑️ ${escapeHtml(config.i18n.confirmDelete.split('?')[0] || 'Delete')}
						</button>
					</div>
				</div>
			`;
		}

		const singleUrl = note.author_url ? note.author_url + (window.bpUserNotes.slug || 'notes') + '/' + note.id + '/' : '#';

		// Extract a clean excerpt from raw content.
		const tempDiv = document.createElement('div');
		tempDiv.innerHTML = note.content_html || '';
		const plainText = tempDiv.textContent || tempDiv.innerText || '';

		return `
			<article class="bpun-card-item${pinnedClass}" data-note-id="${note.id}">
				<div class="bpun-card-top">
					<div class="bpun-card-badges">
						${badgesHtml}
					</div>
					${menuHtml}
				</div>
				<div class="bpun-card-body" data-action="view-note" data-note-id="${note.id}">
					<h3 class="bpun-card-title">${escapeHtml(note.title)}</h3>
					<p class="bpun-card-excerpt">${escapeHtml(plainText)}</p>
				</div>
				<div class="bpun-card-footer">
					<div class="bpun-meta-left">
						<time datetime="${escapeHtml(note.date_iso)}">${escapeHtml(note.time_ago)}</time>
					</div>
					<span class="bpun-readtime">${escapeHtml(note.reading_time)}</span>
				</div>
			</article>
		`;
	}

	/**
	 * Render Empty State.
	 */
	function renderEmptyState() {
		if (!dom.notesWrapper) {
			return;
		}

		const template = document.getElementById('bpun-empty-template');
		if (template && template.content) {
			dom.notesWrapper.innerHTML = '';
			dom.notesWrapper.appendChild(template.content.cloneNode(true));
		} else {
			dom.notesWrapper.innerHTML = '<div class="bpun-empty-state"><h3 class="bpun-empty-title">' + escapeHtml(config.i18n.noNotesFound) + '</h3></div>';
		}

		if (dom.pagination) {
			dom.pagination.style.display = 'none';
		}
	}

	/**
	 * Show Skeleton Cards while loading.
	 */
	function showLoadingSkeleton() {
		if (!dom.notesWrapper) {
			return;
		}
		dom.notesWrapper.innerHTML = `
			<div class="bpun-loader-skeleton">
				<div class="bpun-skeleton-card"></div>
				<div class="bpun-skeleton-card"></div>
				<div class="bpun-skeleton-card"></div>
			</div>
		`;
	}

	/**
	 * Update Tab Counters.
	 *
	 * @param {Object} counts Count statistics.
	 */
	function updateCounters(counts) {
		if (!counts) {
			return;
		}
		if (dom.countAll && typeof counts.total !== 'undefined') {
			dom.countAll.textContent = counts.total;
		}
		if (dom.countPrivate && typeof counts.private !== 'undefined') {
			dom.countPrivate.textContent = counts.private;
		}
		if (dom.countPublic && typeof counts.public !== 'undefined') {
			dom.countPublic.textContent = counts.public;
		}
	}

	/**
	 * Update Pagination Controls.
	 *
	 * @param {number} current Current page.
	 * @param {number} total   Max pages.
	 * @param {number} count   Total matching notes.
	 */
	function updatePagination(current, total, count) {
		if (!dom.pagination) {
			return;
		}

		if (total <= 1) {
			dom.pagination.style.display = 'none';
			return;
		}

		dom.pagination.style.display = 'flex';
		if (dom.pageInfo) {
			dom.pageInfo.textContent = current + ' / ' + total;
		}
		if (dom.btnPrev) {
			dom.btnPrev.disabled = current <= 1;
		}
		if (dom.btnNext) {
			dom.btnNext.disabled = current >= total;
		}
	}

	/**
	 * Open Create or Edit Note Modal.
	 *
	 * @param {Object|null} note Note object for editing, or null for new note.
	 */
	function openModal(note) {
		if (!dom.modal) {
			return;
		}

		state.editingNote = note || null;

		if (note) {
			// Edit Mode.
			dom.modalTitle.textContent = config.i18n.edit;
			dom.fieldId.value = note.id;
			dom.fieldTitle.value = note.title;
			if (dom.fieldVisibility) {
				dom.fieldVisibility.value = note.visibility;
			}
			if (dom.fieldPinned) {
				dom.fieldPinned.checked = Boolean(note.is_pinned);
			}
			dom.editor.innerHTML = note.content_html || '';
		} else {
			// Create Mode.
			dom.modalTitle.textContent = config.i18n.create;
			dom.fieldId.value = 0;
			dom.fieldTitle.value = '';
			if (dom.fieldVisibility) {
				dom.fieldVisibility.value = config.defaultVisibility || 'private';
			}
			if (dom.fieldPinned) {
				dom.fieldPinned.checked = false;
			}
			dom.editor.innerHTML = '';
		}

		updateEditorStats();
		dom.modal.style.display = 'flex';
		document.body.style.overflow = 'hidden';

		setTimeout(function () {
			if (dom.fieldTitle) {
				dom.fieldTitle.focus();
			}
		}, 50);
	}

	/**
	 * Close Modal.
	 */
	function closeModal() {
		if (!dom.modal) {
			return;
		}
		dom.modal.style.display = 'none';
		document.body.style.overflow = '';
		state.editingNote = null;
	}

	/**
	 * Handle Note Form Submit.
	 *
	 * @param {Event} e Submit event.
	 */
	function handleFormSubmit(e) {
		e.preventDefault();

		const title = dom.fieldTitle ? dom.fieldTitle.value.trim() : '';
		const contentHtml = dom.editor ? dom.editor.innerHTML.trim() : '';
		const noteId = dom.fieldId ? parseInt(dom.fieldId.value, 10) : 0;
		const visibility = dom.fieldVisibility ? dom.fieldVisibility.value : 'private';
		const isPinned = dom.fieldPinned && dom.fieldPinned.checked ? 1 : 0;

		// Validation check.
		const textOnly = dom.editor ? (dom.editor.textContent || dom.editor.innerText || '').trim() : '';
		if (!title && !textOnly) {
			alert(config.i18n.emptyFields);
			return;
		}

		setSavingState(true);

		const formData = new FormData();
		formData.append('action', 'bp_usernotes_save_note');
		formData.append('nonce', config.nonce);
		formData.append('note_id', noteId);
		formData.append('title', title);
		formData.append('content', contentHtml);
		formData.append('visibility', visibility);
		formData.append('is_pinned', isPinned);

		fetch(config.ajaxUrl, {
			method: 'POST',
			body: formData,
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (res) {
				setSavingState(false);
				if (res && res.success) {
					closeModal();
					fetchNotes(state.page);
				} else {
					const msg = res && res.data && res.data.message ? res.data.message : config.i18n.networkError;
					alert(msg);
				}
			})
			.catch(function () {
				setSavingState(false);
				alert(config.i18n.networkError);
			});
	}

	/**
	 * Set saving state on save button.
	 *
	 * @param {boolean} isSaving True if saving in progress.
	 */
	function setSavingState(isSaving) {
		if (!dom.btnSave) {
			return;
		}
		const textSpan = dom.btnSave.querySelector('.bpun-btn-text');
		const spinner = dom.btnSave.querySelector('.bpun-btn-spinner');

		if (isSaving) {
			dom.btnSave.disabled = true;
			if (textSpan) {
				textSpan.textContent = config.i18n.saving;
			}
			if (spinner) {
				spinner.style.display = 'inline-block';
			}
		} else {
			dom.btnSave.disabled = false;
			if (textSpan) {
				textSpan.textContent = config.i18n.save;
			}
			if (spinner) {
				spinner.style.display = 'none';
			}
		}
	}

	/**
	 * Update word and character counts for the active editor.
	 */
	function updateEditorStats() {
		if (!dom.editor || !dom.stats) {
			return;
		}
		const text = (dom.editor.textContent || dom.editor.innerText || '').trim();
		const words = text ? text.split(/\s+/).filter(Boolean).length : 0;
		dom.stats.textContent = words + (words === 1 ? ' word' : ' words');
	}

	/**
	 * Format document command for rich editor.
	 *
	 * @param {string} command Formatting command.
	 * @param {string|null} value Optional command value.
	 */
	function executeFormat(command, value) {
		if (!dom.editor) {
			return;
		}
		dom.editor.focus();

		if (command === 'createLink') {
			const url = prompt('Enter link URL (e.g., https://example.com):', 'https://');
			if (url) {
				document.execCommand('createLink', false, url);
			}
			return;
		}

		document.execCommand(command, false, value);
		updateEditorStats();
	}

	/**
	 * Global Click Delegator.
	 *
	 * @param {Event} e Click event.
	 */
	function handleGlobalClicks(e) {
		// Close any open dropdown menus if clicking outside.
		if (!e.target.closest('.bpun-card-menu-wrap')) {
			document.querySelectorAll('.bpun-dropdown-menu.bpun-menu-open').forEach(function (menu) {
				menu.classList.remove('bpun-menu-open');
			});
		}

		// Menu toggle button.
		const menuTrigger = e.target.closest('.bpun-menu-trigger');
		if (menuTrigger) {
			e.stopPropagation();
			const noteId = menuTrigger.getAttribute('data-note-id');
			const targetMenu = document.getElementById('bpun-menu-' + noteId);
			if (targetMenu) {
				const isOpen = targetMenu.classList.contains('bpun-menu-open');
				document.querySelectorAll('.bpun-dropdown-menu.bpun-menu-open').forEach(function (m) {
					m.classList.remove('bpun-menu-open');
				});
				if (!isOpen) {
					targetMenu.classList.add('bpun-menu-open');
				}
			}
			return;
		}

		// Edit Note action.
		const editBtn = e.target.closest('.bpun-action-edit');
		if (editBtn) {
			e.stopPropagation();
			const noteId = parseInt(editBtn.getAttribute('data-note-id'), 10);
			const targetNote = state.notes.find(function (n) {
				return n.id === noteId;
			});
			if (targetNote) {
				openModal(targetNote);
			}
			return;
		}

		// Delete Note action.
		const deleteBtn = e.target.closest('.bpun-action-delete');
		if (deleteBtn) {
			e.stopPropagation();
			const noteId = parseInt(deleteBtn.getAttribute('data-note-id'), 10);
			if (confirm(config.i18n.confirmDelete)) {
				deleteNote(noteId);
			}
			return;
		}

		// Toggle Visibility action.
		const toggleVisBtn = e.target.closest('.bpun-action-toggle-vis');
		if (toggleVisBtn) {
			e.stopPropagation();
			const noteId = parseInt(toggleVisBtn.getAttribute('data-note-id'), 10);
			toggleVisibility(noteId);
			return;
		}

		// Toggle Pin action.
		const togglePinBtn = e.target.closest('.bpun-action-toggle-pin');
		if (togglePinBtn) {
			e.stopPropagation();
			const noteId = parseInt(togglePinBtn.getAttribute('data-note-id'), 10);
			togglePin(noteId);
			return;
		}

		// Click on empty state create button.
		const emptyCreateBtn = e.target.closest('.bpun-btn-empty-create');
		if (emptyCreateBtn) {
			openModal();
			return;
		}

		// Card click to open note modal or view.
		const cardBody = e.target.closest('.bpun-card-body');
		if (cardBody && !e.target.closest('.bpun-card-menu-wrap')) {
			const noteId = parseInt(cardBody.getAttribute('data-note-id'), 10);
			const targetNote = state.notes.find(function (n) {
				return n.id === noteId;
			});
			if (targetNote && targetNote.can_edit) {
				openModal(targetNote);
			}
		}
	}

	/**
	 * Delete a note via AJAX.
	 *
	 * @param {number} noteId Note post ID.
	 */
	function deleteNote(noteId) {
		const card = document.querySelector('.bpun-card-item[data-note-id="' + noteId + '"]');
		if (card) {
			card.style.opacity = '0.4';
			card.style.pointerEvents = 'none';
		}

		const formData = new FormData();
		formData.append('action', 'bp_usernotes_delete_note');
		formData.append('nonce', config.nonce);
		formData.append('note_id', noteId);

		fetch(config.ajaxUrl, {
			method: 'POST',
			body: formData,
		})
			.then(function (res) {
				return res.json();
			})
			.then(function (data) {
				if (data && data.success) {
					if (card) {
						card.style.transition = 'all 0.3s ease';
						card.style.transform = 'scale(0.9)';
						card.style.opacity = '0';
						setTimeout(function () {
							fetchNotes(state.page);
						}, 300);
					} else {
						// Single note view redirect.
						window.location.href = window.bpUserNotes.authorUrl || './';
					}
				} else {
					if (card) {
						card.style.opacity = '1';
						card.style.pointerEvents = '';
					}
					alert(data && data.data && data.data.message ? data.data.message : config.i18n.networkError);
				}
			})
			.catch(function () {
				if (card) {
					card.style.opacity = '1';
					card.style.pointerEvents = '';
				}
				alert(config.i18n.networkError);
			});
	}

	/**
	 * Toggle visibility between Private and Public.
	 *
	 * @param {number} noteId Note post ID.
	 */
	function toggleVisibility(noteId) {
		const formData = new FormData();
		formData.append('action', 'bp_usernotes_toggle_visibility');
		formData.append('nonce', config.nonce);
		formData.append('note_id', noteId);

		fetch(config.ajaxUrl, {
			method: 'POST',
			body: formData,
		})
			.then(function (res) {
				return res.json();
			})
			.then(function (data) {
				if (data && data.success) {
					fetchNotes(state.page);
				} else {
					alert(data && data.data && data.data.message ? data.data.message : config.i18n.networkError);
				}
			})
			.catch(function () {
				alert(config.i18n.networkError);
			});
	}

	/**
	 * Toggle Pinned status.
	 *
	 * @param {number} noteId Note post ID.
	 */
	function togglePin(noteId) {
		const formData = new FormData();
		formData.append('action', 'bp_usernotes_toggle_pin');
		formData.append('nonce', config.nonce);
		formData.append('note_id', noteId);

		fetch(config.ajaxUrl, {
			method: 'POST',
			body: formData,
		})
			.then(function (res) {
				return res.json();
			})
			.then(function (data) {
				if (data && data.success) {
					fetchNotes(state.page);
				} else {
					alert(data && data.data && data.data.message ? data.data.message : config.i18n.networkError);
				}
			})
			.catch(function () {
				alert(config.i18n.networkError);
			});
	}

	/**
	 * Initialize behaviors on single note view screen.
	 */
	function initSingleNoteView() {
		const article = document.querySelector('.bpun-single-article');
		if (!article) {
			return;
		}

		cacheElements();
		bindEvents();

		const noteId = parseInt(article.getAttribute('data-note-id'), 10);
		if (!noteId) {
			return;
		}

		// Edit button on single view.
		const editBtn = article.querySelector('.bpun-action-edit');
		if (editBtn) {
			editBtn.addEventListener('click', function () {
				const titleEl = article.querySelector('.bpun-single-title');
				const contentEl = article.querySelector('.bpun-single-content');
				const isPublic = article.querySelector('.bpun-badge-public') !== null;
				const isPinned = article.querySelector('.bpun-badge-pinned') !== null;

				const noteObj = {
					id: noteId,
					title: titleEl ? titleEl.textContent.trim() : '',
					content_html: contentEl ? contentEl.innerHTML : '',
					visibility: isPublic ? 'public' : 'private',
					is_pinned: isPinned,
				};

				openModal(noteObj);
			});
		}

		// Delete button on single view.
		const deleteBtn = article.querySelector('.bpun-action-delete');
		if (deleteBtn) {
			deleteBtn.addEventListener('click', function () {
				if (confirm(config.i18n.confirmDelete)) {
					deleteNote(noteId);
				}
			});
		}

		// Toggle visibility on single view.
		const toggleVisBtn = article.querySelector('.bpun-action-toggle-vis');
		if (toggleVisBtn) {
			toggleVisBtn.addEventListener('click', function () {
				toggleVisibility(noteId);
				setTimeout(function () {
					window.location.reload();
				}, 400);
			});
		}
	}

	/**
	 * HTML entity escape helper.
	 *
	 * @param {string} str Unsafe string.
	 * @returns {string} Escaped string.
	 */
	function escapeHtml(str) {
		if (typeof str !== 'string') {
			return '';
		}
		return str
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	// Bootstrap on DOM readiness.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
