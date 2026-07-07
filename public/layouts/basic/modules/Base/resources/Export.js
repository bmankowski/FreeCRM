/**
 * @license licenses/License.html
 * @package YetiForce.View
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
jQuery.Class("Vtiger_Export_Js", {}, {
	exportForm: false,
	getForm: function () {
		if (this.exportForm == false) {
			this.exportForm = jQuery('#exportForm');
		}
		return this.exportForm;
	},
	initEvent: function () {
		var form = this.getForm();
		var xmlTpl = form.find('.xml-tpl');
		var csvSeparatorOptions = form.find('.csv-separator-options');
		var exportTypeSelect = form.find('#exportType');
		
		// Handle export type change
		exportTypeSelect.on('change', function (e) {
			var exportType = jQuery(this).val();
			if (xmlTpl.length) {
				if (exportType == 'xml') {
					xmlTpl.removeClass('hide');
					csvSeparatorOptions.hide();
				} else {
					xmlTpl.addClass('hide');
					csvSeparatorOptions.show();
				}
			} else {
				// Show separator options for CSV, hide for XML
				if (exportType == 'xml') {
					csvSeparatorOptions.hide();
				} else {
					csvSeparatorOptions.show();
				}
			}
		});
		
		// Handle separator radio button changes
		form.find('input[name="csv_separator"]').on('change', function () {
			var customInput = form.find('#csvSeparatorCustomInput');
			if (jQuery(this).val() == 'custom') {
				customInput.show();
				form.find('#csvSeparatorCustomValue').focus();
			} else {
				customInput.hide();
				form.find('#csvSeparatorCustomValue').val('');
			}
		});
		
		// Initialize visibility on page load
		var currentExportType = exportTypeSelect.val();
		if (currentExportType == 'xml') {
			if (xmlTpl.length) {
				xmlTpl.removeClass('hide');
			}
			csvSeparatorOptions.hide();
		} else {
			if (xmlTpl.length) {
				xmlTpl.addClass('hide');
			}
			csvSeparatorOptions.show();
		}
	},
	registerEvents: function () {
		this.initEvent();
	}
})
