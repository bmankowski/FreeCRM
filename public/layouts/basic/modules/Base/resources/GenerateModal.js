/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */

jQuery.Class("Vtiger_GenerateModal_Js", {}, {
	registerGenetateButton: function (container) {
		var thisInstance = this;
		var root = container && container.length ? container : jQuery('.generateMappingModal');
		if (!root.length) {
			return;
		}
		root.find('button.genetateButton').off('click.generateMappingModal').on('click.generateMappingModal', function (e) {
			document.progressLoader = jQuery.progressIndicator({
				message: app.vtranslate('JS_LOADING_PLEASE_WAIT'),
				position: 'html',
				blockInfo: {
					enabled: true
				}
			});
			var currentTarget = jQuery(e.currentTarget);
			var actionUrl = currentTarget.data('url');
			var methodRadio = root.find('[name="method"]:checked');
			var methodHidden = root.find('input[type="hidden"][name="method"]');
			var methodVal = null;
			if (methodRadio.length) {
				methodVal = methodRadio.val();
			} else if (methodHidden.length) {
				methodVal = methodHidden.val();
			}
			if (methodVal === null || typeof methodVal === 'undefined') {
				window.location.href = actionUrl;
			} else {
				var params = {};
				params.data = {
					module: app.getModuleName(),
					action: 'GenerateRecords',
					records: root.find('[name="all_records"]').val(),
					template: currentTarget.data('id'),
					target: currentTarget.data('name'),
					method: methodVal
				};
				params.dataType = 'json';
				AppConnector.request(params).then(
						function (data) {
							var response = data['result'];
							if (data['success']) {
								var records = response.ok;
								thisInstance.summary(root, response);
								document.progressLoader.progressIndicator({'mode': 'hide'});
								if (methodVal == 1) {
									for (var i in records) {
										window.open(actionUrl + records[i], '_blank');
									}
								}
							}
						},
						function (data, err) {
							app.errorLog(data, err);
						}
				);
			}
		});
	},
	summary: function (container, data) {
		container.find('.modal-title').text(app.vtranslate('JS_SUMMARY'));
		container.find('.modal-body').html('<div>' + app.vtranslate('JS_SELECTED_RECORDS') + ': <strong>' + data.all + '</strong></div>\n\
									<div>' + app.vtranslate('JS_SUCCESSFULLY_PERFORMED_ACTION_FOR') + ': <strong>' + data.ok.length + '</strong></div>\n\
									<div>' + app.vtranslate('JS_ACTION_FAILED_FOR') + ': <strong>' + data.fail.length + '</strong></div>');
	}
});
