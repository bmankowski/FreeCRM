/**
 * FreeCRM - Customer Relationship Management System
 *
 * Shared preview and help toolbar for HTML template editors.
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 */
/* global app, jQuery */
var FreeCRM_TemplateEditor_Js = {
	popupWindow: null,
	popupWindowName: 'FreeCRMTemplatePreview',
	previewCssHref: '/layouts/basic/resources/FreeCRMTemplate.css?v=docDefaults2',

	expandDynamicElements: function (html, container) {
		var data = container.find('.js-dynamic-elements-json').first().val() || '[]';
		var elements = [];
		try {
			elements = JSON.parse(data);
		} catch (e) {
			app.errorLog(e);
			return html || '';
		}
		var expanded = html || '';
		for (var pass = 0; pass < 10; pass++) {
			var changed = false;
			expanded = expanded.replace(/\$\(dynamic : ([a-zA-Z0-9_]+)\)\$/g, function (match, code) {
				var content = '';
				jQuery.each(elements, function (index, element) {
					if (element.code === code) {
						if (element.type === 'PLL_DOCUMENT_LAYOUT') {
							content = element.layout_body || '';
						} else {
							content = element.content || '';
						}
						return false;
					}
				});
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

	buildPreviewDocument: function (bodyHtml, extraCss) {
		return '<!doctype html><html><head><meta charset="utf-8"><base href="/">' +
			'<link rel="stylesheet" href="' + this.previewCssHref + '">' +
			'<style>body{font-family:Arial,sans-serif;font-size:12px;padding:24px;color:#222;}' +
			'table{max-width:100%;}img{max-width:100%;}' + (extraCss || '') + '</style></head><body>' +
			(bodyHtml || '') + '</body></html>';
	},

	buildPreviewSection: function (label, html) {
		return '<section class="template-preview-section"><div class="template-preview-label">' + label +
			'</div>' + (html || '') + '</section>';
	},

	buildPreviewDocumentWithSections: function (sections) {
		var body = '';
		var sectionCss = '.template-preview-section{border:1px solid #ddd;margin-bottom:16px;padding:12px;}' +
			'.template-preview-label{background:#f5f5f5;border-bottom:1px solid #ddd;font-weight:bold;margin:-12px -12px 12px;padding:8px;}';
		jQuery.each(sections, function (index, section) {
			body += this.buildPreviewSection(section.label, section.html);
		}.bind(this));
		return this.buildPreviewDocument(body, sectionCss);
	},

	showPreviewIframe: function (scope, documentHtml) {
		var previewContainer = scope.find('.templateEditorPreview').first();
		var previewFrame = previewContainer.find('.templateEditorPreviewFrame').get(0);
		if (!previewFrame) {
			return;
		}
		previewContainer.removeClass('hide');
		if ('srcdoc' in previewFrame) {
			previewFrame.srcdoc = documentHtml;
			return;
		}
		var previewDocument = previewFrame.contentWindow.document;
		previewDocument.open();
		previewDocument.write(documentHtml);
		previewDocument.close();
	},

	showPreviewPopup: function (documentHtml) {
		var w = this.popupWindow;
		if (w && !w.closed) {
			try {
				w.document.open();
				w.document.write(documentHtml);
				w.document.close();
				w.focus();
				return;
			} catch (e) {
				app.errorLog(e);
				this.popupWindow = null;
			}
		}
		w = window.open('', this.popupWindowName, 'width=960,height=720,scrollbars=yes,resizable=yes');
		if (!w) {
			Vtiger_Helper_Js.showMessage({
				text: app.vtranslate('JS_TEMPLATE_PREVIEW_POPUP_BLOCKED', 'Vtiger')
			});
			return;
		}
		this.popupWindow = w;
		w.document.open();
		w.document.write(documentHtml);
		w.document.close();
		w.focus();
	},

	refreshPreviewPopupIfOpen: function (documentHtml) {
		var w = this.popupWindow;
		if (!w || w.closed) {
			return;
		}
		try {
			w.document.open();
			w.document.write(documentHtml);
			w.document.close();
		} catch (e) {
			app.errorLog(e);
			this.popupWindow = null;
		}
	},

	registerToolbar: function (scope, config) {
		config = config || {};
		var self = this;
		scope.off('click.templateEditorHelp').on('click.templateEditorHelp', '.js-template-editor-toggle-help', function () {
			scope.find('.templateEditorHelp').first().toggleClass('hide');
		});
		scope.off('click.templateEditorPreview').on('click.templateEditorPreview', '.js-template-editor-preview', function () {
			if (typeof config.getPreviewDocumentHtml !== 'function') {
				return;
			}
			var documentHtml = config.getPreviewDocumentHtml();
			if (config.previewDisplay === 'popup') {
				self.showPreviewPopup(documentHtml);
			} else {
				self.showPreviewIframe(scope, documentHtml);
			}
		});
	}
};
