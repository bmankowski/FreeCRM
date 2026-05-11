/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */
Settings_Vtiger_ListView_Js("Settings_PDF_ListView_Js", {
	deletePdfTemplate: function (templateId, event) {
		if (event) {
			if (typeof event.stopPropagation === 'function') {
				event.stopPropagation();
			} else {
				event.cancelBubble = true;
			}
			if (typeof event.preventDefault === 'function') {
				event.preventDefault();
			}
		}
		Settings_PDF_ListView_Js.getInstance().deleteTemplate(templateId);
		return false;
	}
}, {
	getListContainer: function () {
		return jQuery('#listViewContainer');
	},
	registerFilterChangeEvent: function () {
		var thisInstance = this;
		jQuery('#moduleFilter').on('change', function (e) {
			jQuery('#pageNumber').val('1');
			jQuery('#pageToJump').val('1');
			jQuery('#orderBy').val('');
			jQuery("#sortOrder").val('');
			var params = {
				module: app.getModuleName(),
				parent: app.getParentModuleName(),
				sourceModule: jQuery(e.currentTarget).val()
			}
			//Make the select all count as empty
			jQuery('#recordsCount').val('');
			//Make total number of pages as empty
			jQuery('#totalPageCount').text('');
			thisInstance.getListViewRecords(params).then(
					function (data) {
						thisInstance.updatePagination();
					}
			);
		});
	},
	/*
	 * Function to register the list view row click event
	 */
	registerRowClickEvent: function () {
		var listViewContentDiv = this.getListViewContentContainer();
		listViewContentDiv.off('click.pdfRow').on('click.pdfRow', '.listViewEntries', function (e) {
			if (jQuery(e.target).closest('.rightRecordActions, .tdActions, .actions, a, button, input').length) {
				return;
			}
			var editUrl = jQuery(e.currentTarget).find('.glyphicon-pencil').closest('a').attr('href');
			window.location.href = editUrl;
		});
	},
	getDefaultParams: function () {
		var pageNumber = jQuery('#pageNumber').val();
		var module = app.getModuleName();
		var parent = app.getParentModuleName();
		var params = {
			'module': module,
			'parent': parent,
			'page': pageNumber,
			'view': "ListView",
			sourceModule: jQuery('#moduleFilter').val()
		};
		return params;
	},
	registerAddNewTemplate: function (container) {
		jQuery('#addButton', container).on('click', function () {
			var selectedModule = jQuery('#moduleFilter option:selected').val();
			window.location.href = jQuery(this).data('url') + '&source_module=' + selectedModule;
		});
	},
	registerImportTemplate: function (container) {
		jQuery('#importButton', container).on('click', function () {
			window.location.href = jQuery(this).data('url');
		});
	},
	registerTemplateDelete: function (container) {
		var thisInstance = this;
		if (container == undefined) {
			container = thisInstance.getListViewContentContainer();
		}
		container.off('click.pdfDelete', '.templateDelete').on('click.pdfDelete', '.templateDelete', function (e) {
			e.stopPropagation();
			e.preventDefault();
			var templateId = jQuery(this).data('id') || jQuery(this).closest('tr').data('id');
			thisInstance.deleteTemplate(templateId);
		});
	},
	deleteTemplate: function (templateId) {
		var thisInstance = this;
		var message = app.vtranslate('LBL_DELETE_CONFIRMATION');
		Vtiger_Helper_Js.showConfirmationBox({'message': message}).then(function () {
			var progressIndicatorElement = jQuery.progressIndicator({
				'message': app.vtranslate('JS_RECORD_GETTING_DELETED'),
				'position': 'html',
				'blockInfo': {
					'enabled': true
				}
			});
			var params = {
				module: app.getModuleName(),
				parent: app.getParentModuleName(),
				action: 'DeleteAjax',
				record: templateId
			};
			AppConnector.request(params).then(function (data) {
				progressIndicatorElement.progressIndicator({'mode': 'hide'});
				if (data.success && data.result && (data.result.success === true || data.result.success === 'true')) {
					var pageNumber = parseInt(jQuery('#pageNumber').val());
					if (jQuery('#noOfEntries').val() == 1 && pageNumber != 1) {
						pageNumber--;
					}
					jQuery('#recordsCount').val('');
					jQuery('#totalPageCount').text('');
					thisInstance.getListViewRecords({
						orderby: jQuery('#orderBy').val(),
						sortorder: jQuery('#sortOrder').val(),
						page: pageNumber
					}).then(function () {
						thisInstance.updatePagination(pageNumber);
					});
				} else {
					Vtiger_Helper_Js.showPnotify({
						text: app.vtranslate('JS_ERROR'),
						title: app.vtranslate('JS_ERROR')
					});
				}
			}, function (error, err) {
				progressIndicatorElement.progressIndicator({'mode': 'hide'});
				app.errorLog(error, err);
			});
		});
	},
	/*
	 * Function which will give you all the list view params
	 */
	getListViewRecords: function (urlParams) {
		var thisInstance = this;
		var aDeferred = jQuery.Deferred();
		this._super(urlParams).then(function (data) {
			thisInstance.registerTemplateDelete();
			aDeferred.resolve(data);
		});
		return aDeferred.promise();
	},
	registerEvents: function () {
		this._super();
		var container = this.getListContainer();
		this.registerFilterChangeEvent();
		this.registerAddNewTemplate(container);
		this.registerTemplateDelete();
		this.registerImportTemplate(container);
	}
});

Settings_PDF_ListView_Js.deletePdfTemplate = function (templateId, event) {
	if (event) {
		if (typeof event.stopPropagation === 'function') {
			event.stopPropagation();
		} else {
			event.cancelBubble = true;
		}
		if (typeof event.preventDefault === 'function') {
			event.preventDefault();
		}
	}
	Settings_PDF_ListView_Js.getInstance().deleteTemplate(templateId);
	return false;
};
