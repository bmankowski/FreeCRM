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
<!-- layouts/basic/modules/Base/uitypes/TimeFieldSearchView.tpl -->
{assign var="FIELD_INFO" value=\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($FIELD_MODEL->getFieldInfo()))}
{if isset($SEARCH_INFO['searchValue'])}
{assign var="SEARCH_VALUE" value=$SEARCH_INFO['searchValue']}
{else}
{assign var="SEARCH_VALUE" value=""}
{/if}
{if !empty($SEARCH_VALUE)}
    {assign var=FIELD_VALUE value=$SEARCH_VALUE}
{else}
    {assign var=FIELD_VALUE value=""}
{/if}
{assign var="TIME_FORMAT" value=$USER_MODEL->get('hour_format')}
<div class="picklistSearchField">
<input type="text" data-format="{$TIME_FORMAT}" class="form-control clockPicker listSearchContributor" title="{$FIELD_MODEL->get('label')|t:$MODULE}" value="{$FIELD_VALUE}" name="{$FIELD_MODEL->getFieldName()}" data-fieldinfo='{$FIELD_INFO}' />
</div>
<!--/layouts/basic/modules/Base/uitypes/TimeFieldSearchView.tpl -->
{/strip}
