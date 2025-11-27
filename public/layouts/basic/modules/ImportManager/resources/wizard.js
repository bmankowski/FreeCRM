/* +***********************************************************************************
 * ImportManager wizard front-end helpers.
 * *********************************************************************************** */
(function ($) {
	'use strict';

	$(function () {
		const $form = $('#ImportManagerStep1');
		if (!$form.length) {
			return;
		}

		const $previewCard = $('#ImportManagerPreview');
		const $previewMeta = $('#ImportManagerPreviewMeta');
		const $formatField = $('#ImportManagerFormat');
		const $xmlOnlyFields = $('.js-import-xml-only');
		const $csvSeparatorOptions = $('.csv-separator-options');

		function toggleFormatFieldsVisibility() {
			const format = ($formatField.val() || '').toLowerCase();
			const isXml = format === 'xml';
			
			// Show/hide XML path field - use direct CSS manipulation for reliability
			if ($xmlOnlyFields.length) {
				if (isXml) {
					$xmlOnlyFields.css('display', '');
				} else {
					$xmlOnlyFields.css('display', 'none');
				}
			}
			
			// Show/hide CSV separator options (same approach as Export.js)
			if ($csvSeparatorOptions.length) {
				if (isXml) {
					$csvSeparatorOptions.hide();
				} else {
					$csvSeparatorOptions.show();
				}
			}
		}

		// Initialize visibility on page load (after a small delay to ensure DOM is ready)
		setTimeout(function() {
			toggleFormatFieldsVisibility();
		}, 100);
		$formatField.on('change', toggleFormatFieldsVisibility);

		$form.on('submit', function (event) {
			event.preventDefault();
			const formEl = $form.get(0);
			if (!formEl.checkValidity()) {
				formEl.reportValidity();
				return;
			}
			uploadAndPreview();
		});

		function uploadAndPreview() {
			const formData = new FormData($form.get(0));
			formData.append('module', 'ImportManager');
			formData.append('action', 'Upload');
			if (typeof window.csrfMagicToken !== 'undefined') {
				formData.append('csrfMagicToken', window.csrfMagicToken);
			}

			const indicator = $.progressIndicator({
				'message': app.vtranslate('LBL_PLEASE_WAIT') || 'Proszę czekać...',
				'position': 'html',
				'blockInfo': { enabled: true }
			});

			$.ajax({
				url: 'index.php',
				type: 'POST',
				data: formData,
				dataType: 'json',
				processData: false,
				contentType: false
			})
				.done(function (response) {
					indicator.progressIndicator({ mode: 'hide' });
					if (!response || response.success !== true) {
						handleError(response);
						return;
					}
					renderPreview(response.result || {});
				})
				.fail(function (jqXHR) {
					indicator.progressIndicator({ mode: 'hide' });
					handleError(jqXHR);
				});
		}

		function renderPreview(payload) {
			if (!payload.preview) {
				handleError({ error: { message: 'Brak danych podglądu.' } });
				return;
			}

			$('#ImportManagerBatchId').val(payload.batchId || '');
			const headers = payload.preview.headers || [];
			const rows = payload.preview.rows || [];

			const $thead = $previewCard.find('thead').empty();
			const $tbody = $previewCard.find('tbody').empty();

			if (headers.length) {
				const $row = $('<tr/>');
				headers.forEach(function (column) {
					$row.append($('<th/>').text(column || ''));
				});
				$thead.append($row);
			}

			if (!rows.length) {
				const colspan = Math.max(headers.length, 1);
				$tbody.append('<tr><td colspan="' + colspan + '" class="text-center text-muted">Brak danych do wyświetlenia.</td></tr>');
			} else {
				rows.forEach(function (row) {
					const $row = $('<tr/>');
					row.forEach(function (value) {
						$row.append($('<td/>').text(value !== null ? value : ''));
					});
					$tbody.append($row);
				});
			}

			const metaParts = [];
			if (payload.file && payload.file.name) {
				metaParts.push(payload.file.name);
			}
			if (payload.preview.meta && payload.preview.meta.encoding) {
				metaParts.push('ENC: ' + payload.preview.meta.encoding);
			}
			if (payload.preview.meta && payload.preview.meta.delimiter) {
				metaParts.push('DEL: ' + payload.preview.meta.delimiter);
			}
			$previewMeta.text(metaParts.join(' • '));

			$previewCard.removeClass('d-none');
			showToast(app.vtranslate('LBL_IMPORT_PREVIEW_READY') || 'Podgląd został wygenerowany.', 'success');
		}

		function handleError(payload) {
			let message = null;
			if (payload && payload.error) {
				message = payload.error.message || payload.error;
			} else if (payload && payload.responseText) {
				message = payload.responseText;
			}
			showToast(message || (app.vtranslate('JS_OPERATION_FAILED') || 'Operacja nie powiodła się.'), 'error');
		}

		function showToast(text, type) {
			if (window.app && typeof app.showNotify === 'function') {
				app.showNotify(text, type);
			} else {
				alert(text);
			}
		}
	});
})(jQuery);

