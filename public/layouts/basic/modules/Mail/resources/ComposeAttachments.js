'use strict';

var Mail_ComposeAttachments_Js = {
	tokens: [],
	totalBytes: 0,
	uploadingCount: 0,
	modalDragDepth: 0,

	init: function (modalContainer, postData) {
		this.container = modalContainer;
		this.postData = postData || {};
		this.root = modalContainer.find('.js-mail-attachments');
		if (!this.root.length) {
			return;
		}
		this.limits = this.root.data('mailAttachmentLimits') || {};
		this.addBtn = this.root.find('.js-mail-attachment-add');
		this.addWrap = this.root.find('.js-mail-attachment-add-wrap');
		this.fileInput = this.root.find('.js-mail-attachment-input');
		this.toolbar = this.root.find('.js-mail-attachment-toolbar');
		this.templateWrap = this.root.find('.js-mail-template-attachments-wrap');
		this.templateList = this.root.find('.js-mail-template-attachment-list');
		this.tokens = [];
		this.totalBytes = 0;
		this.uploadingCount = 0;
		this.modalDragDepth = 0;
		var self = this;
		this.resetStaging().then(function () {
			self.bindEvents();
			self.loadTemplateAttachments();
		});
	},

	resetStaging: function () {
		return AppConnector.request({
			module: 'Mail',
			action: 'ResetComposeAttachment'
		});
	},

	getTokens: function () {
		return this.tokens.slice();
	},

	bindEvents: function () {
		var self = this;
		this.addBtn.off('click.mailAttach').on('click.mailAttach', function (e) {
			e.preventDefault();
			self.fileInput.trigger('click');
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
		this.toolbar.off('click.mailAttachRemove').on('click.mailAttachRemove', '.js-mail-attachment-remove', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var token = jQuery(this).data('token');
			self.removeToken(token);
		});
		this.bindModalDragDrop();
		this.container.off('hidden.bs.modal.mailAttach').on('hidden.bs.modal.mailAttach', function () {
			AppConnector.request({
				module: 'Mail',
				action: 'ResetComposeAttachment'
			});
		});
	},

	bindModalDragDrop: function () {
		var self = this;
		this.container.off('.mailAttachModal');
		this.container.on('dragenter.mailAttachModal', function (e) {
			if (!self.isFileDrag(e)) {
				return;
			}
			self.modalDragDepth += 1;
			self.toolbar.addClass('js-mail-attachment-toolbar--drag');
		});
		this.container.on('dragleave.mailAttachModal', function (e) {
			if (!self.isFileDrag(e)) {
				return;
			}
			self.modalDragDepth = Math.max(0, self.modalDragDepth - 1);
			if (self.modalDragDepth === 0) {
				self.toolbar.removeClass('js-mail-attachment-toolbar--drag');
			}
		});
		this.container.on('dragover.mailAttachModal', function (e) {
			if (!self.isFileDrag(e)) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
		});
		this.container.on('drop.mailAttachModal', function (e) {
			self.modalDragDepth = 0;
			self.toolbar.removeClass('js-mail-attachment-toolbar--drag');
			if (!self.isFileDrag(e)) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			var files = e.originalEvent.dataTransfer.files;
			if (files && files.length) {
				self.uploadFiles(files);
			}
		});
	},

	isFileDrag: function (e) {
		var dt = e.originalEvent && e.originalEvent.dataTransfer;
		if (!dt || !dt.types) {
			return false;
		}
		var types = dt.types;
		if (typeof types.contains === 'function') {
			return types.contains('Files');
		}
		return types.indexOf('Files') !== -1;
	},

	uploadFiles: function (files) {
		var self = this;
		var chain = jQuery.when();
		for (var i = 0; i < files.length; i++) {
			(function (file) {
				chain = chain.then(function () {
					return self.uploadOne(file);
				});
			})(files[i]);
		}
	},

	uploadOne: function (file) {
		if (this.tokens.length >= (this.limits.maxFiles || 10)) {
			this.notify('LBL_MAIL_ATTACHMENT_MAX_FILES', 'error');
			return jQuery.Deferred().reject().promise();
		}
		var maxFileBytes = this.limits.maxFileBytes || 0;
		if (maxFileBytes > 0 && file.size > maxFileBytes) {
			this.notify('LBL_MAIL_ATTACHMENT_TOO_LARGE', 'error');
			return jQuery.Deferred().reject().promise();
		}
		var maxTotalBytes = this.limits.maxTotalBytes || 0;
		if (maxTotalBytes > 0 && this.totalBytes + file.size > maxTotalBytes) {
			this.notify('LBL_MAIL_ATTACHMENT_TOTAL_TOO_LARGE', 'error');
			return jQuery.Deferred().reject().promise();
		}
		var formData = new FormData();
		formData.append('file', file);
		formData.append('module', 'Mail');
		formData.append('action', 'UploadComposeAttachment');
		if (this.postData.sourceModule) {
			formData.append('sourceModule', this.postData.sourceModule);
		}
		var uploadId = 'upload-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
		var self = this;
		this.appendUploadingItem(uploadId, file.name, file.size);
		return AppConnector.request({
			url: 'index.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false
		}).then(function (response) {
			self.removeUploadingItem(uploadId);
			var result = response && response.result ? response.result : null;
			if (!result || !result.token) {
				self.notify('LBL_MAIL_ATTACHMENT_UPLOAD_FAILED', 'error');
				return;
			}
			self.tokens.push(result.token);
			self.totalBytes += parseInt(result.size, 10) || 0;
			self.appendListItem(result.token, result.name, result.size);
		}, function () {
			self.removeUploadingItem(uploadId);
			self.notify('LBL_MAIL_ATTACHMENT_UPLOAD_FAILED', 'error');
		});
	},

	removeToken: function (token) {
		var self = this;
		var idx = this.tokens.indexOf(token);
		if (idx < 0) {
			return;
		}
		var size = parseInt(this.toolbar.find('[data-token="' + token + '"]').data('size'), 10) || 0;
		AppConnector.request({
			module: 'Mail',
			action: 'DeleteComposeAttachment',
			token: token
		}).then(function () {
			self.tokens.splice(idx, 1);
			self.totalBytes = Math.max(0, self.totalBytes - size);
			self.toolbar.find('[data-token="' + token + '"]').remove();
		});
	},

	appendUploadingItem: function (uploadId, name, size) {
		this.uploadingCount += 1;
		var sizeLabel = this.formatSize(size);
		var html = '<div class="btn-group js-mail-attachment-uploading" data-upload-id="' + uploadId + '">' +
			'<span class="btn btn-default btn-xs js-mail-attachment-chip-file" disabled="disabled">' +
			'<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span> ' +
			'<span class="js-mail-attachment-chip-name">' + jQuery('<div>').text(name).html() + '</span>' +
			'<small class="text-muted">' + sizeLabel + '</small>' +
			'</span></div>';
		this.addWrap.before(html);
	},

	removeUploadingItem: function (uploadId) {
		this.toolbar.find('[data-upload-id="' + uploadId + '"]').remove();
		this.uploadingCount = Math.max(0, this.uploadingCount - 1);
	},

	appendListItem: function (token, name, size) {
		var sizeLabel = this.formatSize(size);
		var html = '<div class="btn-group" data-token="' + token + '" data-size="' + (size || 0) + '">' +
			'<span class="btn btn-default btn-xs js-mail-attachment-chip-file" disabled="disabled">' +
			'<span class="js-mail-attachment-chip-name">' + jQuery('<div>').text(name).html() + '</span>' +
			'<small class="text-muted">' + sizeLabel + '</small>' +
			'</span>' +
			'<button type="button" class="btn btn-default btn-xs js-mail-attachment-remove" data-token="' + token + '" title="' +
			app.vtranslate('LBL_DELETE') + '">' +
			'<span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>' +
			'</div>';
		this.addWrap.before(html);
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
					'<div class="btn-group">' +
					'<span class="btn btn-default btn-xs js-mail-attachment-chip-file" disabled="disabled">' +
					'<span class="js-mail-attachment-chip-name">' + jQuery('<div>').text(name).html() + '</span>' +
					'</span>' +
					'<span class="btn btn-default btn-xs" disabled="disabled" title="' + app.vtranslate('LBL_TEMPLATE_ATTACHMENTS') + '">' +
					'<span class="glyphicon glyphicon-lock" aria-hidden="true"></span></span>' +
					'</div>'
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
