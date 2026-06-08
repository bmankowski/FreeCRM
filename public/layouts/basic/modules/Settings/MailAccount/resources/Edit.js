'use strict';

Settings_Vtiger_Edit_Js('Settings_MailAccount_Edit_Js', {}, {
	getForm: function () {
		return jQuery('#MailAccountForm');
	},
	registerEvents: function () {
		this._super();
		var form = this.getForm();
		if (!form.length) {
			return;
		}
		var kind = form.find('[name="kind"]').val();
		var mailboxConfig = {
			formSelector: '#MailAccountForm',
			testButtonSelector: '.js-mailbox-test',
			saveButtonSelector: '.js-mailbox-save',
			validateOnSave: kind !== 'personal',
			buildTestParams: function (formData) {
				formData.module = 'Mail';
				formData.action = 'TestConnection';
				formData.kind = kind;
				return formData;
			}
		};

		if (kind === 'personal') {
			mailboxConfig.saveSuccessMessage = 'LBL_SAVE_MAILBOX_OK';
			mailboxConfig.buildSaveParams = function (formData) {
				formData.module = 'Mail';
				formData.action = 'SavePersonalAccount';
				formData.activate = 1;
				return formData;
			};
		} else {
			mailboxConfig.saveSuccessMessage = 'LBL_SAVE';
			mailboxConfig.buildSaveParams = function (formData) {
				return {
					module: 'MailAccount',
					parent: 'Settings',
					action: 'SaveAjax',
					param: jQuery.extend(formData, { activate: 1 })
				};
			};
			mailboxConfig.handleSaveSuccess = function (data) {
				if (data.result && data.result.url) {
					window.location.href = data.result.url;
				}
			};
		}

		(new Mail_MailboxForm_Js(mailboxConfig)).registerEvents();
	}
});
