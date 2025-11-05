/*+***********************************************************************************
 * FreeCRM - Customer Relationship Management System
 * This file is part of FreeCRM.
 ************************************************************************************/

/**
 * Settings Users ListView JavaScript Class
 */
var Settings_Users_ListView_Js;
Settings_Vtiger_ListView_Js("Settings_Users_ListView_Js",{
	/* Static methods/properties here if needed */
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

