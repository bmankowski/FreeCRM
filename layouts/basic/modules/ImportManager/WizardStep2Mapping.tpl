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

