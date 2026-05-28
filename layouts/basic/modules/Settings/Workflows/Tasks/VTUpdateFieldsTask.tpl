{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Settings/Workflows/Tasks/VTUpdateFieldsTask.tpl -->
	<div class="row">
		<div class="col-md-2"><strong>{"LBL_SET_FIELD_VALUES"|t:$QUALIFIED_MODULE}</strong></div>
	</div><br>
	<div>
		<button type="button" class="btn btn-default" id="addFieldBtn">{"LBL_ADD_FIELD"|t:$QUALIFIED_MODULE}</button>
	</div><br>
	<div class="row conditionsContainer" id="save_fieldvaluemapping">
		{assign var=FIELD_VALUE_MAPPING value=$FIELD_VALUE_MAPPING_DECODED}
		<input type="hidden" id="fieldValueMapping" name="field_value_mapping" value='{$FIELD_VALUE_MAPPING_JSON}' />
		{foreach from=$FIELD_VALUE_MAPPING item=FIELD_MAP}
			<div class="row conditionRow padding-bottom1per">
				<span class="col-md-4">
					<select name="fieldname" class="chzn-select" style="min-width: 250px" data-placeholder="{"LBL_SELECT_FIELD"|t:$QUALIFIED_MODULE}">
						<option></option>
						{foreach from=$MODULE_MODEL->getFields() item=FIELD_MODEL}
                            {if !$FIELD_MODEL->isEditable() or $FIELD_MODEL->getFieldDataType() eq 'reference' or ($MODULE_MODEL->get('name')=="Documents" and in_array($FIELD_MODEL->get('name'),$RESTRICTFIELDS))} 
                                {continue}
                            {/if}
							{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
							{assign var=MODULE_MODEL value=$FIELD_MODEL->getModule()}
							<option value="{$FIELD_MODEL->get('name')}" {if $FIELD_MAP['fieldname'] eq $FIELD_MODEL->get('name')}selected=""{/if}data-fieldtype="{$FIELD_MODEL->getFieldType()}" data-field-name="{$FIELD_MODEL->get('name')}" data-fieldinfo="{$FIELD_INFO_JSON[$FIELD_MODEL->get('name')]}" >
								{if $SOURCE_MODULE neq $MODULE_MODEL->get('name')}
									({$MODULE_MODEL->get('name')|t:$MODULE_MODEL->get('name')})  {$FIELD_MODEL->get('label')|t:$MODULE_MODEL->get('name')}
								{else}
									{$FIELD_MODEL->get('label')|t:$SOURCE_MODULE}
								{/if}
							</option>
						{/foreach}
					</select>
				</span>
				<span class="fieldUiHolder col-md-4 marginLeftZero">
					<input type="text" class="getPopupUi form-control" readonly="" name="fieldValue" value="{$FIELD_MAP['value']}" />
					<input type="hidden" name="valuetype" value="{$FIELD_MAP['valuetype']}" />
				</span>
				<p class="cursorPointer form-control-static">
					<i class="alignMiddle deleteCondition glyphicon glyphicon-trash"></i>
				</p>
			</div>
		{/foreach}
		{include file="FieldExpressions.tpl"|@vtemplate_path:$QUALIFIED_MODULE}
	</div><br>
	<div class="row basicAddFieldContainer hide padding-bottom1per">
		<span class="col-md-4">
			<select name="fieldname" data-placeholder="{"LBL_SELECT_FIELD"|t:$QUALIFIED_MODULE}" class="form-control">
				<option></option>
				{foreach from=$MODULE_MODEL->getFields() item=FIELD_MODEL}
					{if !$FIELD_MODEL->isEditable() or $FIELD_MODEL->getFieldDataType() eq 'reference' or ($MODULE_MODEL->get('name')=="Documents" and in_array($FIELD_MODEL->get('name'),$RESTRICTFIELDS))}
						{continue}
					{/if}
					{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
					{assign var=MODULE_MODEL value=$FIELD_MODEL->getModule()}
					<option value="{$FIELD_MODEL->get('name')}" data-fieldtype="{$FIELD_MODEL->getFieldType()}" data-field-name="{$FIELD_MODEL->get('name')}" data-fieldinfo="{$FIELD_INFO_JSON[$FIELD_MODEL->get('name')]}" >
						{if $SOURCE_MODULE neq $MODULE_MODEL->get('name')}
							({$MODULE_MODEL->get('name')|t:$MODULE_MODEL->get('name')})  {$FIELD_MODEL->get('label')|t:$MODULE_MODEL->get('name')}
						{else}
							{$FIELD_MODEL->get('label')|t:$SOURCE_MODULE}
						{/if}
					</option>
				{/foreach}
			</select>
		</span>
		<span class="fieldUiHolder col-md-4 marginLeftZero">
			<input type="text" class="form-control" readonly="" name="fieldValue" value="" />
			<input type="hidden" name="valuetype" class="form-control" value="rawtext" />
		</span>
		<p class="cursorPointer form-control-static">
			<span class="alignMiddle deleteCondition glyphicon glyphicon-trash"></span>
		</p>
	</div>
<!--/layouts/basic/modules/Settings/Workflows/Tasks/VTUpdateFieldsTask.tpl -->
{/strip}
