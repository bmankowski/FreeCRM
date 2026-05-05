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
<!-- layouts/basic/modules/Settings/CronTasks/ListViewContent.tpl -->

	<button type="button" class="btn btn-success addButton addCron"><span class="glyphicon glyphicon-plus"></span> {"LBL_ADD_CRON"|t:$QUALIFIED_MODULE}</button>
	<br />
	<br />
	<input type="hidden" id="pageStartRange" value="{$PAGING_MODEL->getRecordStartRange()}" />
	<input type="hidden" id="pageEndRange" value="{$PAGING_MODEL->getRecordEndRange()}" />
	<input type="hidden" id="previousPageExist" value="{$PAGING_MODEL->isPrevPageExists()}" />
	<input type="hidden" id="nextPageExist" value="{$PAGING_MODEL->isNextPageExists()}" />
	<input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT}" />
	<input type="hidden" value="{$ORDER_BY}" id="orderBy">
	<input type="hidden" value="{$SORT_ORDER}" id="sortOrder">
	<input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT}" />
	<input type='hidden' value="{$PAGE_NUMBER}" id='pageNumber'>
	<input type='hidden' value="{$PAGING_MODEL->getPageLimit()}" id='pageLimit'>
	<input type="hidden" value="{$LISTVIEW_ENTRIES_COUNT}" id="noOfEntries">

	<div class="listViewEntriesDiv">
		<span class="listViewLoadingImageBlock hide modal" id="loadingListViewModal">
			<img class="listViewLoadingImage" src="{vimage_path('loading.gif')}" alt="no-image" title="{'LBL_LOADING'|t}"/>
			<p class="listViewLoadingMsg">{'LBL_LOADING_LISTVIEW_CONTENTS'|t}........</p>
		</span>
		{assign var="NAME_FIELDS" value=$MODULE_MODEL->getNameFields()}
		{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
			<table class="table tableRWD table-bordered table-condensed  listViewEntriesTable">
				<thead col-visible-alltime='2'>
					<tr class="listViewHeaders">
						<th width="1%" class="{$WIDTHTYPE}"></th>
							{assign var=WIDTH value={99/(count($LISTVIEW_HEADERS))}}
						{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
							<th  {if $LISTVIEW_HEADER@last}colspan="1" {/if} class="{$WIDTHTYPE}">
								<a  {if !($LISTVIEW_HEADER->has('sort'))} class="listViewHeaderValues cursorPointer" data-nextsortorderval="{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-columnname="{$LISTVIEW_HEADER->get('name')}" {/if}>{$LISTVIEW_HEADER->get('label')|t:$QUALIFIED_MODULE}
									{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}&nbsp;&nbsp;<span class="{$SORT_IMAGE}"></span>{/if}</a>
							</th>
						{/foreach}
					</tr>
				</thead>
				<tbody>
					{foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES}
						<tr class="listViewEntries" data-id="{$LISTVIEW_ENTRY->getId()}"
							{if method_exists($LISTVIEW_ENTRY,'getDetailViewUrl')}data-recordurl="{$LISTVIEW_ENTRY->getDetailViewUrl()}"{/if}
							>
							<td width="1%" nowrap class="{$WIDTHTYPE}">
								{if $MODULE eq 'CronTasks'}
									<img src="{vimage_path('drag.png')}" class="alignTop" title="{"LBL_DRAG"|t:$QUALIFIED_MODULE}" />
								{/if}
							</td>
							{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
								{assign var=LISTVIEW_HEADERNAME value=$LISTVIEW_HEADER->get('name')}
								{assign var=CELL value=$LISTVIEW_ENTRY->getDisplayValue($LISTVIEW_HEADERNAME)}
								<td class="listViewEntryValue {$WIDTHTYPE}"  >
									{if $LISTVIEW_HEADERNAME eq 'name'}
										<a href="javascript:void(0);" class="cronTaskNameEdit text-primary" title="{"LBL_EDIT_RECORD"|t:$QUALIFIED_MODULE}"
											onclick="Settings_CronTasks_ListView_Js.triggerEditEvent('{$LISTVIEW_ENTRY->getEditViewUrlForHtml()}');if (event.stopPropagation){ldelim}event.stopPropagation();{rdelim}else{ldelim}event.cancelBubble = true;{rdelim}return false;">
											{if $CELL neq ''}{$CELL|t:$QUALIFIED_MODULE}{/if}
										</a>
									{else}
										{if $CELL neq ''}&nbsp; {$CELL}{/if}
									{/if}
									</td>
							{/foreach}
						</tr>
					{/foreach}
				</tbody>
			</table>
		
		<!--added this div for Temporarily -->
		{if $LISTVIEW_ENTRIES_COUNT eq '0'}
			<table class="emptyRecordsDiv">
				<tbody>
					<tr>
						<td>
							{"LBL_NO"|t} {$MODULE|t:$QUALIFIED_MODULE} {"LBL_FOUND"|t}
						</td>
					</tr>
				</tbody>
			</table>
		{/if}
	</div>
<!--/layouts/basic/modules/Settings/CronTasks/ListViewContent.tpl -->
{/strip}
