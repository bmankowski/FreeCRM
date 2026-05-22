/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
Settings_Workflows_Edit_Js("Settings_Workflows_Edit2_Js",{},{
	
	step2Container : false,
	
	advanceFilterInstance : false,
	
	init : function() {
		this.initialize();
	},
	/**
	 * Function to get the container which holds all the reports step1 elements
	 * @return jQuery object
	 */
	getContainer : function() {
		return this.step2Container;
	},

	/**
	 * Function to set the reports step1 container
	 * @params : element - which represents the reports step1 container
	 * @return : current instance
	 */
	setContainer : function(element) {
		this.step2Container = element;
		return this;
	},
	
	/**
	 * Function  to intialize the reports step1
	 */
	initialize : function(container) {
		if(typeof container == 'undefined') {
			container = jQuery('#workflow_step2');
		}
		if(container.is('#workflow_step2')) {
			this.setContainer(container);
		}else{
			this.setContainer(jQuery('#workflow_step2'));
		}
	},
	
	calculateValues : function(){
		//handled advanced filters saved values.
		var enableFilterElement = jQuery('#enableAdvanceFilters');
		if(enableFilterElement.length > 0 && enableFilterElement.is(':checked') == false) {
			jQuery('#advanced_filter').val(jQuery('#olderConditions').val());
		} else {
			jQuery('[name="filtersavedinnew"]').val("6");
			var advfilterlist = this.advanceFilterInstance.getValues();
			jQuery('#advanced_filter').val(JSON.stringify(advfilterlist));
		}
	},
	
	submit : function(){
		var aDeferred = jQuery.Deferred();
		var form = this.getContainer();
		this.calculateValues();
		var formData = form.serializeFormData();
		var progressIndicatorElement = jQuery.progressIndicator({
			'position' : 'html',
			'blockInfo' : {
				'enabled' : true
			}
		});
		var hideProgress = function () {
			progressIndicatorElement.progressIndicator({
				'mode' : 'hide'
			});
		};
		AppConnector.request(formData).then(
			function(data) {
				form.hide();
				var recordId = (data && data.result && data.result.id) ? data.result.id : form.find('[name="record"]').val();
				if (!recordId) {
					hideProgress();
					Vtiger_Helper_Js.showPnotify({
						animation: 'show',
						type: 'error',
						title: app.vtranslate('JS_MESSAGE'),
						text: app.vtranslate('JS_FAILED_TO_SAVE', 'Settings.Vtiger')
					});
					aDeferred.reject();
					return;
				}
				form.find('[name="record"]').val(recordId);
				if (data && data.result) {
					Vtiger_Helper_Js.showPnotify({
						animation: 'show',
						type: 'success',
						title: app.vtranslate('JS_MESSAGE'),
						text: app.vtranslate('JS_WORKFLOW_SAVED_SUCCESSFULLY', 'Settings:Workflows')
					});
				}
				var params = {
					module : app.getModuleName(),
					parent : app.getParentModuleName(),
					view : 'Edit',
					mode : 'Step3',
					record : recordId
				};
				AppConnector.request(params).then(
					function(step3Data) {
						hideProgress();
						aDeferred.resolve(step3Data);
					},
					function () {
						hideProgress();
						aDeferred.reject();
					}
				);
			},
			function () {
				hideProgress();
				aDeferred.reject();
			}
		);
		return aDeferred.promise();
	},
	
	registerEnableFilterOption : function() {
		jQuery('[name="conditionstype"]').on('change',function(e) {
			var advanceFilterContainer = jQuery('#advanceFilterContainer');
			var currentRadioButtonElement = jQuery(e.currentTarget);
			if(currentRadioButtonElement.hasClass('recreate')){
				if(currentRadioButtonElement.is(':checked')){
					advanceFilterContainer.removeClass('zeroOpacity');
				}
			} else {
				advanceFilterContainer.addClass('zeroOpacity');
			}
		});
	},
	
	
	
	
	registerEvents : function(){
		var opts = app.validationEngineOptions;
		// to prevent the page reload after the validation has completed
		opts['onValidationComplete'] = function(form,valid) {
            //returns the valid status
            return valid;
        };
		opts['promptPosition'] = "bottomRight";
		jQuery('#workflow_step2').validationEngine(opts);

		var container = this.getContainer();
        // When you come to step2 we should remove validation for condition values other than rawtwxt
        jQuery('button[type="submit"]',container).on('click',function(e){
            var fieldUiHolders = jQuery('.fieldUiHolder')
            for(var i=0; i<fieldUiHolders.length;i++){
                var fieldUiHolder  = fieldUiHolders[i];
                var fieldValueElement = jQuery('.getPopupUi',fieldUiHolder);
                var valueType = jQuery('[name="valuetype"]',fieldUiHolder).val();
                if(valueType != 'rawtext'){
                    fieldValueElement.removeAttr('data-validation-engine');
                    fieldValueElement.removeClass('validate[funcCall[Vtiger_Base_Validator_Js.invokeValidation]]');
                }
            }
        });
		app.changeSelectElementView(container);
		this.advanceFilterInstance = Vtiger_AdvanceFilter_Js.getInstance(jQuery('.filterContainer',container));
		this.getPopUp();
		if(jQuery('[name="filtersavedinnew"]',container).val() == '5'){
			this.registerEnableFilterOption();
		}
	}
});