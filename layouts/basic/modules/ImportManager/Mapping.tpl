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

				<div id="ImportManagerRoot" class="import-manager-screen" data-view="mapping">
					{include file='WizardSteps.tpl'|@vtemplate_path:$MODULE}

					{include file='BatchInfo.tpl'|@vtemplate_path:$MODULE}

					{assign var=PREVIEW_HEADERS value=$IMPORT_HEADERS|default:[]}
					{assign var=PREVIEW_ROWS value=$IMPORT_PREVIEW.rows|default:[]}
					{assign var=PREVIEW_META value=$IMPORT_PREVIEW.meta|default:[]}
					
					{* Preview Card *}
					<div class="import-card mb-4" id="ImportManagerPreview">
						<div class="import-card__header">
							<div class="import-card__icon">
								<i class="fa fa-table"></i>
							</div>
							<div class="import-card__title">
								<h5>{\App\Language::translate('LBL_IMPORT_PREVIEW', $MODULE_NAME)}</h5>
								<span class="import-card__subtitle">{\App\Language::translate('LBL_PREVIEW_DESC', $MODULE_NAME)|default:'Podgląd danych z pliku'}</span>
							</div>
							<div class="import-card__badges ml-auto">
								{if $IMPORT_BATCH.file_name}
									<span class="import-badge import-badge--light" id="ImportManagerPreviewFileName">
										<i class="fa fa-file"></i> {$IMPORT_BATCH.file_name|escape}
									</span>
								{/if}
								{if isset($PREVIEW_META.encoding) && $PREVIEW_META.encoding neq ''}
									<span class="import-badge import-badge--info" id="ImportManagerPreviewEncoding">
										{$PREVIEW_META.encoding|escape}
									</span>
								{/if}
								{if isset($PREVIEW_META.delimiter) && $PREVIEW_META.delimiter neq ''}
									{assign var=DELIMITER_VALUE value=$PREVIEW_META.delimiter}
									{if $DELIMITER_VALUE == "\t"}
										{assign var=DELIMITER_DISPLAY value=\App\Language::translate('LBL_TAB', $MODULE_NAME)}
									{else}
										{assign var=DELIMITER_DISPLAY value=$DELIMITER_VALUE}
									{/if}
									<span class="import-badge import-badge--info" id="ImportManagerPreviewDelimiter">
										Sep: {$DELIMITER_DISPLAY|escape}
									</span>
								{/if}
							</div>
						</div>
						<div class="import-card__body p-0">
							<div class="import-preview-table">
								<table class="table table-sm mb-0">
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
												<td colspan="{if $PREVIEW_HEADERS|@count gt 0}{$PREVIEW_HEADERS|@count}{else}1{/if}" class="text-center text-muted py-4">
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

