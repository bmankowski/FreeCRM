/* FreeCRM - Settings LinkAction log list */

Settings_Vtiger_ListView_Js('Settings_LinkAction_ListView_Js', {}, {
	registerFilterChangeEvent: function () {
		const thisInstance = this;
		jQuery('#moduleFilter').on('change', function (e) {
			jQuery('#pageNumber').val('1');
			jQuery('#pageToJump').val('1');
			jQuery('#orderBy').val('');
			jQuery('#sortOrder').val('');
			const params = {
				module: app.getModuleName(),
				parent: app.getParentModuleName(),
				search_key: 'module',
				search_value: jQuery(e.currentTarget).val(),
				page: 1,
			};
			jQuery('#totalPageCount').text('');
			thisInstance.getListViewRecords(params).then(function () {
				thisInstance.updatePagination();
			});
		});
	},

	getDefaultParams: function () {
		const pageNumber = jQuery('#pageNumber').val();
		return {
			module: app.getModuleName(),
			parent: app.getParentModuleName(),
			page: pageNumber,
			view: 'List',
			search_key: 'module',
			search_value: jQuery('#moduleFilter').val(),
		};
	},

	getPageJumpParams: function () {
		return {
			module: app.getModuleName(),
			parent: app.getParentModuleName(),
			action: 'ListAjax',
			mode: 'getPageCount',
			search_key: 'module',
			search_value: jQuery('#moduleFilter').val(),
		};
	},

	updatePagination: function (pageNumber) {
		pageNumber = typeof pageNumber !== 'undefined' ? pageNumber : 1;
		const thisInstance = this;
		const params = {
			module: app.getModuleName(),
			parent: 'Settings',
			view: 'Pagination',
			page: pageNumber,
			mode: 'getPagination',
			totalCount: jQuery('.pagination').data('totalCount'),
			search_key: 'module',
			search_value: jQuery('#moduleFilter').val(),
			noOfEntries: jQuery('#noOfEntries').val(),
		};
		AppConnector.request(params).then(function (data) {
			jQuery('.paginationDiv').html(data);
			thisInstance.registerPageNavigationEvents();
		});
	},

	registerEvents: function () {
		this.registerFilterChangeEvent();
		this.registerPageNavigationEvents();
		this.registerEventForTotalRecordsCount();
	},
});
