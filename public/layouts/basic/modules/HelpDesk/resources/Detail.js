/*+***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 *************************************************************************************************************************************/
Vtiger_Detail_Js("HelpDesk_Detail_Js", {
	setAccountsReference: function () {
		var params = {module: "Accounts", view: "Popup", src_module: "HelpDesk", src_record: app.getRecordId()};
		var popupInstance = Vtiger_Popup_Js.getInstance();
		popupInstance.show(params, function (data) {
			var responseData = JSON.parse(data);
			$.each(responseData, function (key, element) {
				var instance = Vtiger_Detail_Js.getInstance();
				instance.saveFieldValues({
					field: "parent_id",
					value: key
				}).then(function (response) {
					location.reload();
				});
			});
		});
	},
}, {
	registerSetServiceContracts: function () {
		var thisInstance = this;
		$('.selectServiceContracts').on('click', 'ul li', function (e) {
			var element = jQuery(e.currentTarget);
			thisInstance.saveFieldValues({
				field: "servicecontractsid",
				value: element.data('id')
			}).then(function (response) {
				location.reload();
			});
		});
	},
	registerTicketWorkflowModal: function () {
		jQuery(document).on('click', '.js-ticket-workflow-submit', function (e) {
			e.preventDefault();
			var form = jQuery('#ticketWorkflowForm');
			if (!form.length) {
				return;
			}
			var mode = form.data('mode');
			var record = form.data('record');
			var solution = form.find('[name="solution"]').val() || '';
			var comment = form.find('[name="comment"]').val() || '';
			var progressIndicatorElement = jQuery.progressIndicator({});
			AppConnector.request({
				module: 'HelpDesk',
				action: 'TicketWorkflowAjax',
				record: record,
				mode: mode,
				solution: solution,
				comment: comment
			}).then(function (data) {
				progressIndicatorElement.progressIndicator({'mode': 'hide'});
				if (data.success && data.result && data.result.success) {
					app.hideModalWindow();
					location.reload();
					return;
				}
				var messageKey = (data.result && data.result.message) ? data.result.message : 'LBL_ERROR';
				Vtiger_Helper_Js.showPnotify({
					text: app.vtranslate(messageKey, 'HelpDesk'),
					type: 'error'
				});
			}, function () {
				progressIndicatorElement.progressIndicator({'mode': 'hide'});
				Vtiger_Helper_Js.showPnotify({
					text: app.vtranslate('LBL_ERROR', 'Vtiger'),
					type: 'error'
				});
			});
		});
	},
	registerEvents: function () {
		this._super();
		this.registerSetServiceContracts();
		this.registerTicketWorkflowModal();
	}
});
