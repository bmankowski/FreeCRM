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
<!-- layouts/basic/modules/Base/uitypes/BooleanFieldSearchView.tpl -->
    {assign var="FIELD_INFO" value=\App\Utils\Json::encode($FIELD_MODEL->getFieldInfo())}
	{if isset($SEARCH_INFO['searchValue'])}
		{assign var=SEARCH_VALUES value=$SEARCH_INFO['searchValue']}
	{else}
		{assign var=SEARCH_VALUES value=''}
	{/if}
    <div class="boolenSearchField">
    <select class="select2noactive select2 listSearchContributor" name="{$FIELD_MODEL->get('name')}" title="{$FIELD_MODEL->get('label')|t:$MODULE}" data-fieldinfo='{$FIELD_INFO|escape}' {if $FIELD_MODEL->isEditableReadOnly()}readonly="readonly"{/if}>
        <option value="">{"LBL_SELECT_OPTION"|t:"Vtiger"}</option>
        <option value="1" {if $SEARCH_VALUES eq 1} selected{/if}>{"LBL_YES"|t:$MODULE}</option>
        <option value="0" {if $SEARCH_VALUES eq '0'} selected{/if}>{"LBL_NO"|t:$MODULE}</option>
    </select>
    </div>
<!--/layouts/basic/modules/Base/uitypes/BooleanFieldSearchView.tpl -->
{/strip}
