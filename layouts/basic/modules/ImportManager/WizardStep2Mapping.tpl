{assign var=HEADERS value=$IMPORT_HEADERS|default:[]}
{assign var=FIELDS value=$IMPORT_FIELDS|default:[]}
<div id="ImportManagerMappingCard" class="card mt-4">
	<div class="card-body">
		<p class="text-muted mb-3">{\App\Language::translate('LBL_MAPPING_INSTRUCTIONS', $MODULE_NAME)}</p>
		<div class="table-responsive">
			<table class="table table-bordered table-sm mb-3 mapping-table" id="ImportManagerMappingTable">
				<thead>
					<tr>
						<th>{\App\Language::translate('LBL_TARGET_FIELD', $MODULE_NAME)}</th>
						<th>{\App\Language::translate('LBL_SOURCE_COLUMN', $MODULE_NAME)}</th>
						<th>{\App\Language::translate('LBL_DEFAULT_VALUE', $MODULE_NAME)}</th>
					</tr>
				</thead>
				<tbody>
					{if $FIELDS|@count gt 0}
						{foreach from=$FIELDS item=FIELD}
							{assign var=SOURCE_INDEX value=$FIELD.preset.sourceIndex}
							<tr data-field="{$FIELD.name|escape}" data-label="{$FIELD.label|escape}" data-mandatory="{if $FIELD.mandatory}1{else}0{/if}">
								<td class="align-middle">
									<div class="font-weight-bold">
										{$FIELD.label|escape}
									</div>
									<div class="text-muted small mt-1 d-flex align-items-center flex-wrap">
										{if $FIELD.name}
											<span class="badge badge-light mr-2 mb-1">
												{$FIELD.name|escape}
											</span>
										{/if}
										{if $FIELD.type}
											<span class="mb-1 mr-2 text-capitalize">
												{$FIELD.type|escape}
											</span>
										{/if}
										<span class="mb-1">
											{if $FIELD.mandatory}
												{\App\Language::translate('LBL_MANDATORY', $MODULE_NAME)}
											{else}
												{\App\Language::translate('LBL_FIELD_OPTIONAL', $MODULE_NAME)}
											{/if}
										</span>
									</div>
								</td>
								<td>
									<select class="form-control form-control-sm js-source-select">
										<option value="">
											{\App\Language::translate('LBL_SELECT_OPTION', $MODULE_NAME)}
										</option>
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
								<td>
									<input type="text"
										class="form-control form-control-sm js-default-value"
										placeholder="{\App\Language::translate('LBL_DEFAULT_VALUE_PLACEHOLDER', $MODULE_NAME)}"
										value="{$FIELD.preset.defaultValue|escape}" />
								</td>
							</tr>
						{/foreach}
					{else}
						<tr>
							<td colspan="3" class="text-center text-muted">
								{\App\Language::translate('LBL_NO_FIELDS_AVAILABLE', $MODULE_NAME)}
							</td>
						</tr>
					{/if}
				</tbody>
			</table>
		</div>
		<div class="text-muted small mb-3">
			{\App\Language::translate('LBL_DEFAULT_VALUE_INFO', $MODULE_NAME)}
		</div>

		<div class="text-right mt-3">
			<button type="button" class="btn btn-success" id="ImportManagerSaveMapping">
				<span class="fa fa-save mr-1"></span>
				{\App\Language::translate('LBL_SAVE_AND_CONTINUE', $MODULE_NAME)}
			</button>
		</div>
	</div>
</div>

