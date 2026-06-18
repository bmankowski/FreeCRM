/**
 * FreeCRM - Recruitment status transition mail prompts (Settings)
 */

'use strict';

jQuery.Class('Settings_Recruitment_TransitionMail_Js', {}, {
	initSelect2ForSelect: function ($select) {
		if (!$select.length || $select.data('select2')) {
			return;
		}
		app.showSelect2ElementView($select, {
			allowClear: true,
			placeholder: '\u00a0',
			tags: false
		});
		const $rendered = $select.next('.select2-container');
		if ($rendered.length) {
			$rendered.find('.select2-selection__placeholder').text('');
		}
	},
	initSelect2: function (container) {
		const thisInstance = this;
		container.find('.js-mail-short-name-wrap:not(.hide) .js-mail-short-names').each(function () {
			thisInstance.initSelect2ForSelect(jQuery(this));
		});
	},
	syncShortNameSelectState: function ($cell) {
		const $checkbox = $cell.find('.js-mail-prompt-checkbox');
		const $wrap = $cell.find('.js-mail-short-name-wrap');
		const $select = $cell.find('.js-mail-short-names');
		if (!$wrap.length || !$select.length) {
			return;
		}
		const enabled = $checkbox.is(':checked');
		if (enabled) {
			$wrap.removeClass('hide');
			$select.prop('disabled', false);
			this.initSelect2ForSelect($select);
		} else {
			$wrap.addClass('hide');
			$select.prop('disabled', true);
			$select.val(null).trigger('change');
		}
	},
	syncAllCells: function () {
		const thisInstance = this;
		jQuery('#recruitmentTransitionMailContainer .js-mail-prompt-checkbox').each(function () {
			thisInstance.syncShortNameSelectState(jQuery(this).closest('td'));
		});
	},
	registerCheckboxToggle: function () {
		const thisInstance = this;
		const container = jQuery('#recruitmentTransitionMailContainer');
		container.on('change', '.js-mail-prompt-checkbox', function () {
			thisInstance.syncShortNameSelectState(jQuery(this).closest('td'));
		});
	},
	collectEntries: function () {
		const entries = [];
		const container = jQuery('#recruitmentTransitionMailContainer');
		container.find('.js-mail-prompt-checkbox:checked').each(function () {
			const $checkbox = jQuery(this);
			const from = $checkbox.data('from');
			const to = $checkbox.data('to');
			const $select = container.find('.js-mail-short-names[data-from="' + from + '"][data-to="' + to + '"]');
			if (!$select.length) {
				return;
			}
			const shortNames = ($select.val() || []).filter(function (name) {
				return typeof name === 'string' && name.trim() !== '';
			});
			if (!shortNames.length) {
				return;
			}
			entries.push({ from: from, to: to, shortNames: shortNames });
		});
		return entries;
	},
	registerRowActions: function () {
		const container = jQuery('#recruitmentTransitionMailContainer');
		const thisInstance = this;
		container.on('click', '.js-mail-select-row', function () {
			const from = jQuery(this).data('from');
			container.find('tr[data-from-row="' + from + '"] .js-mail-prompt-checkbox').each(function () {
				const $checkbox = jQuery(this);
				$checkbox.prop('checked', true);
				thisInstance.syncShortNameSelectState($checkbox.closest('td'));
			});
		});
		container.on('click', '.js-mail-clear-row', function () {
			const from = jQuery(this).data('from');
			container.find('tr[data-from-row="' + from + '"] .js-mail-prompt-checkbox').each(function () {
				const $checkbox = jQuery(this);
				$checkbox.prop('checked', false);
				thisInstance.syncShortNameSelectState($checkbox.closest('td'));
			});
		});
	},
	registerSave: function () {
		const thisInstance = this;
		const container = jQuery('#recruitmentTransitionMailContainer');
		container.on('click', '.js-save-transition-mail', function () {
			let invalid = false;
			container.find('.js-mail-prompt-checkbox:checked').each(function () {
				const $checkbox = jQuery(this);
				const from = $checkbox.data('from');
				const to = $checkbox.data('to');
				const $select = container.find('.js-mail-short-names[data-from="' + from + '"][data-to="' + to + '"]');
				if (!$select.length) {
					return;
				}
				const vals = $select.val();
				if (!vals || !vals.length) {
					invalid = true;
				}
			});
			if (invalid) {
				Vtiger_Helper_Js.showPnotify({
					text: app.vtranslate('LBL_SAVE_TRANSITION_MAIL_SHORT_NAMES_REQUIRED', 'Settings:Recruitment'),
					type: 'error'
				});
				return;
			}
			const entries = thisInstance.collectEntries();
			app.saveAjax('saveTransitionMail', { entries: entries }).done(function (data) {
				const message = data && data.result && data.result.message
					? app.vtranslate(data.result.message)
					: app.vtranslate('LBL_SAVE_TRANSITION_MAIL_SUCCESS', 'Settings:Recruitment');
				Vtiger_Helper_Js.showPnotify({ text: message, type: 'success', animation: 'show' });
				window.location.reload();
			}).fail(function () {
				Vtiger_Helper_Js.showPnotify({
					text: app.vtranslate('LBL_SAVE_TRANSITION_MAIL_FAILED', 'Settings:Recruitment'),
					type: 'error'
				});
			});
		});
	},
	registerEvents: function () {
		const container = jQuery('#recruitmentTransitionMailContainer');
		this.syncAllCells();
		this.initSelect2(container);
		this.registerCheckboxToggle();
		this.registerRowActions();
		this.registerSave();
	}
});

jQuery(document).ready(function () {
	const instance = new Settings_Recruitment_TransitionMail_Js();
	instance.registerEvents();
});
