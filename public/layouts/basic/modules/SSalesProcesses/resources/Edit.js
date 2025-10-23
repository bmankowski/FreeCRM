/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */
Vtiger_Edit_Js("SSalesProcesses_Edit_Js", {}, {
	registerEvents: function () {
		this._super();
		// Ensure date fields are properly initialized
		var editViewForm = this.getForm();
		app.registerEventForDatePickerFields(editViewForm);
	}
});

