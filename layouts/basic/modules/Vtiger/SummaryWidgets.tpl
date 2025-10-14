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
<!-- layouts/basic/modules/Vtiger/SummaryWidgets.tpl -->
	<input type="hidden" name="page" value="{$PAGING_MODEL->get('page')}" />
	<input type="hidden" name="pageLimit" value="{$LIMIT}" />
	<input type="hidden" name="col" value="{$COLUMNS}" />
	<input type="hidden" name="relatedModule" value="{$RELATED_MODULE_NAME}" />
	<input type="hidden" name="relatedModuleName" class="relatedModuleName" value="{$RELATED_MODULE_NAME}" />
	{if $RELATED_MODULE_NAME && $RELATED_RECORDS}
		{assign var=FILENAME value="SummaryWidgetsContent.tpl"}
		{include file=$FILENAME|vtemplate_path:$MODULE RELATED_RECORDS=$RELATED_RECORDS}
	{elseif $PAGING_MODEL->get('nrt') == 1}
		<div class="summaryWidgetContainer">
			<p class="textAlignCenter">{"LBL_NO_RELATED"|t:$MODULE} {vtranslate($RELATED_MODULE_NAME, $RELATED_MODULE_NAME)}</p>
		</div>
	{/if}
	{assign var=NUMBER_OF_RECORDS value=count($RELATED_RECORDS)}
	{if $NUMBER_OF_RECORDS == 0}
		<div class="summaryWidgetContainer noCommentsMsgContainer">
			<p class="textAlignCenter">{"LBL_NO_RECORDS_FOUND"|t:$MODULE_NAME}</p>
		</div>
	{/if}
	{if !$IS_READ_ONLY && $LIMIT neq 'no_limit' && $NUMBER_OF_RECORDS >= $LIMIT}
		<div class="container-fluid">
			<div class="pull-right">
				<button type="button" class="btn btn-primary btn-xs moreRecentRecords" data-label-key="{$RELATED_MODULE_NAME}" >{"LBL_MORE"|t:$MODULE_NAME}</button>
			</div>
		</div>
	{/if}
<!--/layouts/basic/modules/Vtiger/SummaryWidgets.tpl -->
{/strip}
