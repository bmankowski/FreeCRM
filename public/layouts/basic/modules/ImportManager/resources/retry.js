/* +***********************************************************************************
 * ImportManager failed rows retry helpers.
 * *********************************************************************************** */
(function ($) {
	'use strict';

	function t(key, fallback) {
		if (window.app && typeof app.vtranslate === 'function') {
			const translated = app.vtranslate(key);
			if (translated && translated !== key) {
				return translated;
			}
		}
		return fallback || key;
	}

	function showToast(text, type) {
		if (window.app && typeof app.showNotify === 'function') {
			app.showNotify(text, type);
		} else {
			alert(text);
		}
	}

	$(function () {
		const $form = $('#ImportManagerRetryForm');
		if (!$form.length) {
			return;
		}

		const batchId = $form.data('batchId');
		const $saveBtn = $('.js-retry-save');
		const $exportBtn = $('.js-retry-export');

		$saveBtn.on('click', function () {
			const changes = collectChanges();
			if (!changes.length) {
				showToast(t('JS_NOTHING_TO_SAVE', 'Brak zmian do zapisania.'), 'info');
				return;
			}

			const payload = {
				module: 'ImportManager',
				action: 'RetryUpdate',
				batch_id: batchId,
				rows: JSON.stringify(changes)
			};
			if (typeof window.csrfMagicToken !== 'undefined') {
				payload.csrfMagicToken = window.csrfMagicToken;
			}

			$saveBtn.prop('disabled', true);
			$.ajax({
				url: 'index.php',
				type: 'POST',
				dataType: 'json',
				data: payload
			})
				.done(function (response) {
					if (!response || response.success !== true) {
						handleError(response);
						return;
					}
					markRowsAsSaved(changes);
					showToast(t('JS_CHANGES_SAVED', 'Zmiany zostały zapisane.'), 'success');
				})
				.fail(handleError)
				.always(function () {
					$saveBtn.prop('disabled', false);
				});
		});

		$exportBtn.on('click', function () {
			window.location = 'index.php?module=ImportManager&action=ExportErrors&batch_id=' + batchId;
		});

		function collectChanges() {
			const result = [];
			$form.find('.js-retry-row').each(function () {
				const $row = $(this);
				const rowNumber = parseInt($row.data('rowNumber'), 10);
				if (!rowNumber) {
					return;
				}
				const changes = {};
				$row.find('.js-retry-input').each(function () {
					const $input = $(this);
					const field = $input.data('field');
					const original = $input.data('original');
					let current = $input.val();
					if (current === null || current === undefined) {
						current = '';
					}
					if (String(original) !== String(current)) {
						changes[field] = current;
					}
				});

				if (Object.keys(changes).length) {
					result.push({
						rowNumber: rowNumber,
						values: changes
					});
				}
			});
			return result;
		}

		function markRowsAsSaved(changes) {
			changes.forEach(function (change) {
				const $row = $form.find('.js-retry-row[data-row-number="' + change.rowNumber + '"]');
				$row.removeClass('table-danger').addClass('table-success');
				$row.find('.js-retry-input').each(function () {
					const $input = $(this);
					const field = $input.data('field');
					if (change.values.hasOwnProperty(field)) {
						$input.data('original', change.values[field]);
					}
				});
				setTimeout(function () {
					$row.removeClass('table-success');
				}, 1500);
			});
		}

		function handleError(payload) {
			let message = null;
			if (payload && payload.error) {
				message = payload.error.message || payload.error;
			} else if (payload && payload.responseText) {
				message = payload.responseText;
			}
			showToast(message || t('JS_OPERATION_FAILED', 'Operacja nie powiodła się.'), 'error');
		}
	});
})(jQuery);

