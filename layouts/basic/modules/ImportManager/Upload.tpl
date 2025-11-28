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

				<div id="ImportManagerRoot" class="import-manager-screen" data-view="upload">
					{include file='WizardSteps.tpl'|@vtemplate_path:$MODULE}

					<div class="import-manager-wizard c-container">
						<div id="ImportManagerConfig"
							data-max-upload="{$IMPORT_CONFIG.maxUploadSizeMb|default:10}"
							data-preview-rows="{$IMPORT_CONFIG.previewRows|default:30}"
							data-chunk-size="{$IMPORT_CONFIG.chunkSize|default:200}"></div>

						{include file='WizardStep1Upload.tpl'|@vtemplate_path:$MODULE}

						{if !empty($IMPORT_RECENT_BATCHES)}
							<div class="card recent-imports mt-4">
								<div class="card-header d-flex justify-content-between align-items-center flex-wrap">
									<strong>{\App\Language::translate('LBL_RECENT_IMPORTS', $MODULE_NAME)}</strong>
									<span class="text-muted small">
										{\App\Language::translate('LBL_RECENT_IMPORTS_HELP', $MODULE_NAME)}
									</span>
								</div>
								<div class="card-body p-0">
									<ul class="list-group list-group-flush">
										{foreach from=$IMPORT_RECENT_BATCHES item=BATCH}
											<li class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center">
												<div class="mb-2 mb-md-0">
													<span class="badge badge-light mr-2">#{$BATCH.id}</span>
													<strong>{$BATCH.module}</strong>
													<span class="text-muted">• {$BATCH.status}</span>
													<span class="text-muted ml-2">
														{$BATCH.progress}
													</span>
												</div>
												<div class="d-flex align-items-center">
													<small class="text-muted mr-3">{$BATCH.created_at}</small>
													{if $BATCH.continue_url}
														<a class="btn btn-sm btn-outline-primary" href="{$BATCH.continue_url|escape:'html'}">
															{\App\Language::translate('LBL_CONTINUE_STEP', $MODULE_NAME)}
														</a>
													{else}
														<span class="badge badge-secondary">
															{\App\Language::translate('LBL_STEP_DISABLED', $MODULE_NAME)}
														</span>
													{/if}
												</div>
											</li>
										{/foreach}
									</ul>
								</div>
							</div>
						{/if}
					</div>
				</div>
			</div>
		</div>

		<script type="application/json" id="ImportManagerContext">
			{$IMPORT_CONTEXT_JSON nofilter}
		</script>
	{/strip}
{/block}

