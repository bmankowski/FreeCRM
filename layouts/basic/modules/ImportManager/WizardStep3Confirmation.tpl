<div id="ImportManagerStep3" class="card mt-4 d-none import-manager-step">
	<div class="card-header">
		<strong>{\App\Language::translate('LBL_STEP3_TITLE', $MODULE_NAME)}</strong>
	</div>
	<div class="card-body">
		<p class="text-muted">{\App\Language::translate('LBL_CONFIRMATION_DESCRIPTION', $MODULE_NAME)}</p>
		<div id="ImportManagerConfirmationStatus" class="alert alert-success d-none"></div>
		<dl class="row mb-0">
			<dt class="col-sm-4">{\App\Language::translate('LBL_SELECTED_MODULE', $MODULE_NAME)}</dt>
			<dd class="col-sm-8" id="ImportManagerSummaryModule">—</dd>

			<dt class="col-sm-4">{\App\Language::translate('LBL_SELECTED_FILE', $MODULE_NAME)}</dt>
			<dd class="col-sm-8" id="ImportManagerSummaryFile">—</dd>

			<dt class="col-sm-4">{\App\Language::translate('LBL_DUPLICATE_MODE', $MODULE_NAME)}</dt>
			<dd class="col-sm-8" id="ImportManagerSummaryStrategy">—</dd>

			<dt class="col-sm-4">{\App\Language::translate('LBL_MAPPED_FIELDS_COUNT', $MODULE_NAME)}</dt>
			<dd class="col-sm-8" id="ImportManagerSummaryFields">—</dd>
		</dl>

		<div class="alert alert-info mt-3 mb-0">
			<strong>{\App\Language::translate('LBL_PENDING_IMPLEMENTATION', $MODULE_NAME)}</strong>
			<div>{\App\Language::translate('LBL_STAGE_MODE_INFO', $MODULE_NAME)}</div>
			<div class="btn-group mt-3" role="group" aria-label="Stage run mode">
				<button type="button" class="btn btn-primary" id="ImportManagerStartInline" disabled="disabled">
					<span class="fa fa-bolt"></span>
					{\App\Language::translate('LBL_STAGE_INLINE_BUTTON', $MODULE_NAME)}
				</button>
				<button type="button" class="btn btn-outline-primary" id="ImportManagerStartQueued" disabled="disabled">
					<span class="fa fa-clock"></span>
					{\App\Language::translate('LBL_STAGE_QUEUE_BUTTON', $MODULE_NAME)}
				</button>
			</div>
			<div class="small text-muted mt-2">{\App\Language::translate('LBL_STAGE_MODE_HINT', $MODULE_NAME)}</div>
		</div>

		<div class="alert alert-success mt-3 mb-0 d-none" id="ImportManagerImportSection">
			<strong>{\App\Language::translate('LBL_IMPORT_SECTION_TITLE', $MODULE_NAME)}</strong>
			<div class="text-muted mt-1" id="ImportManagerImportSummary"></div>
			<div class="btn-group mt-3" role="group" aria-label="Import run mode">
				<button type="button" class="btn btn-success" id="ImportManagerRunImportInline" disabled="disabled">
					<span class="fa fa-cloud-upload-alt"></span>
					{\App\Language::translate('LBL_IMPORT_INLINE_BUTTON', $MODULE_NAME)}
				</button>
				<button type="button" class="btn btn-outline-success" id="ImportManagerRunImportQueue" disabled="disabled">
					<span class="fa fa-tasks"></span>
					{\App\Language::translate('LBL_IMPORT_QUEUE_BUTTON', $MODULE_NAME)}
				</button>
			</div>
			<div class="small text-muted mt-2" id="ImportManagerImportHint">{\App\Language::translate('LBL_IMPORT_MODE_HINT', $MODULE_NAME)}</div>
		</div>
	</div>
</div>

