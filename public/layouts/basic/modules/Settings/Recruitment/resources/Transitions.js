/**
 * FreeCRM - Recruitment status transition rules (Settings)
 */

'use strict';

jQuery.Class('Settings_Recruitment_Transitions_Js', {}, {
	collectTransitions: function () {
		const transitions = [];
		jQuery('#recruitmentTransitionsContainer .js-transition-checkbox:checked').each(function () {
			const $el = jQuery(this);
			transitions.push({
				from: $el.data('from'),
				to: $el.data('to')
			});
		});
		return transitions;
	},
	registerRowActions: function () {
		const container = jQuery('#recruitmentTransitionsContainer');
		container.on('click', '.js-select-row', function () {
			const from = jQuery(this).data('from');
			container.find('.js-transition-checkbox[data-from="' + from + '"]').prop('checked', true);
		});
		container.on('click', '.js-clear-row', function () {
			const from = jQuery(this).data('from');
			container.find('.js-transition-checkbox[data-from="' + from + '"]').prop('checked', false);
		});
		container.on('click', '.js-select-col', function () {
			const to = jQuery(this).data('to');
			container.find('.js-transition-checkbox[data-to="' + to + '"]').prop('checked', true);
		});
	},
	registerSave: function () {
		const thisInstance = this;
		const container = jQuery('#recruitmentTransitionsContainer');
		container.on('click', '.js-save-transitions', function () {
			const transitions = thisInstance.collectTransitions();
			app.saveAjax('saveTransitions', { transitions: transitions }).done(function (data) {
				const message = data && data.result && data.result.message
					? app.vtranslate(data.result.message)
					: app.vtranslate('LBL_SAVE_TRANSITIONS_SUCCESS', 'Settings:Recruitment');
				Vtiger_Helper_Js.showPnotify({ text: message, type: 'success', animation: 'show' });
				window.location.reload();
			}).fail(function () {
				Vtiger_Helper_Js.showPnotify({
					text: app.vtranslate('LBL_SAVE_TRANSITIONS_FAILED', 'Settings:Recruitment'),
					type: 'error'
				});
			});
		});
	},
	registerEvents: function () {
		this.registerRowActions();
		this.registerSave();
	}
});

jQuery(document).ready(function () {
	const instance = new Settings_Recruitment_Transitions_Js();
	instance.registerEvents();
});
