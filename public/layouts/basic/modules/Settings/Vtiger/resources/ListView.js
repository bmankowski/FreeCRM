/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_ListView_Js("Settings_Vtiger_ListView_Js",{
	triggerDelete : function(event,url){
		event.stopPropagation();
		var instance = Vtiger_ListView_Js.getInstance();
		instance.DeleteRecord(url);
	}
},{
	/*
	 * Function to register the list view container
	 */
	getListViewContainer: function () {
		if (this.listViewContainer == false) {
			this.listViewContainer = jQuery('div.listViewContentDiv');
		}
		return this.listViewContainer;
	},
	
	/*
	 * Function to register the list view delete record click event
	 */
	DeleteRecord: function(url){
		var thisInstance = this;
		var css = jQuery.extend({'text-align' : 'left'},css);
			
		AppConnector.request(url).then(
			function(data) {
				if(data) {
					var container = app.showModalWindow(data);
					thisInstance.postDeleteAction(container);
				}
			},
			function(error,err){

			}
		);
	},
	
	/**
	 * Reload settings list table contents after delete/save (lighter than full ListView refresh).
	 */
	reloadListViewContents : function(pageNumber, urlParams) {
		var thisInstance = this;
		var aDeferred = jQuery.Deferred();
		pageNumber = parseInt(pageNumber, 10) || 1;
		var listViewContentsContainer = jQuery('#listViewContents');
		if (!listViewContentsContainer.length) {
			aDeferred.reject();
			return aDeferred.promise();
		}
		var params = {
			module: app.getModuleName(),
			parent: app.getParentModuleName(),
			view: 'ListView',
			page: pageNumber
		};
		if (typeof this.getDefaultParams === 'function') {
			params = jQuery.extend(this.getDefaultParams(), params);
		}
		if (urlParams) {
			params = jQuery.extend(params, urlParams);
		}
		if (params.view === 'List') {
			params.view = 'ListView';
		}
		var progressIndicatorElement = jQuery.progressIndicator({
			position: 'html',
			blockInfo: {enabled: true}
		});
		AppConnector.request(params).then(
			function(data) {
				progressIndicatorElement.progressIndicator({mode: 'hide'});
				listViewContentsContainer.html(data);
				app.changeSelectElementView(listViewContentsContainer);
				thisInstance.registerRowClickEvent();
				thisInstance.registerHeadersClickEvent();
				aDeferred.resolve(data);
			},
			function(textStatus, errorThrown) {
				progressIndicatorElement.progressIndicator({mode: 'hide'});
				aDeferred.reject(textStatus, errorThrown);
			}
		);
		return aDeferred.promise();
	},

	/**
	 * Settings list refresh (used by delete and legacy getListViewRecords callers).
	 */
	getListViewRecords : function(urlParams) {
		var pageNumber = 1;
		if (urlParams && urlParams.page) {
			pageNumber = urlParams.page;
		} else {
			pageNumber = parseInt(jQuery('#pageNumber').val(), 10) || 1;
		}
		return this.reloadListViewContents(pageNumber, urlParams);
	},

	refreshListAfterRecordChange : function(pageNumber) {
		var thisInstance = this;
		pageNumber = parseInt(pageNumber, 10) || parseInt(jQuery('#pageNumber').val(), 10) || 1;
		if (parseInt(jQuery('#noOfEntries').val(), 10) === 1 && jQuery('#previousPageExist').val()) {
			pageNumber = Math.max(pageNumber - 1, 1);
		}
		jQuery('#recordsCount').val('');
		jQuery('#totalPageCount').text('');
		jQuery('.pagination').data('totalCount', 0);
		thisInstance.reloadListViewContents(pageNumber).then(
			function() {
				thisInstance.updatePagination(pageNumber);
			},
			function() {
				window.location.reload();
			}
		);
	},

	/**
	 * Function to load list view after deletion of record from list view
	 */
	postDeleteAction : function(container){
		var thisInstance = this;
		var deleteForm = jQuery(container).find('#DeleteModal');
		if (!deleteForm.length) {
			return;
		}
		deleteForm.off('submit.settingsDelete').on('submit.settingsDelete', function(e){
			e.preventDefault();
			var deleteConfirmForm = jQuery(this);
			var recordId = deleteConfirmForm.find('[name="record"]').val();
			var deleteActionUrl = deleteConfirmForm.serializeFormData();
			AppConnector.request(deleteActionUrl).then(
				function(data) {
					if (data && data.success === false) {
						app.hideModalWindow();
						var errorMessage = (data.error && data.error.message) ? data.error.message : app.vtranslate('JS_DELETE_FAILED');
						Settings_Vtiger_Index_Js.showMessage({text: errorMessage, type: 'error'});
						return;
					}
					app.hideModalWindow();
					Settings_Vtiger_Index_Js.showMessage({
						text: app.vtranslate('JS_RECORD_DELETED_SUCCESSFULLY')
					});
					if (recordId) {
						jQuery('.listViewEntries[data-id="' + recordId + '"]').remove();
					}
					thisInstance.refreshListAfterRecordChange();
				},
				function(error,err){
					app.hideModalWindow();
				}
			);
		});
	},
	
	/**
	 * Function to get Page Jump Params
	 */
	getPageJumpParams : function(){
		var module = app.getModuleName();
		var cvId = this.getCurrentCvId();
		var pageCountParams = {
			'module' : module,
			'parent' : "Settings",
			'action' : "ListAjax",
			'mode' : "getPageCount",
			"viewname": cvId
		}
		var sourceModule = jQuery('#moduleFilter').val();
		if(typeof sourceModule != 'undefined'){
			pageCountParams['sourceModule'] = sourceModule;
		}
		return pageCountParams;
	},
	registerEvents : function() {
		//this.triggerDisplayTypeEvent();
		this.registerRowClickEvent();
		this.registerCheckBoxClickEvent();
		this.registerHeadersClickEvent();
		this.registerPageNavigationEvents();
		this.registerEventForTotalRecordsCount();
	}
});
