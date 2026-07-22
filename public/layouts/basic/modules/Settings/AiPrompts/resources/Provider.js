/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 */
jQuery.Class('Settings_AiPrompts_Provider_Js', {}, {
	getForm: function () {
		return jQuery('#EditAiProvider');
	},
	showError: function (message) {
		var form = this.getForm();
		form.find('.js-provider-alert-success').hide();
		form.find('.js-provider-alert-error').show().find('p').text(message || '');
	},
	showSuccess: function (message) {
		var form = this.getForm();
		form.find('.js-provider-alert-error').hide();
		form.find('.js-provider-alert-success').show().find('p').text(message || '');
	},
	typedApiKey: function () {
		return jQuery.trim(this.getForm().find('.js-api-key').val() || '');
	},
	canFetchModels: function () {
		var form = this.getForm();
		if (form.find('.js-clear-api-key').is(':checked')) {
			return false;
		}
		var keyVal = this.typedApiKey();
		if (keyVal !== '') {
			return keyVal.indexOf('sk-') === 0;
		}
		return String(form.data('hasApiKey')) === '1';
	},
	refreshFetchButtonState: function () {
		var form = this.getForm();
		var btn = form.find('.js-fetch-models');
		var enabled = this.canFetchModels();
		btn.prop('disabled', !enabled).toggleClass('disabled', !enabled);
		btn.attr('title', enabled
			? app.vtranslate('JS_AI_FETCH_MODELS_HINT', 'Settings.AiPrompts')
			: app.vtranslate('JS_AI_FETCH_MODELS_NEED_KEY', 'Settings.AiPrompts'));
	},
	populateModelSelect: function (models, selected) {
		var form = this.getForm();
		var $select = form.find('.js-ai-model');
		selected = selected || $select.val();
		if ($select.data('select2')) {
			$select.select2('destroy');
		}
		$select.empty();
		var found = false;
		jQuery.each(models, function (i, id) {
			var opt = new Option(id, id, false, id === selected);
			$select.append(opt);
			if (id === selected) {
				found = true;
			}
		});
		if (selected && !found) {
			$select.prepend(new Option(selected, selected, true, true));
		}
		app.showSelect2ElementView($select, {
			width: '100%',
			placeholder: '\u00a0',
			tags: true
		});
	},
	registerFetchModels: function () {
		var thisInstance = this;
		var form = this.getForm();
		form.find('.js-fetch-models').on('click', function () {
			if (!thisInstance.canFetchModels()) {
				thisInstance.showError(app.vtranslate('JS_AI_FETCH_MODELS_NEED_KEY', 'Settings.AiPrompts'));
				return;
			}
			form.find('.js-provider-alert-error, .js-provider-alert-success').hide();
			var btn = form.find('.js-fetch-models');
			var status = form.find('.js-models-status');
			var previousHtml = btn.html();
			btn.prop('disabled', true);
			status.text(app.vtranslate('JS_AI_FETCHING_MODELS', 'Settings.AiPrompts'));
			var progressIndicatorElement = jQuery.progressIndicator({
				blockInfo: {'enabled': true}
			});
			var params = {};
			var typed = thisInstance.typedApiKey();
			if (typed.indexOf('sk-') === 0) {
				params.api_key = typed;
			}
			app.saveAjax('listModels', params, {
				module: 'AiPrompts',
				parent: 'Settings',
				action: 'SaveProviderAjax'
			}).then(function (respons) {
				progressIndicatorElement.progressIndicator({'mode': 'hide'});
				btn.html(previousHtml);
				thisInstance.refreshFetchButtonState();
				var result = respons && respons.result ? respons.result : {};
				if (!result.success) {
					thisInstance.showError(result.message || app.vtranslate('JS_AI_FETCH_MODELS_FAILED', 'Settings.AiPrompts'));
					status.text(app.vtranslate('LBL_AI_MODEL_HELP', 'Settings.AiPrompts'));
					return;
				}
				var models = result.models || [];
				thisInstance.populateModelSelect(models);
				var msg = result.message || app.vtranslate('JS_AI_MODELS_LOADED', 'Settings.AiPrompts').replace('%d', models.length);
				status.text(msg);
				thisInstance.showSuccess(msg);
			}).fail(function () {
				progressIndicatorElement.progressIndicator({'mode': 'hide'});
				btn.html(previousHtml);
				thisInstance.refreshFetchButtonState();
				thisInstance.showError(app.vtranslate('JS_AI_FETCH_MODELS_FAILED', 'Settings.AiPrompts'));
				status.text(app.vtranslate('LBL_AI_MODEL_HELP', 'Settings.AiPrompts'));
			});
		});
	},
	registerSubmitForm: function () {
		var thisInstance = this;
		var form = this.getForm();
		form.on('submit', function (e) {
			e.preventDefault();
			form.find('.js-provider-alert-error, .js-provider-alert-success').hide();
			if (form.validationEngine('validate') !== true) {
				app.formAlignmentAfterValidation(form);
				return false;
			}
			var paramsForm = form.serializeFormData();
			if (!form.find('.js-clear-api-key').is(':checked')) {
				delete paramsForm.clear_api_key;
			}
			var typed = thisInstance.typedApiKey();
			if (typed === '' || typed.indexOf('sk-') !== 0) {
				delete paramsForm.api_key;
			} else {
				paramsForm.api_key = typed;
			}
			var progressIndicatorElement = jQuery.progressIndicator({
				blockInfo: {'enabled': true}
			});
			app.saveAjax('save', paramsForm, {
				module: 'AiPrompts',
				parent: 'Settings',
				action: 'SaveProviderAjax'
			}).then(function (respons) {
				progressIndicatorElement.progressIndicator({'mode': 'hide'});
				var result = respons && respons.result ? respons.result : {};
				if (result.success) {
					thisInstance.showSuccess(result.message || '');
					if (typeof Settings_Vtiger_Index_Js !== 'undefined' && Settings_Vtiger_Index_Js.showMessage) {
						Settings_Vtiger_Index_Js.showMessage({text: result.message});
					}
					if (form.find('.js-clear-api-key').is(':checked')) {
						form.data('hasApiKey', 0);
						form.find('.js-api-key').val('').prop('disabled', false)
							.attr('placeholder', app.vtranslate('LBL_AI_API_KEY_PLACEHOLDER', 'Settings.AiPrompts'));
						form.find('.js-clear-api-key').prop('checked', false);
					} else if (typed.indexOf('sk-') === 0) {
						form.data('hasApiKey', 1);
						form.find('.js-api-key').val('')
							.attr('placeholder', app.vtranslate('LBL_AI_API_KEY_STORED_PLACEHOLDER', 'Settings.AiPrompts'));
					}
					thisInstance.refreshFetchButtonState();
				} else {
					thisInstance.showError(result.message || app.vtranslate('JS_SAVE_FAILED', 'Settings.AiPrompts'));
				}
			}).fail(function () {
				progressIndicatorElement.progressIndicator({'mode': 'hide'});
				thisInstance.showError(app.vtranslate('JS_SAVE_FAILED', 'Settings.AiPrompts'));
			});
			return false;
		});
	},
	registerEvents: function () {
		var form = this.getForm();
		if (!form.length) {
			return;
		}
		form.validationEngine(app.validationEngineOptions);
		app.showSelect2ElementView(form.find('select.js-ai-model'), {
			width: '100%',
			placeholder: '\u00a0',
			tags: true
		});
		this.registerSubmitForm();
		this.registerFetchModels();
		this.refreshFetchButtonState();
		form.find('.js-clear-api-key').on('change', function () {
			form.find('.js-api-key').prop('disabled', jQuery(this).is(':checked'));
			this.refreshFetchButtonState();
		}.bind(this));
		form.find('.js-api-key').on('input change', this.refreshFetchButtonState.bind(this));
	}
});

jQuery(function () {
	var instance = new Settings_AiPrompts_Provider_Js();
	instance.registerEvents();
});
