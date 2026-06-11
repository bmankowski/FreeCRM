'use strict';

var Mail_ComposeAttachments_Js = {
	tokens: [],
	totalBytes: 0,

	init: function (modalContainer, postData) {
		this.container = modalContainer;
		this.postData = postData || {};
		this.root = modalContainer.find('.js-mail-attachments');
		if (!this.root.length) {
			return;
		}
		this.limits = this.root.data('mailAttachmentLimits') || {};
		this.dropzone = this.root.find('.js-mail-attachment-dropzone');
		this.fileInput = this.root.find('.js-mail-attachment-input');
		this.list = this.root.find('.js-mail-attachment-list');
		this.templateWrap = this.root.find('.js-mail-template-attachments-wrap');
		this.templateList = this.root.find('.js-mail-template-attachment-list');
		this.tokens = [];
		this.totalBytes = 0;
		this.bindEvents();
		this.loadTemplateAttachments();
	},

	getTokens: function () {
		return this.tokens.slice();
	},

	bindEvents: function () {
		var self = this;
		this.dropzone.off('click.mailAttach').on('click.mailAttach', function (e) {
			if (e.target === self.fileInput[0]) {
				return;
			}
			self.fileInput.trigger('click');
		});
		this.dropzone.on('dragenter dragover', function (e) {
			e.preventDefault();
			e.stopPropagation();
			self.dropzone.addClass('dragover');
		});
		this.dropzone.on('dragleave dragend drop', function (e) {
			e.preventDefault();
			e.stopPropagation();
			self.dropzone.removeClass('dragover');
		});
		this.dropzone.on('drop', function (e) {
			var files = e.originalEvent.dataTransfer.files;
			if (files && files.length) {
				self.uploadFiles(files);
			}
		});
		this.fileInput.off('change.mailAttach').on('change.mailAttach', function () {
			var files = self.fileInput[0].files;
			if (files && files.length) {
				self.uploadFiles(files);
			}
			self.fileInput.val('');
		});
		this.container.find('#field, #template').off('change.mailAttachTemplate').on('change.mailAttachTemplate', function () {
			self.loadTemplateAttachments();
		});
		this.list.off('click.mailAttachRemove').on('click.mailAttachRemove', '.js-mail-attachment-remove', function (e) {
			e.preventDefault();
			var token = jQuery(this).data('token');
			self.removeToken(token);
		});
	},

	uploadFiles: function (files) {
		for (var i = 0; i < files.length; i++) {
			this.uploadOne(files[i]);
		}
	},

	uploadOne: function (file) {
		if (this.tokens.length >= (this.limits.maxFiles || 10)) {
			this.notify('LBL_MAIL_ATTACHMENT_MAX_FILES', 'error');
			return;
		}
		var maxFileBytes = this.limits.maxFileBytes || 0;
		if (maxFileBytes > 0 && file.size > maxFileBytes) {
			this.notify('LBL_MAIL_ATTACHMENT_TOO_LARGE', 'error');
			return;
		}
		var maxTotalBytes = this.limits.maxTotalBytes || 0;
		if (maxTotalBytes > 0 && this.totalBytes + file.size > maxTotalBytes) {
			this.notify('LBL_MAIL_ATTACHMENT_TOTAL_TOO_LARGE', 'error');
			return;
		}
		var formData = new FormData();
		formData.append('file', file);
		formData.append('module', 'Mail');
		formData.append('action', 'UploadComposeAttachment');
		if (this.postData.sourceModule) {
			formData.append('sourceModule', this.postData.sourceModule);
		}
		var progress = jQuery.progressIndicator({blockInfo: {enabled: true}});
		var self = this;
		AppConnector.request({
			url: 'index.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false
		}).then(function (response) {
			progress.progressIndicator({mode: 'hide'});
			var result = response && response.result ? response.result : null;
			if (!result || !result.token) {
				self.notify('LBL_MAIL_ATTACHMENT_UPLOAD_FAILED', 'error');
				return;
			}
			self.tokens.push(result.token);
			self.totalBytes += parseInt(result.size, 10) || 0;
			self.appendListItem(result.token, result.name, result.size);
		}, function () {
			progress.progressIndicator({mode: 'hide'});
			self.notify('LBL_MAIL_ATTACHMENT_UPLOAD_FAILED', 'error');
		});
	},

	removeToken: function (token) {
		var self = this;
		var idx = this.tokens.indexOf(token);
		if (idx < 0) {
			return;
		}
		var size = parseInt(this.list.find('[data-token="' + token + '"]').data('size'), 10) || 0;
		AppConnector.request({
			module: 'Mail',
			action: 'DeleteComposeAttachment',
			token: token
		}).then(function () {
			self.tokens.splice(idx, 1);
			self.totalBytes = Math.max(0, self.totalBytes - size);
			self.list.find('[data-token="' + token + '"]').remove();
		});
	},

	appendListItem: function (token, name, size) {
		var sizeLabel = this.formatSize(size);
		var html = '<li data-token="' + token + '" data-size="' + (size || 0) + '">' +
			'<span class="mail-compose-attachment-name">' + jQuery('<div>').text(name).html() + ' (' + sizeLabel + ')</span>' +
			'<button type="button" class="btn btn-danger btn-xs js-mail-attachment-remove" data-token="' + token + '">' +
			'<span class="glyphicon glyphicon-trash"></span></button></li>';
		this.list.append(html);
	},

	loadTemplateAttachments: function () {
		var self = this;
		var field = this.container.find('#field').val();
		var template = this.container.find('#template').val();
		if (!template || !field) {
			this.templateWrap.addClass('hide');
			this.templateList.empty();
			return;
		}
		var previewData = jQuery.extend({}, this.postData, {
			field: field,
			template: template,
			action: 'Mail',
			mode: 'previewMail'
		});
		delete previewData.view;
		AppConnector.request(previewData).then(function (response) {
			var result = response && response.result ? response.result : {};
			var items = result.templateAttachments || [];
			self.templateList.empty();
			if (!items.length) {
				self.templateWrap.addClass('hide');
				return;
			}
			items.forEach(function (item) {
				var name = item.name || 'Document';
				self.templateList.append(
					'<li><span class="mail-compose-attachment-name">' +
					jQuery('<div>').text(name).html() + '</span></li>'
				);
			});
			self.templateWrap.removeClass('hide');
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

	notify: function (key, type) {
		Vtiger_Helper_Js.showPnotify({
			text: app.vtranslate(key),
			type: type,
			animation: 'show'
		});
	}
};
