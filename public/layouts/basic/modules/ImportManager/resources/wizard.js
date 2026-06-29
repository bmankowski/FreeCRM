/* +***********************************************************************************
 * ImportManager multi-view workflow helpers.
 * *********************************************************************************** */
(function ($) {
	'use strict';

	const VIEW_UPLOAD = 'upload';
	const VIEW_MAPPING = 'mapping';
	const VIEW_DUPLICATES = 'duplicates';
	const VIEW_STAGING = 'staging';
	const VIEW_FINALIZE = 'finalize';

	$(function () {
		const root = document.getElementById('ImportManagerRoot');
		if (!root) {
			return;
		}
		const viewName = root.getAttribute('data-view') || VIEW_UPLOAD;
		const context = parseContext(document.getElementById('ImportManagerContext'));
		const manager = new ImportManager(viewName, context || {}, root);
		window.ImportManagerInstance = manager; // Global reference for modal callbacks
		manager.init();
	});

	function parseContext(node) {
		if (!node) {
			return {};
		}
		const raw = (node.textContent || node.innerText || '').trim();
		if (!raw) {
			return {};
		}
		try {
			return JSON.parse(raw);
		} catch (error) {
			console.error('ImportManager context parse error', error);
			console.error('Raw content (first 200 chars):', raw.substring(0, 200));
			console.error('Raw content length:', raw.length);
			// Try to find where the error is
			if (error.message && error.message.includes('column')) {
				const match = error.message.match(/column (\d+)/);
				if (match) {
					const col = parseInt(match[1]);
					const start = Math.max(0, col - 50);
					const end = Math.min(raw.length, col + 50);
					console.error('Context around error:', raw.substring(start, end));
				}
			}
			return {};
		}
	}

	function ImportManager(view, context, root) {
		this.view = view;
		this.root = root;
		this.context = context || {};
		this.state = {
			batchId: this.context.batch ? this.context.batch.id : null,
			moduleName: this.context.batch ? this.context.batch.module : null,
			headers: this.context.headers || (this.context.preview ? this.context.preview.headers : []),
			preview: this.context.preview || null,
			fileMeta: this.context.file || {},
			fields: [],
			mapping: this.context.mapping || [],
			defaultValues: this.context.defaultValues || {},
			duplicateSets: this.context.duplicateSets || { required: [], optional: [] },
			duplicateConfig: this.context.duplicateConfig || { activeSets: [], suggestedSets: [] },
			duplicateStrategy: this.context.batch ? (this.context.batch.duplicate_strategy || 'skip') : 'skip',
			stats: this.context.stats || {},
			importSummary: this.context.import || {},
			pendingPreview: false,
		};
		this.dom = {};
	}

	ImportManager.prototype.init = function () {
		switch (this.view) {
			case VIEW_MAPPING:
				this.initMappingView();
				break;
			case VIEW_DUPLICATES:
				this.initDuplicatesView();
				break;
			case VIEW_STAGING:
				this.initStagingView();
				break;
			case VIEW_FINALIZE:
				this.initFinalizeView();
				break;
			case VIEW_UPLOAD:
			default:
				this.initUploadView();
				break;
		}
	};

	/* ----------------------- Upload view ----------------------- */
	ImportManager.prototype.initUploadView = function () {
		this.dom.form = $('#ImportManagerStep1');
		if (!this.dom.form.length) {
			console.warn('ImportManagerStep1 form not found');
			return;
		}
		this.dom.targetModule = $('#ImportManagerTargetModule');
		this.dom.formatField = $('#ImportManagerFormat');
		this.dom.xmlOnly = $('.js-import-xml-only');
		this.dom.csvOptions = $('.csv-separator-options');
		this.dom.fileInput = $('#ImportManagerFile');
		this.dom.batchField = $('#ImportManagerBatchId');
		this.dom.dropzone = $('#ImportManagerDropzone');
		this.dom.submitButton = $('#ImportManagerSubmit');
		

		this.dom.recentList = $('.import-recent-list');
		if (this.dom.recentList.length) {
			this.dom.recentList.on('click', '.js-delete-batch', this.handleDeleteBatch.bind(this));
		}

		this.toggleFormatFieldsVisibility();
		this.dom.formatField.on('change', this.toggleFormatFieldsVisibility.bind(this));
		this.dom.targetModule.on('change', this.handleTargetModuleChange.bind(this));
		this.dom.fileInput.on('change', this.handleFileChange.bind(this));
		this.dom.form.on('submit', this.handleUploadSubmit.bind(this));
		
		// Initialize dropzone visual enhancements
		this.initDropzone();
		
		// Set initial button state
		this.updateSubmitButtonState();
	};
	
	ImportManager.prototype.initDropzone = function () {
		const dropzone = this.dom.dropzone;
		const fileInput = this.dom.fileInput;
		const self = this;
		
		if (!dropzone.length) return;
		
		// Drag and drop visual feedback
		dropzone.on('dragenter dragover', function (e) {
			e.preventDefault();
			e.stopPropagation();
			dropzone.addClass('dragover');
		});
		
		dropzone.on('dragleave', function (e) {
			e.preventDefault();
			e.stopPropagation();
			dropzone.removeClass('dragover');
		});
		
		// Handle file drop
		dropzone.on('drop', function (e) {
			e.preventDefault();
			e.stopPropagation();
			dropzone.removeClass('dragover');
			
			const files = e.originalEvent.dataTransfer.files;
			if (files && files.length > 0) {
				// Assign dropped file to the file input
				const dataTransfer = new DataTransfer();
				dataTransfer.items.add(files[0]);
				fileInput.get(0).files = dataTransfer.files;
				
				// Trigger file change handler
				self.handleFileChange();
			}
		});
		
		// Remove file button
		dropzone.find('.import-dropzone__remove').on('click', (e) => {
			e.preventDefault();
			e.stopPropagation();
			fileInput.val('');
			this.updateDropzonePreview(null);
		});
	};
	
	ImportManager.prototype.updateDropzonePreview = function (file) {
		const dropzone = this.dom.dropzone;
		const content = dropzone.find('.import-dropzone__content');
		const preview = dropzone.find('.import-dropzone__preview');
		
		if (!file) {
			content.show();
			preview.hide();
			return;
		}
		
		// Format file size
		const formatSize = function(bytes) {
			if (bytes < 1024) return bytes + ' B';
			if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
			return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
		};
		
		// Determine icon based on file type
		const ext = file.name.split('.').pop().toLowerCase();
		let iconClass = 'fa-file';
		if (ext === 'csv') iconClass = 'fa-file-csv';
		else if (ext === 'xml') iconClass = 'fa-file-code';
		else if (ext === 'zip') iconClass = 'fa-file-archive';
		
		preview.find('.import-dropzone__file-icon i').attr('class', 'fa ' + iconClass);
		preview.find('.import-dropzone__file-name').text(file.name);
		preview.find('.import-dropzone__file-size').text(formatSize(file.size));
		
		content.hide();
		preview.show();
	};

	ImportManager.prototype.handleTargetModuleChange = function () {
		this.state.moduleName = this.dom.targetModule.val() || null;
		this.updateSubmitButtonState();
	};

	ImportManager.prototype.handleFileChange = function () {
		const files = this.dom.fileInput.get(0).files;
		this.state.pendingPreview = files && files.length > 0;
		
		// Update dropzone visual preview
		if (files && files.length > 0) {
			this.updateDropzonePreview(files[0]);
			this.autoSelectFormatFromFile(files[0]);
		} else {
			this.updateDropzonePreview(null);
		}
		
		this.updateSubmitButtonState();
	};

	ImportManager.prototype.autoSelectFormatFromFile = function (file) {
		if (!this.dom.formatField || !this.dom.formatField.length) {
			return;
		}
		const ext = (file.name.split('.').pop() || '').toLowerCase();
		const supported = ['csv', 'xml', 'zip'];
		if (supported.indexOf(ext) === -1 || this.dom.formatField.val() === ext) {
			return;
		}
		this.dom.formatField.val(ext).trigger('change');
	};
	
	ImportManager.prototype.updateSubmitButtonState = function () {
		const hasFile = this.dom.fileInput.get(0).files && this.dom.fileInput.get(0).files.length > 0;
		const hasModule = !!this.dom.targetModule.val();
		const isReady = hasFile && hasModule;
		
		if (this.dom.submitButton && this.dom.submitButton.length) {
			if (isReady) {
				this.dom.submitButton.closest('.import-card__footer').show();
			} else {
				this.dom.submitButton.closest('.import-card__footer').hide();
			}
		}
	};

	ImportManager.prototype.handleUploadSubmit = function (event) {
		event.preventDefault();
		const formEl = this.dom.form.get(0);
		if (!formEl.checkValidity()) {
			formEl.reportValidity();
			return;
		}
		this.uploadAndPreview();
	};

	ImportManager.prototype.handleDeleteBatch = function (event) {
		event.preventDefault();
		const $btn = $(event.currentTarget);
		const batchId = parseInt($btn.data('batchId'), 10);
		if (!batchId) {
			return;
		}
		if (!window.confirm(t('JS_DELETE_BATCH_CONFIRM', 'Usunąć ten import z historii? Tej operacji nie można cofnąć.'))) {
			return;
		}

		const indicator = this.showProgress(t('LBL_PLEASE_WAIT', 'Proszę czekać...'));
		$.ajax({
			url: 'index.php',
			type: 'POST',
			dataType: 'json',
			data: {
				module: 'ImportManager',
				action: 'DeleteBatch',
				batch_id: batchId,
				csrfMagicToken: window.csrfMagicToken || ''
			}
		})
			.done((response) => {
				indicator.progressIndicator({ mode: 'hide' });
				if (!response || response.success !== true) {
					this.handleError(response);
					return;
				}
				const $item = $btn.closest('.import-recent-item');
				$item.fadeOut(200, function () {
					$(this).remove();
				});
				this.showToast(t('LBL_BATCH_DELETED', 'Import został usunięty.'), 'success');
			})
			.fail((jqXHR) => {
				indicator.progressIndicator({ mode: 'hide' });
				this.handleError(jqXHR);
			});
	};


	ImportManager.prototype.toggleFormatFieldsVisibility = function () {
		const format = (this.dom.formatField.val() || '').toLowerCase();
		const isXml = format === 'xml';
		if (this.dom.xmlOnly.length) {
			this.dom.xmlOnly.css('display', isXml ? '' : 'none');
		}
		if (this.dom.csvOptions.length) {
			isXml ? this.dom.csvOptions.hide() : this.dom.csvOptions.show();
		}
	};

	ImportManager.prototype.uploadAndPreview = function () {
		const formData = new FormData(this.dom.form.get(0));
		formData.append('module', 'ImportManager');
		formData.append('action', 'Upload');
		if (typeof window.csrfMagicToken !== 'undefined') {
			formData.append('csrfMagicToken', window.csrfMagicToken);
		}

		const indicator = this.showProgress(t('LBL_PLEASE_WAIT', 'Proszę czekać...'));

		$.ajax({
			url: 'index.php',
			type: 'POST',
			data: formData,
			dataType: 'json',
			processData: false,
			contentType: false
		})
			.done((response) => {
				indicator.progressIndicator({ mode: 'hide' });
				console.log('Upload AJAX response received:', response);
				if (!response || response.success !== true) {
					console.error('Upload failed - response:', response);
					this.handleError(response);
					return;
				}
				console.log('Upload successful, result:', response.result);
				const batchId = response.result && response.result.batchId ? response.result.batchId : null;
				if (batchId) {
					// Przekieruj od razu do strony mapowania - podgląd będzie tam
					const mappingUrl = 'index.php?module=ImportManager&view=Mapping&batch_id=' + batchId;
					console.log('Redirecting to mapping:', mappingUrl);
					window.location.href = mappingUrl;
				} else {
					console.error('No batchId in upload response');
					this.handleError({ error: { message: t('JS_UPLOAD_NO_BATCH_ID', 'Nie otrzymano ID wsadu z serwera.') } });
				}
			})
			.fail((jqXHR) => {
				indicator.progressIndicator({ mode: 'hide' });
				console.error('Upload AJAX failed:', jqXHR);
				console.error('Response text:', jqXHR.responseText);
				console.error('Status:', jqXHR.status, jqXHR.statusText);
				this.handleError(jqXHR);
			});
	};


	ImportManager.prototype.buildViewUrl = function (viewName) {
		if (!this.state.batchId) {
			return '#';
		}
		return 'index.php?module=ImportManager&view=' + viewName + '&batch_id=' + this.state.batchId;
	};

	/* ----------------------- Mapping view ----------------------- */
	ImportManager.prototype.initMappingView = function () {
		this.dom.mappingTable = $('#ImportManagerMappingTable');
		this.dom.saveMapping = $('#ImportManagerSaveMapping');

		if (!this.state.batchId || !this.state.moduleName) {
			return;
		}

		if (this.dom.saveMapping.length) {
			this.dom.saveMapping.on('click', this.saveMappingDefinition.bind(this));
		}
	};

	ImportManager.prototype.collectMappingPayload = function () {
		const rows = [];
		const defaults = {};
		const usedFields = {};
		let mappedCount = 0;

		this.dom.mappingTable.find('tbody tr').each(function () {
			const $row = $(this);
			const fieldName = $row.data('field');
			if (!fieldName || usedFields[fieldName]) {
				return;
			}
			usedFields[fieldName] = true;

			const $source = $row.find('.js-source-select');
			const indexValue = $source.val();
			let columnIndex = null;
			let columnName = '';
			let hasColumn = false;
			if (indexValue !== '') {
				const parsed = parseInt(indexValue, 10);
				if (!Number.isNaN(parsed)) {
					columnIndex = parsed;
					const $selected = $source.find('option:selected');
					columnName = $selected.data('columnName') || $selected.attr('data-column-name') || '';
					if (!columnName && parsed >= 0 && Array.isArray(this.state.headers) && this.state.headers[parsed]) {
						columnName = this.state.headers[parsed];
					}
					if (!columnName && $selected.length) {
						columnName = $selected.text() || '';
					}
					hasColumn = true;
					mappedCount++;
				}
			}

			const defaultValue = $row.find('.js-default-value').val();
			if (!hasColumn && !defaultValue) {
				return;
			}

			rows.push({
				field: fieldName,
				index: columnIndex,
				column: columnName,
				label: $row.data('label') || fieldName,
			});

			if (defaultValue) {
				defaults[fieldName] = defaultValue;
			}
		});

		if (!rows.length) {
			throw new Error(t('JS_MAPPING_REQUIRED', 'Wybierz co najmniej jedno pole docelowe.'));
		}

		return {
			mapping: rows,
			defaultValues: defaults,
			sourceHeaders: this.state.headers || [],
			duplicateSets: {
				selected: this.state.duplicateConfig.activeSets || [],
			},
			duplicateStrategy: this.state.duplicateStrategy || 'skip',
		};
	};

	ImportManager.prototype.saveMappingDefinition = function () {
		try {
			var payload = this.collectMappingPayload();
		} catch (error) {
			this.handleError({ error: { message: error.message || error } });
			return;
		}

		const data = {
			module: 'ImportManager',
			action: 'SaveMapping',
			batch_id: this.state.batchId,
			target_module: this.state.moduleName,
			mapping: JSON.stringify(payload.mapping),
			default_values: JSON.stringify(payload.defaultValues),
			source_headers: JSON.stringify(payload.sourceHeaders),
			duplicate_sets: JSON.stringify(payload.duplicateSets),
			duplicate_strategy: payload.duplicateStrategy,
			csrfMagicToken: window.csrfMagicToken || ''
		};

		const indicator = this.showProgress(t('LBL_SAVING', 'Zapisywanie...'));

		$.ajax({
			url: 'index.php',
			type: 'POST',
			dataType: 'json',
			data
		})
			.done((response) => {
				indicator.progressIndicator({ mode: 'hide' });
				if (!response || response.success !== true) {
					this.handleError(response);
					return;
				}
				window.location = this.buildViewUrl('Duplicates');
			})
			.fail((jqXHR) => {
				indicator.progressIndicator({ mode: 'hide' });
				this.handleError(jqXHR);
			});
	};

	/* ----------------------- Duplicates view ----------------------- */
	ImportManager.prototype.initDuplicatesView = function () {
		this.dom.requiredSets = $('#ImportManagerRequiredSets');
		this.dom.optionalSets = $('#ImportManagerOptionalSets');
		this.dom.duplicateStrategy = $('#ImportManagerDuplicateStrategy');
		this.dom.saveDuplicates = $('#ImportManagerSaveDuplicates');
		if (this.dom.duplicateStrategy.length) {
			this.dom.duplicateStrategy.val(this.state.duplicateStrategy || 'skip');
		}

		if (!this.state.batchId) {
			return;
		}

		$('#ImportManagerAddDuplicateRule').on('click', this.openDuplicateRuleModal.bind(this));
		// Note: #ImportManagerSaveDuplicateRule click is handled inside openDuplicateRuleModal
		this.dom.optionalSets.on('change', '.js-optional-set', this.handleOptionalSetToggle.bind(this));
		this.dom.requiredSets.on('click', '.js-remove-duplicate-set', this.handleRemoveDuplicateSet.bind(this));
		if (this.dom.saveDuplicates.length) {
			this.dom.saveDuplicates.on('click', this.saveDuplicateConfiguration.bind(this));
		}
	};

	ImportManager.prototype.parseDuplicateFields = function (payload) {
		if (!payload) {
			return [];
		}
		if (Array.isArray(payload)) {
			return payload.slice();
		}
		if (typeof payload === 'string') {
			try {
				const parsed = JSON.parse(payload);
				if (Array.isArray(parsed)) {
					return parsed;
				}
			} catch (error) {
				return payload.split(',').map((item) => item.trim()).filter(Boolean);
			}
		}
		return [];
	};

	ImportManager.prototype.normalizeDuplicateFields = function (fields) {
		const normalized = [];
		(fields || []).forEach((field) => {
			const value = (field || '').toString().trim();
			if (value && !normalized.includes(value)) {
				normalized.push(value);
			}
		});
		return normalized;
	};

	ImportManager.prototype.buildDuplicateSetKey = function (fields) {
		return fields
			.slice()
			.sort((a, b) => a.localeCompare(b))
			.join('::')
			.toLowerCase();
	};

	ImportManager.prototype.buildDuplicateLabel = function (fields) {
		return (fields || []).join(' + ');
	};

	ImportManager.prototype.addDuplicateChip = function (fields, label) {
		if (!this.dom.requiredSets.length) {
			return;
		}
		const normalized = this.normalizeDuplicateFields(fields);
		if (!normalized.length) {
			return;
		}
		const key = this.buildDuplicateSetKey(normalized);
		if (this.dom.requiredSets.find('.js-duplicate-set[data-key="' + key + '"]').length) {
			return;
		}
		const chipLabel = label || this.buildDuplicateLabel(normalized);
		const $chip = $('<span class="import-duplicates-chip js-duplicate-set"/>')
			.attr('data-key', key)
			.attr('data-fields', JSON.stringify(normalized))
			.text(chipLabel);
		const $remove = $('<button type="button" class="import-duplicates-chip__remove js-remove-duplicate-set" aria-label="' + t('LBL_REMOVE', 'Usuń') + '"><span class="fa fa-times"></span></button>')
			.attr('data-key', key);
		$chip.append($remove);
		this.dom.requiredSets.find('.js-duplicates-empty').remove();
		this.dom.requiredSets.append($chip);
		this.syncOptionalCheckbox(key, true);
		this.toggleDuplicateEmptyState();
	};

	ImportManager.prototype.toggleDuplicateEmptyState = function () {
		if (!this.dom.requiredSets.length) {
			return;
		}
		const hasSets = this.dom.requiredSets.find('.js-duplicate-set').length > 0;
		const $placeholder = this.dom.requiredSets.find('.js-duplicates-empty');
		if (!hasSets && !$placeholder.length) {
			this.dom.requiredSets.append('<span class="text-muted js-duplicates-empty">' + t('LBL_DUPLICATES_NOT_CONFIGURED', 'Nie zdefiniowano żadnych zestawów duplikatów.') + '</span>');
		} else if (hasSets && $placeholder.length) {
			$placeholder.remove();
		}
	};

	ImportManager.prototype.syncOptionalCheckbox = function (key, checked) {
		if (!this.dom.optionalSets.length) {
			return;
		}
		const $checkbox = this.dom.optionalSets.find('.js-optional-set[data-key="' + key + '"]');
		if ($checkbox.length) {
			$checkbox.prop('checked', checked === true);
		}
	};

	ImportManager.prototype.collectDuplicateSets = function () {
		const result = [];
		if (!this.dom.requiredSets || !this.dom.requiredSets.length) {
			return result;
		}
		// Zbierz tylko widoczne elementy (nie ukryte/usunięte)
		this.dom.requiredSets.find('.js-duplicate-set:visible').each(function () {
			const $chip = $(this);
			const payload = $chip.attr('data-fields');
			if (!payload) {
				return;
			}
			try {
				const parsed = JSON.parse(payload);
				if (Array.isArray(parsed) && parsed.length) {
					result.push(parsed);
				}
			} catch (error) {
				console.warn('Failed to parse duplicate set fields:', payload, error);
			}
		});
		console.log('Collected duplicate sets:', result.length, result);
		return result;
	};

	ImportManager.prototype.handleOptionalSetToggle = function (event) {
		const $checkbox = $(event.currentTarget);
		const fields = this.parseDuplicateFields($checkbox.attr('data-fields'));
		if (!fields.length) {
			return;
		}
		const label = $checkbox.attr('data-label') || this.buildDuplicateLabel(fields);
		if ($checkbox.is(':checked')) {
			this.addDuplicateChip(fields, label);
		} else {
			const key = $checkbox.attr('data-key');
			if (key) {
				this.removeDuplicateSetByKey(key, { syncOptional: false });
			}
		}
		this.toggleDuplicateEmptyState();
		// Automatycznie zapisz zmiany do bazy danych
		this.saveDuplicateSetsToDatabase();
	};

	ImportManager.prototype.handleRemoveDuplicateSet = function (event) {
		event.preventDefault();
		event.stopPropagation();
		const $button = $(event.currentTarget);
		
		// Najpierw spróbuj znaleźć chip (rodzic przycisku)
		const $chip = $button.closest('.js-duplicate-set');
		if (!$chip.length) {
			console.error('Cannot find parent chip element');
			return;
		}
		
		const chipKey = $chip.attr('data-key');
		const buttonKey = $button.attr('data-key');
		const key = chipKey || buttonKey;
		
		console.log('handleRemoveDuplicateSet called');
		console.log('Button key:', buttonKey);
		console.log('Chip key:', chipKey);
		console.log('Using key:', key);
		console.log('Chip element:', $chip);
		
		if (!key) {
			console.error('No data-key attribute found on chip or button');
			return;
		}
		
		this.removeDuplicateSetByKey(key);
		// Automatycznie zapisz zmiany do bazy danych
		this.saveDuplicateSetsToDatabase();
	};

	ImportManager.prototype.removeDuplicateSetByKey = function (key, options) {
		if (!key) {
			console.warn('removeDuplicateSetByKey called without key');
			return;
		}
		if (!this.dom.requiredSets || !this.dom.requiredSets.length) {
			console.warn('requiredSets container not found');
			return;
		}
		console.log('Looking for duplicate set with key:', key);
		console.log('All duplicate sets in DOM:', this.dom.requiredSets.find('.js-duplicate-set').map(function() {
			return $(this).attr('data-key');
		}).get());
		
		// Użyj filter zamiast selektora atrybutu, aby uniknąć problemów z escapowaniem
		const $chip = this.dom.requiredSets.find('.js-duplicate-set').filter(function() {
			const chipKey = $(this).attr('data-key');
			const matches = chipKey === key;
			if (!matches && chipKey) {
				console.log('Key mismatch - looking for:', key, 'found:', chipKey, 'equal:', chipKey === key);
			}
			return matches;
		});
		
		if ($chip.length) {
			console.log('Found and removing duplicate set with key:', key, 'chip:', $chip);
			$chip.remove();
			if (!options || options.syncOptional !== false) {
				this.syncOptionalCheckbox(key, false);
			}
			this.toggleDuplicateEmptyState();
		} else {
			console.error('Duplicate set with key not found:', key);
			console.log('Available keys:', this.dom.requiredSets.find('.js-duplicate-set').map(function() {
				return $(this).attr('data-key');
			}).get());
		}
	};

	ImportManager.prototype.openDuplicateRuleModal = function () {
		const $modalContent = $('#ImportManagerDuplicateRuleModal');
		if (!$modalContent.length) {
			return;
		}
		// Reset checkboxes
		$modalContent.find('.js-duplicate-field-checkbox').prop('checked', false);
		
		// Use CRM's standard modal system
		const modalHtml = $modalContent.clone().removeClass('d-none').show();
		app.showModalWindow(modalHtml, function(modalContainer) {
			// Re-bind the save button in the new modal instance
			modalContainer.find('#ImportManagerSaveDuplicateRule').off('click').on('click', function() {
				window.ImportManagerInstance.saveNewDuplicateRuleFromModal(modalContainer);
			});
		});
	};
	
	ImportManager.prototype.saveNewDuplicateRuleFromModal = function (modalContainer) {
		const selectedFields = [];
		const fieldNames = [];
		modalContainer.find('.js-duplicate-field-checkbox:checked').each(function () {
			const name = $(this).val();
			const label = $(this).data('label') || name;
			selectedFields.push(label);
			fieldNames.push(name);
		});
		if (fieldNames.length === 0) {
			app.showNotify({
				title: t('JS_SELECT_AT_LEAST_ONE_FIELD', 'Wybierz co najmniej jedno pole.'),
				type: 'error'
			});
			return;
		}
		const label = selectedFields.join(' + ');
		this.addDuplicateChip(fieldNames, label);
		this.toggleDuplicateEmptyState();
		app.hideModalWindow();
		app.showNotify({
			title: t('JS_DUPLICATE_RULE_ADDED', 'Reguła duplikatów została dodana.'),
			type: 'success'
		});
		// Auto-save to database
		this.saveDuplicateSetsToDatabase();
	};

	ImportManager.prototype.saveNewDuplicateRule = function () {
		const selectedFields = [];
		const labels = [];
		$('#ImportManagerDuplicateRuleFields .js-duplicate-field-checkbox:checked').each(function () {
			const value = $(this).val();
			if (value) {
				selectedFields.push(value);
				const label = $(this).attr('data-label') || $(this).closest('.form-check').find('label').text() || value;
				labels.push(label.trim());
			}
		});
		if (!selectedFields.length) {
			this.showToast(t('JS_SELECT_AT_LEAST_ONE_FIELD', 'Wybierz co najmniej jedno pole.'), 'error');
			return;
		}
		this.addDuplicateChip(selectedFields, labels.join(' + '));
		this.toggleDuplicateEmptyState();
		$('#ImportManagerDuplicateRuleModal').modal('hide');
		this.showToast(t('JS_DUPLICATE_RULE_ADDED', 'Reguła duplikatów została dodana.'), 'success');
		// Automatycznie zapisz zmiany do bazy danych
		this.saveDuplicateSetsToDatabase();
	};

	ImportManager.prototype.saveDuplicateSetsToDatabase = function (showToastOnSuccess) {
		if (!this.state.batchId) {
			console.warn('Cannot save duplicate sets: no batchId');
			return;
		}
		const selectedSets = this.collectDuplicateSets();
		console.log('Auto-saving duplicate sets:', selectedSets.length, selectedSets);
		const data = {
			module: 'ImportManager',
			action: 'SaveDuplicates',
			batch_id: this.state.batchId,
			duplicate_sets: JSON.stringify({ selected: selectedSets }),
			duplicate_strategy: this.dom.duplicateStrategy ? (this.dom.duplicateStrategy.val() || 'skip') : 'skip',
			csrfMagicToken: window.csrfMagicToken || ''
		};

		$.ajax({
			url: 'index.php',
			type: 'POST',
			dataType: 'json',
			data
		})
			.done((response) => {
				if (!response || response.success !== true) {
					console.error('Failed to save duplicate sets:', response);
					if (showToastOnSuccess) {
						this.handleError(response);
					}
					return;
				}
				console.log('Duplicate sets saved successfully');
			})
			.fail((jqXHR) => {
				console.error('Failed to save duplicate sets:', jqXHR);
				if (showToastOnSuccess) {
					this.handleError(jqXHR);
				}
			});
	};

	ImportManager.prototype.saveDuplicateConfiguration = function () {
		const selectedSets = this.collectDuplicateSets();
		console.log('Saving duplicate configuration with sets:', selectedSets);
		const data = {
			module: 'ImportManager',
			action: 'SaveDuplicates',
			batch_id: this.state.batchId,
			duplicate_sets: JSON.stringify({ selected: selectedSets }),
			duplicate_strategy: this.dom.duplicateStrategy.val() || 'skip',
			csrfMagicToken: window.csrfMagicToken || ''
		};
		console.log('Sending data:', data);

		const indicator = this.showProgress(t('LBL_SAVING', 'Zapisywanie...'));

		$.ajax({
			url: 'index.php',
			type: 'POST',
			dataType: 'json',
			data
		})
			.done((response) => {
				indicator.progressIndicator({ mode: 'hide' });
				if (!response || response.success !== true) {
					this.handleError(response);
					return;
				}
				window.location = this.buildViewUrl('Staging');
			})
			.fail((jqXHR) => {
				indicator.progressIndicator({ mode: 'hide' });
				this.handleError(jqXHR);
			});
	};

	/* ----------------------- Staging view ----------------------- */
	ImportManager.prototype.initStagingView = function () {
		this.dom.stageInline = $('#ImportManagerStartInline');
		this.dom.stageQueued = $('#ImportManagerStartQueued');
		this.dom.confirmationStatus = $('#ImportManagerConfirmationStatus');
		this.dom.retryAlert = $('#ImportManagerRetryAlert');
		this.dom.openRetry = $('#ImportManagerOpenRetry');
		this.dom.stageTotals = $('#ImportManagerStageTotals');

		if (this.dom.stageInline.length) {
			this.dom.stageInline.on('click', () => this.triggerStaging('inline'));
		}
		if (this.dom.stageQueued.length) {
			this.dom.stageQueued.on('click', () => this.triggerStaging('queue'));
		}
		if (this.dom.openRetry.length) {
			this.dom.openRetry.on('click', () => {
				if (this.state.batchId) {
					window.location = this.buildViewUrl('Retry');
				}
			});
		}
	};

	ImportManager.prototype.triggerStaging = function (mode) {
		if (!this.state.batchId) {
			return;
		}
		const indicator = this.showProgress(t('LBL_STAGING_IN_PROGRESS', 'Trwa przygotowywanie danych...'));
		const data = {
			module: 'ImportManager',
			action: 'Stage',
			batch_id: this.state.batchId,
			run_mode: mode || '',
			csrfMagicToken: window.csrfMagicToken || ''
		};

		$.ajax({
			url: 'index.php',
			type: 'POST',
			dataType: 'json',
			data
		})
			.done((response) => {
				indicator.progressIndicator({ mode: 'hide' });
				if (!response || response.success !== true) {
					this.handleError(response);
					return;
				}
				const result = response.result || {};
				if (result.queued) {
					this.showToast(t('LBL_STAGE_QUEUED', 'Przygotowanie danych dodano do kolejki.') + ' #' + (result.jobId || ''), 'info');
					return;
				}
				window.location.reload();
			})
			.fail((jqXHR) => {
				indicator.progressIndicator({ mode: 'hide' });
				this.handleError(jqXHR);
			});
	};

	/* ----------------------- Finalize view ----------------------- */
	ImportManager.prototype.initFinalizeView = function () {
		this.dom.importSection = $('#ImportManagerImportSection');
		this.dom.importSummary = $('#ImportManagerImportSummary');
		this.dom.importHint = $('#ImportManagerImportHint');
		this.dom.confirmationStatus = $('#ImportManagerConfirmationStatus');
		this.dom.runImportInline = $('#ImportManagerRunImportInline');
		this.dom.runImportQueue = $('#ImportManagerRunImportQueue');

		if (this.dom.runImportInline.length) {
			this.dom.runImportInline.on('click', () => this.triggerImport('inline'));
		}
		if (this.dom.runImportQueue.length) {
			this.dom.runImportQueue.on('click', () => this.triggerImport('queue'));
		}
	};

	ImportManager.prototype.triggerImport = function (mode) {
		if (!this.state.batchId) {
			return;
		}
		const indicator = this.showProgress(t('LBL_IMPORT_IN_PROGRESS', 'Trwa importowanie rekordów...'));
		const data = {
			module: 'ImportManager',
			action: 'Import',
			batch_id: this.state.batchId,
			run_mode: mode || '',
			csrfMagicToken: window.csrfMagicToken || ''
		};

		$.ajax({
			url: 'index.php',
			type: 'POST',
			dataType: 'json',
			data
		})
			.done((response) => {
				indicator.progressIndicator({ mode: 'hide' });
				if (!response || response.success !== true) {
					this.handleError(response);
					return;
				}
				const result = response.result || {};
				if (result.queued) {
					this.showToast(t('LBL_IMPORT_QUEUED', 'Import dodano do kolejki.') + ' #' + (result.jobId || ''), 'info');
					return;
				}
				if (result.result) {
					this.state.importSummary = result.result;
					if (this.dom.confirmationStatus.length) {
						this.dom.confirmationStatus
							.removeClass('d-none')
							.text(
								t('LBL_IMPORT_RESULT_MESSAGE', 'Import zakończony. Utworzono: %s, zaktualizowano: %s, błędy: %s.')
									.replace('%s, zaktualizowano: %s, błędy: %s.', result.result.created + ', zaktualizowano: ' + result.result.updated + ', błędy: ' + result.result.failed + '.')
							);
					}
					window.location.reload();
				}
			})
			.fail((jqXHR) => {
				indicator.progressIndicator({ mode: 'hide' });
				this.handleError(jqXHR);
			});
	};

	/* ----------------------- Helpers ----------------------- */
	ImportManager.prototype.showProgress = function (message) {
		return $.progressIndicator({
			message: message || t('LBL_PLEASE_WAIT', 'Proszę czekać...'),
			position: 'html',
			blockInfo: { enabled: true }
		});
	};

	ImportManager.prototype.handleError = function (payload) {
		let message = null;
		if (payload && payload.error) {
			message = payload.error.message || payload.error;
		} else if (payload && payload.responseJSON && payload.responseJSON.error) {
			message = payload.responseJSON.error.message || payload.responseJSON.error;
		} else if (payload && payload.responseText) {
			message = payload.responseText;
		}
		this.showToast(message || t('JS_OPERATION_FAILED', 'Operacja nie powiodła się.'), 'error');
	};

	ImportManager.prototype.showToast = function (text, type) {
		if (window.app && typeof app.showNotify === 'function') {
			app.showNotify({ text: text, type: type });
		} else {
			if (type === 'error') {
				console.error(text);
			} else {
				console.log(text);
			}
		}
	};

	function t(key, fallback) {
		if (window.app && typeof app.vtranslate === 'function') {
			const translated = app.vtranslate(key);
			if (translated && translated !== key) {
				return translated;
			}
		}
		return fallback || key;
	}
})(jQuery);


