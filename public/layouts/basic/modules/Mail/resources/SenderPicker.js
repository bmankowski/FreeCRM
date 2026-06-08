'use strict';

var Mail_SenderPicker_Js = {
	init: function (root, config) {
		this.root = root;
		this.config = config || {};
		this.templateSelect = root.find('#mail-template, #template');
		this.senderSelect = root.find('#mail-sender, #mailSender');
		if (!this.senderSelect.length) {
			return;
		}
		var self = this;
		this.templateSelect.on('change.mailSender', function () {
			self.refresh();
		});
		this.refresh();
	},
	refresh: function () {
		var opt = this.templateSelect.find('option:selected');
		var type = opt.data('sender-type') || 'system_smtp';
		var templateSmtpId = parseInt(opt.data('smtp-id'), 10) || 0;
		var defaultRef = opt.data('default-sender-ref') || '';
		var html = this.buildOptions(type, templateSmtpId);
		var current = this.senderSelect.val();
		this.senderSelect.html(html);
		if (current && this.senderSelect.find('option[value="' + current + '"]').length) {
			this.senderSelect.val(current);
		} else if (defaultRef && this.senderSelect.find('option[value="' + defaultRef + '"]').length) {
			this.senderSelect.val(defaultRef);
		} else {
			this.senderSelect.find('option:first').prop('selected', true);
		}
		var picker = this.root.find('.js-mail-sender-picker');
		if (html === '') {
			picker.addClass('hide');
		} else {
			picker.removeClass('hide');
		}
	},
	buildOptions: function (type, templateSmtpId) {
		var composeSenders = this.config.composeSenders || [];
		if (composeSenders.length) {
			return this.buildComposeSenderOptions(composeSenders);
		}

		var accounts = this.config.accounts || [];
		var smtpList = this.config.smtpList || {};
		var accountLabel = app.vtranslate('LBL_SENDER_ACCOUNT');
		var smtpLabel = app.vtranslate('LBL_SYSTEM_SMTP');
		var parts = [];

		if (type === 'user_account' || type === 'any') {
			if (accounts.length) {
				parts.push('<optgroup label="' + accountLabel + '">');
				accounts.forEach(function (acc) {
					parts.push(
						'<option value="account:' + acc.id + '">' +
						jQuery('<div>').text((acc.name || '') + ' (' + (acc.username || '') + ')').html() +
						'</option>'
					);
				});
				parts.push('</optgroup>');
			}
		}
		if (type === 'system_smtp' || type === 'any') {
			var smtpIds = Object.keys(smtpList);
			if (type === 'system_smtp' && templateSmtpId) {
				smtpIds = smtpIds.filter(function (id) {
					return parseInt(id, 10) === templateSmtpId;
				});
			}
			if (smtpIds.length) {
				parts.push('<optgroup label="' + smtpLabel + '">');
				smtpIds.forEach(function (id) {
					var smtp = smtpList[id];
					var label = (smtp.from_name || smtp.name || smtp.host || '') +
						(smtp.from_email ? ' <' + smtp.from_email + '>' : '');
					parts.push(
						'<option value="smtp:' + id + '">' +
						jQuery('<div>').text(label).html() +
						'</option>'
					);
				});
				parts.push('</optgroup>');
			}
		}

		return parts.join('');
	},
	buildComposeSenderOptions: function (senders) {
		var personalLabel = app.vtranslate('LBL_SENDER_PERSONAL');
		var groupLabel = app.vtranslate('LBL_SENDER_GROUP');
		var parts = [];

		senders.forEach(function (sender) {
			var displayName = sender.from_name || sender.name || '';
			if (sender.role === 'group' && displayName === '') {
				displayName = sender.group_name || groupLabel;
			}
			var email = sender.username || '';
			var label;
			if (displayName !== '' && email !== '') {
				label = displayName + ' <' + email + '>';
			} else if (email !== '') {
				label = email;
			} else if (displayName !== '') {
				label = displayName;
			} else {
				label = sender.role === 'group' ? groupLabel : personalLabel;
			}
			parts.push(
				'<option value="' + sender.ref + '">' +
				jQuery('<div>').text(label).html() +
				'</option>'
			);
		});

		return parts.join('');
	},
	getSenderRef: function () {
		if (!this.senderSelect || !this.senderSelect.length) {
			return '';
		}
		return this.senderSelect.val() || '';
	},
	applyPreviewMeta: function (meta) {
		if (!meta || !this.templateSelect.length) {
			return;
		}
		var opt = this.templateSelect.find('option:selected');
		if (meta.defaultSenderRef) {
			opt.attr('data-default-sender-ref', meta.defaultSenderRef);
		}
		if (meta.senderType) {
			opt.attr('data-sender-type', meta.senderType);
		}
		if (meta.templateSmtpId) {
			opt.attr('data-smtp-id', meta.templateSmtpId);
		}
		this.refresh();
	}
};
