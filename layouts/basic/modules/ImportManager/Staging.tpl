{extends file='MainLayout.tpl'|@vtemplate_path:$MODULE}

{block name='content'}
	{strip}
		<div class="mainContainer">
			<div class="contentsDiv">
				<div class="widget_header row marginBottom10px">
					<div class="col-sm-12">
						{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
					</div>
				</div>

				<div id="ImportManagerRoot" data-view="staging">
					{include file='WizardSteps.tpl'|@vtemplate_path:$MODULE}

					{assign var=TOTAL_ROWS value=$IMPORT_STATS.total|default:0}
					{assign var=ERROR_ROWS value=$IMPORT_STATS.errors|default:0}
					{assign var=READY_ROWS value=$IMPORT_STATS.ready|default:$TOTAL_ROWS-$ERROR_ROWS}

					<div class="card mb-3 import-manager-batch-card">
						<div class="card-body d-flex flex-wrap align-items-center">
							<div class="mr-4 mb-2">
								<span class="text-muted text-uppercase small d-block">{\App\Language::translate('LBL_BATCH_ID', $MODULE_NAME)}</span>
								<strong>#{$IMPORT_BATCH.id}</strong>
							</div>
							<div class="mr-4 mb-2">
								<span class="text-muted text-uppercase small d-block">{\App\Language::translate('LBL_SELECTED_MODULE', $MODULE_NAME)}</span>
								<strong>{$IMPORT_BATCH.module}</strong>
							</div>
							{if $IMPORT_BATCH.file_name}
								<div class="mr-4 mb-2">
									<span class="text-muted text-uppercase small d-block">{\App\Language::translate('LBL_SELECTED_FILE', $MODULE_NAME)}</span>
									<strong>{$IMPORT_BATCH.file_name}</strong>
								</div>
							{/if}
							<div class="mr-4 mb-2">
								<span class="text-muted text-uppercase small d-block">{\App\Language::translate('LBL_STATUS', $MODULE_NAME)}</span>
								<strong>{$IMPORT_BATCH.status}</strong>
							</div>
							<div class="mr-4 mb-2">
								<span class="text-muted text-uppercase small d-block">{\App\Language::translate('LBL_STAGE_SUMMARY', $MODULE_NAME)}</span>
								<strong id="ImportManagerStageTotals">
									{$READY_ROWS} / {$TOTAL_ROWS}
								</strong>
							</div>
							<div class="mb-2">
								<span class="text-muted text-uppercase small d-block">{\App\Language::translate('LBL_DUPLICATE_STRATEGY', $MODULE_NAME)}</span>
								<strong>{$IMPORT_BATCH.duplicate_strategy}</strong>
							</div>
						</div>
					</div>

					{if !empty($IMPORT_DUPLICATE_SETS)}
						<div class="card mb-3">
							<div class="card-header bg-light">
								<strong>{\App\Language::translate('LBL_DUPLICATE_SETS_APPLIED', $MODULE_NAME)}</strong>
							</div>
							<div class="card-body">
								<p class="text-muted mb-3">
									{\App\Language::translate('LBL_DUPLICATE_SETS_INFO', $MODULE_NAME)}
								</p>
								<div class="d-flex flex-wrap gap-2">
									{foreach from=$IMPORT_DUPLICATE_SETS item=SET}
										<span class="badge badge-info" style="font-size: 0.9rem; padding: 0.5rem 0.75rem;">
											{$SET.label|escape}
										</span>
									{/foreach}
								</div>
							</div>
						</div>
					{/if}

					<div class="card mb-3 import-stage-card">
						<div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap">
							<strong>{\App\Language::translate('LBL_STAGE_PANEL_TITLE', $MODULE_NAME)}</strong>
							<div class="badge badge-light text-uppercase small import-stage-summary">
								{$READY_ROWS} / {$TOTAL_ROWS}
							</div>
						</div>
						<div class="card-body">
							<div id="ImportManagerConfirmationStatus" class="alert alert-info d-none" role="status"></div>
							<p class="text-muted mb-3">
								{\App\Language::translate('LBL_STAGE_MODE_INFO', $MODULE_NAME)}
							</p>
							<div class="import-stage-actions d-flex flex-wrap align-items-center mb-3">
								<div class="btn-group mr-3 mb-2" role="group" aria-label="Stage run mode">
									<button type="button" class="btn btn-primary" id="ImportManagerStartInline">
										<span class="fa fa-bolt mr-1"></span>
										{\App\Language::translate('LBL_STAGE_INLINE_BUTTON', $MODULE_NAME)}
									</button>
									<button type="button" class="btn btn-outline-primary" id="ImportManagerStartQueued">
										<span class="fa fa-clock mr-1"></span>
										{\App\Language::translate('LBL_STAGE_QUEUE_BUTTON', $MODULE_NAME)}
									</button>
								</div>
								<div class="small text-muted">
									{\App\Language::translate('LBL_STAGE_MODE_HINT', $MODULE_NAME)}
								</div>
							</div>

							<div class="alert alert-warning d-none" id="ImportManagerRetryAlert"></div>
							<button type="button" class="btn btn-outline-secondary mt-2 d-none" id="ImportManagerOpenRetry">
								<span class="fa fa-edit mr-1"></span>
								{\App\Language::translate('LBL_OPEN_RETRY', $MODULE_NAME)}
							</button>

							<div class="d-flex flex-wrap align-items-center justify-content-between mt-4">
								<div class="text-muted small">
									{\App\Language::translate('LBL_STAGE_SUMMARY', $MODULE_NAME)}:
									<strong>{$READY_ROWS}</strong> / <strong>{$TOTAL_ROWS}</strong>
								</div>
								<a href="index.php?module=ImportManager&view=Finalize&batch_id={$IMPORT_BATCH.id}" class="btn btn-success">
									{\App\Language::translate('LBL_GO_TO_FINALIZE', $MODULE_NAME)}
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<script type="application/json" id="ImportManagerContext">
			{$IMPORT_CONTEXT_JSON nofilter}
		</script>
	{/strip}
{/block}

