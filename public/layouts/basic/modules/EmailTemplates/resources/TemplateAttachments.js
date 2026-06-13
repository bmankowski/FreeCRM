'use strict';

var EmailTemplates_TemplateAttachments_Js = {
	init: function (form) {
		this.form = form;
		this.root = form.find('.js-emailtemplate-attachments');
		if (!this.root.length) {
			return;
		}
		this.limits = this.root.data('mailAttachmentLimits') || {};
		this.list = this.root.find('.js-emailtemplate-attachment-list');
		this.saveFirst = this.root.find('.js-emailtemplate-attachments-save-first');
		this.uploadBtn = this.root.find('.js-emailtemplate-attachment-upload');
		this.selectBtn = this.root.find('.js-emailtemplate-attachment-select');
		this.templateId = parseInt(form.find('[name="record"]').val(), 10) || 0;
		this.bindEvents();
		this.refreshState();
	},

	bindEvents: function () {
		var self = this;
		this.uploadBtn.off('click.templateAttach').on('click.templateAttach', function () {
			self.openUploadModal();
		});
		this.selectBtn.off('click.templateAttach').on('click.templateAttach', function () {
			self.openSelectPopup();
		});
		this.root.off('click.templateAttach', '.js-emailtemplate-attachment-remove').on('click.templateAttach', '.js-emailtemplate-attachment-remove', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var documentId = parseInt(jQuery(this).attr('data-document-id'), 10);
			if (documentId > 0) {
				self.unlink(documentId);
			}
		});
	},

	refreshState: function () {
		if (this.templateId <= 0) {
			this.saveFirst.removeClass('hide');
			this.uploadBtn.prop('disabled', true);
			this.selectBtn.prop('disabled', true);
			this.list.empty();
			return;
		}
		this.saveFirst.addClass('hide');
		this.uploadBtn.prop('disabled', false);
		this.selectBtn.prop('disabled', false);
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
		this.list.empty();
		items.forEach(function (item) {
			var name = item.name || 'Document';
			var sizeLabel = self.formatSize(item.size || 0);
			var missing = item.hasFile === false;
			var fileClass = missing ? ' js-emailtemplate-attachment-chip-file--missing' : '';
			var warning = missing ? ' title="' + app.vtranslate('LBL_ATTACHMENT_FILE_MISSING', 'EmailTemplates') + '"' : '';
			var html = '<div class="btn-group" data-document-id="' + item.id + '">' +
				'<span class="btn btn-default btn-xs js-emailtemplate-attachment-chip-file' + fileClass + '" disabled="disabled"' + warning + '>' +
				'<span class="js-emailtemplate-attachment-chip-name">' + jQuery('<div>').text(name).html() + '</span>' +
				'<small class="text-muted">' + sizeLabel + '</small>' +
				'</span>' +
				'<button type="button" class="btn btn-default btn-xs js-emailtemplate-attachment-remove" data-document-id="' + item.id + '" title="' +
				app.vtranslate('LBL_DELETE', 'Vtiger') + '">' +
				'<span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>' +
				'</div>';
			self.list.append(html);
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
				return;
			}
			var result = response && response.result ? response.result : {};
			self.renderList(result.items || []);
		}, function (error) {
			self.notifyError(error);
		});
	},

	formatSize: function (bytes) {
		var n = parseInt(bytes, 10) || 0;
		if (n < 1024) {
			return n + ' B';
		}
		if (n < 1048576) {
			return (n / 1024).toFixed(1) + ' KB';
		}
		return (n / 1048576).toFixed(1) + ' MB';
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
