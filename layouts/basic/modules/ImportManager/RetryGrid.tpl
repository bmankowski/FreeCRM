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

				<div class="c-container import-retry-view">
					<div class="card">
						<div class="card-header d-flex justify-content-between align-items-center">
							<div>
								<strong>{\App\Language::translate('LBL_RETRY_HEADING', $MODULE)}</strong>
								<div class="text-muted small">
									{\App\Language::translate('LBL_BATCH_ID', $MODULE)}: #{$BATCH_ID}
									&nbsp;•&nbsp;
									{\App\Language::translate('LBL_TARGET_MODULE', $MODULE)}: {$MODULE_NAME}
								</div>
							</div>
							<div>
								<button type="button" class="btn btn-outline-secondary btn-sm js-retry-export">
									<span class="fa fa-download"></span>
									{\App\Language::translate('LBL_EXPORT_ERRORS', $MODULE)}
								</button>
							</div>
						</div>
						<div class="card-body">
							{if !empty($FAILED_ROWS)}
								<form id="ImportManagerRetryForm" data-batch-id="{$BATCH_ID}">
									<div class="alert alert-warning">
										{\App\Language::translate('LBL_RETRY_INFO', $MODULE)}
									</div>
									<div class="table-responsive">
										<table class="table table-bordered table-sm js-retry-table">
											<thead>
												<tr>
													<th class="text-center">#</th>
													{foreach from=$MAPPING_FIELDS item=FIELD}
														{assign var=FIELD_NAME value=$FIELD.field}
														<th class="{if isset($ERROR_FIELDS.$FIELD_NAME)}bg-danger text-white{/if}">
															{$FIELD.label}
															<div class="text-muted small">
																{if $FIELD.mandatory}
																	{\App\Language::translate('LBL_FIELD_MANDATORY', $MODULE)}
																{else}
																	{\App\Language::translate('LBL_FIELD_OPTIONAL', $MODULE)}
																{/if}
															</div>
														</th>
													{/foreach}
													<th>{\App\Language::translate('LBL_ERRORS', $MODULE)}</th>
												</tr>
											</thead>
											<tbody>
												{foreach from=$FAILED_ROWS item=ROW}
													<tr class="js-retry-row" data-row-number="{$ROW.rowNumber}">
														<td class="text-center font-weight-bold">{$ROW.rowNumber}</td>
														{foreach from=$MAPPING_FIELDS item=FIELD}
															{assign var=FIELD_NAME value=$FIELD.field}
															{assign var=HAS_ROW_ERROR value=isset($ROW.errorFields.$FIELD_NAME)}
															<td class="{if $HAS_ROW_ERROR}bg-danger text-white font-weight-bold{/if}">
																<input type="text"
																	class="form-control form-control-sm js-retry-input{if $HAS_ROW_ERROR} is-invalid{/if}"
																	data-field="{$FIELD_NAME}"
																	value="{$ROW.values.$FIELD_NAME|escape}"
																	data-original="{$ROW.values.$FIELD_NAME|escape}" />
															</td>
														{/foreach}
														<td class="bg-danger text-white small">
															{if !empty($ROW.errorsFormatted)}
																{foreach from=$ROW.errorsFormatted item=ERROR}
																	<div>{$ERROR|escape}</div>
																{/foreach}
															{else}
																—
															{/if}
														</td>
													</tr>
												{/foreach}
											</tbody>
										</table>
									</div>
									<div class="text-right mt-3">
										<button type="button" class="btn btn-primary js-retry-save">
											<span class="fa fa-save"></span>
											{\App\Language::translate('LBL_SAVE_CHANGES', $MODULE)}
										</button>
									</div>
								</form>
							{else}
								<div class="alert alert-success mb-0">
									{\App\Language::translate('LBL_NO_FAILED_ROWS', $MODULE)}
								</div>
							{/if}
						</div>
					</div>
				</div>
			</div>
		</div>
	{/strip}
{/block}

