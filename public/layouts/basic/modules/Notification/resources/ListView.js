/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */
Vtiger_ListView_Js("Notification_ListView_Js", {
	setAsMarked: function (id) {
		Vtiger_Index_Js.markNotifications(id).then(function () {
			Vtiger_Index_Js.getNotificationsForReminder();
			Vtiger_ListView_Js.getInstance().getListViewRecords();
		})
	}
}, {});
