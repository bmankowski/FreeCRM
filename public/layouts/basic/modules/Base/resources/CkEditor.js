/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 *************************************************************************************/
jQuery.Class("Vtiger_CkEditor_Js", {
	_languagePackPromises: {},
	_basePathFixed: false,

	fixBasePath: function () {
		if (typeof CKEDITOR === 'undefined') {
			return;
		}
		if (Vtiger_CkEditor_Js._basePathFixed) {
			return;
		}
		var scripts = document.getElementsByTagName('script');
		for (var i = 0; i < scripts.length; i++) {
			var match = scripts[i].src.match(/^(.*\/libraries\/jquery\/ckeditor\/)ckeditor(?:\.min)?\.js(?:\?.*)?$/i);
			if (match) {
				CKEDITOR.basePath = match[1];
				Vtiger_CkEditor_Js._basePathFixed = true;
				return;
			}
		}
		CKEDITOR.basePath = 'libraries/jquery/ckeditor/';
		Vtiger_CkEditor_Js._basePathFixed = true;
	},
}, {

	/*
	 *Function to set the textArea element 
	 */
	setElement: function (element) {
		this.element = element;
		return this;
	},

	/*
	 *Function to get the textArea element
	 */
	getElement: function () {
		return this.element;
	},

	/*
	 * Function to return Element's id atrribute value
	 */
	getElementId: function () {
		var element = this.getElement();
		return element.attr('id');
	},
	/*
	 * Function to get the instance of ckeditor
	 */

	getCkEditorInstanceFromName: function () {
		var elementName = this.getElementId();
		return CKEDITOR.instances[elementName];
	},

	resolveCkEditorLanguage: function (preferred) {
		var lang = preferred || jQuery('body').data('language') || '';
		var short = lang.split('_')[0] || 'en';
		if (!CKEDITOR.lang.languages[short]) {
			short = 'en';
		}
		return short;
	},

	ensureLanguagePack: function (lang, callback) {
		var self = this;
		Vtiger_CkEditor_Js.fixBasePath();
		lang = self.resolveCkEditorLanguage(lang);
		if (CKEDITOR.lang[lang] && CKEDITOR.lang[lang].editor) {
			CKEDITOR.lang[lang].dir = CKEDITOR.lang.rtl[lang] ? 'rtl' : 'ltr';
			callback();
			return;
		}
		var promises = Vtiger_CkEditor_Js._languagePackPromises;
		if (!promises[lang]) {
			promises[lang] = jQuery.getScript(CKEDITOR.getUrl('lang/' + lang + '.js'));
		}
		promises[lang].done(function () {
			if (CKEDITOR.lang[lang] && CKEDITOR.lang[lang].editor) {
				CKEDITOR.lang[lang].dir = CKEDITOR.lang.rtl[lang] ? 'rtl' : 'ltr';
				callback();
				return;
			}
			delete promises[lang];
			if (lang !== 'en') {
				Vtiger_CkEditor_Js.prototype.ensureLanguagePack.call(self, 'en', callback);
			} else {
				callback();
			}
		}).fail(function () {
			delete promises[lang];
			if (lang !== 'en') {
				Vtiger_CkEditor_Js.prototype.ensureLanguagePack.call(self, 'en', callback);
			} else {
				callback();
			}
		});
	},

	/***
	 * Function to get the plain text
	 */
	getPlainText: function () {
		var ckEditorInstnace = this.getCkEditorInstanceFromName();
		return ckEditorInstnace.document.getBody().getText();
	},
	/*
	 * Function to load CkEditor
	 * @params : element: element on which CkEditor has to be loaded, config: custom configurations for ckeditor
	 */
	loadCkEditor: function (element, customConfig) {

		this.setElement(element);
		var instance = this.getCkEditorInstanceFromName();
		var elementName = this.getElementId();
		if (!elementName) {
			return;
		}
		var config = {
			on: {
				instanceReady: function (evt) {
					var editor = evt.editor;
					editor.on('blur', function () {
						editor.updateElement();
					});
				}}
		};

		if (typeof customConfig != 'undefined') {
			config = jQuery.extend(config, customConfig);
		}
		config.language = this.resolveCkEditorLanguage(config.language);
		this.ensureLanguagePack(config.language, function () {
			if (instance) {
				instance.destroy(true);
			}
			CKEDITOR.replace(elementName, config);
		});
	},

	/*
	 * Function to load contents in ckeditor textarea
	 * @params : textArea Element,contents ;
	 */
	loadContentsInCkeditor: function (contents) {
		var editor = this.getCkEditorInstanceFromName();
		var editorData = editor.getData();
		var replaced_text = editorData.replace(editorData, contents);
		editor.setData(replaced_text);
	}
});
