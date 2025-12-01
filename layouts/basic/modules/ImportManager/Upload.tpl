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

				<div class="import-manager-wizard">
						<div id="ImportManagerConfig"
							data-max-upload="{$IMPORT_CONFIG.maxUploadSizeMb|default:10}"
							data-preview-rows="{$IMPORT_CONFIG.previewRows|default:30}"
							data-chunk-size="{$IMPORT_CONFIG.chunkSize|default:200}"></div>

						{include file='WizardStep1Upload.tpl'|@vtemplate_path:$MODULE}

						{if !empty($IMPORT_RECENT_BATCHES)}
							<div class="import-card import-card--info mt-4">
								<div class="import-card__header import-card__header--compact">
									<div class="import-card__icon import-card__icon--small">
										<i class="fa fa-history"></i>
									</div>
									<div class="import-card__title">
										<h5>{\App\Language::translate('LBL_RECENT_IMPORTS', $MODULE_NAME)}</h5>
										<span class="import-card__subtitle">{\App\Language::translate('LBL_RECENT_IMPORTS_HELP', $MODULE_NAME)}</span>
									</div>
								</div>
								<div class="import-card__body p-0">
									<div class="import-recent-list">
										{foreach from=$IMPORT_RECENT_BATCHES item=BATCH}
											<div class="import-recent-item">
												<div class="import-recent-item__main">
													<span class="import-recent-item__id">#{$BATCH.id}</span>
													<span class="import-recent-item__module">{$BATCH.module}</span>
													<span class="import-recent-item__status import-recent-item__status--{$BATCH.status|lower}">{$BATCH.status}</span>
													{if $BATCH.progress}
														<span class="import-recent-item__progress">{$BATCH.progress}</span>
													{/if}
												</div>
												<div class="import-recent-item__actions">
													<span class="import-recent-item__date">
														<i class="fa fa-clock-o"></i>
														{$BATCH.created_at}
													</span>
													{if $BATCH.continue_url}
														<a class="import-btn import-btn--small" href="{$BATCH.continue_url|escape:'html'}">
															<i class="fa fa-arrow-right"></i>
															{\App\Language::translate('LBL_CONTINUE_STEP', $MODULE_NAME)}
														</a>
													{else}
														<span class="import-recent-item__disabled">
															{\App\Language::translate('LBL_STEP_DISABLED', $MODULE_NAME)}
														</span>
													{/if}
												</div>
											</div>
										{/foreach}
									</div>
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

