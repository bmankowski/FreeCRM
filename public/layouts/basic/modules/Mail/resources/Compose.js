'use strict';

jQuery(function ($) {
	var form = $('#mail-compose-form');
	if (!form.length) {
		return;
	}
	var picker = Mail_SenderPicker_Js;
	picker.init(form, {
		composeSenders: form.data('mailComposeSenders') || [],
		accounts: form.data('mailAccounts') || [],
		smtpList: form.data('mailSmtpList') || {}
	});

	form.find('.js-mail-send').on('click', function () {
		if (!form.validationEngine('validate')) {
			return;
		}
		var data = {
			module: 'Mail',
			action: 'Send',
			senderRef: picker.getSenderRef(),
			templateId: form.find('#mail-template').val(),
			sourceModule: form.find('[name=sourceModule]').val(),
			sourceRecord: form.find('[name=sourceRecord]').val(),
			to: form.find('[name=to]').val(),
			subject: form.find('[name=subject]').val(),
			content: form.find('[name=content]').val()
		};
		AppConnector.request(data).then(function (response) {
			if (response.result && response.result.success) {
				window.location.href = 'index.php?module=Mail&view=Detail&record=' + response.result.messageId;
			} else {
				var errKey = (response.result && response.result.error) || 'JS_ERROR';
				if (errKey.indexOf('LBL_') !== 0 && errKey.indexOf('JS_') !== 0) {
					errKey = 'JS_ERROR';
				}
				Vtiger_Helper_Js.showPnotify({
					text: app.vtranslate(errKey),
					type: 'error',
					animation: 'show'
				});
			}
		});
	});
});
