/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */
Settings_Template_Edit_Js("Settings_Template_Edit3_Js", {}, {
	step3Container: false,
	advanceFilterInstance: false,
	codeMirrorInstances: {},
	activeEditorId: 'body_content',
	previewPopoutWindow: null,
	init: function () {
		this.initialize();
	},
	/**
	 * Function to get the container which holds all the reports step1 elements
	 * @return jQuery object
	 */
	getContainer: function () {
		return this.step3Container;
	},
	/**
	 * Function to set the reports step1 container
	 * @params : element - which represents the reports step1 container
	 * @return : current instance
	 */
	setContainer: function (element) {
		this.step3Container = element;
		return this;
	},
	/**
	 * Function  to intialize the reports step1
	 */
	initialize: function (container) {
		if (typeof container === 'undefined') {
			container = jQuery('#pdf_step3');
		}
		if (container.is('#pdf_step3')) {
			this.setContainer(container);
		} else {
			this.setContainer(jQuery('#pdf_step3'));
		}
	},
	submit: function () {
		this.syncCodeMirrorEditors();
		var aDeferred = jQuery.Deferred();
		var form = this.getContainer();
		var formData = form.serializeFormData();
		var progressIndicatorElement = jQuery.progressIndicator({
			'position': 'html',
			'blockInfo': {
				'enabled': true
			}
		});

		var saveData = form.serializeFormData();
		saveData['action'] = 'Save';
		saveData['step'] = 3;
		AppConnector.request(saveData).then(
				function (data) {
					data = JSON.parse(data);
					if (data.success == true) {
						Settings_Vtiger_Index_Js.showMessage({text: app.vtranslate('JS_TEMPLATE_SAVED_SUCCESSFULLY')});

						AppConnector.request(formData).then(
								function (data) {
									form.hide();
									progressIndicatorElement.progressIndicator({
										'mode': 'hide'
									})
									aDeferred.resolve(data);
								},
								function (error, err) {
									app.errorLog(error, err);
								}
						);
					}
				},
				function (error, err) {
					app.errorLog(error, err);
				}
		);
		return aDeferred.promise();
	},
	saveOnly: function () {
		var thisInstance = this;
		this.syncCodeMirrorEditors();
		var aDeferred = jQuery.Deferred();
		var form = this.getContainer();
		var progressIndicatorElement = jQuery.progressIndicator({
			'position': 'html',
			'blockInfo': {
				'enabled': true
			}
		});

		var saveData = form.serializeFormData();
		saveData['action'] = 'Save';
		saveData['step'] = 3;
		AppConnector.request(saveData).then(
				function (data) {
					data = JSON.parse(data);
					if (data.success == true) {
						Settings_Vtiger_Index_Js.showMessage({text: app.vtranslate('JS_TEMPLATE_SAVED_SUCCESSFULLY')});
						thisInstance.refreshPreviewIfVisible();
					}
					progressIndicatorElement.progressIndicator({
						'mode': 'hide'
					});
					aDeferred.resolve(data);
				},
				function (error, err) {
					progressIndicatorElement.progressIndicator({
						'mode': 'hide'
					});
					app.errorLog(error, err);
					aDeferred.reject(error, err);
				}
		);
		return aDeferred.promise();
	},
	registerCancelStepClickEvent: function (form) {
		jQuery('button.cancelLink', form).on('click', function () {
			window.history.back();
		});
	},
	getEditorValue: function (editorId) {
		if (this.codeMirrorInstances[editorId]) {
			return this.codeMirrorInstances[editorId].getValue();
		}
		return this.getContainer().find('#' + editorId).val();
	},
	setEditorValue: function (editorId, value) {
		if (this.codeMirrorInstances[editorId]) {
			this.codeMirrorInstances[editorId].setValue(value);
			this.codeMirrorInstances[editorId].refresh();
			return;
		}
		this.getContainer().find('#' + editorId).val(value);
	},
	insertEditorValue: function (editorId, value) {
		var editor = this.codeMirrorInstances[editorId];
		if (editor) {
			editor.replaceSelection(value);
			editor.focus();
			editor.refresh();
			return;
		}
		var textarea = this.getContainer().find('#' + editorId);
		textarea.val((textarea.val() || '') + value);
	},
	getDynamicElements: function () {
		var data = this.getContainer().find('.js-dynamic-elements-json').val() || '[]';
		try {
			return JSON.parse(data);
		} catch (e) {
			app.errorLog(e);
			return [];
		}
	},
	getDynamicElementContent: function (code) {
		var content = '';
		jQuery.each(this.getDynamicElements(), function (index, element) {
			if (element.code === code) {
				if (element.type === 'PLL_DOCUMENT_LAYOUT') {
					content = element.layout_body || '';
				} else {
					content = element.content || '';
				}
				return false;
			}
		});
		return content;
	},
	expandDynamicElements: function (html) {
		var thisInstance = this;
		var expanded = html || '';
		for (var i = 0; i < 10; i++) {
			var changed = false;
			expanded = expanded.replace(/\$\(dynamic : ([a-zA-Z0-9_]+)\)\$/g, function (match, code) {
				var content = thisInstance.getDynamicElementContent(code);
				if (content === '') {
					return '';
				}
				changed = true;
				return content;
			});
			if (!changed) {
				break;
			}
		}
		return expanded;
	},
	insertDynamicElement: function (code) {
		if (!code) {
			return;
		}
		this.insertEditorValue(this.activeEditorId || 'body_content', '\n$(dynamic : ' + code + ')$\n');
		this.formatHtmlEditors();
	},
	applyDocumentLayout: function (element) {
		if (!element) {
			return;
		}
		var header = element.layout_header || '';
		var body = element.layout_body || '';
		var footer = element.layout_footer || '';
		var hasIncoming = (header + body + footer).replace(/\s/g, '').length > 0;
		var existing =
			(this.getEditorValue('header_content') || '') +
			(this.getEditorValue('body_content') || '') +
			(this.getEditorValue('footer_content') || '');
		if (existing.replace(/\s/g, '').length > 0 && hasIncoming) {
			if (!confirm(app.vtranslate('JS_TEMPLATE_DOCUMENT_LAYOUT_OVERWRITE'))) {
				return;
			}
		}
		this.setEditorValue('header_content', header);
		this.setEditorValue('body_content', body);
		this.setEditorValue('footer_content', footer);
		this.formatHtmlEditors();
	},
	runEditorCommand: function (commandName) {
		var editor = this.codeMirrorInstances[this.activeEditorId] || this.codeMirrorInstances.body_content;
		if (!editor || typeof editor.execCommand !== 'function') {
			return;
		}
		editor.focus();
		editor.execCommand(commandName);
	},
	formatHtmlEditors: function () {
		var thisInstance = this;
		if (typeof html_beautify !== 'function') {
			Settings_Vtiger_Index_Js.showMessage({text: 'Brak lokalnej biblioteki formatowania HTML.'});
			return;
		}
		jQuery.each(['header_content', 'body_content', 'footer_content'], function (index, editorId) {
			var value = thisInstance.getEditorValue(editorId);
			if (jQuery.trim(value) === '') {
				return true;
			}
			thisInstance.setEditorValue(editorId, html_beautify(value, {
				indent_size: 2,
				indent_char: ' ',
				max_preserve_newlines: 2,
				preserve_newlines: true,
				wrap_line_length: 160
			}));
		});
	},
	renderPreview: function () {
		var documentHtml = this.buildPreviewHtml();
		var w = this.previewPopoutWindow;
		if (w && !w.closed) {
			try {
				w.document.open();
				w.document.write(documentHtml);
				w.document.close();
				w.focus();
				return;
			} catch (e) {
				app.errorLog(e);
				this.previewPopoutWindow = null;
			}
		}
		w = window.open('', 'FreeCRMTemplatePdfPreview', 'width=960,height=720,scrollbars=yes,resizable=yes');
		if (!w) {
			Settings_Vtiger_Index_Js.showMessage({text: app.vtranslate('JS_TEMPLATE_PREVIEW_POPUP_BLOCKED')});
			return;
		}
		this.previewPopoutWindow = w;
		w.document.open();
		w.document.write(documentHtml);
		w.document.close();
		w.focus();
	},
	refreshPreviewIfVisible: function () {
		var w = this.previewPopoutWindow;
		if (!w || w.closed) {
			return;
		}
		var documentHtml = this.buildPreviewHtml();
		try {
			w.document.open();
			w.document.write(documentHtml);
			w.document.close();
		} catch (e) {
			app.errorLog(e);
			this.previewPopoutWindow = null;
		}
	},
	buildPreviewHtml: function () {
		var header = this.expandDynamicElements(this.getEditorValue('header_content'));
		var body = this.expandDynamicElements(this.getEditorValue('body_content'));
		var footer = this.expandDynamicElements(this.getEditorValue('footer_content'));
		return '<!doctype html><html><head><meta charset="utf-8">' +
			'<base href="/"><link rel="stylesheet" href="/layouts/basic/resources/FreeCRMTemplate.css?v=docDefaults2">' +
			'<style>body{font-family:Arial,sans-serif;font-size:12px;padding:24px;color:#222;}' +
			'.pdf-preview-section{border:1px solid #ddd;margin-bottom:16px;padding:12px;}' +
			'.pdf-preview-label{background:#f5f5f5;border-bottom:1px solid #ddd;font-weight:bold;margin:-12px -12px 12px;padding:8px;}' +
			'table{max-width:100%;} img{max-width:100%;}</style></head><body>' +
			this.buildPreviewSection('Nagłówek', header) +
			this.buildPreviewSection('Treść główna', body) +
			this.buildPreviewSection('Stopka', footer) +
			'</body></html>';
	},
	buildPreviewSection: function (label, html) {
		return '<section class="pdf-preview-section"><div class="pdf-preview-label">' + label + '</div>' + html + '</section>';
	},
	registerEditorToolbarEvents: function () {
		var container = this.getContainer();
		var thisInstance = this;
		container.off('click.pdfFormatHtml').on('click.pdfFormatHtml', '.js-format-html', function () {
			thisInstance.formatHtmlEditors();
		});
		container.off('click.pdfSaveStep3').on('click.pdfSaveStep3', '.js-save-step3-only', function () {
			thisInstance.saveOnly();
		});
		container.off('click.pdfPreviewHtml').on('click.pdfPreviewHtml', '.js-preview-html', function () {
			thisInstance.renderPreview();
		});
		container.off('change.templateDynamicAliasVariable').on('change.templateDynamicAliasVariable', '#templateDynamicAliasVariable', function () {
			var select = jQuery(this);
			var code = select.val();
			if (!code) {
				return;
			}
			thisInstance.insertDynamicElement(code);
			var firstCode = select.find('option').first().val();
			select.val(firstCode).trigger('change.select2');
		});
		container.off('click.pdfTemplateAiHelp').on('click.pdfTemplateAiHelp', '.js-toggle-ai-help', function () {
			container.find('.pdfTemplateAiHelp').toggleClass('hide');
		});
	},
	/**
	 * Sync CodeMirror instances back to their source textarea fields.
	 */
	syncCodeMirrorEditors: function () {
		for (var editorId in this.codeMirrorInstances) {
			if (this.codeMirrorInstances.hasOwnProperty(editorId)) {
				this.codeMirrorInstances[editorId].save();
			}
		}
	},
	/**
	 * Registers CodeMirror on HTML template textarea fields.
	 */
	registerCodeMirror: function () {
		var thisInstance = this;
		var container = this.getContainer();
		if (typeof CodeMirror === 'undefined') {
			return;
		}
		container.data('codeMirrorInitialized', true);
		jQuery.each(['header_content', 'body_content', 'footer_content'], function (index, editorId) {
			if (thisInstance.codeMirrorInstances[editorId]) {
				thisInstance.codeMirrorInstances[editorId].toTextArea();
				delete thisInstance.codeMirrorInstances[editorId];
			}
			var textarea = container.find('#' + editorId).get(0);
			if (!textarea) {
				return true;
			}
			thisInstance.codeMirrorInstances[editorId] = CodeMirror.fromTextArea(textarea, {
				autoCloseBrackets: true,
				autoCloseTags: true,
				indentUnit: 2,
				lineNumbers: true,
				lineWrapping: true,
				matchBrackets: true,
				mode: 'htmlmixed',
				styleActiveLine: true,
				tabSize: 2,
				extraKeys: {
					'Ctrl-F': 'findPersistent',
					'Ctrl-H': 'replace',
					'Ctrl-S': function () {
						thisInstance.saveOnly();
					},
					'Cmd-S': function () {
						thisInstance.saveOnly();
					}
				}
			});
			thisInstance.codeMirrorInstances[editorId].on('focus', function () {
				thisInstance.activeEditorId = editorId;
			});
			thisInstance.codeMirrorInstances[editorId].setSize('100%', editorId === 'body_content' ? 360 : 140);
		});
	},
	registerEvents: function () {
		var container = this.getContainer();

		var opts = app.validationEngineOptions;
		// to prevent the page reload after the validation has completed
		opts['onValidationComplete'] = function (form, valid) {
			//returns the valid status
			return valid;
		};
		opts['promptPosition'] = "bottomRight";
		container.validationEngine(opts);
		app.showSelect2ElementView(container.find('select'));
		this.registerCancelStepClickEvent(container);
		this.registerCodeMirror();
		this.registerEditorToolbarEvents();
	}
});

jQuery(function () {
	var form = jQuery('#pdf_step3');
	if (!form.length || form.data('codeMirrorInitialized') || typeof Settings_Template_Edit3_Js === 'undefined') {
		return;
	}
	var stepInstance = new Settings_Template_Edit3_Js();
	stepInstance.initialize(form);
	stepInstance.registerCodeMirror();
	stepInstance.registerEditorToolbarEvents();
	form.off('submit.pdfCodeMirrorFallback').on('submit.pdfCodeMirrorFallback', function (e) {
		e.preventDefault();
		stepInstance.syncCodeMirrorEditors();
		stepInstance.submit().then(function (data) {
			var currentContainer = form.closest('.pdfTemplateContents');
			currentContainer.replaceWith(data);
			var editInstance = new Settings_Template_Edit_Js();
			editInstance.initiate(jQuery('.pdfTemplateContents').first());
		});
	});
});
