/*+***********************************************************************************
 * FreeCRM - shared mailbox form test/save handlers (Users + Settings MailAccount)
 *************************************************************************************/

jQuery.Class('Mail_MailboxForm_Js', {
	config: {
		formSelector: '#PersonalMailboxForm',
		testButtonSelector: '.js-mailbox-test',
		saveButtonSelector: '.js-mailbox-save',
		saveSuccessMessage: 'LBL_SAVE_MAILBOX_OK',
		validateOnSave: false
	}
}, {
	init: function (options) {
		this.config = jQuery.extend({}, Mail_MailboxForm_Js.config, options);
		return this;
	},

	showMailNotify: function (labelKey, type) {
		Vtiger_Helper_Js.showPnotify({
			text: app.vtranslate(labelKey),
			type: type,
			animation: 'show'
		});
	},

	onTestSuccess: function (form, result) {
		(new Mail_ImapFolderPicker_Js()).render(form, result);
		if (result.suggested_sent && !form.find('[name="imap_folder_sent"]').val()) {
			form.find('[name="imap_folder_sent"]').val(result.suggested_sent);
		}
	},

	buildTestParams: function (formData) {
		formData.module = 'Mail';
		formData.action = 'TestConnection';
		return formData;
	},

	buildSaveParams: function (formData) {
		return formData;
	},

	handleSaveSuccess: function (data) {
		if (data.result && data.result.url) {
			window.location.href = data.result.url;
		}
	},

	handleSaveError: function (data) {
		var errKey = (data.result && (data.result.error || data.result.message))
			|| (data.error && data.error.message)
			|| 'LBL_CONNECTION_FAILED';
		this.showMailNotify(errKey, 'error');
	},

	registerEvents: function () {
		var thisInstance = this;
		var form = jQuery(this.config.formSelector);
		if (!form.length) {
			return;
		}

		jQuery(this.config.testButtonSelector).on('click', function () {
			var params = thisInstance.buildTestParams(form.serializeFormData());
			AppConnector.request(params).then(function (data) {
				if (data.result && data.result.success) {
					thisInstance.onTestSuccess(form, data.result);
					thisInstance.showMailNotify('LBL_CONNECTION_OK', 'success');
				} else {
					thisInstance.showMailNotify((data.result && data.result.error) || 'LBL_CONNECTION_FAILED', 'error');
				}
			});
		});

		jQuery(this.config.saveButtonSelector).on('click', function () {
			if (thisInstance.config.validateOnSave && !form.validationEngine('validate')) {
				return;
			}
			var params = thisInstance.buildSaveParams(form.serializeFormData());
			AppConnector.request(params).then(function (data) {
				if (data.result && data.result.success) {
					thisInstance.showMailNotify(thisInstance.config.saveSuccessMessage, 'success');
					thisInstance.handleSaveSuccess(data);
				} else {
					thisInstance.handleSaveError(data);
				}
			});
		});
	}
});
