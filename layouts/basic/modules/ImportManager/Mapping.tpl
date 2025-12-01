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

					{assign var=HEADERS value=$IMPORT_HEADERS|default:[]}
					{assign var=FIELDS value=$IMPORT_FIELDS|default:[]}

					{* Mapping Card *}
					<div class="import-card import-card--primary" id="ImportManagerMappingCard">
						<div class="import-card__header">
							<div class="import-card__icon">
								<i class="fa fa-random"></i>
							</div>
							<div class="import-card__title">
								<h5>{\App\Language::translate('LBL_FIELD_MAPPING', $MODULE_NAME)|default:'Mapowanie pól'}</h5>
								<span class="import-card__subtitle">{\App\Language::translate('LBL_MAPPING_INSTRUCTIONS', $MODULE_NAME)}</span>
							</div>
						</div>
						<div class="import-card__body">
							<div class="import-mapping-table-wrapper">
								<table class="import-mapping-table" id="ImportManagerMappingTable">
									<thead>
										<tr>
											<th class="import-mapping-table__th">
												<i class="fa fa-bullseye import-mapping-table__th-icon"></i>
												{\App\Language::translate('LBL_TARGET_FIELD', $MODULE_NAME)}
											</th>
											<th class="import-mapping-table__th">
												<i class="fa fa-file-import import-mapping-table__th-icon"></i>
												{\App\Language::translate('LBL_SOURCE_COLUMN', $MODULE_NAME)}
											</th>
											<th class="import-mapping-table__th">
												<i class="fa fa-pencil-alt import-mapping-table__th-icon"></i>
												{\App\Language::translate('LBL_DEFAULT_VALUE', $MODULE_NAME)}
											</th>
										</tr>
									</thead>
									<tbody>
										{if $FIELDS|@count gt 0}
											{foreach from=$FIELDS item=FIELD}
												{assign var=SOURCE_INDEX value=$FIELD.preset.sourceIndex}
												<tr class="import-mapping-table__row {if $FIELD.mandatory}import-mapping-table__row--mandatory{/if}" 
													data-field="{$FIELD.name|escape}" 
													data-label="{$FIELD.label|escape}" 
													data-mandatory="{if $FIELD.mandatory}1{else}0{/if}">
													<td class="import-mapping-table__cell import-mapping-table__cell--field">
														<div class="import-field-info">
															<span class="import-field-info__name">{$FIELD.label|escape}</span>
															<div class="import-field-info__meta">
																{if $FIELD.name}
																	<span class="import-field-info__code">{$FIELD.name|escape}</span>
																{/if}
																{if $FIELD.type}
																	<span class="import-field-info__type">{$FIELD.type|escape}</span>
																{/if}
																{if $FIELD.mandatory}
																	<span class="import-field-info__badge import-field-info__badge--required">
																		{\App\Language::translate('LBL_MANDATORY', $MODULE_NAME)}
																	</span>
																{else}
																	<span class="import-field-info__badge import-field-info__badge--optional">
																		{\App\Language::translate('LBL_FIELD_OPTIONAL', $MODULE_NAME)}
																	</span>
																{/if}
															</div>
														</div>
													</td>
													<td class="import-mapping-table__cell">
														<select class="form-control import-select js-source-select">
															<option value="">{\App\Language::translate('LBL_SELECT_OPTION', $MODULE_NAME)}</option>
															{foreach from=$HEADERS item=HEADER key=HEADER_INDEX}
																{assign var=HEADER_POSITION value=$HEADER_INDEX+1}
																<option value="{$HEADER_INDEX}" data-column-name="{$HEADER|escape}" {if $SOURCE_INDEX !== null && $SOURCE_INDEX == $HEADER_INDEX}selected="selected"{/if}>
																	{if $HEADER neq ''}
																		{$HEADER|escape}
																	{else}
																		{\App\Language::translate('LBL_COLUMN_PLACEHOLDER', $MODULE_NAME)} {$HEADER_POSITION}
																	{/if}
																</option>
															{/foreach}
														</select>
													</td>
													<td class="import-mapping-table__cell">
														<input type="text"
															class="form-control import-input js-default-value"
															placeholder="{\App\Language::translate('LBL_DEFAULT_VALUE_PLACEHOLDER', $MODULE_NAME)}"
															value="{$FIELD.preset.defaultValue|escape}" />
													</td>
												</tr>
											{/foreach}
										{else}
											<tr>
												<td colspan="3" class="import-mapping-table__empty">
													<i class="fa fa-inbox"></i>
													{\App\Language::translate('LBL_NO_FIELDS_AVAILABLE', $MODULE_NAME)}
												</td>
											</tr>
										{/if}
									</tbody>
								</table>
							</div>
							
							<div class="import-mapping-footer">
								<div class="import-mapping-footer__hint">
									<i class="fa fa-info-circle"></i>
									{\App\Language::translate('LBL_DEFAULT_VALUE_INFO', $MODULE_NAME)}
								</div>
								<div class="import-mapping-footer__actions">
									<button type="button" class="btn btn-success import-btn import-btn--primary" id="ImportManagerSaveMapping">
										<i class="fa fa-arrow-right mr-2"></i>
										{\App\Language::translate('LBL_SAVE_AND_CONTINUE', $MODULE_NAME)}
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	{/strip}
	<script type="application/json" id="ImportManagerContext">
{$IMPORT_CONTEXT_JSON nofilter}
	</script>
{/block}

