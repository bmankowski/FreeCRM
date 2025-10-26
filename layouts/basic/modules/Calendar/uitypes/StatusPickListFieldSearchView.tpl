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
<!-- layouts/basic/modules/Calendar/uitypes/StatusPickListFieldSearchView.tpl -->
{assign var="FIELD_INFO" value=\App\Json::encode($FIELD_MODEL->getFieldInfo())}
{assign var=PICKLIST_VALUES value=$FIELD_MODEL->getPicklistValues()}
{assign var=EVENTS_MODULE_MODEL value=\App\Modules\Base\Models\Module::getInstance('Events')}
{assign var=EVENT_STATUS_FIELD_MODEL value=$EVENTS_MODULE_MODEL->getField('activitystatus')}
{assign var=EVENT_STAUTS_PICKLIST_VALUES value=$EVENT_STATUS_FIELD_MODEL->getPicklistValues()}
{assign var=PICKLIST_VALUES value=array_merge($FIELD_MODEL->getPicklistValues(),$EVENT_STAUTS_PICKLIST_VALUES)}
{if isset($SEARCH_INFO['searchValue']) && $SEARCH_INFO['searchValue'] neq ''}
    {assign var=SEARCH_VALUES value=explode(',',$SEARCH_INFO['searchValue'])}
{else}
    {assign var=SEARCH_VALUES value=[]}
{/if}
<div class="picklistSearchField">
    <select class="select2 listSearchContributor" name="{$FIELD_MODEL->get('name')}" title="{$FIELD_MODEL->get('label')|t:$MODULE}" multiple data-fieldinfo='{$FIELD_INFO|escape}'>
        {foreach item=PICKLIST_LABEL key=PICKLIST_KEY from=$PICKLIST_VALUES}
            <option title="{$PICKLIST_LABEL}" value="{$PICKLIST_KEY}" {if in_array($PICKLIST_KEY,$SEARCH_VALUES)} selected{/if}>{$PICKLIST_LABEL}</option>
        {/foreach}
    </select>
</div>
<!--/layouts/basic/modules/Calendar/uitypes/StatusPickListFieldSearchView.tpl -->
{/strip}
