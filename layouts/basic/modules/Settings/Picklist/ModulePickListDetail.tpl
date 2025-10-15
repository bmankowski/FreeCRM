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
<!-- layouts/basic/modules/Settings/Picklist/ModulePickListDetail.tpl -->
    {if !empty($NO_PICKLIST_FIELDS) }
        <label style="padding-top: 40px;"> <b>
                {$SELECTED_MODULE_NAME|t:$SELECTED_MODULE_NAME} {'NO_PICKLIST_FIELDS'|t:$QUALIFIED_NAME}. &nbsp; 
            {if !empty($CREATE_PICKLIST_URL)}
                <a href="{$CREATE_PICKLIST_URL}">{'LBL_CREATE_NEW'|t:$QUALIFIED_NAME}</a>
            {/if}
            </b>
        </label>
    {else}
	<div class="row">
		<label class="fieldLabel col-md-3"><strong>{"LBL_SELECT_PICKLIST_IN"|t:$QUALIFIED_MODULE}&nbsp;{$SELECTED_MODULE_NAME|t:$QUALIFIED_MODULE}</strong></label>
		<div class="col-md-4 fieldValue">
			<select class="chzn-select form-control" id="modulePickList">
				<optgroup>
					{foreach key=PICKLIST_FIELD item=FIELD_MODEL from=$PICKLIST_FIELDS}
						<option value="{$FIELD_MODEL->getId()}">{$FIELD_MODEL->get('label')|t:$SELECTED_MODULE_NAME}</option>
					{/foreach}	
				</optgroup>
			</select>
		</div>
	</div><br>
    {/if}
<!--/layouts/basic/modules/Settings/Picklist/ModulePickListDetail.tpl -->
{/strip}	
