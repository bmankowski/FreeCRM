/*+***********************************************************************************
 * FreeCRM - Users preference mailbox
 *************************************************************************************/

jQuery.Class('Users_PreferenceMailbox_Js', {}, {
	registerEvents: function () {
		(new Mail_MailboxForm_Js({
			formSelector: '#PersonalMailboxForm',
			testButtonSelector: '.js-personal-mail-test',
			saveButtonSelector: '.js-personal-mail-save',
			saveSuccessMessage: 'LBL_SAVE_MAILBOX_OK',
			buildTestParams: function (formData) {
				formData.module = 'Mail';
				formData.action = 'TestConnection';
				formData.kind = 'personal';
				return formData;
			},
			buildSaveParams: function (formData) {
				formData.module = 'Mail';
				formData.action = 'SavePersonalAccount';
				formData.activate = 1;
				return formData;
			}
		})).registerEvents();
	}
});
