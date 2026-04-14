/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */

jQuery.Class('Kandydaci_TransformDocumentToCVModal_Js', {}, {
	registerSubmit: function (container) {
		var form = container.find('form');
		if (!form.length) {
			return;
		}
		// Prevent full page navigation that would render raw JSON.
		form.on('submit', function (e) {
			e.preventDefault();
		});
		container.find('[name="saveButton"]').on('click', function (e) {
			e.preventDefault();
			var params = {
				type: 'POST',
				url: 'index.php',
				dataType: 'json',
				data: form.serializeFormData()
			};
			var progress = jQuery.progressIndicator({blockInfo: {enabled: true}});
			AppConnector.request(params).then(function (data) {
				progress.progressIndicator({mode: 'hide'});
				if (typeof data === 'string' && data.substring(0, 1) === '{') {
					data = jQuery.parseJSON(data);
				}
				if (data && data.success && data.result && data.result.redirect) {
					app.hideModalWindow();
					window.location.href = data.result.redirect;
					return;
				}
				// Fallback: close modal even if backend returns a different success payload.
				if (data && data.success) {
					app.hideModalWindow();
				}
			}, function (error, err) {
				progress.progressIndicator({mode: 'hide'});
				app.errorLog(error, err);
			});
		});
	},
	registerEvents: function () {
		var container = jQuery('.modalKandydaciTransformDocumentToCVModal');
		this.registerSubmit(container);
	}
});

jQuery(document).ready(function () {
	(new Kandydaci_TransformDocumentToCVModal_Js()).registerEvents();
});

