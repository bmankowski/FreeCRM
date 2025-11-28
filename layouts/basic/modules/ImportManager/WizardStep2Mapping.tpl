<div id="ImportManagerStep2" class="card mt-4 d-none import-manager-step">
	<div class="card-header">
		<strong>{\App\Language::translate('LBL_STEP2_TITLE', $MODULE_NAME)}</strong>
	</div>
	<div class="card-body">
		<p class="text-muted mb-3">{\App\Language::translate('LBL_MAPPING_INSTRUCTIONS', $MODULE_NAME)}</p>
		<div class="table-responsive">
			<table class="table table-bordered table-sm mb-3 mapping-table" id="ImportManagerMappingTable">
				<thead>
					<tr>
						<th>{\App\Language::translate('LBL_TARGET_FIELD', $MODULE_NAME)}</th>
						<th>{\App\Language::translate('LBL_SOURCE_COLUMN', $MODULE_NAME)}</th>
						<th>{\App\Language::translate('LBL_DEFAULT_VALUE', $MODULE_NAME)}</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div class="text-muted small mb-3">{\App\Language::translate('LBL_DEFAULT_VALUE_INFO', $MODULE_NAME)}</div>

		<div class="card mt-3">
			<div class="card-header py-2">
				<strong>{\App\Language::translate('LBL_DUPLICATE_SETS', $MODULE_NAME)}</strong>
			</div>
			<div class="card-body">
				<div class="mb-2">
					<p class="mb-1 font-weight-bold">{\App\Language::translate('LBL_REQUIRED_SETS', $MODULE_NAME)}</p>
					<div id="ImportManagerRequiredSets" class="badge-container"></div>
				</div>
				<div>
					<p class="mb-1 font-weight-bold">{\App\Language::translate('LBL_OPTIONAL_SETS', $MODULE_NAME)}</p>
					<div id="ImportManagerOptionalSets" class="optional-set-list"></div>
				</div>
				<div class="form-group mt-3 mb-0">
					<label for="ImportManagerDuplicateStrategy">{\App\Language::translate('LBL_DUPLICATE_STRATEGY', $MODULE_NAME)}</label>
					<select id="ImportManagerDuplicateStrategy" class="form-control">
						<option value="skip">{\App\Language::translate('LBL_STRATEGY_SKIP', $MODULE_NAME)}</option>
						<option value="overwrite">{\App\Language::translate('LBL_STRATEGY_OVERWRITE', $MODULE_NAME)}</option>
						<option value="merge">{\App\Language::translate('LBL_STRATEGY_MERGE', $MODULE_NAME)}</option>
					</select>
				</div>
			</div>
		</div>

		<div class="text-right mt-3">
			<button type="button" class="btn btn-success" id="ImportManagerSaveMapping">
				<span class="fa fa-save"></span>
				{\App\Language::translate('LBL_SAVE_AND_CONTINUE', $MODULE_NAME)}
			</button>
		</div>
	</div>
</div>

