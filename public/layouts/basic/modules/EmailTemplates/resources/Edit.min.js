/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */
Vtiger_Edit_Js("EmailTemplates_Edit_Js", {}, {
	codeMirrorInstance: false,

	getTargetModuleName: function (form) {
		if (typeof form == 'undefined') {
			form = this.getForm();
		}
		var moduleNameField = form.find('[name="module_name"]');
		if (moduleNameField.length) {
			return moduleNameField.val();
		}
		return form.find('[name="module"]').val();
	},
	loadVariablePanel: function (form) {
		var thisInstance = this;
		if (typeof form == 'undefined') {
			form = this.getForm();
		}
		var panel = form.find('#variablePanel');
		panel.progressIndicator();
		AppConnector.request({
			module: 'EmailTemplates',
			record: app.getRecordId(),
			view: 'VariablePanel',
			type: 'mail',
			selectedModule: thisInstance.getTargetModuleName(form)
		}).then(function (response) {
			panel.html(response);
			thisInstance.afterLoadVariablePanel(panel);
		}, function (data, err) {
			panel.progressIndicator({mode: 'hide'});
		});
	},
	afterLoadVariablePanel: function (panel) {
		var form = this.getForm();
		var thisInstance = this;
		app.showSelect2ElementView(panel.find('select.select2'));
		this.registerDynamicElementEvents(form, panel);
		app.registerCopyClipboard('#variablePanel .clipboard');
		FreeCRM_TemplateEditor_Js.registerToolbar(panel, {
			previewDisplay: 'inline',
			getPreviewDocumentHtml: function () {
				var body = FreeCRM_TemplateEditor_Js.expandDynamicElements(
					thisInstance.getContentValue(form),
					panel
				);
				return FreeCRM_TemplateEditor_Js.buildPreviewDocument(body);
			}
		});
	},
	getDynamicElements: function (form) {
		var data = form.find('#variablePanel .js-dynamic-elements-json').val() || '[]';
		try {
			return JSON.parse(data);
		} catch (e) {
			app.errorLog(e);
			return [];
		}
	},
	getDynamicElementContent: function (form, code) {
		var content = '';
		jQuery.each(this.getDynamicElements(form), function (index, element) {
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
	expandDynamicElements: function (form, html) {
		return FreeCRM_TemplateEditor_Js.expandDynamicElements(html, form.find('#variablePanel'));
	},
	insertDynamicElement: function (form, code) {
		if (!code) {
			return;
		}
		this.insertContentValue(form, '\n$(dynamic : ' + code + ')$\n');
		this.formatHtmlEditor(form);
	},
	registerDynamicElementEvents: function (form, panel) {
		var thisInstance = this;
		var scope = panel || form.find('#variablePanel');
		scope.off('change.templateDynamicAliasVariable').on('change.templateDynamicAliasVariable', '#templateDynamicAliasVariable', function () {
			var select = jQuery(this);
			var code = select.val();
			if (!code) {
				return;
			}
			thisInstance.insertDynamicElement(form, code);
			var firstCode = select.find('option').first().val();
			select.val(firstCode).trigger('change.select2');
		});
	},
	registerVariablePanelEvent: function (form) {
		var thisInstance = this;
		if (typeof form == 'undefined') {
			form = this.getForm();
		}
		form.find('.blockContainer[data-label="LBL_CONTENT_MAIL"] .blockContent').prepend('<div id="variablePanel" class="col-md-12 paddingLRZero borderBottom bc-gray-lighter"></div>');
		thisInstance.loadVariablePanel(form);
		form.find('[name="module_name"]').on('change', function (e) {
			thisInstance.loadVariablePanel(form);
		});
	},
	getContentTextarea: function (form) {
		return form.find('[name="content"]').first();
	},
	getContentValue: function (form) {
		if (this.codeMirrorInstance) {
			return this.codeMirrorInstance.getValue();
		}
		return this.getContentTextarea(form).val();
	},
	setContentValue: function (form, value) {
		if (this.codeMirrorInstance) {
			this.codeMirrorInstance.setValue(value);
			this.codeMirrorInstance.refresh();
			return;
		}
		this.getContentTextarea(form).val(value);
	},
	insertContentValue: function (form, value) {
		if (this.codeMirrorInstance) {
			this.codeMirrorInstance.replaceSelection(value);
			this.codeMirrorInstance.focus();
			this.codeMirrorInstance.refresh();
			return;
		}
		var textarea = this.getContentTextarea(form);
		textarea.val((textarea.val() || '') + value);
	},
	syncCodeMirrorEditor: function () {
		if (this.codeMirrorInstance) {
			this.codeMirrorInstance.save();
		}
	},
	runEditorCommand: function (commandName) {
		if (!this.codeMirrorInstance || typeof this.codeMirrorInstance.execCommand !== 'function') {
			return;
		}
		this.codeMirrorInstance.focus();
		this.codeMirrorInstance.execCommand(commandName);
	},
	getTemplateSnippet: function (snippetKey) {
		var snippets = {
			emailTemplate: [
				'<div class="fc-doc container">',
				'  <div class="card mb-3">',
				'    <div class="card-header">Wiadomość</div>',
				'    <div class="card-body">',
				'      <h1 class="h3 mb-2">Tytuł wiadomości</h1>',
				'      <p>Dzień dobry,</p>',
				'      <p>treść wiadomości z wykorzystaniem zmiennych FreeCRM.</p>',
				'      <p class="mb-1">Pozdrawiam,</p>',
				'      <p class="font-bold">$(user : full_name)$</p>',
				'    </div>',
				'  </div>',
				'</div>'
			].join('\n'),
			pdfTemplate: [
				'<div class="fc-doc container">',
				'  <div class="row mb-3">',
				'    <div class="col-8">',
				'      <h1 class="h3 mb-1">Tytuł dokumentu</h1>',
				'      <p class="text-muted">Numer: $(record : number)$</p>',
				'    </div>',
				'    <div class="col-4 text-right">',
				'      <img class="img-logo" src="$(organization : logo)$" alt="Logo">',
				'    </div>',
				'  </div>',
				'',
				'  <div class="card mb-3">',
				'    <div class="card-header">Dane podstawowe</div>',
				'    <div class="card-body">',
				'      <table class="table table-sm table-bordered">',
				'        <tbody>',
				'          <tr>',
				'            <th>Etykieta</th>',
				'            <td>$(record : field_name)$</td>',
				'          </tr>',
				'        </tbody>',
				'      </table>',
				'    </div>',
				'  </div>',
				'</div>'
			].join('\n')
		};
		return snippets[snippetKey] || '';
	},
	registerTemplateToolbar: function (form) {
		var textarea = this.getContentTextarea(form);
		if (!textarea.length || form.find('.emailTemplateToolbar').length) {
			return;
		}
		var toolbar = jQuery(
			'<div class="emailTemplateToolbar text-right">' +
				'<select class="form-control input-sm js-email-template-snippet">' +
					'<option value="">' + app.vtranslate('JS_INSERT_TEMPLATE_BLOCK') + '</option>' +
					'<option value="emailTemplate">' + app.vtranslate('JS_TEMPLATE_SNIPPET_EMAIL') + '</option>' +
					'<option value="pdfTemplate">' + app.vtranslate('JS_TEMPLATE_SNIPPET_PDF') + '</option>' +
				'</select> ' +
			'</div>'
		);
		textarea.closest('.fieldValue').before(toolbar);
	},
	registerCodeMirror: function (form) {
		var textarea = this.getContentTextarea(form);
		if (!textarea.length || typeof CodeMirror === 'undefined') {
			return;
		}
		if (typeof CKEDITOR !== 'undefined') {
			var editorId = textarea.attr('id');
			if (editorId && CKEDITOR.instances[editorId]) {
				CKEDITOR.instances[editorId].destroy(true);
			}
		}
		if (this.codeMirrorInstance) {
			this.codeMirrorInstance.toTextArea();
		}
		this.codeMirrorInstance = CodeMirror.fromTextArea(textarea.get(0), {
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
				'Ctrl-H': 'replace'
			}
		});
		this.codeMirrorInstance.setSize('100%', 420);
		textarea.validationEngine('detach');
	},
	formatHtmlEditor: function (form) {
		if (typeof html_beautify !== 'function') {
			return;
		}
		var value = this.getContentValue(form);
		if (jQuery.trim(value) === '') {
			return;
		}
		this.setContentValue(form, html_beautify(value, {
			indent_size: 2,
			indent_char: ' ',
			max_preserve_newlines: 2,
			preserve_newlines: true,
			wrap_line_length: 160
		}));
	},
	registerTemplateToolbarEvents: function (form) {
		var thisInstance = this;
		form.off('change.emailTemplateSnippet').on('change.emailTemplateSnippet', '.js-email-template-snippet', function () {
			var snippet = thisInstance.getTemplateSnippet(jQuery(this).val());
			if (snippet !== '') {
				thisInstance.insertContentValue(form, '\n' + snippet + '\n');
				thisInstance.formatHtmlEditor(form);
			}
			jQuery(this).val('');
		});
	},
	registerSubmitEvent: function () {
		var thisInstance = this;
		var editViewForm = this.getForm();
		editViewForm.off('submit').on('submit', function (e) {
			thisInstance.syncCodeMirrorEditor();
			if (typeof editViewForm.data('submit') != 'undefined') {
				e.preventDefault();
				return false;
			}
			document.progressLoader = jQuery.progressIndicator({
				'message': app.vtranslate('JS_SAVE_LOADER_INFO'),
				'position': 'html',
				'blockInfo': {
					'enabled': true
				}
			});
			if (editViewForm.validationEngine('validate')) {
				editViewForm.data('submit', 'true');
				var recordPreSaveEvent = jQuery.Event(Vtiger_Edit_Js.recordPreSave);
				editViewForm.trigger(recordPreSaveEvent, {'value': 'edit'});
				if (recordPreSaveEvent.isDefaultPrevented()) {
					document.progressLoader.progressIndicator({'mode': 'hide'});
					editViewForm.removeData('submit');
					e.preventDefault();
					return false;
				}
			} else {
				document.progressLoader.progressIndicator({'mode': 'hide'});
				editViewForm.removeData('submit');
				app.formAlignmentAfterValidation(editViewForm);
				e.preventDefault();
				return false;
			}
		});
	},
	registerBasicEvents: function (container) {
		this._super(container);
		this.registerVariablePanelEvent(container);
		this.registerTemplateToolbar(container);
		this.registerCodeMirror(container);
		this.registerTemplateToolbarEvents(container);
	},
});
