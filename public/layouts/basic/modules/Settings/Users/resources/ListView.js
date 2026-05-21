/*+***********************************************************************************
 * FreeCRM - Customer Relationship Management System
 * This file is part of FreeCRM.
 ************************************************************************************/

/**
 * Settings Users ListView JavaScript Class
 */
var Settings_Users_ListView_Js;
Settings_Vtiger_ListView_Js("Settings_Users_ListView_Js",{
	/**
	 * Load delete-user form in a modal (AJAX), then submit via deleteUser().
	 * @param {string} deleteRecordActionUrl
	 */
	deleteRecord: function (deleteRecordActionUrl) {
		var message = app.vtranslate('LBL_DELETE_USER_CONFIRMATION');
		Vtiger_Helper_Js.showConfirmationBox({message: message}).then(function () {
			AppConnector.request(deleteRecordActionUrl).then(function (data) {
				if (data) {
					var callback = function () {
						var params = app.validationEngineOptions;
						params.onValidationComplete = function (form, valid) {
							if (valid) {
								Settings_Users_ListView_Js.deleteUser(form);
							}
							return false;
						};
						jQuery('#deleteUser').validationEngine(app.validationEngineOptions);
					};
					app.showModalWindow(data, function () {
						if (typeof callback === 'function') {
							callback();
						}
					});
				}
			});
		});
	},
	/**
	 * Delete user and transfer records to another user.
	 * @param {jQuery} form
	 */
	deleteUser: function (form) {
		var userid = form.find('[name="userid"]').val();
		var transferUserId = form.find('[name="tranfer_owner_id"]').val();
		var progressInstance = jQuery.progressIndicator({
			position: 'html',
			blockInfo: {enabled: true}
		});
		var params = {
			module: app.getModuleName(),
			action: 'DeleteAjax',
			transfer_user_id: transferUserId,
			userid: userid,
			permanent: jQuery('[name="deleteUserPermanent"]:checked', form).val()
		};
		AppConnector.request(params).then(function (data) {
			progressInstance.progressIndicator({mode: 'hide'});
			if (data.success) {
				app.hideModalWindow();
				Vtiger_Helper_Js.showPnotify({
					text: data.result.message,
					type: 'success'
				});
				window.location.reload();
			}
		});
	},
	/**
	 * Function to trigger change password for a single user
	 * @param {string} CHPWActionUrl - URL to load the change password form
	 * @param {string} module - Module name
	 */
	triggerChangePassword: function (CHPWActionUrl, module) {
		AppConnector.request(CHPWActionUrl).then(function (data) {
			if (data) {
				var callback = function (modalData) {
					var params = app.validationEngineOptions;
					params.onValidationComplete = function (form, valid) {
						if (valid) {
							Settings_Users_ListView_Js.savePassword(form);
						}
						return false;
					};
					jQuery('#changePassword').validationEngine(app.validationEngineOptions);
				};
				app.showModalWindow(data, function (modalData) {
					if (typeof callback === 'function') {
						callback(modalData);
					}
				});
			}
		});
	},
	/**
	 * Function to save password after form validation
	 * @param {jQuery} form - Form element
	 */
	savePassword: function (form) {
		var newPassword = form.find('[name="new_password"]');
		var confirmPassword = form.find('[name="confirm_password"]');
		var oldPasswordField = form.find('[name="old_password"]');
		var userId = form.find('[name="userid"]').val();

		if (newPassword.val() == confirmPassword.val()) {
			var params = {
				'module': app.getModuleName(),
				'action': "SaveAjax",
				'mode': 'savePassword',
				'old_password': oldPasswordField.length ? oldPasswordField.val() : '',
				'new_password': newPassword.val(),
				'userid': userId
			};
			AppConnector.request(params).then(function (response) {
				if (response.success) {
					app.hideModalWindow();
					Vtiger_Helper_Js.showPnotify({text: app.vtranslate(response.result.message), type: 'success'});
				} else {
					Vtiger_Helper_Js.showPnotify(response.error.message);
					return false;
				}
			});
		} else {
			newPassword.validationEngine('showPrompt', app.vtranslate('JS_REENTER_PASSWORDS'), 'error', 'topLeft', true);
			return false;
		}
	}
},{
	/**
	 * Function to get page jump params for Users list
	 */
	getPageJumpParams: function () {
		var module = app.getModuleName();
		var cvId = this.getCurrentCvId();
		var pageCountParams = {
			'module': module,
			'parent': 'Settings',
			'view': "ListAjax",
			'mode': "getPageCount",
			'search_key': 'status',
			'operator': 'e',
			'search_value': jQuery('#usersFilter').val()
		};
		return pageCountParams;
	},

	/**
	 * Function to filter Active and Inactive users from Users List View
	 */
	usersFilter: function () {
		var thisInstance = this;
		jQuery('#usersFilter').on('change', function () {
			var progressInstance = jQuery.progressIndicator({
				'position': 'html',
				'blockInfo': {
					'enabled': true
				}
			});
			
			// Reload the entire page with the filter parameter
			var params = {
				'module': app.getModuleName(),
				'view': 'ListView',
				'parent': 'Settings',
				'search_key': 'status',
				'operator': 'e',
				'search_value': jQuery('#usersFilter').val()
			};
			
			// Build URL and navigate
			var url = 'index.php?module=' + params.module + 
					  '&parent=' + params.parent + 
					  '&view=' + params.view + 
					  '&search_key=' + params.search_key + 
					  '&operator=' + params.operator + 
					  '&search_value=' + params.search_value;
			
			window.location.href = url;
		});
	},

	/**
	 * Update pagination with current filter
	 */
	updatePaginationFilter: function () {
		var thisInstance = this;
		var params = {};
		params['page'] = 1;
		params['module'] = app.getModuleName();
		params['parent'] = 'Settings';
		params['view'] = 'Pagination';
		params['mode'] = 'getPagination';
		params['search_key'] = 'status';
		params['search_value'] = jQuery('#usersFilter').val();
		params['operator'] = "e";
		
		AppConnector.request(params).then(function (data) {
			jQuery('.paginationDiv').html(data);
			thisInstance.registerPageNavigationEvents();
		});
	},

	/**
	 * Update pagination for page navigation
	 */
	updatePagination: function (pageNumber) {
		pageNumber = typeof pageNumber !== 'undefined' ? pageNumber : 1;
		var thisInstance = this;
		var params = {};
		params['module'] = app.getModuleName();
		params['parent'] = 'Settings';
		params['view'] = 'Pagination';
		params['page'] = pageNumber;
		params['mode'] = 'getPagination';
		
		// Check if status filter is active
		var filterValue = jQuery('#usersFilter').val();
		if (filterValue) {
			params['search_key'] = 'status';
			params['search_value'] = filterValue;
			params['operator'] = "e";
		}
		
		AppConnector.request(params).then(function (data) {
			jQuery('.paginationDiv').html(data);
			thisInstance.registerPageNavigationEvents();
		});
	},

	/**
	 * Register all events for the list view
	 */
	registerEvents: function () {
		this._super();
		this.usersFilter();
	}
});

