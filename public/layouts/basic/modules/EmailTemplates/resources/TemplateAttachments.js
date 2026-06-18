'use strict';

var EmailTemplates_TemplateAttachments_Js = {
	init: function (form) {
		this.form = form;
		this.root = form.find('.js-emailtemplate-attachments');
		if (!this.root.length) {
			return;
		}
		this.limits = this.root.data('mailAttachmentLimits') || {};
		this.fieldWrap = this.root.find('.js-emailtemplate-attachment-field');
		this.selectElement = this.root.find('.js-emailtemplate-attachment-select');
		this.saveFirst = this.root.find('.js-emailtemplate-attachments-save-first');
		this.uploadBtn = this.root.find('.js-emailtemplate-attachment-upload');
		this.templateId = parseInt(form.find('[name="record"]').val(), 10) || 0;
		this.syncing = false;
		this.bindEvents();
		this.bindSelect2Events();
		this.refreshState();
	},

	bindEvents: function () {
		var self = this;
		this.uploadBtn.off('click.templateAttach').on('click.templateAttach', function () {
			self.openUploadModal();
		});
	},

	bindSelect2Events: function () {
		var self = this;
		var select = this.selectElement;

		this.fieldWrap.on('mousedown.templateAttach click.templateAttach', '.select2-selection__choice__remove', function (e) {
			e.preventDefault();
			e.stopPropagation();
			select.data('unselecting', true);
			window.setTimeout(function () {
				select.removeData('unselecting');
			}, 250);
		});

		select.on('select2:opening.templateAttach', function (e) {
			e.preventDefault();
			if (select.data('unselecting')) {
				return;
			}
			if (select.prop('disabled')) {
				return;
			}
			self.openSelectPopup();
		});

		this.fieldWrap.on('focusin.templateAttach click.templateAttach', '.select2-search__field', function (e) {
			if (select.data('unselecting')) {
				return;
			}
			if (select.prop('disabled')) {
				return;
			}
			e.preventDefault();
			self.openSelectPopup();
		});

		select.on('select2:unselect.templateAttach', function (e) {
			if (self.syncing) {
				return;
			}
			var documentId = parseInt(e.params.data.id, 10);
			if (documentId > 0) {
				self.unlink(documentId);
			}
		});
	},

	refreshState: function () {
		if (this.templateId <= 0) {
			this.saveFirst.removeClass('hide');
			this.uploadBtn.prop('disabled', true);
			this.selectElement.prop('disabled', true);
			this.renderList([]);
			return;
		}
		this.saveFirst.addClass('hide');
		this.uploadBtn.prop('disabled', false);
		this.selectElement.prop('disabled', false);
		this.loadList();
	},

	loadList: function () {
		var self = this;
		AppConnector.request({
			module: 'EmailTemplates',
			action: 'TemplateAttachment',
			mode: 'list',
			templateId: this.templateId
		}).then(function (response) {
			var result = response && response.result ? response.result : {};
			if (result.limits) {
				self.limits = result.limits;
			}
			self.renderList(result.items || []);
		});
	},

	renderList: function (items) {
		var self = this;
		var select = this.selectElement;
		this.syncing = true;

		if (select.hasClass('select2-hidden-accessible')) {
			select.select2('destroy');
		}

		select.empty();
		items.forEach(function (item) {
			var name = item.name || 'Document';
			var option = new Option(name, String(item.id), true, true);
			if (item.hasFile === false) {
				option.className = 'js-emailtemplate-attachment-missing';
				option.title = app.vtranslate('LBL_ATTACHMENT_FILE_MISSING', 'EmailTemplates');
			}
			select.append(option);
		});

		this.initSelect2();
		this.syncing = false;
	},

	initSelect2: function () {
		var select = this.selectElement;
		app.showSelect2ElementView(select, {
			minimumResultsForSearch: Infinity,
			closeOnSelect: false,
			tags: false,
			placeholder: app.vtranslate('JS_SELECT_SOME_OPTIONS'),
			templateSelection: function (data, container) {
				if (data.element && data.element.className) {
					jQuery(container).addClass(data.element.className);
				}
				if (data.element && data.element.title) {
					jQuery(container).attr('title', data.element.title);
				}
				return data.text;
			}
		});
	},

	openUploadModal: function () {
		var self = this;
		Vtiger_Index_Js.massAddDocuments(
			'index.php?module=Documents&view=MassAddDocuments',
			{
				createmode: 'link',
				return_module: 'EmailTemplates',
				return_id: this.templateId,
				onSuccess: function () {
					self.loadList();
				}
			}
		);
	},

	openSelectPopup: function () {
		var self = this;
		var popupInstance = Vtiger_Popup_Js.getInstance();
		popupInstance.show({
			module: 'Documents',
			src_module: 'EmailTemplates',
			src_record: this.templateId,
			multi_select: true
		}, function (responseString) {
			var responseData = JSON.parse(responseString);
			var ids = Object.keys(responseData);
			if (!ids.length) {
				return;
			}
			AppConnector.request({
				module: 'EmailTemplates',
				action: 'TemplateAttachment',
				mode: 'link',
				templateId: self.templateId,
				documentIds: JSON.stringify(ids)
			}).then(function (response) {
				if (response && response.error) {
					self.notifyError(response.error);
					return;
				}
				var result = response && response.result ? response.result : {};
				self.renderList(result.items || []);
			}, function (error) {
				self.notifyError(error);
			});
		});
	},

	unlink: function (documentId) {
		var self = this;
		AppConnector.request({
			module: 'EmailTemplates',
			action: 'TemplateAttachment',
			mode: 'unlink',
			templateId: this.templateId,
			documentId: documentId
		}).then(function (response) {
			if (response && response.error) {
				self.notifyError(response.error);
				self.loadList();
				return;
			}
			var result = response && response.result ? response.result : {};
			self.renderList(result.items || []);
		}, function (error) {
			self.notifyError(error);
			self.loadList();
		});
	},

	notifyError: function (error) {
		var message = error;
		if (error && error.message) {
			message = error.message;
		}
		if (typeof message === 'object' && message.message) {
			message = message.message;
		}
		Vtiger_Helper_Js.showPnotify({
			text: typeof message === 'string' ? message : app.vtranslate('JS_ERROR'),
			type: 'error',
			animation: 'show'
		});
	}
};
