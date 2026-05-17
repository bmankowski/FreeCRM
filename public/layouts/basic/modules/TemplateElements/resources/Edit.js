/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 */
Vtiger_Edit_Js('TemplateElements_Edit_Js', {}, {
	codeMirrors: {},
	registerCodeGeneration: function () {
		var form = this.getForm();
		var label = form.find('[name="label"]');
		var code = form.find('[name="code"]');
		if (code.prop('readonly') || code.val()) {
			return;
		}
		label.on('keyup change', function () {
			if (code.data('manualChange')) {
				return;
			}
			code.val(TemplateElements_Edit_Js.generateCode(label.val()));
		});
		code.on('keyup change', function () {
			code.data('manualChange', true);
			code.val(TemplateElements_Edit_Js.generateCode(code.val()));
		});
	},
	destroyCodeMirrors: function () {
		var thisInstance = this;
		jQuery.each(this.codeMirrors, function (k, cm) {
			if (cm && typeof cm.toTextArea === 'function') {
				cm.toTextArea();
			}
		});
		this.codeMirrors = {};
	},
	registerCodeMirror: function () {
		this.destroyCodeMirrors();
		if (typeof CodeMirror === 'undefined') {
			return;
		}
		var form = this.getForm();
		var layoutType = jQuery('#documentLayoutTypeValue').val();
		var isLayout = form.find('[name="type"]').val() === layoutType;
		var opts = {
			mode: 'htmlmixed',
			lineNumbers: true,
			lineWrapping: true,
			matchBrackets: true,
			autoCloseBrackets: true,
			autoCloseTags: true,
			styleActiveLine: true
		};
		var thisInstance = this;
		var bind = function (textareaId, key, height) {
			var ta = form.find('#' + textareaId).get(0);
			if (!ta) {
				return;
			}
			thisInstance.codeMirrors[key] = CodeMirror.fromTextArea(ta, opts);
			thisInstance.codeMirrors[key].setSize('100%', height);
		};
		if (isLayout) {
			bind('dynamicElementLayoutHeader', 'layout_header', 200);
			bind('dynamicElementLayoutBody', 'layout_body', 420);
			bind('dynamicElementLayoutFooter', 'layout_footer', 200);
		} else {
			bind('dynamicElementContent', 'content', 420);
		}
	},
	applyTypeVisibility: function () {
		var form = this.getForm();
		var layoutType = jQuery('#documentLayoutTypeValue').val();
		var isLayout = form.find('[name="type"]').val() === layoutType;
		form.find('.js-dynamic-layout-editor').toggleClass('hide', !isLayout);
		form.find('.js-dynamic-fragment-editor').toggleClass('hide', isLayout);
		var content = form.find('#dynamicElementContent');
		var body = form.find('#dynamicElementLayoutBody');
		if (isLayout) {
			content.removeAttr('data-validation-engine');
			body.attr('data-validation-engine', 'validate[required]');
		} else {
			body.removeAttr('data-validation-engine');
			content.attr('data-validation-engine', 'validate[required]');
		}
	},
	registerTypeSwitch: function () {
		var thisInstance = this;
		this.getForm().on('change', 'select[name="type"]', function () {
			thisInstance.applyTypeVisibility();
			thisInstance.registerCodeMirror();
		});
	},
	syncCodeMirrors: function () {
		jQuery.each(this.codeMirrors, function (k, cm) {
			if (cm && typeof cm.save === 'function') {
				cm.save();
			}
		});
	},
	saveOnly: function () {
		var thisInstance = this;
		var form = this.getForm();
		thisInstance.syncCodeMirrors();
		if (form.validationEngine('validate') !== true) {
			app.formAlignmentAfterValidation(form);
			return jQuery.Deferred().reject().promise();
		}
		var progressIndicatorElement = jQuery.progressIndicator({
			position: 'html',
			blockInfo: {
				enabled: true
			}
		});
		var saveData = form.serializeFormData();
		saveData.action = 'Save';
		saveData.mode = 'edit';
		delete saveData.returnToList;
		if (typeof csrfMagicName !== 'undefined' && typeof csrfMagicToken !== 'undefined') {
			saveData[csrfMagicName] = csrfMagicToken;
		}
		return AppConnector.request(saveData).then(
			function (data) {
				var payload = data;
				if (typeof data === 'string') {
					try {
						payload = JSON.parse(data);
					} catch (e) {
						payload = {};
					}
				}
				var result = payload.result || payload;
				if (result.record) {
					form.find('[name="record"]').val(result.record);
					form.find('[name="code"]').prop('readonly', true);
				}
				if (result.url && window.history && window.history.replaceState) {
					window.history.replaceState(null, '', result.url);
				}
				Vtiger_Helper_Js.showMessage({
					text: app.vtranslate('JS_SAVE_NOTIFY_OK', 'Vtiger')
				});
				progressIndicatorElement.progressIndicator({
					mode: 'hide'
				});
			},
			function (error, err) {
				progressIndicatorElement.progressIndicator({
					mode: 'hide'
				});
				app.errorLog(error, err);
			}
		);
	},
	registerKeyboardShortcuts: function () {
		var thisInstance = this;
		jQuery(document).on('keydown.templateElementsEdit', function (e) {
			if (!(e.ctrlKey || e.metaKey) || e.key !== 's') {
				return;
			}
			var form = thisInstance.getForm();
			if (!form.length || !jQuery.contains(document, form.get(0))) {
				return;
			}
			e.preventDefault();
			thisInstance.saveOnly();
		});
	},
	registerSubmit: function () {
		var thisInstance = this;
		this.getForm().on('submit', function (e) {
			thisInstance.syncCodeMirrors();
			if (jQuery(e.currentTarget).validationEngine('validate') !== true) {
				app.formAlignmentAfterValidation(jQuery(e.currentTarget));
				return false;
			}
		});
	},
	registerEvents: function () {
		var form = this.getForm();
		if (typeof Clipboard !== 'undefined') {
			app.registerCopyClipboard();
		}
		if (form.length) {
			form.validationEngine(app.validationEngineOptions);
			form.find(':input').inputmask();
		}
		this.registerCodeGeneration();
		this.applyTypeVisibility();
		this.registerTypeSwitch();
		this.registerCodeMirror();
		this.registerSubmit();
		this.registerKeyboardShortcuts();
	}
});

TemplateElements_Edit_Js.generateCode = function (value) {
	var code = (value || '').toLowerCase();
	if (typeof code.normalize === 'function') {
		code = code.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
	}
	return code.replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
};
