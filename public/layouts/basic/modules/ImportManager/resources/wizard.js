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

		const dom = {
			form: $form,
			formatField: $('#ImportManagerFormat'),
			xmlOnly: $('.js-import-xml-only'),
			csvOptions: $('.csv-separator-options'),
			previewCard: $('#ImportManagerPreview'),
			previewMeta: $('#ImportManagerPreviewMeta'),
			step2: $('#ImportManagerStep2'),
			step3: $('#ImportManagerStep3'),
			mappingTable: $('#ImportManagerMappingTable'),
			requiredSets: $('#ImportManagerRequiredSets'),
			optionalSets: $('#ImportManagerOptionalSets'),
			duplicateStrategy: $('#ImportManagerDuplicateStrategy'),
			saveMapping: $('#ImportManagerSaveMapping'),
			summaryModule: $('#ImportManagerSummaryModule'),
			summaryFile: $('#ImportManagerSummaryFile'),
			summaryStrategy: $('#ImportManagerSummaryStrategy'),
			summaryFields: $('#ImportManagerSummaryFields'),
			confirmationStatus: $('#ImportManagerConfirmationStatus'),
			startImport: $('#ImportManagerStartImport'),
		};

		const state = {
			batchId: null,
			moduleName: null,
			fields: [],
			duplicateConfig: {
				requiredSets: [],
				optionalSets: [],
			},
			preview: null,
			fileMeta: {},
		};
		

		setTimeout(toggleFormatFieldsVisibility, 100);
		dom.formatField.on('change', toggleFormatFieldsVisibility);
		$('#ImportManagerTargetModule').on('change', resetWizardState);
		dom.form.on('submit', handleUploadSubmit);
		dom.startImport.on('click', function () {
			triggerStaging();
		});
		
		dom.saveMapping.on('click', saveMappingDefinition);

		function handleUploadSubmit(event) {
			event.preventDefault();
			const formEl = dom.form.get(0);
			if (!formEl.checkValidity()) {
				formEl.reportValidity();
				return;
			}
			uploadAndPreview();
		}

		function toggleFormatFieldsVisibility() {
			const format = (dom.formatField.val() || '').toLowerCase();
			const isXml = format === 'xml';
			if (dom.xmlOnly.length) {
				dom.xmlOnly.css('display', isXml ? '' : 'none');
			}
			if (dom.csvOptions.length) {
				isXml ? dom.csvOptions.hide() : dom.csvOptions.show();
			}
		}

		function uploadAndPreview() {
			resetWizardState();
			const formData = new FormData(dom.form.get(0));
			formData.append('module', 'ImportManager');
			formData.append('action', 'Upload');
			if (typeof window.csrfMagicToken !== 'undefined') {
				formData.append('csrfMagicToken', window.csrfMagicToken);
			}

			const indicator = $.progressIndicator({
				message: t('LBL_PLEASE_WAIT', 'Proszę czekać...'),
				position: 'html',
				blockInfo: { enabled: true }
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
				handleError({ error: { message: t('JS_NO_PREVIEW_DATA', 'Brak danych podglądu.') } });
				return;
			}

			state.batchId = payload.batchId || null;
			state.preview = payload.preview || {};
			state.fileMeta = payload.file || {};
			state.moduleName = $('#ImportManagerTargetModule').val() || null;
			$('#ImportManagerBatchId').val(state.batchId || '');

			const headers = state.preview.headers || [];
			const rows = state.preview.rows || [];

			const $thead = dom.previewCard.find('thead').empty();
			const $tbody = dom.previewCard.find('tbody').empty();

			if (headers.length) {
				const $row = $('<tr/>');
				headers.forEach(function (column) {
					$row.append($('<th/>').text(column || ''));
				});
				$thead.append($row);
			}

			if (!rows.length) {
				const colspan = Math.max(headers.length, 1);
				$tbody.append('<tr><td colspan="' + colspan + '" class="text-center text-muted">' + t('JS_NO_PREVIEW_ROWS', 'Brak danych do wyświetlenia.') + '</td></tr>');
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
			if (state.fileMeta.name) {
				metaParts.push(state.fileMeta.name);
			}
			if (state.preview.meta && state.preview.meta.encoding) {
				metaParts.push('ENC: ' + state.preview.meta.encoding);
			}
			if (state.preview.meta && state.preview.meta.delimiter) {
				metaParts.push('DEL: ' + state.preview.meta.delimiter);
			}
			dom.previewMeta.text(metaParts.join(' • '));

			dom.previewCard.removeClass('d-none');
			if (state.moduleName) {
				prepareMappingStep();
			}
		}

		function prepareMappingStep() {
			if (!state.moduleName || !state.preview) {
				return;
			}
			const indicator = $.progressIndicator({
				message: t('LBL_LOADING', 'Ładowanie...'),
				position: 'html',
				blockInfo: { enabled: true }
			});

			loadModuleMetadata(state.moduleName)
				.done(function () {
					renderMappingTable(state.preview.headers || []);
					renderDuplicateSections();
					dom.step2.removeClass('d-none');
				})
				.fail(handleError)
				.always(function () {
					indicator.progressIndicator({ mode: 'hide' });
				});
		}

		function loadModuleMetadata(moduleName) {
			const deferred = $.Deferred();
			$.ajax({
				url: 'index.php',
				type: 'POST',
				dataType: 'json',
				data: {
					module: 'ImportManager',
					action: 'Fields',
					target_module: moduleName,
					csrfMagicToken: window.csrfMagicToken || ''
				}
			})
				.done(function (response) {
					if (!response || response.success !== true) {
						deferred.reject(response);
						return;
					}
					const result = response.result || {};
					state.fields = (result.fields || []).map(function (field) {
						field.nameNormalized = (field.name || '').toLowerCase();
						field.labelNormalized = (field.label || '').toLowerCase().replace(/\W/g, '');
						return field;
					});
					state.duplicateConfig = result.duplicateSets || { requiredSets: [], optionalSets: [] };
					deferred.resolve();
				})
				.fail(function (jqXHR) {
					deferred.reject(jqXHR);
				});
			return deferred.promise();
		}

		function renderMappingTable(headers) {
			const $table = dom.mappingTable.removeClass('d-none');
			const $tbody = $table.find('tbody').empty();
			const headerList = Array.isArray(headers) ? headers : [];
			if (!state.fields.length) {
				dom.mappingTable.addClass('d-none');
				showToast(t('JS_NO_FIELDS_AVAILABLE', 'Brak pól do zmapowania.'), 'error');
				return;
			}
			if (!headerList.length) {
				showToast(t('JS_NO_HEADERS_AVAILABLE', 'Plik nie zawiera wiersza nagłówków – zmapuj pola ręcznie.'), 'info');
			}

			const usedHeaders = {};
			state.fields.forEach(function (field) {
				const autoIndex = guessSourceIndexForField(field, headerList, usedHeaders);
				addMappingRow(field, headerList, { sourceIndex: autoIndex });
			});
		}

		function describeField(field) {
			const parts = [];
			if (field.name) {
				parts.push(field.name);
			}
			if (field.type) {
				parts.push(field.type);
			}
			if (typeof field.mandatory === 'boolean') {
				parts.push(field.mandatory ? t('LBL_MANDATORY', 'Wymagane') : t('LBL_FIELD_OPTIONAL', 'Opcjonalne'));
			}
			return parts.join(', ');
		}

		function normalizeHeaderValue(value) {
			return (value || '').toString().toLowerCase().replace(/\W/g, '');
		}

		function guessSourceIndexForField(field, headers, usedIndexes) {
			const normalizedFieldNames = [];
			if (field.nameNormalized) {
				normalizedFieldNames.push(field.nameNormalized);
			} else {
				normalizedFieldNames.push(normalizeHeaderValue(field.name));
			}
			if (field.labelNormalized) {
				normalizedFieldNames.push(field.labelNormalized);
			} else if (field.label) {
				normalizedFieldNames.push(normalizeHeaderValue(field.label));
			}

			for (let i = 0; i < headers.length; i++) {
				if (usedIndexes[i]) {
					continue;
				}
				const normalizedHeader = normalizeHeaderValue(headers[i]);
				if (!normalizedHeader) {
					continue;
				}
				if (normalizedFieldNames.includes(normalizedHeader)) {
					usedIndexes[i] = true;
					return i;
				}
			}
			return null;
		}

		function addMappingRow(field, headers, preset = {}) {
			const $row = $('<tr/>');

			const $fieldCell = $('<td class="align-middle"/>');
			const $sourceCell = $('<td/>');
			const $defaultCell = $('<td/>');

			const label = field.label || field.name;
			const detail = describeField(field);
			const $label = $('<div class="field-label"/>').text(label);
			const $detail = $('<div class="text-muted small js-field-detail"/>').text(detail);
			$fieldCell.append($label);
			if (detail) {
				$fieldCell.append($detail);
			}

			const $sourceSelect = buildSourceSelect(headers, preset.sourceIndex);
			$sourceCell.append($sourceSelect);

			const $defaultWrapper = $('<div class="default-value-wrapper d-flex align-items-center"/>');
			const $defaultInput = $('<input type="text" class="form-control form-control-sm js-default-value"/>')
				.attr('placeholder', t('LBL_DEFAULT_VALUE_PLACEHOLDER', 'Podaj wartość'));
			if (preset.defaultValue) {
				$defaultInput.val(preset.defaultValue);
			}
			$defaultWrapper.append($defaultInput);
			$defaultCell.append($defaultWrapper);

			$row
				.attr('data-field', field.name)
				.attr('data-mandatory', field.mandatory ? 1 : 0)
				.attr('data-label', label);

			$row.append($fieldCell, $sourceCell, $defaultCell);
			dom.mappingTable.find('tbody').append($row);
		}

		function buildSourceSelect(headers, selectedIndex) {
			const $select = $('<select class="form-control form-control-sm js-source-select"/>');
			$select.append('<option value="">' + t('LBL_SELECT_OPTION', 'Wybierz') + '</option>');
			headers.forEach(function (header, index) {
				const label = header && header.length ? header : (t('LBL_COLUMN_PLACEHOLDER', 'Kolumna') + ' ' + (index + 1));
				const option = $('<option/>')
					.val(String(index))
					.text(label)
					.attr('data-column-name', header || '');
				if (typeof selectedIndex === 'number' && selectedIndex === index) {
					option.prop('selected', true);
				}
				$select.append(option);
			});
			return $select;
		}

		function renderDuplicateSections() {
			const required = state.duplicateConfig.requiredSets || [];
			const optional = state.duplicateConfig.optionalSets || [];

			if (!required.length) {
				dom.requiredSets.html('<span class="text-muted">—</span>');
			} else {
				const badges = required.map(function (set) {
					const text = (set || []).join(' + ');
					return '<span class="badge badge-light mr-1 mb-1">' + text + '</span>';
				});
				dom.requiredSets.html(badges.join(''));
			}

			if (!optional.length) {
				dom.optionalSets.html('<span class="text-muted">' + t('LBL_NO_OPTIONAL_SETS', 'Brak dodatkowych zestawów') + '</span>');
			} else {
				const list = $('<div/>');
				optional.forEach(function (set, index) {
					const text = (set || []).join(' + ');
					const $row = $('<div class="form-check"/>');
					const $checkbox = $('<input type="checkbox" class="form-check-input js-optional-set"/>')
						.attr('id', 'ImportManagerOptionalSet' + index)
						.attr('data-index', index);
					const $label = $('<label class="form-check-label"/>')
						.attr('for', 'ImportManagerOptionalSet' + index)
						.text(text);

					$row.append($checkbox, $label);
					list.append($row);
				});
				dom.optionalSets.html(list);
			}
		}

		function collectMappingPayload() {
			const rows = [];
			const defaults = {};
			const usedFields = {};
			let mappedCount = 0;

			dom.mappingTable.find('tbody tr').each(function () {
				const $row = $(this);
				const fieldName = $row.data('field');
				if (!fieldName || usedFields[fieldName]) {
					return;
				}
				usedFields[fieldName] = true;

				const $sourceSelect = $row.find('.js-source-select');
				const sourceValue = $sourceSelect.val();
				let columnIndex = null;
				let columnName = '';
				if (sourceValue !== '') {
					const parsed = parseInt(sourceValue, 10);
					if (!Number.isNaN(parsed)) {
						columnIndex = parsed;
						const selectedOption = $sourceSelect.find('option:selected');
						columnName = selectedOption.data('columnName') || selectedOption.text() || '';
					}
				}

				const defaultValue = $row.find('.js-default-value').val();
				if (defaultValue !== '') {
					defaults[fieldName] = defaultValue;
				}

				if (columnIndex === null && defaultValue === '' && Number($row.data('mandatory')) === 1) {
					const label = $row.data('label') || fieldName;
					throw new Error(t('JS_COLUMN_OR_DEFAULT_REQUIRED', 'Wybierz kolumnę z pliku lub ustaw wartość domyślną dla pola %s.').replace('%s', label));
				}

				if (columnIndex !== null || defaultValue !== '') {
					mappedCount++;
				}
				rows.push({
					index: Number.isInteger(columnIndex) ? columnIndex : null,
					column: columnName,
					field: fieldName
				});
			});

			if (mappedCount === 0) {
				throw new Error(t('JS_MAPPING_REQUIRED', 'Wybierz co najmniej jedno pole docelowe.'));
			}

			return {
				rows,
				defaults
			};
		}

		function collectDuplicateSets() {
			const indexes = [];
			dom.optionalSets.find('.js-optional-set:checked').each(function () {
				const idx = parseInt($(this).attr('data-index'), 10);
				if (!isNaN(idx)) {
					indexes.push(idx);
				}
			});
			return { optionalActive: indexes };
		}

		function saveMappingDefinition() {
			if (!state.batchId) {
				handleError({ error: { message: t('JS_BATCH_NOT_READY', 'Brak przygotowanego wsadu. Wygeneruj najpierw podgląd.') } });
				return;
			}

			let mappingPayload;
			let duplicates;
			try {
				mappingPayload = collectMappingPayload();
				duplicates = collectDuplicateSets();
			} catch (validationError) {
				handleError({ error: { message: validationError.message } });
				return;
			}

			const payload = {
				module: 'ImportManager',
				action: 'SaveMapping',
				batch_id: state.batchId,
				target_module: state.moduleName,
				mapping: JSON.stringify(mappingPayload.rows),
				default_values: JSON.stringify(mappingPayload.defaults),
				duplicate_sets: JSON.stringify(duplicates),
				source_headers: JSON.stringify(state.preview ? (state.preview.headers || []) : []),
				duplicate_strategy: dom.duplicateStrategy.val() || 'skip'
			};
			if (typeof window.csrfMagicToken !== 'undefined') {
				payload.csrfMagicToken = window.csrfMagicToken;
			}

			const indicator = $.progressIndicator({
				message: t('LBL_SAVING', 'Zapisywanie...'),
				position: 'html',
				blockInfo: { enabled: true }
			});

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
					showConfirmationStep(mappingPayload.rows, response.result || {});
					showToast(t('JS_MAPPING_SAVE_SUCCESS', 'Mapowanie zostało zapisane.'), 'success');
				})
				.fail(handleError)
				.always(function () {
					indicator.progressIndicator({ mode: 'hide' });
				});
		}

		function showConfirmationStep(mapping, serverResult) {
			dom.summaryModule.text(state.moduleName || '—');
			dom.summaryFile.text(state.fileMeta.name || '—');
			const strategy = serverResult.duplicate_strategy || dom.duplicateStrategy.val() || 'skip';
			const strategyLabel = dom.duplicateStrategy.find('option[value="' + strategy + '"]').text() || strategy;
			dom.summaryStrategy.text(strategyLabel);
			dom.summaryFields.text(mapping.length);
			dom.confirmationStatus.text(t('JS_MAPPING_READY', 'Mapowanie zapisane – możesz przejść do potwierdzenia.')).removeClass('d-none');
		dom.startImport.prop('disabled', false);
			dom.step3.removeClass('d-none');
		}

	function triggerStaging() {
		if (!state.batchId) {
			handleError({ error: { message: t('JS_BATCH_NOT_READY', 'Brak przygotowanego wsadu. Wygeneruj najpierw podgląd.') } });
			return;
		}

		const indicator = $.progressIndicator({
			message: t('LBL_STAGING_IN_PROGRESS', 'Trwa przygotowywanie danych...'),
			position: 'html',
			blockInfo: { enabled: true }
		});

		const payload = {
			module: 'ImportManager',
			action: 'Stage',
			batch_id: state.batchId
		};
		if (typeof window.csrfMagicToken !== 'undefined') {
			payload.csrfMagicToken = window.csrfMagicToken;
		}

		$.ajax({
			url: 'index.php',
			type: 'POST',
			dataType: 'json',
			data: payload
		})
			.done(function (response) {
				indicator.progressIndicator({ mode: 'hide' });
				if (!response || response.success !== true) {
					handleError(response);
					return;
				}
				handleStagingResult(response.result || {});
			})
			.fail(function (jqXHR) {
				indicator.progressIndicator({ mode: 'hide' });
				handleError(jqXHR);
			});
	}

	function handleStagingResult(result) {
		const total = result.total || 0;
		const failed = result.failed || 0;
		const successMsg = t('LBL_STAGE_SUCCESS', 'Staging zakończony. Rekordy: %s, błędne: %s.')
			.replace('%s', total)
			.replace('%s', failed);
		dom.confirmationStatus
			.removeClass('alert-success alert-danger')
			.addClass(failed > 0 ? 'alert-danger' : 'alert-success')
			.text(successMsg)
			.removeClass('d-none');
		showToast(successMsg, failed > 0 ? 'warning' : 'success');
	}

		function resetWizardState() {
			state.batchId = null;
			state.preview = null;
			state.fileMeta = {};
			state.moduleName = null;
			state.fields = [];
			state.duplicateConfig = { requiredSets: [], optionalSets: [] };
			dom.previewCard.addClass('d-none');
			dom.step2.addClass('d-none');
			dom.step3.addClass('d-none');
			dom.mappingTable.find('tbody').empty();
			dom.requiredSets.empty();
			dom.optionalSets.empty();
			dom.confirmationStatus.addClass('d-none').text('');
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

		function showToast(text, type) {
			if (window.app && typeof app.showNotify === 'function') {
				app.showNotify(text, type);
			} else {
				alert(text);
			}
		}

		function t(key, fallback) {
			if (window.app && typeof app.vtranslate === 'function') {
				const translated = app.vtranslate(key);
				if (translated && translated !== key) {
					return translated;
				}
			}
			return fallback || key;
		}
	});
})(jQuery);

