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

				<div id="ImportManagerRoot" class="import-manager-screen" data-view="finalize">
					{include file='WizardSteps.tpl'|@vtemplate_path:$MODULE}

					{assign var=TOTAL_ROWS value=$IMPORT_STATS.total|default:0}
					{assign var=ERROR_ROWS value=$IMPORT_STATS.errors|default:0}
					{assign var=READY_ROWS value=$IMPORT_STATS.ready|default:$TOTAL_ROWS-$ERROR_ROWS}
					{assign var=IS_COMPLETED value=($IMPORT_BATCH.status eq 'completed' || $IMPORT_BATCH.status eq 'failed')}
					{assign var=IMPORT_RESULT value=$IMPORT_IMPORT_SUMMARY|default:[]}

					{include file='BatchInfo.tpl'|@vtemplate_path:$MODULE 
								BATCH_STAGE_SUMMARY="`$READY_ROWS` / `$TOTAL_ROWS`"}

					{* Import Panel *}
					<div class="import-card import-card--primary">
						<div class="import-card__header import-card__header--compact">
							<div class="import-card__icon import-card__icon--small">
								<i class="fa fa-cloud-upload-alt"></i>
							</div>
							<div class="import-card__title">
								<h5>{\App\Language::translate('LBL_IMPORT_SECTION_TITLE', $MODULE_NAME)}</h5>
								<span class="import-card__subtitle">{\App\Language::translate('LBL_IMPORT_MODE_HINT', $MODULE_NAME)}</span>
							</div>
						</div>
						<div class="import-card__body">
							{* Status Alert (hidden by default) *}
							<div id="ImportManagerConfirmationStatus" class="alert alert-info d-none" role="status"></div>

							{* Stats Summary *}
							{if $IS_COMPLETED}
								<div class="import-staging-stats mb-4">
									<div class="import-staging-stat import-staging-stat--success">
										<div class="import-staging-stat__value">{$IMPORT_RESULT.created|default:0}</div>
										<div class="import-staging-stat__label">
											{\App\Language::translate('LBL_CREATED_ROWS', $MODULE_NAME)|default:'Utworzono'}</div>
									</div>
									<div class="import-staging-stat">
										<div class="import-staging-stat__value">{$IMPORT_RESULT.updated|default:0}</div>
										<div class="import-staging-stat__label">
											{\App\Language::translate('LBL_UPDATED_ROWS', $MODULE_NAME)|default:'Zaktualizowano'}</div>
									</div>
									<div class="import-staging-stat import-staging-stat--warning">
										<div class="import-staging-stat__value">{$IMPORT_RESULT.skipped|default:0}</div>
										<div class="import-staging-stat__label">
											{\App\Language::translate('LBL_SKIPPED_ROWS', $MODULE_NAME)|default:'Pominięto (duplikaty)'}</div>
									</div>
									<div class="import-staging-stat import-staging-stat--danger">
										<div class="import-staging-stat__value">{$IMPORT_RESULT.failed|default:0}</div>
										<div class="import-staging-stat__label">
											{\App\Language::translate('LBL_ERROR_ROWS', $MODULE_NAME)|default:'Błędnych'}</div>
									</div>
								</div>
							{else}
								<div class="import-staging-stats mb-4">
									<div class="import-staging-stat">
										<div class="import-staging-stat__value">{$TOTAL_ROWS}</div>
										<div class="import-staging-stat__label">
											{\App\Language::translate('LBL_TOTAL_ROWS', $MODULE_NAME)|default:'Wszystkich'}</div>
									</div>
									<div class="import-staging-stat import-staging-stat--success">
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
							{/if}

							{* Action Buttons *}
							{if !$IS_COMPLETED}
							<div class="import-staging-actions">
								<div class="import-staging-actions__label">
									{\App\Language::translate('LBL_FINALIZE_ACTION_HINT', $MODULE_NAME)|default:'Wybierz sposób importu:'}
								</div>
								<div class="import-staging-actions__buttons">
									<button type="button" class="import-btn import-btn--primary" id="ImportManagerRunImportInline">
										<i class="fa fa-bolt"></i>
										<span>{\App\Language::translate('LBL_IMPORT_INLINE_BUTTON', $MODULE_NAME)}</span>
									</button>
									<button type="button" class="import-btn import-btn--secondary" id="ImportManagerRunImportQueue">
										<i class="fa fa-clock"></i>
										<span>{\App\Language::translate('LBL_IMPORT_QUEUE_BUTTON', $MODULE_NAME)}</span>
									</button>
								</div>
							</div>
							{/if}

							{* Result Message *}
							{if $RESULT_MESSAGE_TEXT}
								<div class="alert alert-info mt-4" id="ImportManagerResultStatus">
									<i class="fa fa-info-circle mr-2"></i>
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
