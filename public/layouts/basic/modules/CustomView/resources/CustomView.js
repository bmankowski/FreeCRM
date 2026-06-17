/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 *************************************************************************************/
var Vtiger_CustomView_Js = {
	contentsCotainer: false,
	columnListSelect2Element: false,
	advanceFilterInstance: false,
	columnSelectElement: false,
	selectedColumnsList: false,
	columnSelectionOrder: [],
	_editorAssetsPromise: false,
	_loadScript: function (src) {
		var deferred = jQuery.Deferred();
		var existing = jQuery('script[src*="' + src + '"]');
		if (existing.length && existing.data('customViewLoaded')) {
			deferred.resolve();
			return deferred.promise();
		}
		jQuery.ajax({ url: src, dataType: 'script', cache: true }).done(function () {
			jQuery('script[src*="' + src + '"]').data('customViewLoaded', true);
			deferred.resolve();
		}).fail(function () {
			deferred.reject();
		});
		return deferred.promise();
	},
	_loadStylesheet: function (href) {
		var deferred = jQuery.Deferred();
		if (jQuery('link[href*="' + href + '"]').length) {
			deferred.resolve();
			return deferred.promise();
		}
		var link = document.createElement('link');
		link.rel = 'stylesheet';
		link.type = 'text/css';
		link.href = href;
		link.onload = function () {
			deferred.resolve();
		};
		link.onerror = function () {
			deferred.reject();
		};
		document.head.appendChild(link);
		return deferred.promise();
	},
	ensureEditorAssets: function () {
		if (Vtiger_CustomView_Js._editorAssetsPromise) {
			return Vtiger_CustomView_Js._editorAssetsPromise;
		}
		var deferred = jQuery.Deferred();
		Vtiger_CustomView_Js._editorAssetsPromise = deferred.promise();
		var loads = [];
		if (typeof CKEDITOR === 'undefined') {
			loads.push(Vtiger_CustomView_Js._loadScript('libraries/jquery/ckeditor/ckeditor.min.js'));
			loads.push(Vtiger_CustomView_Js._loadScript('libraries/jquery/ckeditor/adapters/jquery.min.js'));
		}
		if (typeof Vtiger_CkEditor_Js === 'undefined') {
			loads.push(Vtiger_CustomView_Js._loadScript('layouts/basic/modules/Base/resources/CkEditor.min.js'));
		}
		if (typeof jQuery.fn.ColorPicker === 'undefined') {
			loads.push(Vtiger_CustomView_Js._loadStylesheet('libraries/jquery/colorpicker/css/colorpicker.css'));
			loads.push(Vtiger_CustomView_Js._loadScript('libraries/jquery/colorpicker/js/colorpicker.min.js'));
		} else if (!jQuery('link[href*="colorpicker/css/colorpicker"]').length) {
			loads.push(Vtiger_CustomView_Js._loadStylesheet('libraries/jquery/colorpicker/css/colorpicker.css'));
		}
		if (!loads.length) {
			deferred.resolve();
			return Vtiger_CustomView_Js._editorAssetsPromise;
		}
		jQuery.when.apply(jQuery, loads).done(function () {
			deferred.resolve();
		}).fail(function () {
			Vtiger_CustomView_Js._editorAssetsPromise = false;
			deferred.reject();
		});
		return Vtiger_CustomView_Js._editorAssetsPromise;
	},
	loadFilterView: function (url) {
		var progressIndicatorElement = jQuery.progressIndicator();
		AppConnector.request(url).then(
				function (data) {
					app.hideModalWindow();
					var contents = jQuery(".contentsDiv").html(data);
					Vtiger_CustomView_Js.columnSelectElement = false;
					Vtiger_CustomView_Js.columnListSelect2Element = false;
					Vtiger_CustomView_Js.columnSelectionOrder = [];
					Vtiger_CustomView_Js.ensureEditorAssets().done(function () {
						progressIndicatorElement.progressIndicator({'mode': 'hide'});
						Vtiger_CustomView_Js.registerEventsInternal();
						Vtiger_CustomView_Js.advanceFilterInstance = Vtiger_AdvanceFilter_Js.getInstance(jQuery('.filterContainer', contents));
					}).fail(function () {
						progressIndicatorElement.progressIndicator({'mode': 'hide'});
					});
				},
				function (error, err) {
					progressIndicatorElement.progressIndicator({'mode': 'hide'});
				}
		);
	},
	loadDateFilterValues: function () {
		var selectedDateFilter = jQuery('#standardDateFilter option:selected');
		var currentDate = selectedDateFilter.data('currentdate');
		var endDate = selectedDateFilter.data('enddate');
		jQuery("#standardFilterCurrentDate").val(currentDate);
		jQuery("#standardFilterEndDate").val(endDate);
	},
	/**
	 * Function to get the contents container
	 * @return : jQuery object of contents container
	 */
	getContentsContainer: function () {
		if (Vtiger_CustomView_Js.contentsCotainer == false) {
			Vtiger_CustomView_Js.contentsCotainer = jQuery('div.contentsDiv');
		}
		return Vtiger_CustomView_Js.contentsCotainer;
	},
	getColumnListSelect2Element: function () {
		return Vtiger_CustomView_Js.columnListSelect2Element;
	},
	/**
	 * Function to get the view columns selection element
	 * @return : jQuery object of view columns selection element
	 */
	getColumnSelectElement: function () {
		if (Vtiger_CustomView_Js.columnSelectElement == false) {
			Vtiger_CustomView_Js.columnSelectElement = jQuery('#viewColumnsSelect');
		}
		return Vtiger_CustomView_Js.columnSelectElement;
	},
	/**
	 * Function to get the selected columns list
	 * @return : jQuery object of selectedColumnsList
	 */
	getSelectedColumnsList: function () {
		if (Vtiger_CustomView_Js.selectedColumnsList == false) {
			Vtiger_CustomView_Js.selectedColumnsList = jQuery('#selectedColumnsList');
		}
		return Vtiger_CustomView_Js.selectedColumnsList;
	},
	normalizeColumnIds: function (columnIds) {
		if (!columnIds) {
			return [];
		}
		if (Array.isArray(columnIds)) {
			return columnIds;
		}
		return Object.keys(columnIds).sort(function (a, b) {
			return Number(a) - Number(b);
		}).map(function (key) {
			return columnIds[key];
		});
	},
	reorderColumnSelectOptions: function (columnIds) {
		var selectElement = Vtiger_CustomView_Js.getColumnSelectElement();
		columnIds = Vtiger_CustomView_Js.normalizeColumnIds(columnIds);
		if (!columnIds.length) {
			return;
		}
		columnIds.forEach(function (id) {
			selectElement.append(selectElement.find('option').filter(function () {
				return String(jQuery(this).val()) === String(id);
			}));
		});
	},
	initColumnSelectionOrder: function (columnIds) {
		Vtiger_CustomView_Js.columnSelectionOrder = Vtiger_CustomView_Js.normalizeColumnIds(columnIds);
	},
	registerColumnOrderEvents: function () {
		var selectElement = Vtiger_CustomView_Js.getColumnSelectElement();
		selectElement.off('select2:select.customViewOrder select2:unselect.customViewOrder columnorderchange.customViewOrder');
		selectElement.on('select2:select.customViewOrder', function (e) {
			var id = String(e.params.data.id);
			Vtiger_CustomView_Js.columnSelectionOrder = Vtiger_CustomView_Js.columnSelectionOrder.filter(function (x) {
				return x !== id;
			});
			Vtiger_CustomView_Js.columnSelectionOrder.push(id);
		});
		selectElement.on('select2:unselect.customViewOrder', function (e) {
			var id = String(e.params.data.id);
			Vtiger_CustomView_Js.columnSelectionOrder = Vtiger_CustomView_Js.columnSelectionOrder.filter(function (x) {
				return x !== id;
			});
		});
		selectElement.on('columnorderchange.customViewOrder', function (e, ordered) {
			if (ordered && ordered.length) {
				Vtiger_CustomView_Js.columnSelectionOrder = ordered.map(String);
			}
		});
	},
	/**
	 * Function which will get the selected columns
	 * @return : array of selected values
	 */
	getSelectedColumns: function () {
		var selectElement = Vtiger_CustomView_Js.getColumnSelectElement();
		var selected = (selectElement.val() || []).map(String);
		var order = Vtiger_CustomView_Js.columnSelectionOrder.filter(function (id) {
			return selected.indexOf(id) >= 0;
		});
		selected.forEach(function (id) {
			if (order.indexOf(id) < 0) {
				order.push(id);
			}
		});
		Vtiger_CustomView_Js.columnSelectionOrder = order;
		Vtiger_CustomView_Js.reorderColumnSelectOptions(order);
		return order;
	},
	saveFilter: function () {
		var aDeferred = jQuery.Deferred();
		var formElement = jQuery("#CustomView");
		var formData = formElement.serializeFormData();

		var progress = $.progressIndicator({
			'message': app.vtranslate('JS_SAVE_LOADER_INFO'),
			'blockInfo': {
				'enabled': true
			}
		});

		AppConnector.request(formData).then(
				function (data) {
					aDeferred.resolve(data);
				},
				function (error) {
					aDeferred.reject(error);
				}
		);
		return aDeferred.promise();
	},
	saveAndViewFilter: function () {
		Vtiger_CustomView_Js.saveFilter().then(
				function (response) {
					if (response.success) {
						var url;
						if (app.getParentModuleName() == 'Settings') {
							url = 'index.php?module=CustomView&parent=Settings&view=Index';
						} else {
							url = response['result']['listviewurl'];
						}
						window.location.href = url;
					} else {
						$.unblockUI();
						var params = {
							title: app.vtranslate('JS_DUPLICATE_RECORD'),
							text: response.error['message']
						};
						Vtiger_Helper_Js.showPnotify(params);
					}
				},
				function (error) {

				}
		);
	},
	/**
	 * Function which will register the select2 elements for columns selection
	 */
	registerSelect2ElementForColumnsSelection: function () {
		var selectElement = Vtiger_CustomView_Js.getColumnSelectElement();
		return app.changeSelectElementView(selectElement, 'selectize', {plugins: ['drag_drop', 'remove_button'], maxItems: 12});
	},
	registerIconEvents: function () {
		var container = this.getContentsContainer();
		container.on('change', '.iconPreferences input', function (e) {
			var currentTarget = $(e.currentTarget);
			var buttonElement = currentTarget.closest('.btn');
			var iconElement = currentTarget.next();
			if (currentTarget.prop('checked')) {
				buttonElement.removeClass('btn-default').addClass('btn-primary');
				iconElement.removeClass(iconElement.data('unchecked')).addClass(iconElement.data('check'));
			} else {
				buttonElement.removeClass('btn-primary').addClass('btn-default');
				iconElement.removeClass(iconElement.data('check')).addClass(iconElement.data('unchecked'));
			}
		});
		container.find('.iconPreferences input').each(function (e) {
			jQuery(this).trigger('change');
		});
	},
	registerCkEditorElement: function () {
		var container = this.getContentsContainer();
		container.find('.ckEditorSource').each(function (e) {
			var ckEditorInstance = new Vtiger_CkEditor_Js();
			ckEditorInstance.loadCkEditor(jQuery(this)); //{toolbar: 'Basic'}
		});
	},
	registerBlockToggleEvent: function () {
		var container = this.getContentsContainer();
		container.on('click', '.blockHeader', function (e) {
			var blockHeader = jQuery(e.currentTarget);
			var blockContents = blockHeader.next();
			var iconToggle = blockHeader.find('.iconToggle');
			if (blockContents.hasClass('hide')) {
				blockContents.removeClass('hide');
				iconToggle.removeClass(iconToggle.data('hide')).addClass(iconToggle.data('show'));
			} else {
				blockContents.addClass('hide');
				iconToggle.removeClass(iconToggle.data('show')).addClass(iconToggle.data('hide'));
			}
		});
	},
	registerColorEvent: function () {
		var container = this.getContentsContainer();
		var field = container.find('.colorPicker');
		var color = field.val();
		var addon = field.parent().find('.input-group-addon');

		field.ColorPicker({
			onChange: function (hsb, hex, rgb) {
				color = '#' + hex;
				field.val(color);
				addon.css('background-color', color);
			},
			onBeforeShow: function () {
				$(this).ColorPickerSetColor(this.value);
			}
		});
	},
	registerEvents: function () {
		Vtiger_CustomView_Js.ensureEditorAssets().done(function () {
			Vtiger_CustomView_Js.registerEventsInternal();
		});
	},
	registerSortOrderEvents: function () {
		var container = this.getContentsContainer();
		var form = container.find('#CustomView');
		container.find('.customViewSortClear').on('click', function (e) {
			jQuery(e.currentTarget).closest('.input-group').find('select').val('').trigger('change');
		});
		container.find('.customViewSortOrderButton').on('click', function (e) {
			var currentTarget = jQuery(e.currentTarget);
			currentTarget.find('.glyphicon').each(function () {
				if (jQuery(this).hasClass('hide')) {
					jQuery(this).removeClass('hide');
					form.find('[name="sortOrder"]').val(jQuery(this).data('val'));
				} else {
					jQuery(this).addClass('hide');
				}
			});
		});
	},
	registerEventsInternal: function () {
		this.registerIconEvents();
		this.registerCkEditorElement();
		this.registerBlockToggleEvent();
		this.registerColorEvent();
		this.registerSortOrderEvents();
		var select2Element = Vtiger_CustomView_Js.columnListSelect2Element = Vtiger_CustomView_Js.registerSelect2ElementForColumnsSelection();
		var contentsContainer = Vtiger_CustomView_Js.getContentsContainer();
		jQuery('.stndrdFilterDateSelect').datepicker();
		app.changeSelectElementView(jQuery('.chzn-select'));
		app.changeSelectElementView(contentsContainer.find('#defaultOrderBy'), 'select2');

		var columnsList = Vtiger_CustomView_Js.normalizeColumnIds(JSON.parse(jQuery('input[name="columnslist"]').val()));
		Vtiger_CustomView_Js.initColumnSelectionOrder(columnsList);
		select2Element.val(null).trigger('change');
		if (columnsList.length) {
			Vtiger_CustomView_Js.reorderColumnSelectOptions(columnsList);
			select2Element.val(columnsList).trigger('change');
		}
		Vtiger_CustomView_Js.registerColumnOrderEvents();
		jQuery("#standardDateFilter").change(function () {
			Vtiger_CustomView_Js.loadDateFilterValues();
		});

		jQuery("#CustomView").submit(function (e) {
			var selectElement = Vtiger_CustomView_Js.getColumnSelectElement();
			if (jQuery('#viewname').val().length > 40) {
				var params = {
					title: app.vtranslate('JS_MESSAGE'),
					text: app.vtranslate('JS_VIEWNAME_ALERT')
				};
				Vtiger_Helper_Js.showPnotify(params);
				e.preventDefault();
				return;
			}

			//Mandatory Fields selection validation
			//Any one Mandatory Field should select while creating custom view.
			var mandatoryFieldsList = JSON.parse(jQuery('#mandatoryFieldsList').val());
			var selectedOptions = selectElement.val();
			var mandatoryFieldsMissing = true;
			if (selectedOptions) {
				for (var i = 0; i < selectedOptions.length; i++) {
					if (jQuery.inArray(selectedOptions[i], mandatoryFieldsList) >= 0) {
						mandatoryFieldsMissing = false;
						break;
					}
				}
			}
			if (mandatoryFieldsMissing) {
				var message = app.vtranslate('JS_PLEASE_SELECT_ATLEAST_ONE_MANDATORY_FIELD');
				select2Element.validationEngine('showPrompt', message, 'error', 'topLeft', true);
				e.preventDefault();
				return;
			} else {
				select2Element.validationEngine('hide');
			}
			//Mandatory Fields validation ends
			var result = jQuery(e.currentTarget).validationEngine('validate');
			if (result == true) {
				//handled standard filters saved values.
				var stdfilterlist = {};

				if ((jQuery('#standardFilterCurrentDate').val() != '') && (jQuery('#standardFilterEndDate').val() != '') && (jQuery('select.standardFilterColumn option:selected').val() != 'none')) {
					stdfilterlist['columnname'] = jQuery('select.standardFilterColumn option:selected').val();
					stdfilterlist['stdfilter'] = jQuery('select#standardDateFilter option:selected').val();
					stdfilterlist['startdate'] = jQuery('#standardFilterCurrentDate').val();
					stdfilterlist['enddate'] = jQuery('#standardFilterEndDate').val();
					jQuery('#stdfilterlist').val(JSON.stringify(stdfilterlist));
				}

				//handled advanced filters saved values.
				var advfilterlist = Vtiger_CustomView_Js.advanceFilterInstance.getValues();
				jQuery('#advfilterlist').val(JSON.stringify(advfilterlist));
				jQuery('input[name="columnslist"]', contentsContainer).val(JSON.stringify(Vtiger_CustomView_Js.getSelectedColumns()));
				Vtiger_CustomView_Js.saveAndViewFilter();
				return false;
			} else {
				app.formAlignmentAfterValidation(jQuery(e.currentTarget));
			}
		});
		jQuery('#CustomView').validationEngine(app.validationEngineOptions);
	}
};
