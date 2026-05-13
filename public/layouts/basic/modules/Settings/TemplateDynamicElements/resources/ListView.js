/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 */
Settings_Vtiger_ListView_Js('Settings_TemplateDynamicElements_ListView_Js', {
	deleteDynamicElement: function (recordId, event) {
		if (event) {
			event.stopPropagation();
			event.preventDefault();
		}
		Settings_TemplateDynamicElements_ListView_Js.getInstance().deleteRecord(recordId);
		return false;
	}
}, {
	registerRowClickEvent: function () {
		this.getListViewContentContainer().off('click.dynamicElementRow').on('click.dynamicElementRow', '.listViewEntries', function (e) {
			if (jQuery(e.target).closest('.rightRecordActions, .tdActions, .actions, a, button, input').length) {
				return;
			}
			var row = jQuery(e.currentTarget);
			var editUrl = row.data('recordurl') || row.attr('data-recordurl');
			if (editUrl) {
				window.location.href = editUrl;
			}
		});
	},
	deleteRecord: function (recordId) {
		var thisInstance = this;
		Vtiger_Helper_Js.showConfirmationBox({'message': app.vtranslate('LBL_DELETE_CONFIRMATION')}).then(function () {
			var progress = jQuery.progressIndicator({
				'message': app.vtranslate('JS_RECORD_GETTING_DELETED'),
				'position': 'html',
				'blockInfo': {'enabled': true}
			});
			AppConnector.request({
				module: app.getModuleName(),
				parent: app.getParentModuleName(),
				action: 'DeleteAjax',
				record: recordId
			}).then(function (data) {
				progress.progressIndicator({'mode': 'hide'});
				if (data.success && data.result && (data.result.success === true || data.result.success === 'true')) {
					thisInstance.getListViewRecords().then(function () {
						thisInstance.updatePagination();
					});
				} else {
					Vtiger_Helper_Js.showPnotify({
						text: data.result && data.result.message ? data.result.message : app.vtranslate('JS_ERROR'),
						title: app.vtranslate('JS_ERROR')
					});
				}
			}, function (error, err) {
				progress.progressIndicator({'mode': 'hide'});
				app.errorLog(error, err);
			});
		});
	},
	registerEvents: function () {
		this._super();
		this.registerRowClickEvent();
	}
});
