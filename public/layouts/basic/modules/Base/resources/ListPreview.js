/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

jQuery(function () {
Vtiger_ListView_Js("Vtiger_ListPreview_Js", {}, {
	isPreviewMode: function () {
		return jQuery('#listViewMode').val() === 'preview' && jQuery('#listPreviewContainer').length > 0;
	},

	getDefaultParams: function () {
		var params = this._super();
		params.view = 'ListPreview';
		return params;
	},

	getStorageKey: function () {
		return 'listPreview.leftWidth.' + app.getModuleName();
	},

	applyLeftWidth: function (px) {
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
	},

	registerResizer: function () {
		var thisInstance = this;
		var resizer = jQuery('#listPreviewResizer');
		var split = jQuery('#listPreviewSplit');
		if (!resizer.length || !split.length) {
			return;
		}
		var saved = parseInt(localStorage.getItem(this.getStorageKey()), 10);
		if (!isNaN(saved) && saved > 0) {
			this.applyLeftWidth(saved);
		} else {
			this.applyLeftWidth(Math.floor(split.width() / 2));
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
				thisInstance.applyLeftWidth(startLeftWidth + (ev.clientX - startX));
			});
			jQuery(document).on('mouseup.listPreview', function () {
				jQuery(document).off('mousemove.listPreview mouseup.listPreview');
				jQuery('body').removeClass('unselectable').css('cursor', '');
				var finalWidth = jQuery('#listPreviewLeft').outerWidth();
				try {
					localStorage.setItem(thisInstance.getStorageKey(), String(finalWidth));
				} catch (e) {}
			});
		});
		jQuery(window).off('resize.listPreview').on('resize.listPreview', function () {
			var current = jQuery('#listPreviewLeft').outerWidth();
			if (current) {
				thisInstance.applyLeftWidth(current);
			}
		});
	},

	loadPreview: function (recordId, rowEl) {
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
	},

	registerRowClickEvent: function () {
		var thisInstance = this;
		var listViewContentDiv = this.getListViewContentContainer();
		listViewContentDiv.off('click.listPreview', 'tr.listViewEntries a[href]').on('click.listPreview', 'tr.listViewEntries a[href]', function (e) {
			if (!thisInstance.isPreviewMode()) {
				return;
			}
			var link = jQuery(e.currentTarget);
			if (link.hasClass('noLinkBtn')) {
				return;
			}
			// Keep native browser behavior for intentional new-tab/window actions.
			if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button === 1) {
				return;
			}
			e.preventDefault();
			e.stopImmediatePropagation();
			var row = link.closest('tr.listViewEntries');
			var recordId = row.data('id');
			if (recordId) {
				thisInstance.loadPreview(recordId, row);
			}
		});
		listViewContentDiv.off('click.listPreview', '.listViewEntries').on('click.listPreview', '.listViewEntries', function (e) {
			if (!thisInstance.isPreviewMode()) {
				return;
			}
			if (jQuery(e.target).closest('a').length && (e.ctrlKey || e.metaKey || e.shiftKey || e.button === 1)) {
				return;
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
			if (recordId) {
				thisInstance.loadPreview(recordId, row);
			}
		});
	},

	loadFirstRecordPreview: function () {
		var firstRow = this.getListViewContentContainer().find('tr.listViewEntries[data-id]').first();
		if (firstRow.length) {
			this.loadPreview(firstRow.data('id'), firstRow);
		}
	},

	getActiveRow: function () {
		var rows = this.getListViewContentContainer().find('tr.listViewEntries[data-id]:visible');
		var activeRow = rows.filter('.active').first();
		return activeRow.length ? activeRow : rows.first();
	},

	scrollRowIntoView: function (row) {
		if (row && row.length && row[0] && typeof row[0].scrollIntoView === 'function') {
			row[0].scrollIntoView({block: 'nearest'});
		}
	},

	registerKeyboardNavigation: function () {
		var thisInstance = this;
		var handleNavigation = function (e) {
			if (!thisInstance.isPreviewMode() || (e.key !== 'ArrowUp' && e.key !== 'ArrowDown')) {
				return;
			}
			var target = jQuery(e.target);
			if (target.is('input, textarea, select') || target.closest('[contenteditable="true"]').length) {
				return;
			}
			var rows = thisInstance.getListViewContentContainer().find('tr.listViewEntries[data-id]:visible');
			if (!rows.length) {
				return;
			}
			var currentIndex = rows.index(thisInstance.getActiveRow());
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
			thisInstance.loadPreview(targetRow.data('id'), targetRow);
			thisInstance.scrollRowIntoView(targetRow);
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
	},

	registerListPreviewEvents: function () {
		var thisInstance = this;
		this.registerResizer();
		this.loadFirstRecordPreview();
		this.registerKeyboardNavigation();
		jQuery('body').off('LoadRecordList.PostLoad.listPreview').on('LoadRecordList.PostLoad.listPreview', function () {
			thisInstance.loadFirstRecordPreview();
			thisInstance.registerKeyboardNavigation();
		});
	},

	registerEvents: function () {
		if (this._listPreviewEventsRegistered || !this.isPreviewMode()) {
			return;
		}
		this._super();
		this.registerListPreviewEvents();
		this._listPreviewEventsRegistered = true;
	}
});

	if (jQuery('#listViewMode').val() !== 'preview' || !jQuery('#listPreviewContainer').length) {
		return;
	}
	var moduleClassName = app.getModuleName() + "_ListPreview_Js";
	var instance = typeof window[moduleClassName] !== 'undefined' ? new window[moduleClassName]() : new Vtiger_ListPreview_Js();
	Vtiger_ListView_Js.listInstance = instance;
	instance.registerEvents();
});

