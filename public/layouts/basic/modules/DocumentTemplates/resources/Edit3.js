/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */
DocumentTemplates_Edit_Js("DocumentTemplates_Edit3_Js", {}, {
	step3Container: false,
	advanceFilterInstance: false,
	codeMirrorInstances: {},
	activeEditorId: 'body_content',
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
						Vtiger_Helper_Js.showMessage({text: app.vtranslate('JS_TEMPLATE_SAVED_SUCCESSFULLY')});

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
						Vtiger_Helper_Js.showMessage({text: app.vtranslate('JS_TEMPLATE_SAVED_SUCCESSFULLY')});
						FreeCRM_TemplateEditor_Js.refreshPreviewPopupIfOpen(thisInstance.buildPreviewHtml());
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
		return FreeCRM_TemplateEditor_Js.expandDynamicElements(html, this.getContainer());
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
			Vtiger_Helper_Js.showMessage({text: 'Brak lokalnej biblioteki formatowania HTML.'});
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
	buildPreviewHtml: function () {
		var container = this.getContainer();
		var editor = FreeCRM_TemplateEditor_Js;
		var header = editor.expandDynamicElements(this.getEditorValue('header_content'), container);
		var body = editor.expandDynamicElements(this.getEditorValue('body_content'), container);
		var footer = editor.expandDynamicElements(this.getEditorValue('footer_content'), container);
		return editor.buildPreviewDocumentWithSections([
			{label: app.vtranslate('LBL_DOCUMENT_HEADER', 'DocumentTemplates'), html: header},
			{label: app.vtranslate('LBL_DOCUMENT_BODY', 'DocumentTemplates'), html: body},
			{label: app.vtranslate('LBL_DOCUMENT_FOOTER', 'DocumentTemplates'), html: footer}
		]);
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
		FreeCRM_TemplateEditor_Js.registerToolbar(container, {
			previewDisplay: 'popup',
			getPreviewDocumentHtml: function () {
				return thisInstance.buildPreviewHtml();
			}
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
					},
					'Shift-Ctrl-F': function () {
						thisInstance.formatHtmlEditors();
					},
					'Shift-Cmd-F': function () {
						thisInstance.formatHtmlEditors();
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
	if (!form.length || form.data('codeMirrorInitialized') || typeof DocumentTemplates_Edit3_Js === 'undefined') {
		return;
	}
	var stepInstance = new DocumentTemplates_Edit3_Js();
	stepInstance.initialize(form);
	stepInstance.registerCodeMirror();
	stepInstance.registerEditorToolbarEvents();
	form.off('submit.pdfCodeMirrorFallback').on('submit.pdfCodeMirrorFallback', function (e) {
		e.preventDefault();
		stepInstance.syncCodeMirrorEditors();
		stepInstance.submit().then(function (data) {
			var currentContainer = form.closest('.pdfTemplateContents');
			currentContainer.replaceWith(data);
			var editInstance = new DocumentTemplates_Edit_Js();
			editInstance.initiate(jQuery('.pdfTemplateContents').first());
		});
	});
});
