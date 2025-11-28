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

				<div id="ImportManagerRoot" data-view="mapping">
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

					{assign var=PREVIEW_HEADERS value=$IMPORT_HEADERS|default:[]}
					{assign var=PREVIEW_ROWS value=$IMPORT_PREVIEW.rows|default:[]}
					{assign var=PREVIEW_META value=$IMPORT_PREVIEW.meta|default:[]}
					<div class="card mb-3" id="ImportManagerPreview">
						<div class="card-header bg-light">
							<div class="d-flex justify-content-between align-items-center flex-wrap">
								<strong>{\App\Language::translate('LBL_IMPORT_PREVIEW', $MODULE_NAME)}</strong>
								<div class="mt-2 mt-md-0">
									{if $IMPORT_BATCH.file_name}
										<span class="badge badge-light mr-1" id="ImportManagerPreviewFileName">
											{$IMPORT_BATCH.file_name|escape}
										</span>
									{/if}
									{if isset($PREVIEW_META.encoding) && $PREVIEW_META.encoding neq ''}
										<span class="badge badge-info mr-1" id="ImportManagerPreviewEncoding">
											{\App\Language::translate('LBL_ENCODING', $MODULE_NAME)}: {$PREVIEW_META.encoding|escape}
										</span>
									{/if}
									{if isset($PREVIEW_META.delimiter) && $PREVIEW_META.delimiter neq ''}
										{assign var=DELIMITER_VALUE value=$PREVIEW_META.delimiter}
										{if $DELIMITER_VALUE == "\t"}
											{assign var=DELIMITER_DISPLAY value=\App\Language::translate('LBL_TAB', $MODULE_NAME)}
										{else}
											{assign var=DELIMITER_DISPLAY value=$DELIMITER_VALUE}
										{/if}
										<span class="badge badge-info" id="ImportManagerPreviewDelimiter">
											{\App\Language::translate('LBL_DELIMITER', $MODULE_NAME)}: {$DELIMITER_DISPLAY|escape}
										</span>
									{/if}
								</div>
							</div>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-bordered table-sm mb-0">
									<thead>
										{if $PREVIEW_HEADERS|@count gt 0}
											<tr>
												{foreach from=$PREVIEW_HEADERS item=HEADER}
													<th>{$HEADER|escape}</th>
												{/foreach}
											</tr>
										{else}
											<tr>
												<th>{\App\Language::translate('LBL_NO_HEADERS_AVAILABLE', $MODULE_NAME)}</th>
											</tr>
										{/if}
									</thead>
									<tbody>
										{if $PREVIEW_ROWS|@count gt 0}
											{foreach from=$PREVIEW_ROWS item=ROW}
												<tr>
													{foreach from=$ROW item=CELL}
														<td>{$CELL|escape}</td>
													{/foreach}
												</tr>
											{/foreach}
										{else}
											<tr>
												<td colspan="{if $PREVIEW_HEADERS|@count gt 0}{$PREVIEW_HEADERS|@count}{else}1{/if}" class="text-center text-muted">
													{\App\Language::translate('LBL_NO_PREVIEW_ROWS', $MODULE_NAME)}
												</td>
											</tr>
										{/if}
									</tbody>
								</table>
							</div>
						</div>
					</div>

					{include file='WizardStep2Mapping.tpl'|@vtemplate_path:$MODULE}
				</div>
			</div>
		</div>
	{/strip}
	<script type="application/json" id="ImportManagerContext">
{$IMPORT_CONTEXT_JSON nofilter}
	</script>
{/block}

