{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/MappedFields/Step2.tpl -->
	<div class="mfTemplateContents">
		<form name="editMFTemplate" action="index.php" method="post" id="mf_step2" class="form-horizontal">
			<input type="hidden" name="module" value="MappedFields">
			<input type="hidden" name="view" value="Edit">
			<input type="hidden" name="mode" value="Step3" />
			<input type="hidden" name="parent" value="Settings" />
			<input type="hidden" class="step" value="2" />
			<input type="hidden" name="record" value="{$RECORDID}" />
			{assign var="PARAMS" value=$MAPPEDFIELDS_MODULE_MODEL->get('params')}
			<div class="col-md-12 paddingLRZero">
				<div class="panel panel-default">
					<div class="panel-heading">
						<label>
							<strong>{'LBL_STEP_N'|t:$QUALIFIED_MODULE, 2}: {"LBL_MAPPING_SETTINGS_DETAILS"|t:$QUALIFIED_MODULE}</strong>
						</label>
					</div>
					<div class="panel-body">
						<div class="btn-toolbar">
							<button id="addMapping" class="btn btn-default addButton marginBottom10px" type="button">
								<span class="glyphicon glyphicon-plus"></span>&nbsp;<strong>{"LBL_ADD_CONDITION"|t:$QUALIFIED_MODULE}</strong>
							</button>
							<div class="checkbox col-md-8">
								<label>
									<input type="checkbox" name="autofill" {if $PARAMS.autofill} checked {/if}>{"LBL_AUTOFILL"|t:$QUALIFIED_MODULE} &nbsp;
								</label>
								<span class="popoverTooltip delay0"  data-placement="top"
									  data-content="{"LBL_AUTOFILL_INFO"|t:$QUALIFIED_MODULE}">
									<span class="glyphicon glyphicon-info-sign"></span>
								</span>
							</div>
						</div>
						<div class="contents" id="detailView">
							<div class="table-responsive">
								<table class="table table-bordered" id="mappingToGenerate">
									<tbody>
										<tr class="blockHeader">
											<th class="sourceModuleName"><b>{'SINGLE_'|cat:$SEL_MODULE_MODEL->getName()|t:$SEL_MODULE_MODEL->getName()}</b></th>
											<th><b>{"LBL_FIELDS_TYPE"|t:$QUALIFIED_MODULE}</b></th>
											<th class="targetModuleName"><b>{'SINGLE_'|cat:$REL_MODULE_MODEL->getName()|t:$REL_MODULE_MODEL->getName()}</b></th>
											<th class="defaultHeader"><b>{"LBL_DEFAULT_VALUE"|t:$QUALIFIED_MODULE}</b></th>
											<th class="actionsHeader"><b>{"LBL_ACTIONS"|t:$QUALIFIED_MODULE}</b></th>
										</tr>
										{foreach key=MAPPING_ID item=MAPPING_ARRAY from=$MAPPEDFIELDS_MODULE_MODEL->getMapping()  name="mappingLoop"}
											{assign var="SEQ" value=$smarty.foreach.mappingLoop.iteration}
											<tr class="listViewEntries" sequence-number="{$SEQ}">
												<td>
													<select class="sourceFields select2" name="mapping[{$SEQ}][source]">
														{foreach key=BLOCK_NAME item=FIELDS from=$SEL_MODULE_MODEL->getFields(true)}
															<optgroup label="{$BLOCK_NAME|t:$SEL_MODULE_MODEL->getName()}">
																{foreach key=FIELD_ID item=FIELD_OBJECT from=$FIELDS}
																	<option data-type="{$FIELD_OBJECT->getFieldDataType()}" data-mappingtype="{$FIELD_OBJECT->getFieldType()}" {if $FIELD_ID eq $MAPPING_ARRAY['source']->getId()} selected {/if} label="{$FIELD_OBJECT->getFieldLabelKey()|t:$SEL_MODULE_MODEL->getName()}" value="{$FIELD_ID}">
																		{$FIELD_OBJECT->getFieldLabelKey()|t:$SEL_MODULE_MODEL->getName()}
																	</option>
																{/foreach}
															</optgroup>
														{/foreach}
													</select>
													<input type="hidden" class="mappingType" name="mapping[{$SEQ}][type]" value="{$MAPPING_ARRAY['type']}" />
												</td>
												<td class="selectedFieldDataType text-center alignMiddle">{$MAPPING_ARRAY['source']->getFieldDataType()|t:$QUALIFIED_MODULE}</td>
												<td>
													<select class="targetFields select2" name="mapping[{$SEQ}][target]">
														{foreach key=BLOCK_NAME item=FIELDS from=$REL_MODULE_MODEL->getFields()}
															<optgroup label="{$BLOCK_NAME|t:$REL_MODULE_MODEL->getName()}">
																{foreach key=FIELD_ID item=FIELD_OBJECT from=$FIELDS}
																	{if $MAPPING_ARRAY['target']->getFieldDataType() eq $FIELD_OBJECT->getFieldDataType()}
																		<option data-type="{$FIELD_OBJECT->getFieldDataType()}" {if $FIELD_ID eq $MAPPING_ARRAY['target']->getId()} selected {/if} label="{$FIELD_OBJECT->getFieldLabelKey()|t:$SEL_MODULE_MODEL->getName()}" value="{$FIELD_ID}">
																			{$FIELD_OBJECT->getFieldLabelKey()|t:$REL_MODULE_MODEL->getName()}
																		</option>
																	{/if}
																{/foreach}
															</optgroup>
														{/foreach}
													</select>
												</td>
												<td class="">
													{if $MAPPING_ARRAY['default']}
														<input type="hidden" class="form-control default" value="{$MAPPING_ARRAY['default']}" />
													{/if}
												</td>
												<td class="textAlignCenter">
													<button title="{"LBL_DELETE"|t:$QUALIFIED_MODULE}" type="button" class="btn btn-default deleteMapping">
														<i class="glyphicon glyphicon-trash"></i>
													</button>
												</td>
											</tr>
										{/foreach}
										<tr class="hide newMapping listViewEntries">
											<td>
												<select class="sourceFields newSelect">
													<option data-type="{"LBL_NONE"|t:$QUALIFIED_MODULE}" value="0" label="{"LBL_NONE"|t:$QUALIFIED_MODULE}">{"LBL_NONE"|t:$QUALIFIED_MODULE}</option>
													{foreach key=BLOCK_NAME item=FIELDS from=$SEL_MODULE_MODEL->getFields(true)}
														<optgroup label="{$BLOCK_NAME|t:$SEL_MODULE_MODEL->getName()}">
															{foreach key=FIELD_ID item=FIELD_OBJECT from=$FIELDS}
																<option data-type="{$FIELD_OBJECT->getFieldDataType()}" data-type-name="{$FIELD_OBJECT->getFieldDataType()|t:$QUALIFIED_MODULE}" data-mappingtype="{$FIELD_OBJECT->getFieldType()}" label="{$FIELD_OBJECT->getFieldLabelKey()|t:$SEL_MODULE_MODEL->getName()}" value="{$FIELD_ID}">
																	{$FIELD_OBJECT->getFieldLabelKey()|t:$SEL_MODULE_MODEL->getName()}
																</option>
															{/foreach}
														</optgroup>
													{/foreach}
												</select>
												<input type="hidden" class="mappingType" value="" />
											</td>
											<td class="selectedFieldDataType text-center alignMiddle"></td>
											<td>
												<select class="targetFields newSelect">
													{foreach key=BLOCK_NAME item=FIELDS from=$REL_MODULE_MODEL->getFields()}
														<optgroup label="{$BLOCK_NAME|t:$REL_MODULE_MODEL->getName()}">
															{foreach key=FIELD_ID item=FIELD_OBJECT from=$FIELDS}
																<option data-type="{$FIELD_OBJECT->getFieldDataType()}" label="{$FIELD_OBJECT->getFieldLabelKey()|t:$SEL_MODULE_MODEL->getName()}" value="{$FIELD_ID}">
																	{$FIELD_OBJECT->getFieldLabelKey()|t:$REL_MODULE_MODEL->getName()}
																</option>
															{/foreach}
														</optgroup>
													{/foreach}
												</select>
											</td>
											<td class="">
											</td>
											<td class="textAlignCenter">
												<button title="{"LBL_DELETE"|t:$QUALIFIED_MODULE}" type="button" class="btn btn-default deleteMapping">
													<i class="glyphicon glyphicon-trash"></i>
												</button>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<div class="panel-footer clearfix">
						<div class="btn-toolbar pull-right">
							<button class="btn btn-danger backStep" type="button"><strong>{"LBL_BACK"|t:$QUALIFIED_MODULE}</strong></button>
							<button class="btn btn-success" type="submit"><strong>{"LBL_NEXT"|t:$QUALIFIED_MODULE}</strong></button>
							<button class="btn btn-warning cancelLink" type="reset">{"LBL_CANCEL"|t:$QUALIFIED_MODULE}</button>
						</div>
					</div>
				</div>
			</div>

	</div>
</form>
</div>

<div class="hide" id="defaultValuesElementsContainer">
	{foreach key=BLOCK_NAME item=FIELDS from=$REL_MODULE_MODEL->getFields()}
		{foreach key=_FIELD_ID item=_FIELD_INFO from=$FIELDS}
			{assign var="_FIELD_TYPE" value=$_FIELD_INFO->getFieldDataType()}
			{assign var="_FIELD_UITYPE" value=$_FIELD_INFO->getUIType()}
			{if $_FIELD_TYPE eq 'picklist' || $_FIELD_TYPE eq 'multipicklist'}
				<select id="{$_FIELD_ID}_defaultvalue" {if $_FIELD_TYPE eq 'multipicklist'} multiple {/if} class="form-control" disabled>
					{if $_FIELD_INFO->getFieldName() neq 'hdnTaxType' || $_FIELD_TYPE neq 'multipicklist'} <option value=" ">{"LBL_SELECT_OPTION"|t:"Vtiger"}</option> {/if}
					{foreach item=_PICKLIST_DETAILS from=$_FIELD_INFO->getPicklistDetails()}
						<option value="{$_PICKLIST_DETAILS.value}">{$_PICKLIST_DETAILS.label|@t:$REL_MODULE_MODEL->getName()}</option>
					{/foreach}
				</select>
			{elseif in_array($_FIELD_TYPE, ['owner', 'sharedOwner']) || $_FIELD_UITYPE eq '52'}
				<select id="{$_FIELD_ID}_defaultvalue" name="{$_FIELD_ID}_defaultvalue" class="" disabled {if $_FIELD_TYPE eq 'sharedOwner'} multiple {/if}>
					{if $_FIELD_TYPE neq 'sharedOwner'} <option value="0">{'LBL_NONE'|@t:$QUALIFIED_MODULE}</option> {/if}
					{foreach key=BLOCK_NAME item=ITEM from=$USERS_LIST}
						{if $_FIELD_UITYPE eq '52'} continue {/if}
						<optgroup label="{$BLOCK_NAME|@t:$QUALIFIED_MODULE}">
							{foreach key=_ID item=_NAME from=$ITEM}
								<option value="{$_ID}">{$_NAME}</option>
							{/foreach}
						</optgroup>
					{/foreach}
				</select>
			{elseif $_FIELD_TYPE eq 'date'}
				<input type="text" id="{$_FIELD_ID}_defaultvalue" data-date-format="{$DATE_FORMAT}" class="defaultInputTextContainer form-control col-md-2 dateField" value="" disabled/>
			{elseif $_FIELD_TYPE eq 'datetime'}
				<input type="text" id="{$_FIELD_ID}_defaultvalue" class="defaultInputTextContainer form-control col-md-2" value="" data-date-format="{$DATE_FORMAT}"/>
			{elseif $_FIELD_TYPE eq 'boolean'}
				<input type="checkbox" id="{$_FIELD_ID}_defaultvalue" class="" disabled/>
			{elseif !in_array($_FIELD_TYPE,['sharedOwner','reference'])}
				<input type="input" id="{$_FIELD_ID}_defaultvalue" class="defaultInputTextContainer form-control" disabled/>
			{/if}
		{/foreach}
	{/foreach}
</div>
<!--/layouts/basic/modules/Settings/MappedFields/Step2.tpl -->
{/strip}
