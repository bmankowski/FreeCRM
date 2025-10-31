{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
* Contributor(s): YetiForce.com
********************************************************************************/
-->*}
{strip}
	<!-- layouts/basic/modules/Calendar/RelatedList.tpl -->
	<div class="relatedContainer">
		{assign var=RELATED_MODULE_NAME value=$RELATED_MODULE_NAME}
		<input type="hidden" name="currentPageNum" value="{$PAGING_MODEL->getCurrentPage()}" />
		<input type="hidden" name="relatedModuleName" class="relatedModuleName" value="{$RELATED_MODULE_NAME}" />
		<input type="hidden" value="{$ORDER_BY}" id="orderBy">
		<input type="hidden" value="{$SORT_ORDER}" id="sortOrder">
		<input type="hidden" value="{$RELATED_ENTIRES_COUNT}" id="noOfEntries">
		<input type='hidden' value="{$PAGING_MODEL->getPageLimit()}" id='pageLimit'>
		<input type='hidden' value="{$TOTAL_ENTRIES}" id='totalCount'>
		<input type="hidden" id="autoRefreshListOnChange"
			value="{AppConfig::performance('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE')}" />
		<div class="relatedHeader calendarRelatedHeader">
			<div class="btn-toolbar row">
				<div class="col-sm-6 col-md-6" style="display: flex; align-items: center;">
					{foreach item=RELATED_LINK from=$RELATED_LIST_LINKS['LISTVIEWBASIC']}
						{if $IS_CREATE_PERMITTED}
							<div class="btn-group paddingRight10">
								{assign var=IS_SELECT_BUTTON value={$RELATED_LINK->get('_selectRelation')}}
								<button type="button"
									class="btn btn-default addButton
										{if $IS_SELECT_BUTTON eq true} selectRelation {/if} moduleColor_{$RELATED_MODULE_NAME} {if $RELATED_LINK->linkqcs eq true}quickCreateSupported{/if}"
									{if $IS_SELECT_BUTTON eq true} data-moduleName={$RELATED_LINK->get('_module')->get('name')}
										{/if} {if ($RELATED_LINK->isPageLoadLink())} {if $RELATION_FIELD}
									data-name="{$RELATION_FIELD->getName()}" {/if} data-url="{$RELATED_LINK->getUrl()}" 
								{/if}
								{if $IS_SELECT_BUTTON neq true}name="addButton" {/if}>{if $IS_SELECT_BUTTON eq false}<span
								class="glyphicon glyphicon-plus icon-white"></span>{/if}&nbsp;<strong>{$RELATED_LINK->getLabel()}</strong></button>
					</div>
				{/if}
				{/foreach}
				<div class="btn-group" style="margin-left: 5px;">
					<input class="switchBtn" type="checkbox" {if $TIME=='current'}checked{/if}
						title="{"LBL_CHANGE_ACTIVITY_TYPE"|t}" data-size="normal" data-label-width="5"
						data-handle-width="90" data-on-text="{"LBL_CURRENT"|t}" data-off-text="{"LBL_HISTORY"|t}">
				</div>
			</div>
			<div class="col-xs-12 col-sm-6 col-md-6">
				<div class="paginationDiv pull-right">
					{include file='Pagination.tpl'|@vtemplate_path:$MODULE VIEWNAME='related'}
				</div>
			</div>
		</div>
	</div>
	<div class="contents-topscroll">
		<div class="topscroll-div">
			&nbsp;
		</div>
	</div>
	<div class="relatedContents contents-bottomscroll">
		<div class="bottomscroll-div">
			{assign var=FILENAME value="RelatedListContents.tpl"}
			{include file=$FILENAME|vtemplate_path:$RELATED_MODULE_NAME}
		</div>
	</div>
</div>
<!--/layouts/basic/modules/Calendar/RelatedList.tpl -->
{/strip}