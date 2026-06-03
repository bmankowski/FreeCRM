'use strict';

Settings_Vtiger_Edit_Js('Settings_MailAccount_Edit_Js', {}, {
	showMailNotify: function (labelKey, type) {
		Vtiger_Helper_Js.showPnotify({
			text: app.vtranslate(labelKey),
			type: type,
			animation: 'show'
		});
	},
	onTestSuccess: function (form, result) {
		(new Mail_ImapFolderPicker_Js()).render(form, result);
	},
	registerEvents: function () {
		this._super();
		var form = jQuery('#MailAccountForm');
		var thisInstance = this;
		if (!form.length) {
			return;
		}
		form.find('.js-mail-test-connection').on('click', function () {
			var params = form.serializeFormData();
			params.module = 'MailAccount';
			params.parent = 'Settings';
			params.action = 'SaveAjax';
			params.param = params;
			params.param.test_connection = 1;
			AppConnector.request(params).then(function (data) {
				if (data.result && data.result.success) {
					thisInstance.onTestSuccess(form, data.result);
					thisInstance.showMailNotify('LBL_CONNECTION_OK', 'success');
				} else {
					thisInstance.showMailNotify((data.result && data.result.message) || 'LBL_CONNECTION_FAILED', 'error');
				}
			});
		});
		form.find('.js-mail-save-account').on('click', function () {
			var params = form.serializeFormData();
			params.module = 'MailAccount';
			params.parent = 'Settings';
			params.action = 'SaveAjax';
			params.param = params;
			AppConnector.request(params).then(function (data) {
				if (data.result && data.result.success) {
					if (data.result.url) {
						window.location.href = data.result.url;
					}
				} else {
					thisInstance.showMailNotify((data.result && data.result.message) || 'LBL_CONNECTION_FAILED', 'error');
				}
			});
		});
	}
});
