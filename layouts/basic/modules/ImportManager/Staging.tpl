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

				<div id="ImportManagerRoot" class="import-manager-screen" data-view="staging">
					{include file='WizardSteps.tpl'|@vtemplate_path:$MODULE}

					{assign var=TOTAL_ROWS value=$IMPORT_STATS.total|default:0}
					{assign var=ERROR_ROWS value=$IMPORT_STATS.errors|default:0}
					{assign var=READY_ROWS value=$IMPORT_STATS.ready|default:$TOTAL_ROWS-$ERROR_ROWS}

					{include file='BatchInfo.tpl'|@vtemplate_path:$MODULE 
								BATCH_STAGE_SUMMARY="`$READY_ROWS` / `$TOTAL_ROWS`"}

					{* Duplicate Sets Applied *}
					{if !empty($IMPORT_DUPLICATE_SETS)}
						<div class="import-card import-card--info mb-4">
							<div class="import-card__header import-card__header--compact">
								<div class="import-card__icon import-card__icon--small import-card__icon--warning">
									<i class="fa fa-clone"></i>
								</div>
								<div class="import-card__title">
									<h5>{\App\Language::translate('LBL_DUPLICATE_SETS_APPLIED', $MODULE_NAME)}</h5>
									<span
										class="import-card__subtitle">{\App\Language::translate('LBL_DUPLICATE_SETS_INFO', $MODULE_NAME)}</span>
								</div>
							</div>
							<div class="import-card__body">
								<div class="import-duplicates-chipset">
									{foreach from=$IMPORT_DUPLICATE_SETS item=SET}
										<span class="import-duplicates-chip import-duplicates-chip--readonly">
											<i class="fa fa-check-circle import-duplicates-chip__icon"></i>
											{$SET.label|escape}
										</span>
									{/foreach}
								</div>
							</div>
						</div>
					{/if}

					{* Staging Panel *}
					<div class="import-card import-card--primary">
						<div class="import-card__header import-card__header--compact">
							<div class="import-card__icon import-card__icon--small">
								<i class="fa fa-cogs"></i>
							</div>
							<div class="import-card__title">
								<h5>{\App\Language::translate('LBL_STAGE_PANEL_TITLE', $MODULE_NAME)}</h5>
								<span
									class="import-card__subtitle">{\App\Language::translate('LBL_STAGE_MODE_INFO', $MODULE_NAME)}</span>
							</div>
						</div>
						<div class="import-card__body">
								{* Action Buttons *}
							<div class="import-staging-actions">
								<div class="import-staging-actions__label">
									{\App\Language::translate('LBL_STAGE_MODE_HINT', $MODULE_NAME)}
								</div>
								<div class="import-staging-actions__buttons">
									<button type="button" class="import-btn import-btn--primary" id="ImportManagerStartInline">
										<i class="fa fa-bolt"></i>
										<span>{\App\Language::translate('LBL_STAGE_INLINE_BUTTON', $MODULE_NAME)}</span>
									</button>
									<button type="button" class="import-btn import-btn--secondary"
										id="ImportManagerStartQueued">
										<i class="fa fa-clock"></i>
										<span>{\App\Language::translate('LBL_STAGE_QUEUE_BUTTON', $MODULE_NAME)}</span>
									</button>
								</div>
							</div>

							{* Stats Summary *}
							<div class="import-staging-stats my-4 gap-2">
								<div class="import-staging-stat mr-2">
									<div class="import-staging-stat__value">{$TOTAL_ROWS}</div>
									<div class="import-staging-stat__label">
										{\App\Language::translate('LBL_TOTAL_ROWS', $MODULE_NAME)|default:'Wszystkich'}</div>
								</div>
								<div class="import-staging-stat import-staging-stat--success mr-2">
									<div class="import-staging-stat__value">{$READY_ROWS}</div>
									<div class="import-staging-stat__label">
										{\App\Language::translate('LBL_READY_ROWS', $MODULE_NAME)|default:'Gotowych'}</div>
								</div>
								<div class="import-staging-stat import-staging-stat--danger">
									<div class="import-staging-stat__value">{$ERROR_ROWS}</div>
									<div class="import-staging-stat__label">
										{\App\Language::translate('LBL_ERROR_ROWS', $MODULE_NAME)|default:'Błędnych'}</div>
								</div>
							</div>


							{* Error Fix Alert *}
							{if $ERROR_ROWS > 0}
								<div class="alert alert-danger d-flex align-items-center justify-content-between my-4 py-3 px-2">
									<div>
										<i class="fa fa-exclamation-triangle mr-2"></i>
										<strong>{\App\Language::translate('LBL_ERRORS_FOUND', $MODULE_NAME)|default:'Wykryto błędy!'}</strong>
										{\App\Language::translate('LBL_ERRORS_NEED_FIX', $MODULE_NAME)|default:'Popraw błędne wiersze przed kontynuowaniem importu.'}
									</div>
								</div>
							{/if}

							{* Footer with navigation *}
							<div class="import-card__footer">
								{if $ERROR_ROWS > 0}
									<a href="index.php?module=ImportManager&view=Retry&batch_id={$IMPORT_BATCH.id}"
										class="import-btn import-btn--danger mr-2">
										<i class="fa fa-wrench mr-2"></i>
										<span>{\App\Language::translate('LBL_FIX_ERRORS', $MODULE_NAME)|default:'Popraw błędy'}</span>
										<span class="badge badge-light ml-2">{$ERROR_ROWS}</span>
									</a>
								{/if}
								<a href="index.php?module=ImportManager&view=Finalize&batch_id={$IMPORT_BATCH.id}"
									class="import-btn import-btn--primary">
									<span>{\App\Language::translate('LBL_GO_TO_FINALIZE', $MODULE_NAME)}</span>
									<i class="fa fa-arrow-right ml-2"></i>
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