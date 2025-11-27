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
			defaultValues: $('#ImportManagerDefaultValues'),
			addDefaultButton: $('#ImportManagerAddDefaultValue'),
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
		dom.addDefaultButton.on('click', function () {
			addDefaultRow();
		});
		dom.defaultValues.on('click', '.js-remove-default', function () {
			$(this).closest('.default-row').remove();
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
			const $tbody = dom.mappingTable.find('tbody').empty();
			headers.forEach(function (header, index) {
				const autoField = guessFieldForHeader(header);
				const $row = $('<tr/>').attr('data-column-index', index);
				const $columnCell = $('<td/>').addClass('js-column-name').text(header || ('Column ' + (index + 1)));
				const $fieldCell = $('<td/>');
				const $detailsCell = $('<td class="d-none d-md-table-cell text-muted small"/>');

				const $select = $('<select class="form-control form-control-sm js-field-select"/>');
				$select.append('<option value="">' + t('LBL_SELECT_OPTION', 'Wybierz') + '</option>');
				state.fields.forEach(function (field) {
					const label = field.label ? field.label + ' (' + field.name + ')' : field.name;
					const option = $('<option/>').val(field.name).text(label);
					if (autoField && autoField.name === field.name) {
						option.prop('selected', true);
					}
					if (field.mandatory) {
						option.attr('data-mandatory', '1');
					}
					option.attr('data-type', field.type || '');
					$select.append(option);
				});

				$fieldCell.append($select);
				$detailsCell.text(autoField ? describeField(autoField) : '');

				$select.on('change', function () {
					const field = state.fields.find(f => f.name === $(this).val());
					$detailsCell.text(field ? describeField(field) : '');
				});

				$row.append($columnCell, $fieldCell, $detailsCell);
				$tbody.append($row);
			});

			dom.defaultValues.empty();
		}

		function guessFieldForHeader(header) {
			const normalized = (header || '').toLowerCase().replace(/\W/g, '');
			return state.fields.find(function (field) {
				return field.nameNormalized === normalized || field.labelNormalized === normalized;
			});
		}

		function describeField(field) {
			const parts = [];
			if (field.type) {
				parts.push(field.type);
			}
			if (field.mandatory) {
				parts.push(t('LBL_MANDATORY', 'Wymagane'));
			}
			return parts.join(' • ');
		}

		function addDefaultRow(selectedField, value) {
			if (!state.fields.length) {
				return;
			}
			const $row = $('<div class="default-row form-row align-items-center mb-2"/>');
			const $fieldCol = $('<div class="col-5"/>');
			const $valueCol = $('<div class="col-6"/>');
			const $actionsCol = $('<div class="col-1 text-right"/>');

			const $select = $('<select class="form-control form-control-sm js-default-field"/>')
				.append('<option value="">' + t('LBL_SELECT_OPTION', 'Wybierz') + '</option>');
			state.fields.forEach(function (field) {
				const option = $('<option/>').val(field.name).text(field.label || field.name);
				if (selectedField && selectedField === field.name) {
					option.prop('selected', true);
				}
				$select.append(option);
			});

			const $input = $('<input type="text" class="form-control form-control-sm js-default-value"/>')
				.attr('placeholder', t('LBL_DEFAULT_VALUE_PLACEHOLDER', 'Podaj wartość'));
			if (value) {
				$input.val(value);
			}

			const $remove = $('<button type="button" class="btn btn-link text-danger p-0 js-remove-default" title="' + t('LBL_REMOVE', 'Usuń') + '">&times;</button>');

			$fieldCol.append($select);
			$valueCol.append($input);
			$actionsCol.append($remove);

			$row.append($fieldCol, $valueCol, $actionsCol);
			dom.defaultValues.append($row);
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

		function collectMappingRows() {
			const rows = [];
			const usedFields = {};
			let hasSelection = false;
			dom.mappingTable.find('tbody tr').each(function (index) {
				const $row = $(this);
				const columnName = $row.find('.js-column-name').text();
				const fieldName = $row.find('.js-field-select').val();
				if (!fieldName) {
					return;
				}
				hasSelection = true;
				if (usedFields[fieldName]) {
					throw new Error(t('JS_DUPLICATE_FIELD_MAPPING', 'Pole zostało przypisane wielokrotnie: %s').replace('%s', fieldName));
				}
				usedFields[fieldName] = true;
				rows.push({
					index: index,
					column: columnName,
					field: fieldName
				});
			});

			if (!hasSelection) {
				throw new Error(t('JS_MAPPING_REQUIRED', 'Wybierz co najmniej jedno pole docelowe.'));
			}
			return rows;
		}

		function collectDefaultValues() {
			const values = {};
			dom.defaultValues.find('.default-row').each(function () {
				const field = $(this).find('.js-default-field').val();
				const value = $(this).find('.js-default-value').val();
				if (field && value !== '') {
					values[field] = value;
				}
			});
			return values;
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

			let mapping;
			let defaults;
			let duplicates;
			try {
				mapping = collectMappingRows();
				defaults = collectDefaultValues();
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
				mapping: JSON.stringify(mapping),
				default_values: JSON.stringify(defaults),
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
					showConfirmationStep(mapping, response.result || {});
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
			dom.step3.removeClass('d-none');
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
			dom.defaultValues.empty();
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

