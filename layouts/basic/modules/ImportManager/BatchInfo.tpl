{* 
 * FreeCRM - Batch Info Card Component
 * Reusable component for displaying batch information across wizard steps
 * 
 * Required variables:
 *   $IMPORT_BATCH - array with batch data (id, module, file_name, status, duplicate_strategy)
 * 
 * Optional variables:
 *   $BATCH_EXTRA_ITEMS - array of extra items to display [{label: '', value: '', highlight: false}]
 *}

<div class="import-card import-card--info mb-4">
	<div class="import-card__header import-card__header--compact">
		<div class="import-card__icon import-card__icon--small">
			<i class="fa fa-info-circle"></i>
		</div>
		<div class="import-card__title">
			<h5>{\App\Language::translate('LBL_BATCH_DETAILS', $MODULE_NAME)}</h5>
		</div>
	</div>
	<div class="import-card__body">
		<div class="import-batch-info">
			<div class="import-batch-info__item">
				<span class="import-batch-info__label">{\App\Language::translate('LBL_BATCH_ID', $MODULE_NAME)}</span>
				<span class="import-batch-info__value import-batch-info__value--id">#{$IMPORT_BATCH.id}</span>
			</div>
			<div class="import-batch-info__item">
				<span class="import-batch-info__label">{\App\Language::translate('LBL_SELECTED_MODULE', $MODULE_NAME)}</span>
				<span class="import-batch-info__value">{$IMPORT_BATCH.module}</span>
			</div>
			{if $IMPORT_BATCH.file_name}
				<div class="import-batch-info__item">
					<span class="import-batch-info__label">{\App\Language::translate('LBL_SELECTED_FILE', $MODULE_NAME)}</span>
					<span class="import-batch-info__value">{$IMPORT_BATCH.file_name}</span>
				</div>
			{/if}
			<div class="import-batch-info__item">
				<span class="import-batch-info__label">{\App\Language::translate('LBL_STATUS', $MODULE_NAME)}</span>
				<span class="import-batch-info__value import-batch-info__value--status">{$IMPORT_BATCH.status}</span>
			</div>
			{if isset($IMPORT_BATCH.duplicate_strategy) && $IMPORT_BATCH.duplicate_strategy}
				<div class="import-batch-info__item">
					<span class="import-batch-info__label">{\App\Language::translate('LBL_DUPLICATE_STRATEGY', $MODULE_NAME)}</span>
					<span class="import-batch-info__value">{$IMPORT_BATCH.duplicate_strategy}</span>
				</div>
			{/if}
			{* Stage summary - for Staging view *}
			{if isset($BATCH_STAGE_SUMMARY) && $BATCH_STAGE_SUMMARY}
				<div class="import-batch-info__item">
					<span class="import-batch-info__label">{\App\Language::translate('LBL_STAGE_SUMMARY', $MODULE_NAME)}</span>
					<span class="import-batch-info__value import-batch-info__value--highlight" id="ImportManagerStageTotals">
						{$BATCH_STAGE_SUMMARY}
					</span>
				</div>
			{/if}
		</div>
	</div>
</div>

