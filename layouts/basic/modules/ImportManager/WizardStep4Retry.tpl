<div id="ImportManagerStep4" class="card mt-4 d-none import-manager-step">
	<div class="card-header">
		<strong>{\App\Language::translate('LBL_STEP4_RETRY_TITLE', $MODULE_NAME)}</strong>
	</div>
	<div class="card-body">
		<p class="text-muted">{\App\Language::translate('LBL_STEP4_RETRY_DESC', $MODULE_NAME)}</p>
		<div class="alert alert-warning d-none" id="ImportManagerRetryAlert"></div>
		<button type="button" class="btn btn-outline-secondary mt-2 d-none" id="ImportManagerOpenRetry">
			<span class="fa fa-edit"></span>
			{\App\Language::translate('LBL_OPEN_RETRY', $MODULE_NAME)}
		</button>
	</div>
</div>

