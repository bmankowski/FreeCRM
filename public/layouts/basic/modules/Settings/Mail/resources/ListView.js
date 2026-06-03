/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */
Settings_Vtiger_ListView_Js("Settings_Mail_ListView_Js", {}, {
	registerAcceptanceEvent: function () {
		var thisInstance = this;
		var list = jQuery('.listViewEntriesDiv');
		list.on('click', '.acceptanceRecord', function (e) {
			var elem = this;
			var id = $(this).closest('tr').data('id');
			var progressIndicator = jQuery.progressIndicator();
			AppConnector.request({
				module: app.getModuleName(),
				parent: app.getParentModuleName(),
				action: 'SaveAjax',
				mode: 'acceptanceRecord',
				id: id
			}).then(
					function (data) {
						progressIndicator.progressIndicator({'mode': 'hide'});
						Settings_Vtiger_Index_Js.showMessage({text: data.result.message});
						$(elem).remove()
					},
					function (error) {
						progressIndicator.progressIndicator({'mode': 'hide'});
					}
			);
		});
	},
	massDeleteAction: function () {
		$('.massDelete').on("click", function () {
			var listInstance = Settings_Vtiger_ListView_Js.getInstance();
			var validationResult = listInstance.checkListRecordSelected();
			if (validationResult != true) {
				var selectedIds = listInstance.readSelectedIds(true);
				var excludedIds = listInstance.readExcludedIds(true);
				var searchParams = listInstance.getParams().searchParams;
				var message = app.vtranslate('LBL_MASS_DELETE_CONFIRMATION');
				Vtiger_Helper_Js.showConfirmationBox({'message': message}).then(
						function (e) {
							var params = {};
							params['module'] = app.getModuleName();
							params['parent'] = app.getParentModuleName();
							params['action'] = 'MassDelete';
							params['selected_ids'] = selectedIds;
							params['excluded_ids'] = excludedIds;
							if (searchParams && Object.keys(searchParams).length) {
								params['searchParams'] = searchParams;
							}
							var deleteMessage = app.vtranslate('JS_RECORDS_ARE_GETTING_DELETED');
							var progressIndicatorElement = jQuery.progressIndicator({
								'message': deleteMessage,
								'position': 'html',
								'blockInfo': {
									'enabled': true
								}
							});
							AppConnector.request(params).then(
									function (data) {
										progressIndicatorElement.progressIndicator({
											'mode': 'hide'
										});
										listInstance.postMassDeleteRecords();
										if (data.error) {
											Vtiger_Helper_Js.showPnotify({text: app.vtranslate(data.error.message), title: app.vtranslate('JS_LBL_PERMISSION')});
										}
									},
									function (error) {
										console.log('Error: ' + error)
									}
							);
						},
						function (error, err) {
							Vtiger_ListView_Js.clearList();
						})

			} else {
				listInstance.noRecordSelectedAlert();
			}
		});

	},
	registerFilterChangeEvent: function () {
		var thisInstance = this;
		jQuery('#mailQueueFilter').on('change', function (e) {
			jQuery('#pageNumber').val("1");
			jQuery('#pageToJump').val('1');
			jQuery('#orderBy').val('');
			jQuery("#sortOrder").val('');
			var params = {
				module: app.getModuleName(),
				parent: app.getParentModuleName(),
				'orderby': jQuery(e.currentTarget).val(),
				'page': 1,
			}
			//Make total number of pages as empty
			jQuery('#totalPageCount').text("");
			thisInstance.getListViewRecords(params).then(
					function (data) {
						thisInstance.updatePagination();
					}
			);
		});
	},
	getParams: function () {
		var listViewContainer = this.getListViewContainer();
		var searchParams = {};
		listViewContainer.find('input.listSearchContributor, select.listSearchContributor').each(function (i, obj) {
			if ($(obj).val() != null) {
				var column = $(obj).attr("name")
				searchParams[column] = {};
				searchParams[column]['value'] = $(obj).val();
			}
		});

		var params = {
			module: app.getModuleName(),
			parent: app.getParentModuleName(),
			page: 1,
			view: "ListView",
			searchParams: searchParams
		}
		return params
	},
	registerListSearch: function () {
		var thisInstance = this;
		var listViewContainer = this.getListViewContainer();
		listViewContainer.find('input.listSearchContributor').on('keypress', function (e) {
			if (e.keyCode == 13) {
				var params = thisInstance.getParams();
				jQuery('#totalPageCount').text("");
				thisInstance.getListViewRecords(params).then(
						function (data) {
							thisInstance.updatePagination();
						}
				);
			}
		});
		listViewContainer.find('[data-trigger="listSearch"]').on('click', function (e) {
			var params = thisInstance.getParams();
			thisInstance.getListViewRecords(params).then(
					function (data) {
						thisInstance.updatePagination();
					}
			);
		});
	},
	registerListViewSelect: function () {
		if (app.getMainParams('autoRefreshListOnChange') == '1') {
			var thisInstance = this;
			var listViewContainer = this.getListViewContainer();
			listViewContainer.on('change', '.listViewEntriesTable select', function (e) {
				var params = thisInstance.getParams();
				thisInstance.getListViewRecords(params).then(
						function (data) {
							thisInstance.updatePagination();
						}
				);
			});
		}
	},
	getRecordsCount: function () {
		var aDeferred = jQuery.Deferred();
		var recordCountVal = jQuery('#recordsCount').val();
		if (recordCountVal !== '') {
			aDeferred.resolve(recordCountVal);
			return aDeferred.promise();
		}
		var totalCount = jQuery('#totalCount').val();
		if (totalCount !== '') {
			aDeferred.resolve(totalCount);
			return aDeferred.promise();
		}
		var postData = {
			module: app.getModuleName(),
			parent: app.getParentModuleName(),
			action: 'ListAjax',
			view: 'ListAjax',
			mode: 'getRecordsCount'
		};
		var searchParams = this.getParams().searchParams;
		if (searchParams && Object.keys(searchParams).length) {
			postData.searchParams = searchParams;
		}
		AppConnector.request(postData).then(
				function (data) {
					var response = JSON.parse(data);
					jQuery('#recordsCount').val(response.result.count);
					aDeferred.resolve(response.result.count);
				},
				function () {
					aDeferred.resolve(0);
				}
		);
		return aDeferred.promise();
	},
	registerEvents: function () {
		this._super();
		this.registerFilterChangeEvent();
		this.massDeleteAction();
		this.registerAcceptanceEvent();
		this.registerListSearch();
		this.registerListViewSelect();
	}
});
