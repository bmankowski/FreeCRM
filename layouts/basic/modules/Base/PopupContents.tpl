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
<!-- layouts/basic/modules/Base/PopupContents.tpl -->
	<input type='hidden' id='pageNumber' value="{$PAGE_NUMBER|default:1}">
	<input type='hidden' id='pageLimit' value="{$PAGING_MODEL->getPageLimit()}">
	<input type="hidden" id="noOfEntries" value="{$LISTVIEW_ENTRIES_COUNT|default:0}">
	<input type="hidden" id="pageStartRange" value="{$PAGING_MODEL->getRecordStartRange()}" />
	<input type="hidden" id="pageEndRange" value="{$PAGING_MODEL->getRecordEndRange()}" />
	<input type="hidden" id="previousPageExist" value="{$PAGING_MODEL->isPrevPageExists()}" />
	<input type="hidden" id="nextPageExist" value="{$PAGING_MODEL->isNextPageExists()}" />
	<input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT|default:0}" />
	<input type="hidden" id="autoRefreshListOnChange" value="{AppConfig::performance('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE')}" />
	<div class="contents-topscroll">
		<div class="topscroll-div">
			&nbsp;
		</div>
	</div>
	<div class="popupEntriesDiv relatedContents contents-bottomscroll">
		<input type="hidden" value="{$ORDER_BY|default:''}" id="orderBy">
		<input type="hidden" value="{$SORT_ORDER|default:''}" id="sortOrder">
		{if $USER_MODEL}
			{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
		{else}
			{assign var=WIDTHTYPE value='medium'}
		{/if}
		<div class="bottomscroll-div">
			<table class="table table-bordered listViewEntriesTable">
				<thead>
					<tr class="listViewHeaders">
						{if $MULTI_SELECT}
							<th class="{$WIDTHTYPE}">
								<input type="checkbox" title="{"LBL_SELECT_ALL_CURRENTPAGE"|t}" class="selectAllInCurrentPage" />
							</th>
						{/if}
						{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
							<th class="{$WIDTHTYPE}">
								<a href="javascript:void(0);" class="listViewHeaderValues {if $LISTVIEW_HEADER->get('name') eq 'listprice' || !$LISTVIEW_HEADER->isListviewSortable()} noSorting {/if}" data-nextsortorderval="{if $ORDER_BY eq $LISTVIEW_HEADER->get('column')}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-columnname="{$LISTVIEW_HEADER->get('column')}">{$LISTVIEW_HEADER->get('label')|t:$MODULE_NAME}
									{if $ORDER_BY eq $LISTVIEW_HEADER->get('column')}<img class="sortImage" alt="{"LBL_SORT_ASCENDING"|t}" src="{vimage_path( $SORT_IMAGE, $MODULE_NAME)}">{else}<img class="hide sortingImage" alt="{"LBL_SORT_DESCENDING"|t}" src="{vimage_path( 'downArrowSmall.png', $MODULE_NAME)}">{/if}</a>
							</th>
						{/foreach}
						<th class="{$WIDTHTYPE}"></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						{if $MULTI_SELECT}
							<td class="{$WIDTHTYPE}"></td>
						{/if}
						{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
							<td class="{$WIDTHTYPE}">
								{assign var=FIELD_UI_TYPE_MODEL value=$LISTVIEW_HEADER->getUITypeModel()}
								{assign var=LISTVIEW_HEADER_NAME value=$LISTVIEW_HEADER->getName()}
								{if isset($SEARCH_DETAILS[$LISTVIEW_HEADER_NAME])}
									{assign var=SEARCH_INFO value=$SEARCH_DETAILS[$LISTVIEW_HEADER_NAME]}
								{else}
									{assign var=SEARCH_INFO value=[]}
								{/if}
								{include file=vtemplate_path($FIELD_UI_TYPE_MODEL->getListSearchTemplateName(),$MODULE_NAME) FIELD_MODEL=$LISTVIEW_HEADER SEARCH_INFO=$SEARCH_INFO USER_MODEL=$USER_MODEL}
							</td>
						{/foreach}
						<td class="{$WIDTHTYPE}"><button class="btn btn-default" data-trigger="listSearch">{'LBL_SEARCH'|t:$MODULE_NAME }</button></td>
					</tr>

					{foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES name=popupListView}
						<tr class="listViewEntries" data-id="{$LISTVIEW_ENTRY->getId()}" data-name='{$LISTVIEW_ENTRY->getName()}' data-info='{\App\Utils\Json::encode($LISTVIEW_ENTRY->getRawData())}'
							{if $GETURL neq '' } data-url='{$LISTVIEW_ENTRY->$GETURL()}' {/if}  id="{$MODULE_NAME}_popUpListView_row_{$smarty.foreach.popupListView.index+1}">
							{if $MULTI_SELECT}
								<td class="{$WIDTHTYPE}">
									<input class="entryCheckBox" title="{"LBL_SELECT_RECORD"|t}" type="checkbox" />
								</td>
							{/if}
							{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
								{assign var=LISTVIEW_HEADERNAME value=$LISTVIEW_HEADER->get('name')}
								<td class="listViewEntryValue {$WIDTHTYPE}">
									{if ($LISTVIEW_HEADER->isNameField() eq true or $LISTVIEW_HEADER->get('uitype') eq '4')}
										<a {if $LISTVIEW_HEADER->isNameField() eq true}class="moduleColor_{$MODULE}"{/if} href="{$LISTVIEW_ENTRY->getDetailViewUrl()}">
											{$LISTVIEW_ENTRY->getListViewDisplayValue($LISTVIEW_HEADERNAME)}
										</a>
									{else if $LISTVIEW_HEADERNAME eq 'listprice'}
										{Currency\App\Fields\Field::convertToUserFormat($LISTVIEW_ENTRY->get($LISTVIEW_HEADERNAME), null, true, true)}
									{else}
										{$LISTVIEW_ENTRY->getListViewDisplayValue($LISTVIEW_HEADERNAME)}
									{/if}
								</td>
							{/foreach}
							<td class="{$WIDTHTYPE}"></td>

						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		<!--added this div for Temporarily -->
		{if $LISTVIEW_ENTRIES_COUNT eq '0'}
			<div class="">
				<div class="emptyRecordsDiv">{"LBL_NO_RELATED_RECORDS_FOUND"|t:$MODULE}.</div>
			</div>
		{/if}
	</div>
<!--/layouts/basic/modules/Base/PopupContents.tpl -->
{/strip}
