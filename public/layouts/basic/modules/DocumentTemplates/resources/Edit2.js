/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */
DocumentTemplates_Edit_Js("DocumentTemplates_Edit2_Js", {}, {
	step2Container: false,
	advanceFilterInstance: false,
	init: function () {
		this.initialize();
	},
	/**
	 * Function to get the container which holds all the reports step1 elements
	 * @return jQuery object
	 */
	getContainer: function () {
		return this.step2Container;
	},
	/**
	 * Function to set the reports step1 container
	 * @params : element - which represents the reports step1 container
	 * @return : current instance
	 */
	setContainer: function (element) {
		this.step2Container = element;
		return this;
	},
	/**
	 * Function  to intialize the reports step1
	 */
	initialize: function (container) {
		if (typeof container === 'undefined') {
			container = jQuery('#pdf_step2');
		}
		if (container.is('#pdf_step2')) {
			this.setContainer(container);
		} else {
			this.setContainer(jQuery('#pdf_step2'));
		}
	},
	submit: function () {
		var aDeferred = jQuery.Deferred();
		var form = this.getContainer();
		var layoutId = form.find('[name="document_layout_source"]').val();
		if (layoutId && parseInt(form.attr('data-has-template-content'), 10) === 1) {
			if (!window.confirm(app.vtranslate('JS_STEP2_DOCUMENT_LAYOUT_OVERWRITE'))) {
				aDeferred.reject();
				return aDeferred.promise();
			}
		}
		var formData = form.serializeFormData();
		var progressIndicatorElement = jQuery.progressIndicator({
			'position': 'html',
			'blockInfo': {
				'enabled': true
			}
		});
		var saveData = form.serializeFormData();
		saveData['action'] = 'Save';
		saveData['step'] = 2;
		AppConnector.request(saveData).then(
				function (data) {
					data = JSON.parse(data);
					if (data.success == true) {
						Vtiger_Helper_Js.showMessage({text: app.vtranslate('JS_TEMPLATE_SAVED_SUCCESSFULLY')});

						AppConnector.request(formData).then(
								function (data) {
									form.hide();
									progressIndicatorElement.progressIndicator({
										'mode': 'hide'
									})
									aDeferred.resolve(data);
								},
								function (error, err) {
									app.errorLog(error, err);
								}
						);
					}
				},
				function (error, err) {
					app.errorLog(error, err);
				}
		);
		return aDeferred.promise();
	},
	registerCancelStepClickEvent: function (form) {
		jQuery('button.cancelLink', form).on('click', function () {
			window.history.back();
		});
	},
	registerMarginCheckboxClickEvent: function (container) {
		container.find('#margin_chkbox').on('change', function () {
			var status = jQuery(this).is(':checked');

			if (status) {
				container.find('.margin_inputs').addClass('hide');
			} else {
				container.find('.margin_inputs').removeClass('hide');
			}
		});
	},
	registerEvents: function () {
		var container = this.getContainer();

		var opts = app.validationEngineOptions;
		opts['promptPosition'] = "topLeft";
		container.validationEngine(opts);
		app.showSelect2ElementView(container.find('.select2'));
		this.registerCancelStepClickEvent(container);
		this.registerMarginCheckboxClickEvent(container);
		app.showPopoverElementView(container.find('.popoverTooltip'));
	}
});
