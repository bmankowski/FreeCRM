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

				<div id="ImportManagerRoot" class="import-manager-screen" data-view="retry">
					{include file='WizardSteps.tpl'|@vtemplate_path:$MODULE}

					{* Batch Info Card *}
					{assign var=IMPORT_BATCH value=$BATCH}
					{include file='BatchInfo.tpl'|@vtemplate_path:$MODULE 
						MODULE_NAME='ImportManager'
						BATCH_STAGE_SUMMARY="`$FAILED_TOTAL` błędnych rekordów"}

					{* Flash Messages *}
					{if $RETRY_FLASH_SUCCESS}
						<div class="alert alert-success mb-4">
							<i class="fa fa-check-circle mr-2"></i>
							{\App\Language::translate('LBL_CHANGES_SAVED', $MODULE)}
						</div>
					{/if}
					{if $RETRY_FLASH_ERROR}
						<div class="alert alert-danger mb-4">
							<i class="fa fa-exclamation-circle mr-2"></i>
							{$RETRY_FLASH_ERROR|escape}
						</div>
					{/if}

					{* Main Retry Panel *}
					<div class="import-card import-card--primary">
						{if !empty($FAILED_ROWS)}
							<div class="import-card__header import-card__header--compact">
								<div class="import-card__icon import-card__icon--small import-card__icon--danger">
									<i class="fa fa-wrench"></i>
								</div>
								<div class="import-card__title">
									<h5>{\App\Language::translate('LBL_RETRY_HEADING', $MODULE)}</h5>
									<span class="import-card__subtitle">{\App\Language::translate('LBL_RETRY_INFO', $MODULE)}</span>
								</div>
								<div class="import-card__actions">
									<a href="index.php?module=ImportManager&action=ExportErrors&batch_id={$BATCH_ID}" 
									   class="import-btn import-btn--secondary import-btn--sm">
										<i class="fa fa-download"></i>
										<span>{\App\Language::translate('LBL_EXPORT_ERRORS', $MODULE)}</span>
									</a>
								</div>
							</div>
						{/if}
						<div class="import-card__body">
							{if !empty($FAILED_ROWS)}
								<form id="ImportManagerRetryForm" method="post" action="index.php" data-batch-id="{$BATCH_ID}">
									<input type="hidden" name="module" value="ImportManager" />
									<input type="hidden" name="action" value="RetryUpdate" />
									<input type="hidden" name="batch_id" value="{$BATCH_ID}" />
									
									<div class="table-responsive import-retry-table-container">
										<table class="table table-bordered table-sm js-retry-table import-retry-table">
											<thead>
												<tr>
													<th class="text-center import-retry-table__row-num">#</th>
													{foreach from=$MAPPING_FIELDS item=FIELD}
														{assign var=FIELD_NAME value=$FIELD.field}
														<th class="import-retry-table__header {if isset($ERROR_FIELDS.$FIELD_NAME)}import-retry-table__header--error{/if}">
															<div class="import-retry-table__header-label">{$FIELD.label}</div>
															<div class="import-retry-table__header-meta">
																{if $FIELD.mandatory}
																	<span class="import-retry-table__mandatory">{\App\Language::translate('LBL_FIELD_MANDATORY', $MODULE)}</span>
																{else}
																	{\App\Language::translate('LBL_FIELD_OPTIONAL', $MODULE)}
																{/if}
															</div>
														</th>
													{/foreach}
													<th class="import-retry-table__errors-col">{\App\Language::translate('LBL_ERRORS', $MODULE)}</th>
												</tr>
											</thead>
											<tbody>
												{foreach from=$FAILED_ROWS item=ROW}
													<tr class="js-retry-row" data-row-number="{$ROW.rowNumber}">
														<td class="text-center font-weight-bold import-retry-table__row-num">{$ROW.rowNumber}</td>
														{foreach from=$MAPPING_FIELDS item=FIELD}
															{assign var=FIELD_NAME value=$FIELD.field}
															{assign var=HAS_ROW_ERROR value=isset($ROW.errorFields.$FIELD_NAME)}
															<td class="{if $HAS_ROW_ERROR}import-retry-table__cell--error{/if} p-2">
																<input type="hidden"
																	name="original[{$ROW.rowNumber}][{$FIELD_NAME}]"
																	value="{$ROW.values.$FIELD_NAME|escape}" />
																<input type="text"
																	class="form-control form-control-sm import-retry-input{if $HAS_ROW_ERROR} import-retry-input--error{/if}"
																	name="rows[{$ROW.rowNumber}][{$FIELD_NAME}]"
																	value="{$ROW.values.$FIELD_NAME|escape}" />
															</td>
														{/foreach}
														<td class="import-retry-table__errors-cell p-2 min-w-400">
															{if !empty($ROW.errorsFormatted)}
																{foreach from=$ROW.errorsFormatted item=ERROR}
																	<div class="import-retry-error-item">
																		<i class="fa fa-exclamation-triangle"></i>
																		{$ERROR|escape}
																	</div>
																{/foreach}
															{else}
																<span class="text-muted">—</span>
															{/if}
														</td>
													</tr>
												{/foreach}
											</tbody>
										</table>
									</div>

									{* Footer with navigation *}
									<div class="import-card__footer mt-4">
										<a href="index.php?module=ImportManager&view=Staging&batch_id={$BATCH_ID}" 
										   class="import-btn import-btn--secondary">
											<i class="fa fa-arrow-left mr-2"></i>
											<span>{\App\Language::translate('LBL_BACK_TO_IMPORT', $MODULE)}</span>
										</a>
										<button type="submit" class="import-btn import-btn--primary">
											<i class="fa fa-save mr-2"></i>
											<span>{\App\Language::translate('LBL_SAVE_CHANGES', $MODULE)}</span>
										</button>
									</div>
								</form>
							{else}
								<div class="import-empty-state">
									<div class="import-empty-state__icon import-empty-state__icon--success">
										<i class="fa fa-check-circle"></i>
									</div>
									<h5 class="import-empty-state__title">{\App\Language::translate('LBL_NO_FAILED_ROWS', $MODULE)}</h5>
									<p class="import-empty-state__text">{\App\Language::translate('LBL_ALL_ROWS_VALID', $MODULE)}</p>
									<div class="import-card__footer">
										<a href="index.php?module=ImportManager&view=Finalize&batch_id={$BATCH_ID}" 
										   class="import-btn import-btn--primary">
											<span>{\App\Language::translate('LBL_CONTINUE_IMPORT', $MODULE)}</span>
											<i class="fa fa-arrow-right ml-2"></i>
										</a>
									</div>
								</div>
							{/if}
						</div>
					</div>
				</div>
			</div>
		</div>
	{/strip}
{/block}
