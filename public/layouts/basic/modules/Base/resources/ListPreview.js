/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

(function () {
	'use strict';

	function isPreviewMode() {
		return jQuery('#listViewMode').val() === 'preview' && jQuery('#listPreviewContainer').length > 0;
	}

	function ensureListViewEvents() {
		// On non-standard list views (ListPreview) the core auto-init may not run.
		var listInstance = Vtiger_ListView_Js.getInstance();
		if (!listInstance.__listPreviewBaseEventsRegistered) {
			listInstance.registerEvents();
			listInstance.__listPreviewBaseEventsRegistered = true;
		}
		return listInstance;
	}

	function patchAjaxTarget(listInstance) {
		if (!isPreviewMode()) {
			return;
		}
		if (listInstance.__listPreviewPatched) {
			return;
		}
		var originalGetDefaultParams = listInstance.getDefaultParams.bind(listInstance);
		listInstance.getDefaultParams = function () {
			var params = originalGetDefaultParams();
			params.view = 'ListPreview';
			return params;
		};
		listInstance.__listPreviewPatched = true;
	}

	function getStorageKey() {
		return 'listPreview.leftWidth.' + app.getModuleName();
	}

	function applyLeftWidth(px) {
		var split = jQuery('#listPreviewSplit');
		var left = jQuery('#listPreviewLeft');
		var right = jQuery('#listPreviewRight');
		if (!split.length || !left.length || !right.length) {
			return;
		}
		var splitWidth = split.width();
		var resizerWidth = jQuery('#listPreviewResizer').outerWidth() || 6;
		var minLeft = 320;
		var minRight = 320;
		var maxLeft = Math.max(minLeft, splitWidth - resizerWidth - minRight);
		var clamped = Math.max(minLeft, Math.min(maxLeft, px));
		left.css('flex', '0 0 ' + clamped + 'px');
	}

	function initResizer() {
		var resizer = jQuery('#listPreviewResizer');
		var split = jQuery('#listPreviewSplit');
		if (!resizer.length || !split.length) {
			return;
		}
		var saved = parseInt(localStorage.getItem(getStorageKey()), 10);
		if (!isNaN(saved) && saved > 0) {
			applyLeftWidth(saved);
		} else {
			applyLeftWidth(Math.floor(split.width() / 2));
		}
		resizer.off('mousedown.listPreview').on('mousedown.listPreview', function (e) {
			if (e.button !== 0) {
				return;
			}
			e.preventDefault();
			var startX = e.clientX;
			var startLeftWidth = jQuery('#listPreviewLeft').outerWidth();
			jQuery('body').addClass('unselectable').css('cursor', 'col-resize');
			jQuery(document).on('mousemove.listPreview', function (ev) {
				ev.preventDefault();
				applyLeftWidth(startLeftWidth + (ev.clientX - startX));
			});
			jQuery(document).on('mouseup.listPreview', function () {
				jQuery(document).off('mousemove.listPreview mouseup.listPreview');
				jQuery('body').removeClass('unselectable').css('cursor', '');
				var finalWidth = jQuery('#listPreviewLeft').outerWidth();
				try {
					localStorage.setItem(getStorageKey(), String(finalWidth));
				} catch (e) {}
			});
		});
		jQuery(window).off('resize.listPreview').on('resize.listPreview', function () {
			var current = jQuery('#listPreviewLeft').outerWidth();
			if (current) {
				applyLeftWidth(current);
			}
		});
	}

	function loadPreview(recordId, rowEl) {
		var container = jQuery('#listPreviewContainer');
		if (!container.length) {
			return;
		}
		var frame = container.find('#listPreviewFrame');
		if (!frame.length) {
			return;
		}
		if (rowEl && rowEl.length) {
			rowEl.closest('table').find('tr.listViewEntries').removeClass('active');
			rowEl.addClass('active');
		}
		frame.attr(
			'src',
			'index.php?module=' + encodeURIComponent(app.getModuleName()) +
			'&view=Preview&record=' + encodeURIComponent(recordId) +
			'&_iframe_pv=' + Date.now()
		);
	}

	function registerRowClickOverride(listInstance) {
		var listViewContentDiv = listInstance.getListViewContentContainer();
		// Remove the default ListView row-click navigation handler and replace it with preview refresh.
		// The core handler is bound without namespace, so we must explicitly unbind it.
		listViewContentDiv.off('click', '.listViewEntries');
		// Remove default name-link navigation to DetailView for preview mode.
		listViewContentDiv.off('click', 'tr.listViewEntries a[href]');
		listViewContentDiv.off('click.listPreview', 'tr.listViewEntries a[href]').on('click.listPreview', 'tr.listViewEntries a[href]', function (e) {
			if (!isPreviewMode()) {
				return;
			}
			var link = jQuery(e.currentTarget);
			if (link.hasClass('noLinkBtn')) {
				return;
			}
			// Keep native browser behavior for intentional new-tab / window actions.
			if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button === 1) {
				return;
			}
			e.preventDefault();
			e.stopImmediatePropagation();
			var row = link.closest('tr.listViewEntries');
			var recordId = row.data('id');
			if (!recordId) {
				return;
			}
			loadPreview(recordId, row);
		});
		listViewContentDiv.off('click.listPreview', '.listViewEntries').on('click.listPreview', '.listViewEntries', function (e) {
			if (!isPreviewMode()) {
				return;
			}
			// Allow opening record links in a new tab/window when user intentionally uses modifiers
			// (Ctrl/Cmd click, Shift click, or middle mouse button).
			if (jQuery(e.target).closest('a').length) {
				if (e.ctrlKey || e.metaKey || e.shiftKey || e.button === 1) {
					return;
				}
			}
			if (jQuery(e.target).is('input[type="checkbox"]')) {
				return;
			}
			if (jQuery(e.target, jQuery(e.currentTarget)).is('td:first-child')) {
				return;
			}
			if ($.contains(jQuery(e.currentTarget).find('td:last-child').get(0), e.target)) {
				return;
			}
			if ($.contains(jQuery(e.currentTarget).find('td:first-child').get(0), e.target)) {
				return;
			}
			e.preventDefault();
			e.stopImmediatePropagation();
			var row = jQuery(e.currentTarget);
			var recordId = row.data('id');
			if (!recordId) {
				return;
			}
			loadPreview(recordId, row);
		});
	}

	function loadFirstRecordPreview(listInstance) {
		var listViewContentDiv = listInstance.getListViewContentContainer();
		var firstRow = listViewContentDiv.find('tr.listViewEntries[data-id]').first();
		if (firstRow.length) {
			loadPreview(firstRow.data('id'), firstRow);
		}
	}

	function getActiveRow(listInstance) {
		var rows = listInstance.getListViewContentContainer().find('tr.listViewEntries[data-id]:visible');
		var activeRow = rows.filter('.active').first();
		return activeRow.length ? activeRow : rows.first();
	}

	function scrollRowIntoView(row) {
		if (row && row.length && row[0] && typeof row[0].scrollIntoView === 'function') {
			row[0].scrollIntoView({block: 'nearest'});
		}
	}

	function registerKeyboardNavigation(listInstance) {
		var handleNavigation = function (e) {
			if (!isPreviewMode() || (e.key !== 'ArrowUp' && e.key !== 'ArrowDown')) {
				return;
			}
			var target = jQuery(e.target);
			if (target.is('input, textarea, select') || target.closest('[contenteditable="true"]').length) {
				return;
			}
			var rows = listInstance.getListViewContentContainer().find('tr.listViewEntries[data-id]:visible');
			if (!rows.length) {
				return;
			}
			var currentIndex = rows.index(getActiveRow(listInstance));
			var targetIndex = currentIndex;
			if (e.key === 'ArrowUp') {
				targetIndex = currentIndex > 0 ? currentIndex - 1 : currentIndex;
			} else {
				targetIndex = currentIndex >= 0 && currentIndex < rows.length - 1 ? currentIndex + 1 : currentIndex;
			}
			var targetRow = rows.eq(targetIndex);
			if (!targetRow.length) {
				return;
			}
			e.preventDefault();
			loadPreview(targetRow.data('id'), targetRow);
			scrollRowIntoView(targetRow);
		};
		var bindFrameNavigation = function () {
			var frame = jQuery('#listPreviewFrame');
			if (!frame.length) {
				return;
			}
			try {
				if (frame[0].contentDocument) {
					jQuery(frame[0].contentDocument)
						.off('keydown.listPreviewKeyboard')
						.on('keydown.listPreviewKeyboard', handleNavigation);
				}
			} catch (e) {
				/* ignore cross-origin or not-yet-ready frames */
			}
		};
		jQuery(document).off('keydown.listPreviewKeyboard').on('keydown.listPreviewKeyboard', handleNavigation);
		jQuery('#listPreviewFrame')
			.off('load.listPreviewKeyboard')
			.on('load.listPreviewKeyboard', bindFrameNavigation);
		bindFrameNavigation();
	}

	function init() {
		if (!isPreviewMode()) {
			return;
		}
		var listInstance = ensureListViewEvents();
		patchAjaxTarget(listInstance);
		initResizer();
		registerRowClickOverride(listInstance);
		loadFirstRecordPreview(listInstance);
		registerKeyboardNavigation(listInstance);

		jQuery('body').off('LoadRecordList.PostLoad.listPreview').on('LoadRecordList.PostLoad.listPreview', function () {
			// After AJAX reload, DOM handlers inside list need reattach.
			registerRowClickOverride(listInstance);
			registerKeyboardNavigation(listInstance);
		});
	}

	jQuery(init);
})();

