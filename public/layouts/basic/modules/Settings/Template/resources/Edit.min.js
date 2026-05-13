/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */
Settings_Vtiger_Edit_Js("Settings_Template_Edit_Js", {
	instance: {}

}, {
	currentInstance: false,
	editContainer: false,
	init: function () {
		this.initiate();
	},
	/**
	 * Function to get the container which holds all the workflow elements
	 * @return jQuery object
	 */
	getContainer: function () {
		return this.editContainer;
	},
	/**
	 * Function to set the reports container
	 * @params : element - which represents the workflow container
	 * @return : current instance
	 */
	setContainer: function (element) {
		this.editContainer = element;
		return this;
	},
	/*
	 * Function to return the instance based on the step of the Workflow
	 */
	getInstance: function (step) {
		if (step in Settings_Template_Edit_Js.instance) {
			return Settings_Template_Edit_Js.instance[step];
		} else {
			var moduleClassName = 'Settings_Template_Edit' + step + '_Js';
			Settings_Template_Edit_Js.instance[step] = new window[moduleClassName]();
			return Settings_Template_Edit_Js.instance[step]
		}
	},
	/*
	 * Function to get the value of the step 
	 * returns 1 or 2 or 3
	 */
	getStepValue: function () {
		var container = this.currentInstance.getContainer();
		return jQuery('.step', container).val();
	},
	getStepValueFromHtml: function (html) {
		var nextStepContainer = jQuery(html);
		return nextStepContainer.find('.step').first().val();
	},
	/*
	 * Function to initiate the step 1 instance
	 */
	initiate: function (container) {
		if (typeof container === 'undefined') {
			container = jQuery('.pdfTemplateContents');
		}
		if (container.is('.pdfTemplateContents')) {
			this.setContainer(container);
		} else {
			this.setContainer(jQuery('.pdfTemplateContents', container));
		}
		var stepVal = jQuery('.step', this.getContainer()).val() || '1';
		this.initiateStep(stepVal);
		this.registerEvents();
		this.syncPdfStepUrl(stepVal);
	},
	/*
	 * Function to initiate all the operations for a step
	 * @params step value
	 */
	initiateStep: function (stepVal) {
		var step = 'step' + stepVal;
		this.activateHeader(step);
		this.currentInstance = this.getInstance(stepVal);
	},
	/*
	 * Function to activate the header based on the class
	 * @params class name
	 */
	activateHeader: function (step) {
		var headersContainer = jQuery('#wizardSteps .crumbs');
		var wizardStepsContainer = headersContainer.closest('#wizardSteps');
		wizardStepsContainer.removeClass('current-step1 current-step2 current-step3 current-step4 current-step5 current-step6');
		wizardStepsContainer.addClass('current-' + step);
		headersContainer.find('.active').removeClass('active');
		jQuery('#' + step, headersContainer).addClass('active');
	},
	getStepContainer: function (stepVal) {
		return jQuery('#pdf_step' + stepVal);
	},
	getRequestDataForStep: function (stepVal) {
		var currentContainer = this.currentInstance.getContainer();
		var requestData = {
			module: jQuery('[name="module"]', currentContainer).val() || app.getModuleName(),
			parent: jQuery('[name="parent"]', currentContainer).val() || app.getParentModuleName(),
			view: jQuery('[name="view"]', currentContainer).val() || app.getViewName(),
			mode: 'Step' + stepVal
		};
		var recordId = jQuery('[name="record"]', currentContainer).val();
		if (recordId) {
			requestData.record = recordId;
		}
		return requestData;
	},
	/**
	 * Keep browser URL in sync with wizard step (same params as full page load: module, parent, view, mode, record).
	 */
	syncPdfStepUrl: function (stepVal) {
		stepVal = parseInt(stepVal, 10);
		if (!stepVal || stepVal < 1 || stepVal > 6) {
			return;
		}
		if (typeof window.history === 'undefined' || typeof window.history.replaceState !== 'function') {
			return;
		}
		var container = this.currentInstance && jQuery.isFunction(this.currentInstance.getContainer)
			? this.currentInstance.getContainer()
			: null;
		if (!container || !container.length) {
			container = jQuery('form[name="EditPdfTemplate"]:visible').first();
		}
		if (!container || !container.length) {
			return;
		}
		try {
			var url = new URL(window.location.href);
			url.searchParams.set('module', jQuery('[name="module"]', container).first().val() || 'Template');
			url.searchParams.set('parent', jQuery('[name="parent"]', container).first().val() || 'Settings');
			url.searchParams.set('view', jQuery('[name="view"]', container).first().val() || 'Edit');
			url.searchParams.set('mode', 'Step' + stepVal);
			var record = jQuery('[name="record"]', container).first().val();
			if (record) {
				url.searchParams.set('record', record);
			} else {
				url.searchParams.delete('record');
			}
			window.history.replaceState({ pdfWizardStep: stepVal }, '', url.toString());
		} catch (e) {
			app.errorLog(e);
		}
	},
	showStep: function (stepVal) {
		var stepContainer = this.getStepContainer(stepVal);
		if (!stepContainer.length) {
			return false;
		}
		jQuery('form[name="EditPdfTemplate"]').hide();
		this.initiateStep(stepVal);
		this.currentInstance.initialize(stepContainer);
		stepContainer.show();
		this.registerCurrentStepEvents();
		this.syncPdfStepUrl(stepVal);
		return true;
	},
	loadStep: function (stepVal) {
		var thisInstance = this;
		var aDeferred = jQuery.Deferred();
		if (this.showStep(stepVal)) {
			aDeferred.resolve();
			return aDeferred.promise();
		}
		var progressIndicatorElement = jQuery.progressIndicator({
			position: 'html',
			blockInfo: {
				enabled: true
			}
		});
		AppConnector.request(this.getRequestDataForStep(stepVal)).then(function (data) {
			thisInstance.getContainer().prepend(data);
			progressIndicatorElement.progressIndicator({
				mode: 'hide'
			});
			thisInstance.showStep(stepVal);
			aDeferred.resolve(data);
		}, function (error, err) {
			progressIndicatorElement.progressIndicator({
				mode: 'hide'
			});
			app.errorLog(error, err);
			aDeferred.reject(error);
		});
		return aDeferred.promise();
	},
	registerCurrentStepEvents: function () {
		var container = this.currentInstance.getContainer();
		this.registerFormSubmitEvent(container);
		if (!container.data('pdfStepEventsRegistered')) {
			this.currentInstance.registerEvents();
			container.data('pdfStepEventsRegistered', true);
		}
	},
	goToStep: function (stepVal) {
		var thisInstance = this;
		var targetStepVal = parseInt(stepVal);
		var currentStepVal = parseInt(this.getStepValue());
		if (!targetStepVal || targetStepVal < 1 || targetStepVal > 6 || targetStepVal === currentStepVal || this.stepNavigationInProgress) {
			return;
		}
		this.stepNavigationInProgress = true;
		if (targetStepVal < currentStepVal) {
			this.loadStep(targetStepVal).always(function () {
				thisInstance.stepNavigationInProgress = false;
			});
			return;
		}
		this.saveCurrentStepForNavigation(targetStepVal).always(function () {
			thisInstance.stepNavigationInProgress = false;
		});
	},
	saveCurrentStepForNavigation: function (targetStepVal) {
		var thisInstance = this;
		var aDeferred = jQuery.Deferred();
		var form = this.currentInstance.getContainer();
		var currentStepVal = parseInt(this.getStepValue());
		var specialValidation = true;
		if (jQuery.isFunction(this.currentInstance.isFormValidate)) {
			specialValidation = this.currentInstance.isFormValidate();
		}
		if (!form.validationEngine('validate') || !specialValidation) {
			aDeferred.reject();
			return aDeferred.promise();
		}
		this.currentInstance.submit().then(function (data) {
			var loadedStepVal = thisInstance.getStepValueFromHtml(data);
			if (!loadedStepVal) {
				loadedStepVal = currentStepVal + 1;
			}
			var loadedStepContainer = thisInstance.getStepContainer(loadedStepVal);
			if (loadedStepContainer.length) {
				loadedStepContainer.closest('.pdfTemplateContents').remove();
			}
			thisInstance.getContainer().prepend(data);
			if (parseInt(loadedStepVal) === targetStepVal) {
				thisInstance.showStep(targetStepVal);
				aDeferred.resolve(data);
				return;
			}
			thisInstance.loadStep(targetStepVal).then(function () {
				aDeferred.resolve(data);
			}, function (error) {
				aDeferred.reject(error);
			});
		});
		return aDeferred.promise();
	},
	/*
	 * Function to register the click event for next button
	 */
	registerFormSubmitEvent: function (form) {
		var thisInstance = this;
		if (jQuery.isFunction(thisInstance.currentInstance.submit)) {
			form.off('submit.pdfEdit').on('submit.pdfEdit', function (e) {
				var form = jQuery(e.currentTarget);
				var specialValidation = true;
				if (jQuery.isFunction(thisInstance.currentInstance.isFormValidate)) {
					specialValidation = thisInstance.currentInstance.isFormValidate();
				}
				if (form.validationEngine('validate') && specialValidation) {
					thisInstance.currentInstance.submit().then(function (data) {
						var nextStepVal = thisInstance.getStepValueFromHtml(data);
						if (!nextStepVal) {
							nextStepVal = parseInt(thisInstance.getStepValue()) + 1;
						}
						var nextStepContainer = thisInstance.getStepContainer(nextStepVal);
						if (nextStepContainer.length) {
							nextStepContainer.closest('.pdfTemplateContents').remove();
						}
						thisInstance.getContainer().prepend(data);
						thisInstance.showStep(nextStepVal);
					});

				}
				e.preventDefault();
			})
		}
	},
	back: function () {
		var step = this.getStepValue();
		var prevStep = parseInt(step) - 1;
		if (prevStep > 0) {
			this.loadStep(prevStep);
		}
	},
	registerCancelStepClickEvent: function (form) {
		jQuery('button.cancelLink', form).off('click.pdfEditCancel').on('click.pdfEditCancel', function () {
			window.history.back();
		});
	},
	/*
	 * Function to register the click event for back step 
	 */
	registerBackStepClickEvent: function () {
		var thisInstance = this;
		var container = this.getContainer();
		container.off('click.pdfEditBack', '.backStep').on('click.pdfEditBack', '.backStep', function (e) {
			thisInstance.back();
		});
	},
	registerWizardStepClickEvent: function () {
		var thisInstance = this;
		jQuery('#wizardSteps').off('click.pdfEditWizardStep', '.step').on('click.pdfEditWizardStep', '.step', function (e) {
			var stepVal = jQuery(e.currentTarget).attr('id').replace('step', '');
			thisInstance.goToStep(stepVal);
			e.preventDefault();
		});
	},
	registerMetatagsClickEvent: function (form) {
		var metaTagsStatus = form.find('#metatags_status');
		if (metaTagsStatus.is(':checked')) {
			form.find('.metatags').addClass('hide');
		} else {
			form.find('.metatags').removeClass('hide');
		}

		metaTagsStatus.off('change.pdfEditMetatags').on('change.pdfEditMetatags', function () {
			var status = jQuery(this).is(':checked');
			if (status) {
				jQuery('.metatags', form).addClass('hide');
			} else {
				jQuery('#set_subject', form).val(jQuery('#secondary_name', form).val());
				jQuery('#set_title', form).val(jQuery('#primary_name', form).val());
				jQuery('.metatags', form).removeClass('hide');
			}
		});
	},
	registerEvents: function () {
		var form = this.currentInstance.getContainer();
		app.registerCopyClipboard();
		this.registerCurrentStepEvents();
		this.registerBackStepClickEvent();
		this.registerCancelStepClickEvent(form);
		this.registerMetatagsClickEvent(form);
		this.registerWizardStepClickEvent();
	}
});
