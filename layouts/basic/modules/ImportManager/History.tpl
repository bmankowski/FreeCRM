{extends file='MainLayout.tpl'|@vtemplate_path:$MODULE}

{block name='content'}
	{strip}
		<div class="mainContainer">
			<div class="contentsDiv">
				<div class="widget_header row marginBottom10px align-items-center">
					<div class="col-sm-8">
						{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
					</div>
					<div class="col-sm-4 text-right">
						<a class="import-btn import-btn--primary import-btn--small"
							href="index.php?module=ImportManager&view=Wizard">
							<i class="fa fa-plus"></i>
							<span>{\App\Language::translate('LBL_NEW_IMPORT', $MODULE_NAME)}</span>
						</a>
					</div>
				</div>

				<div id="ImportManagerRoot" class="import-manager-screen" data-view="history">
					<div class="import-card import-card--info">
						<div class="import-card__header import-card__header--compact">
							<div class="import-card__icon import-card__icon--small">
								<i class="fa fa-history"></i>
							</div>
							<div class="import-card__title">
								<h5>{\App\Language::translate('LBL_IMPORT_HISTORY', $MODULE_NAME)}</h5>
								<span class="import-card__subtitle">{\App\Language::translate('LBL_IMPORT_HISTORY_HELP', $MODULE_NAME)}</span>
							</div>
						</div>
						<div class="import-card__body p-0">
							{if !empty($IMPORT_BATCHES)}
								<div class="import-recent-list">
									{foreach from=$IMPORT_BATCHES item=BATCH}
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
												{if $BATCH.status neq 'running'}
													<button type="button"
														class="import-btn import-btn--small import-btn--danger js-delete-batch"
														data-batch-id="{$BATCH.id}"
														title="{\App\Language::translate('LBL_DELETE_BATCH', $MODULE_NAME)}">
														<i class="fa fa-trash"></i>
														<span>{\App\Language::translate('LBL_DELETE_BATCH', $MODULE_NAME)}</span>
													</button>
												{/if}
												{if $BATCH.continue_url}
													{assign var=ACTION_LABEL_KEY value=$BATCH.action_label_key|default:'LBL_CONTINUE_STEP'}
													<a class="import-btn import-btn--small" href="{$BATCH.continue_url|escape:'html'}">
														{if $ACTION_LABEL_KEY eq 'LBL_VIEW_RESULT'}
															<i class="fa fa-check-circle"></i>
														{elseif $ACTION_LABEL_KEY eq 'LBL_VIEW_ERRORS'}
															<i class="fa fa-exclamation-triangle"></i>
														{else}
															<i class="fa fa-arrow-right"></i>
														{/if}
														{\App\Language::translate($ACTION_LABEL_KEY, $MODULE_NAME)}
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
							{else}
								<div class="import-history-empty text-center p-5 text-muted">
									<i class="fa fa-inbox fa-2x mb-3 d-block"></i>
									{\App\Language::translate('LBL_IMPORT_HISTORY_EMPTY', $MODULE_NAME)}
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
