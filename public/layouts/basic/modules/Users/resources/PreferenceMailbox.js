/*+***********************************************************************************
 * FreeCRM - Users preference mailbox
 *************************************************************************************/

jQuery.Class('Users_PreferenceMailbox_Js', {}, {
	registerEvents: function () {
		this.registerMailboxHandlers();
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
	},
	registerMailboxHandlers: function () {
		var form = jQuery('#PersonalMailboxForm');
		var thisInstance = this;
		if (!form.length) {
			return;
		}
		jQuery('.js-personal-mail-test').on('click', function () {
			var params = form.serializeFormData();
			params.module = 'Mail';
			params.action = 'TestConnection';
			params.kind = 'personal';
			AppConnector.request(params).then(function (data) {
				if (data.result && data.result.success) {
					thisInstance.onTestSuccess(form, data.result);
					thisInstance.showMailNotify('LBL_CONNECTION_OK', 'success');
				} else {
					thisInstance.showMailNotify(data.result.error || 'LBL_CONNECTION_FAILED', 'error');
				}
			});
		});
		jQuery('.js-personal-mail-save').on('click', function () {
			var params = form.serializeFormData();
			params.module = 'Mail';
			params.action = 'SavePersonalAccount';
			params.activate = 1;
			AppConnector.request(params).then(function (data) {
				if (data.result && data.result.success) {
					thisInstance.showMailNotify('LBL_SAVE_MAILBOX_OK', 'success');
				} else {
					var errKey = (data.result && data.result.error) || (data.error && data.error.message) || 'LBL_CONNECTION_FAILED';
					thisInstance.showMailNotify(errKey, 'error');
				}
			});
		});
	}
});
