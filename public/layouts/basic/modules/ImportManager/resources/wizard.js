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
			targetModule: $('#ImportManagerTargetModule'),
			formatField: $('#ImportManagerFormat'),
			xmlOnly: $('.js-import-xml-only'),
			csvOptions: $('.csv-separator-options'),
			previewCard: $('#ImportManagerPreview'),
			previewMeta: $('#ImportManagerPreviewMeta'),
			stepsContainer: $('#ImportManagerStepsContainer'),
			step2: $(),
			step3: $(),
			step4: $(),
			mappingTable: $(),
			requiredSets: $(),
			optionalSets: $(),
			duplicateStrategy: $(),
			summaryModule: $(),
			summaryFile: $(),
			summaryStrategy: $(),
			summaryFields: $(),
			confirmationStatus: $(),
			retryAlert: $(),
			openRetry: $(),
			retryCard: $(),
			importSection: $(),
			importSummary: $(),
			importHint: $(),
			fileInput: $('#ImportManagerFile'),
		};

		const state = {
			batchId: null,
			moduleName: null,
			fields: [],
			duplicateConfig: {
				activeSets: [],
				suggestedSets: [],
			},
			preview: null,
			fileMeta: {},
			readyRows: 0,
			pendingPreview: false,
		};
		

		function refreshDomReferences() {
			dom.step2 = $('#ImportManagerStep2');
			dom.step3 = $('#ImportManagerStep3');
			dom.step4 = $('#ImportManagerStep4');
			dom.mappingTable = $('#ImportManagerMappingTable');
			dom.requiredSets = $('#ImportManagerRequiredSets');
			dom.optionalSets = $('#ImportManagerOptionalSets');
			dom.duplicateStrategy = $('#ImportManagerDuplicateStrategy');
			dom.summaryModule = $('#ImportManagerSummaryModule');
			dom.summaryFile = $('#ImportManagerSummaryFile');
			dom.summaryStrategy = $('#ImportManagerSummaryStrategy');
			dom.summaryFields = $('#ImportManagerSummaryFields');
			dom.confirmationStatus = $('#ImportManagerConfirmationStatus');
			dom.importSection = $('#ImportManagerImportSection');
			dom.importSummary = $('#ImportManagerImportSummary');
			dom.importHint = $('#ImportManagerImportHint');
			dom.retryAlert = $('#ImportManagerRetryAlert');
			dom.openRetry = $('#ImportManagerOpenRetry');
			dom.retryCard = $('#ImportManagerStep4');
		}

		function ensureStepRendered(step) {
			const stepId = stepIds[step];
			if (!stepId || document.getElementById(stepId)) {
				return;
			}
			const templateSelector = stepTemplates[step];
			const template = templateSelector ? document.querySelector(templateSelector) : null;
			if (!template || !template.content) {
				return;
			}
			const clone = template.content.cloneNode(true);
			dom.stepsContainer.append(clone);
			refreshDomReferences();
		}

		const stepTemplates = {
			2: '#ImportManagerStep2Template',
			3: '#ImportManagerStep3Template',
			4: '#ImportManagerStep4Template'
		};
		const stepIds = {
			2: 'ImportManagerStep2',
			3: 'ImportManagerStep3',
			4: 'ImportManagerStep4'
		};

		refreshDomReferences();

		setTimeout(toggleFormatFieldsVisibility, 100);
		dom.formatField.on('change', toggleFormatFieldsVisibility);
		dom.targetModule.on('change', function () {
			resetWizardState();
			state.moduleName = dom.targetModule.val() || null;
			attemptAutoPreview();
		});
		dom.fileInput.on('change', function () {
			const files = this.files;
			if (!files || !files.length) {
				state.pendingPreview = false;
				return;
			}
			state.pendingPreview = true;
			attemptAutoPreview();
		});
		dom.form.on('submit', handleUploadSubmit);
		$(document).on('click', '#ImportManagerSaveMapping', function () {
			saveMappingDefinition();
		});
		$(document).on('click', '#ImportManagerStartInline', function () {
			triggerStaging('inline');
		});
		$(document).on('click', '#ImportManagerStartQueued', function () {
			triggerStaging('queue');
		});
		$(document).on('click', '#ImportManagerRunImportInline', function () {
			triggerImport('inline');
		});
		$(document).on('click', '#ImportManagerRunImportQueue', function () {
			triggerImport('queue');
		});
		$(document).on('change', '#ImportManagerOptionalSets .js-optional-set', function () {
			const idx = parseInt($(this).attr('data-index'), 10);
			if (!Number.isNaN(idx)) {
				toggleSuggestedSet(idx, $(this).is(':checked'));
			}
		});
		$(document).on('click', '.js-remove-duplicate-set', function (event) {
			event.preventDefault();
			const idx = parseInt($(this).attr('data-index'), 10);
			if (!Number.isNaN(idx)) {
				removeDuplicateSet(idx);
			}
		});
		$(document).on('click', '#ImportManagerOpenRetry', function () {
			if (!state.batchId) {
				return;
			}
			window.location = 'index.php?module=ImportManager&view=Retry&batch_id=' + state.batchId;
		});

		function handleUploadSubmit(event) {
			event.preventDefault();
			state.pendingPreview = false;
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
			state.pendingPreview = false;
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
			ensureStepRendered(2);
			refreshDomReferences();
			const indicator = $.progressIndicator({
				message: t('LBL_LOADING', 'Ładowanie...'),
				position: 'html',
				blockInfo: { enabled: true }
			});

			loadModuleMetadata(state.moduleName)
				.done(function () {
					renderMappingTable(state.preview.headers || []);
					renderDuplicateSections();
					if (dom.step2.length) {
						dom.step2.removeClass('d-none');
					}
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
					state.duplicateConfig = mapDuplicateConfig(result.duplicateSets || {});
					deferred.resolve();
				})
				.fail(function (jqXHR) {
					deferred.reject(jqXHR);
				});
			return deferred.promise();
		}

		function renderMappingTable(headers) {
			if (!dom.mappingTable.length) {
				ensureStepRendered(2);
				refreshDomReferences();
			}
			const $table = dom.mappingTable;
			if (!$table.length) {
				return;
			}
			$table.removeClass('d-none');
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

		function mapDuplicateConfig(rawConfig) {
			const active = Array.isArray(rawConfig.activeSets)
				? rawConfig.activeSets
				: (Array.isArray(rawConfig.requiredSets) ? rawConfig.requiredSets : []);
			const suggested = Array.isArray(rawConfig.suggestedSets)
				? rawConfig.suggestedSets
				: (Array.isArray(rawConfig.optionalSets) ? rawConfig.optionalSets : []);
			return {
				activeSets: normalizeDuplicateSets(active),
				suggestedSets: normalizeDuplicateSets(suggested),
			};
		}

		function normalizeDuplicateSets(list) {
			if (!Array.isArray(list)) {
				return [];
			}
			const seen = {};
			const result = [];
			list.forEach(function (set) {
				const normalized = normalizeDuplicateSet(set);
				if (!normalized.length) {
					return;
				}
				const key = serializeDuplicateSet(normalized);
				if (!seen[key]) {
					seen[key] = true;
					result.push(normalized);
				}
			});
			return result;
		}

		function normalizeDuplicateSet(set) {
			if (!Array.isArray(set)) {
				return [];
			}
			const values = [];
			set.forEach(function (field) {
				const name = (field || '').toString().trim();
				if (!name) {
					return;
				}
				if (!values.includes(name)) {
					values.push(name);
				}
			});
			return values;
		}

		function serializeDuplicateSet(set) {
			if (!Array.isArray(set)) {
				return '';
			}
			return set
				.map(function (value) {
					return value.toString().toLowerCase();
				})
				.sort()
				.join('::');
		}

		function isDuplicateSetActive(candidate) {
			const candidateKey = serializeDuplicateSet(candidate);
			if (!candidateKey) {
				return false;
			}
			return state.duplicateConfig.activeSets.some(function (current) {
				return serializeDuplicateSet(current) === candidateKey;
			});
		}

		function addDuplicateSet(set) {
			const normalized = normalizeDuplicateSet(set);
			if (!normalized.length || isDuplicateSetActive(normalized)) {
				return;
			}
			const next = state.duplicateConfig.activeSets.slice();
			next.push(normalized);
			state.duplicateConfig.activeSets = next;
			renderDuplicateSections();
		}

		function removeDuplicateSet(index) {
			const current = state.duplicateConfig.activeSets.slice();
			if (index < 0 || index >= current.length) {
				return;
			}
			current.splice(index, 1);
			state.duplicateConfig.activeSets = current;
			renderDuplicateSections();
		}

		function toggleSuggestedSet(index, enabled) {
			const set = state.duplicateConfig.suggestedSets[index];
			if (!set) {
				return;
			}
			if (enabled) {
				addDuplicateSet(set);
				return;
			}
			const key = serializeDuplicateSet(set);
			const remaining = state.duplicateConfig.activeSets.filter(function (current) {
				return serializeDuplicateSet(current) !== key;
			});
			state.duplicateConfig.activeSets = remaining;
			renderDuplicateSections();
		}

		function renderDuplicateSections() {
			if (!dom.requiredSets.length || !dom.optionalSets.length) {
				ensureStepRendered(2);
				refreshDomReferences();
			}
			const active = state.duplicateConfig.activeSets || [];
			const suggestions = state.duplicateConfig.suggestedSets || [];

			if (!active.length) {
				dom.requiredSets.html('<span class="text-muted">' + t('LBL_DUPLICATES_NOT_CONFIGURED', 'Nie zdefiniowano żadnych zestawów duplikatów.') + '</span>');
			} else {
				const list = $('<div class="d-flex flex-wrap"/>');
				active.forEach(function (set, index) {
					const text = (set || []).join(' + ');
					const $badge = $('<span class="badge badge-light mr-1 mb-1 d-flex align-items-center"/>');
					$badge.text(text + ' ');
					const $remove = $('<button type="button" class="btn btn-link btn-sm text-danger p-0 ml-2 js-remove-duplicate-set" aria-label="' + t('LBL_REMOVE', 'Usuń') + '"/>')
						.attr('data-index', index)
						.append('<span class="fa fa-times" aria-hidden="true"></span>');
					$badge.append($remove);
					list.append($badge);
				});
				dom.requiredSets.html(list);
			}

			if (!suggestions.length) {
				dom.optionalSets.html('<span class="text-muted">' + t('LBL_NO_OPTIONAL_SETS', 'Brak dodatkowych zestawów') + '</span>');
			} else {
				const list = $('<div/>');
				suggestions.forEach(function (set, index) {
					const text = (set || []).join(' + ');
					const isActive = isDuplicateSetActive(set);
					const $row = $('<div class="form-check"/>');
					const $checkbox = $('<input type="checkbox" class="form-check-input js-optional-set"/>')
						.attr('id', 'ImportManagerOptionalSet' + index)
						.attr('data-index', index)
						.prop('checked', isActive);
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
			return {
				selected: state.duplicateConfig.activeSets || []
			};
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
			ensureStepRendered(3);
			refreshDomReferences();
			if (dom.summaryModule.length) {
				dom.summaryModule.text(state.moduleName || '—');
			}
			if (dom.summaryFile.length) {
				dom.summaryFile.text(state.fileMeta.name || '—');
			}
			const strategyElement = dom.duplicateStrategy && dom.duplicateStrategy.length ? dom.duplicateStrategy : null;
			const strategyValue = serverResult.duplicate_strategy || (strategyElement ? strategyElement.val() : '') || 'skip';
			let strategyLabel = strategyValue;
			if (strategyElement) {
				const option = strategyElement.find('option[value="' + strategyValue + '"]');
				if (option.length) {
					strategyLabel = option.text();
				}
			}
			if (dom.summaryStrategy.length) {
				dom.summaryStrategy.text(strategyLabel);
			}
			if (dom.summaryFields.length) {
				dom.summaryFields.text(mapping.length);
			}
			if (dom.confirmationStatus.length) {
				dom.confirmationStatus
					.text(t('JS_MAPPING_READY', 'Mapowanie zapisane – możesz przejść do potwierdzenia.'))
					.removeClass('d-none');
			}
			setStageButtonsEnabled(true);
			setImportButtonsEnabled(false);
			if (dom.importSection.length) {
				dom.importSection.addClass('d-none');
			}
			state.readyRows = 0;
			if (dom.step3.length) {
				dom.step3.removeClass('d-none');
			}
		}

	function triggerStaging(mode) {
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
		if (mode) {
			payload.run_mode = mode;
		}
		if (typeof window.csrfMagicToken !== 'undefined') {
			payload.csrfMagicToken = window.csrfMagicToken;
		}
		setStageButtonsEnabled(false);

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
				const payload = response.result || {};
				if (payload.queued) {
					handleQueuedJob(payload);
				} else {
					handleStagingResult(payload.result || payload);
				}
			})
			.fail(function (jqXHR) {
				indicator.progressIndicator({ mode: 'hide' });
				handleError(jqXHR);
			})
			.always(function () {
				setStageButtonsEnabled(true);
			});
	}

	function triggerImport(mode) {
		if (!state.batchId) {
			handleError({ error: { message: t('JS_BATCH_NOT_READY', 'Brak przygotowanego wsadu. Wygeneruj najpierw podgląd.') } });
			return;
		}
		if (state.readyRows <= 0) {
			handleError({ error: { message: t('LBL_IMPORT_NO_READY_ROWS', 'Brak rekordów gotowych do importu – przygotuj dane ponownie.') } });
			return;
		}

		const indicator = $.progressIndicator({
			message: t('LBL_IMPORT_IN_PROGRESS', 'Trwa importowanie rekordów...'),
			position: 'html',
			blockInfo: { enabled: true }
		});

		const payload = {
			module: 'ImportManager',
			action: 'Import',
			batch_id: state.batchId
		};
		if (mode) {
			payload.run_mode = mode;
		}
		setImportButtonsEnabled(false);

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
					setImportButtonsEnabled(true);
					return;
				}
				const payload = response.result || {};
				if (payload.queued) {
					handleImportQueued(payload);
				} else {
					handleImportResult(payload.result || payload);
				}
			})
			.fail(function (jqXHR) {
				indicator.progressIndicator({ mode: 'hide' });
				setImportButtonsEnabled(true);
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
		state.readyRows = Math.max(total - failed, 0);
		updateImportSection(total, failed, false, null);
		showRetryCard(total, failed, false);
		showToast(successMsg, failed > 0 ? 'warning' : 'success');
	}

	function handleQueuedJob(payload) {
		const jobId = payload.jobId || '?';
		const message = t('LBL_STAGE_QUEUED', 'Przygotowanie danych dodano do kolejki (zadanie #%s).').replace('%s', jobId);
		dom.confirmationStatus
			.removeClass('alert-danger alert-success')
			.addClass('alert-info')
			.text(message)
			.removeClass('d-none');
		showRetryCard(0, 0, true, jobId);
		state.readyRows = 0;
		updateImportSection(0, 0, true, jobId);
		showToast(message, 'info');
	}

	function handleImportResult(result) {
		const created = result.created || 0;
		const updated = result.updated || 0;
		const skipped = result.skipped || 0;
		const failed = result.failed || 0;
		const summary = t('LBL_IMPORT_RESULT_MESSAGE', 'Import ukończony. Utworzono: %s, zaktualizowano: %s, pominięto: %s, błędy: %s.')
			.replace('%s', created)
			.replace('%s', updated)
			.replace('%s', skipped)
			.replace('%s', failed);
		if (dom.importSummary.length) {
			dom.importSummary.text(summary);
		}
		if (dom.importSection.length) {
			dom.importSection.removeClass('d-none');
		}
		setImportButtonsEnabled(false);
		state.readyRows = 0;
		showToast(summary, failed > 0 ? 'warning' : 'success');
	}

	function handleImportQueued(payload) {
		const jobId = payload.jobId || '?';
		const message = t('LBL_IMPORT_QUEUED', 'Import dodano do kolejki (zadanie #%s).').replace('%s', jobId);
		if (dom.importSection.length) {
			dom.importSection.addClass('d-none');
		}
		if (dom.importSummary.length) {
			dom.importSummary.text('');
		}
		state.readyRows = 0;
		showToast(message, 'info');
	}

	function showRetryCard(total, failed, queued, jobId) {
		if (queued || failed > 0) {
			ensureStepRendered(4);
			refreshDomReferences();
		}
		if (queued) {
			if (dom.retryCard.length) {
				dom.retryCard.removeClass('d-none');
			}
			const queueText = t('LBL_STAGE_QUEUED', 'Przygotowanie danych dodano do kolejki (zadanie #%s).')
				.replace('%s', jobId || '?');
			if (dom.retryAlert.length) {
				dom.retryAlert
					.removeClass('alert-danger alert-success')
					.addClass('alert-info')
					.text(queueText);
			}
			if (dom.openRetry.length) {
				dom.openRetry.addClass('d-none');
			}
			return;
		}

		if (failed > 0) {
			const msg = t('LBL_RETRY_ALERT', 'Wykryto błędne rekordy (%s/%s). Popraw je przed kontynuacją.')
				.replace('%s', failed)
				.replace('%s', total);
			if (dom.retryAlert.length) {
				dom.retryAlert
					.removeClass('alert-info alert-success')
					.addClass('alert-danger')
					.text(msg)
					.removeClass('d-none');
			}
			if (dom.openRetry.length) {
				dom.openRetry.removeClass('d-none');
			}
			if (dom.retryCard.length) {
				dom.retryCard.removeClass('d-none');
			}
		} else {
			if (dom.retryAlert.length) {
				dom.retryAlert.addClass('d-none').text('');
			}
			if (dom.openRetry.length) {
				dom.openRetry.addClass('d-none');
			}
			if (dom.retryCard.length) {
				dom.retryCard.addClass('d-none');
			}
		}
	}

	function updateImportSection(total, failed, queued, jobId) {
		if (!dom.importSection.length) {
			return;
		}
		if (queued) {
			dom.importSection.addClass('d-none');
			dom.importSummary.text('');
			setImportButtonsEnabled(false);
			return;
		}
		const ready = Math.max((total || 0) - (failed || 0), 0);
		if (ready <= 0) {
			dom.importSection.addClass('d-none');
			dom.importSummary.text('');
			setImportButtonsEnabled(false);
			return;
		}
		const summary = t('LBL_IMPORT_READY_INFO', 'Gotowe rekordy: %s (błędne: %s).')
			.replace('%s', ready)
			.replace('%s', failed || 0);
		dom.importSummary.text(summary);
		dom.importSection.removeClass('d-none');
		setImportButtonsEnabled(true);
	}

	function resetWizardState() {
		state.batchId = null;
		state.preview = null;
		state.fileMeta = {};
		state.moduleName = null;
		state.fields = [];
		state.duplicateConfig = { requiredSets: [], optionalSets: [] };
		state.readyRows = 0;
		state.pendingPreview = false;
		if (dom.stepsContainer && dom.stepsContainer.length) {
			dom.stepsContainer.empty();
			refreshDomReferences();
		}
		dom.previewCard.addClass('d-none');
		dom.step2.addClass('d-none');
		dom.step3.addClass('d-none');
		dom.retryCard.addClass('d-none');
		if (dom.mappingTable.length) {
			dom.mappingTable.find('tbody').empty();
		}
		if (dom.requiredSets.length) {
			dom.requiredSets.empty();
		}
		if (dom.optionalSets.length) {
			dom.optionalSets.empty();
		}
		if (dom.confirmationStatus.length) {
			dom.confirmationStatus.addClass('d-none').text('');
		}
		if (dom.openRetry.length) {
			dom.openRetry.addClass('d-none');
		}
		if (dom.retryAlert.length) {
			dom.retryAlert.addClass('d-none').text('');
		}
		setStageButtonsEnabled(false);
		setImportButtonsEnabled(false);
		if (dom.importSection.length) {
			dom.importSection.addClass('d-none');
		}
		if (dom.importSummary.length) {
			dom.importSummary.text('');
		}
	}

	function setStageButtonsEnabled(enabled) {
		$('#ImportManagerStartInline, #ImportManagerStartQueued').prop('disabled', !enabled);
	}

	function setImportButtonsEnabled(enabled) {
		$('#ImportManagerRunImportInline, #ImportManagerRunImportQueue').prop('disabled', !enabled);
	}

	function attemptAutoPreview() {
		if (!state.pendingPreview) {
			return;
		}
		const formEl = dom.form.get(0);
		if (!formEl || !dom.targetModule.val()) {
			return;
		}
		const fileEl = dom.fileInput && dom.fileInput.length ? dom.fileInput.get(0) : null;
		if (!fileEl || !fileEl.files || !fileEl.files.length) {
			return;
		}
		if (!formEl.checkValidity()) {
			return;
		}
		state.pendingPreview = false;
		uploadAndPreview();
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

