/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */

jQuery.Class("Vtiger_SwitchUsers_Js", {}, {
	setReturnUrlForSwitchedUsers: function (container) {
		var returnUrl = window.location.pathname.replace(/^\//, '') + window.location.search;
		if (returnUrl.indexOf('index.php') !== 0) {
			returnUrl = 'index.php' + (window.location.search || '');
		}
		var field = container.find('[name="returnUrlForSwitchedUsers"]');
		if (!field.length) {
			container.find('form').append(jQuery('<input>', {type: 'hidden', name: 'returnUrlForSwitchedUsers'}));
			field = container.find('[name="returnUrlForSwitchedUsers"]');
		}
		field.val(returnUrl);
	},
	registerSave: function (container) {
		var thisInstance = this;
		var form = container.find('form[name="switchUsersForm"]');
		// Keep return URL in sync for "switch back to yourself" (type=submit).
		form.off('submit.switchUsers').on('submit.switchUsers', function () {
			thisInstance.setReturnUrlForSwitchedUsers(container);
		});
		// container is the modal element (with class 'switchUsersContainer')
		container.off('click', '.modal-body button.btn-success').on('click', '.modal-body button.btn-success', function (e) {
			// Only handle if it's the switch button (not other buttons)
			var button = jQuery(e.currentTarget);
			if (button.hasClass('dismiss') || button.hasClass('getYourself') || button.hasClass('close')) {
				return true; // Let other buttons work normally
			}
			e.preventDefault();
			e.stopPropagation();
			document.progressLoader = jQuery.progressIndicator({
				message: app.vtranslate('JS_LOADING_PLEASE_WAIT'),
				position: 'html',
				blockInfo: {
					enabled: true
				}
			});
			var userId = container.find('[name="user"]').val();
			if (!userId) {
				document.progressLoader.progressIndicator({mode: 'hide'});
				return false;
			}
			form.find('[name="id"]').val(userId);
			thisInstance.setReturnUrlForSwitchedUsers(container);
			// Native submit — jQuery .submit() does not navigate when submit handlers exist.
			var formEl = form.get(0);
			if (formEl) {
				formEl.submit();
			}
			return false;
		});
	},
	registerEvents: function (container) {
		// If container is provided (from modal callback), use it; otherwise find it
		if (!container || !container.length) {
			container = jQuery('.switchUsersContainer');
		}
		// If still not found, try to find modal
		if (!container.length) {
			container = jQuery('.modal.switchUsersContainer');
		}
		if (container.length) {
			this.registerSave(container);
		}
	}
});

// Static method for callback from modal - ensure it's available on window
if (typeof window.Vtiger_SwitchUsers_Js === 'undefined') {
	window.Vtiger_SwitchUsers_Js = Vtiger_SwitchUsers_Js;
}
Vtiger_SwitchUsers_Js.registerEvents = function (container) {
	try {
		// container is the modal element from Bootstrap modal (passed from showModalWindow callback)
		// It's the .modal element which has class 'switchUsersContainer'
		// So we can use it directly
		if (container && container.length > 0) {
			var instance = new Vtiger_SwitchUsers_Js();
			instance.registerEvents(container);
		}
	} catch (e) {
		console.error('Error registering SwitchUsers events:', e);
	}
};

jQuery(document).ready(function (e) {
	// Also register on document ready for backward compatibility
	var instance = new Vtiger_SwitchUsers_Js();
	instance.registerEvents();
});
