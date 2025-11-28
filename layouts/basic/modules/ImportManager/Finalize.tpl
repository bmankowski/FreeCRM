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

				<div id="ImportManagerRoot" data-view="finalize">
					{include file='WizardSteps.tpl'|@vtemplate_path:$MODULE}

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
							{if isset($IMPORT_BATCH.duplicate_strategy)}
								<div class="mb-2">
									<span class="text-muted text-uppercase small d-block">{\App\Language::translate('LBL_DUPLICATE_STRATEGY', $MODULE_NAME)}</span>
									<strong>{$IMPORT_BATCH.duplicate_strategy}</strong>
								</div>
							{/if}
						</div>
					</div>

					<div class="card mb-3 import-finalize-card">
						<div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap">
							<strong>{\App\Language::translate('LBL_IMPORT_SECTION_TITLE', $MODULE_NAME)}</strong>
							{if $READY_INFO_TEXT}
								<div class="badge badge-light text-uppercase small">
									{$READY_INFO_TEXT}
								</div>
							{/if}
						</div>
						<div class="card-body">
							<div id="ImportManagerConfirmationStatus" class="alert alert-info d-none" role="status"></div>
							<p class="text-muted mb-3">
								{\App\Language::translate('LBL_IMPORT_MODE_HINT', $MODULE_NAME)}
							</p>
							<div class="import-finalize-actions d-flex flex-wrap align-items-center mb-3">
								<div class="btn-group mr-3 mb-2" role="group" aria-label="Import mode">
									<button type="button" class="btn btn-success" id="ImportManagerRunImportInline">
										<span class="fa fa-cloud-upload-alt mr-1"></span>
										{\App\Language::translate('LBL_IMPORT_INLINE_BUTTON', $MODULE_NAME)}
									</button>
									<button type="button" class="btn btn-outline-success" id="ImportManagerRunImportQueue">
										<span class="fa fa-tasks mr-1"></span>
										{\App\Language::translate('LBL_IMPORT_QUEUE_BUTTON', $MODULE_NAME)}
									</button>
								</div>
							</div>

							{if $RESULT_MESSAGE_TEXT}
								<div class="alert alert-info mt-3" id="ImportManagerResultStatus">
									{$RESULT_MESSAGE_TEXT}
								</div>
							{/if}
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

