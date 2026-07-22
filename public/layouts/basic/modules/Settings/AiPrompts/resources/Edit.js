/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 */
Settings_Vtiger_Edit_Js('Settings_AiPrompts_Edit_Js', {}, {
	updatePlaceholdersHelp: function () {
		var form = this.getForm();
		var key = form.find('.js-action-key').val();
		var map = window.AiPromptsPlaceholders || {};
		var list = map[key] || [];
		var tokens = list.map(function (name) {
			return '{{' + name + '}}';
		});
		form.find('.js-placeholders-help').text(
			tokens.length ? tokens.join(', ') : app.vtranslate('JS_NO_PLACEHOLDERS', 'Settings.AiPrompts')
		);
	},
	registerSubmitForm: function () {
		var form = this.getForm();
		var thisInstance = this;
		form.on('submit', function (e) {
			e.preventDefault();
			form.find('.alert').hide();
			if (form.validationEngine('validate') !== true) {
				app.formAlignmentAfterValidation(form);
				return false;
			}
			var paramsForm = form.serializeFormData();
			if (!form.find('[name="active"]').is(':checked')) {
				paramsForm.active = 0;
			}
			var progressIndicatorElement = jQuery.progressIndicator({
				blockInfo: {'enabled': true}
			});
			app.saveAjax('save', paramsForm).then(function (respons) {
				progressIndicatorElement.progressIndicator({'mode': 'hide'});
				if (true == respons.result.success) {
					window.location.href = respons.result.url;
				} else {
					form.find('.alert').show();
					form.find('.alert p').text(respons.result.message || app.vtranslate('JS_SAVE_FAILED', 'Settings.AiPrompts'));
				}
			}).fail(function () {
				progressIndicatorElement.progressIndicator({'mode': 'hide'});
				form.find('.alert').show();
				form.find('.alert p').text(app.vtranslate('JS_SAVE_FAILED', 'Settings.AiPrompts'));
			});
			return false;
		});
	},
	registerEvents: function () {
		var form = this.getForm();
		if (form.length) {
			form.validationEngine(app.validationEngineOptions);
		}
		this.registerSubmitForm();
		this.updatePlaceholdersHelp();
		form.find('.js-action-key').on('change', this.updatePlaceholdersHelp.bind(this));
		app.showSelect2ElementView(form.find('select.select2'));
	}
});
