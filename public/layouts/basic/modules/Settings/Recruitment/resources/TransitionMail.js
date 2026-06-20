/**
 * FreeCRM - Recruitment status transition mail prompts (Settings)
 */

'use strict';

jQuery.Class('Settings_Recruitment_TransitionMail_Js', {}, {
	DELIVERY_PROMPT: 'prompt',
	DELIVERY_AUTO: 'auto',

	getCellWrap: function (from, to) {
		return jQuery('#recruitmentTransitionMailContainer .js-mail-templates-wrap[data-from="' + from + '"][data-to="' + to + '"]');
	},

	getPillsContainer: function ($wrap) {
		return $wrap.find('.js-mail-template-pills');
	},

	getModeLabel: function (mode) {
		const module = 'Settings:Recruitment';
		return mode === this.DELIVERY_AUTO
			? app.vtranslate('LBL_DELIVERY_AUTO', module)
			: app.vtranslate('LBL_DELIVERY_PROMPT', module);
	},

	escapeHtml: function (value) {
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	},

	buildPillHtml: function (shortName, deliveryMode) {
		const mode = deliveryMode === this.DELIVERY_AUTO ? this.DELIVERY_AUTO : this.DELIVERY_PROMPT;
		const modeLabel = this.getModeLabel(mode);
		const removeLabel = app.vtranslate('LBL_PILL_REMOVE', 'Settings:Recruitment');
		const toggleLabel = app.vtranslate('LBL_DELIVERY_MODE_TOGGLE', 'Settings:Recruitment');
		const esc = this.escapeHtml.bind(this);
		return '<span class="js-mail-template-pill transition-mail-pill" data-short-name="' + esc(shortName) + '" data-delivery-mode="' + mode + '">'
			+ '<span class="transition-mail-pill__name" title="' + esc(shortName) + '">' + esc(shortName) + '</span>'
			+ '<button type="button" class="js-mail-pill-mode transition-mail-pill__mode transition-mail-pill__mode--' + mode + '" title="' + esc(toggleLabel) + '" aria-label="' + esc(toggleLabel) + '">' + esc(modeLabel) + '</button>'
			+ '<button type="button" class="js-mail-pill-remove transition-mail-pill__remove" title="' + esc(removeLabel) + '" aria-label="' + esc(removeLabel) + '">&times;</button>'
			+ '</span>';
	},

	refreshAddTemplateOptions: function ($wrap) {
		const $select = $wrap.find('.js-mail-add-template');
		if (!$select.length) {
			return;
		}
		const used = {};
		this.getPillsContainer($wrap).find('.js-mail-template-pill').each(function () {
			used[jQuery(this).data('shortName')] = true;
		});
		$select.find('option').each(function () {
			const $option = jQuery(this);
			const val = $option.val();
			if (!val) {
				return;
			}
			$option.prop('disabled', !!used[val]);
		});
		$select.val('');
	},

	syncTemplatesWrapState: function ($cell) {
		const $checkbox = $cell.find('.js-mail-prompt-checkbox');
		const $wrap = $cell.find('.js-mail-templates-wrap');
		if (!$wrap.length) {
			return;
		}
		const enabled = $checkbox.is(':checked');
		if (enabled) {
			$wrap.removeClass('hide');
			this.refreshAddTemplateOptions($wrap);
		} else {
			$wrap.addClass('hide');
			this.getPillsContainer($wrap).empty();
			$wrap.find('.js-mail-add-template').val('');
		}
	},

	syncAllCells: function () {
		const thisInstance = this;
		jQuery('#recruitmentTransitionMailContainer .js-mail-prompt-checkbox').each(function () {
			thisInstance.syncTemplatesWrapState(jQuery(this).closest('td'));
		});
	},

	registerCheckboxToggle: function () {
		const thisInstance = this;
		const container = jQuery('#recruitmentTransitionMailContainer');
		container.on('change', '.js-mail-prompt-checkbox', function () {
			thisInstance.syncTemplatesWrapState(jQuery(this).closest('td'));
		});
	},

	registerPillActions: function () {
		const thisInstance = this;
		const container = jQuery('#recruitmentTransitionMailContainer');

		container.on('change', '.js-mail-add-template', function () {
			const $select = jQuery(this);
			const shortName = ($select.val() || '').trim();
			if (!shortName) {
				return;
			}
			const $wrap = $select.closest('.js-mail-templates-wrap');
			const $pills = thisInstance.getPillsContainer($wrap);
			if ($pills.find('.js-mail-template-pill[data-short-name="' + shortName + '"]').length) {
				$select.val('');
				return;
			}
			$pills.append(thisInstance.buildPillHtml(shortName, thisInstance.DELIVERY_PROMPT));
			thisInstance.refreshAddTemplateOptions($wrap);
		});

		container.on('click', '.js-mail-pill-mode', function (e) {
			e.preventDefault();
			const $btn = jQuery(this);
			const $pill = $btn.closest('.js-mail-template-pill');
			const current = $pill.data('deliveryMode') === thisInstance.DELIVERY_AUTO
				? thisInstance.DELIVERY_AUTO
				: thisInstance.DELIVERY_PROMPT;
			const next = current === thisInstance.DELIVERY_AUTO
				? thisInstance.DELIVERY_PROMPT
				: thisInstance.DELIVERY_AUTO;
			$pill.attr('data-delivery-mode', next).data('deliveryMode', next);
			$btn
				.removeClass('transition-mail-pill__mode--prompt transition-mail-pill__mode--auto')
				.addClass('transition-mail-pill__mode--' + next)
				.text(thisInstance.getModeLabel(next));
		});

		container.on('click', '.js-mail-pill-remove', function (e) {
			e.preventDefault();
			const $wrap = jQuery(this).closest('.js-mail-templates-wrap');
			jQuery(this).closest('.js-mail-template-pill').remove();
			thisInstance.refreshAddTemplateOptions($wrap);
		});
	},

	collectEntries: function () {
		const entries = [];
		const self = this;
		const container = jQuery('#recruitmentTransitionMailContainer');
		container.find('.js-mail-prompt-checkbox:checked').each(function () {
			const $checkbox = jQuery(this);
			const from = $checkbox.data('from');
			const to = $checkbox.data('to');
			const $wrap = container.find('.js-mail-templates-wrap[data-from="' + from + '"][data-to="' + to + '"]');
			if (!$wrap.length) {
				return;
			}
			const templates = [];
			$wrap.find('.js-mail-template-pill').each(function () {
				const $pill = jQuery(this);
				const shortName = String($pill.data('shortName') || '').trim();
				if (!shortName) {
					return;
				}
				const deliveryMode = $pill.data('deliveryMode') === self.DELIVERY_AUTO
					? self.DELIVERY_AUTO
					: self.DELIVERY_PROMPT;
				templates.push({ shortName: shortName, deliveryMode: deliveryMode });
			});
			if (!templates.length) {
				return;
			}
			entries.push({ from: from, to: to, templates: templates });
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
				thisInstance.syncTemplatesWrapState($checkbox.closest('td'));
			});
		});
		container.on('click', '.js-mail-clear-row', function () {
			const from = jQuery(this).data('from');
			container.find('tr[data-from-row="' + from + '"] .js-mail-prompt-checkbox').each(function () {
				const $checkbox = jQuery(this);
				$checkbox.prop('checked', false);
				thisInstance.syncTemplatesWrapState($checkbox.closest('td'));
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
				const $wrap = container.find('.js-mail-templates-wrap[data-from="' + from + '"][data-to="' + to + '"]');
				if (!$wrap.length || !$wrap.find('.js-mail-template-pill').length) {
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
		if (!container.length || container.data('transitionMailBound')) {
			return;
		}
		container.data('transitionMailBound', true);
		this.syncAllCells();
		this.registerCheckboxToggle();
		this.registerPillActions();
		this.registerRowActions();
		this.registerSave();
	}
});
