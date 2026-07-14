{strip}
<!-- layouts/basic/modules/DelayedEmails/ListViewContent.tpl -->
<input type="hidden" id="pageStartRange" value="{$PAGING_MODEL->getRecordStartRange()}" />
<input type="hidden" id="pageEndRange" value="{$PAGING_MODEL->getRecordEndRange()}" />
<input type="hidden" id="previousPageExist" value="{$PAGING_MODEL->isPrevPageExists()}" />
<input type="hidden" id="nextPageExist" value="{$PAGING_MODEL->isNextPageExists()}" />
<input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT}" />
<input type="hidden" value="{$ORDER_BY}" id="orderBy">
<input type="hidden" value="{$SORT_ORDER}" id="sortOrder">
<input type='hidden' value="{$PAGE_NUMBER}" id='pageNumber'>
<input type='hidden' value="{$PAGING_MODEL->getPageLimit()}" id='pageLimit'>
<input type="hidden" value="{$LISTVIEW_ENTRIES_COUNT}" id="noOfEntries">

<div class="listViewEntriesDiv">
	{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
	<table class="table tableRWD table-bordered table-condensed listViewEntriesTable">
		<thead>
			<tr class="listViewHeaders">
				{assign var=WIDTH value={99/(count($LISTVIEW_HEADERS))}}
				{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
					{assign var=LISTVIEW_HEADERNAME value=$LISTVIEW_HEADER->get('name')}
					<th width="{$WIDTH}%" nowrap class="{$WIDTHTYPE}">
						{if $LISTVIEW_HEADERNAME eq 'actions'}
							{$LISTVIEW_HEADER->get('label')|t:$MODULE}
						{else}
							<a {if $LISTVIEW_HEADER->isListviewSortable()} class="listViewHeaderValues cursorPointer" data-nextsortorderval="{if $COLUMN_NAME eq $LISTVIEW_HEADERNAME}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-columnname="{$LISTVIEW_HEADERNAME}" {/if}>{$LISTVIEW_HEADER->get('label')|t:$MODULE}
								{if $COLUMN_NAME eq $LISTVIEW_HEADERNAME}&nbsp;&nbsp;<span class="{$SORT_IMAGE}"></span>{/if}</a>
						{/if}
					</th>
				{/foreach}
			</tr>
		</thead>
		<tbody>
		{foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES}
			<tr class="listViewEntries" data-id="{$LISTVIEW_ENTRY->getId()}">
				{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
					{assign var=LISTVIEW_HEADERNAME value=$LISTVIEW_HEADER->get('name')}
					{if $LISTVIEW_HEADERNAME eq 'actions'}
						<td nowrap class="{$WIDTHTYPE} rightRecordActions" width="{$WIDTH}%">
							{assign var=LINKS value=$LISTVIEW_ENTRY->getRecordLinks()}
							{if count($LINKS) > 0}
								<div class="actions">
									{foreach from=$LINKS item=LINK}
										{include file='ButtonLink.tpl'|@vtemplate_path:$MODULE BUTTON_VIEW='listViewBasic' MODULE=$MODULE}
									{/foreach}
								</div>
							{/if}
						</td>
					{else}
						<td class="listViewEntryValue {$WIDTHTYPE}" width="{$WIDTH}%" nowrap>
							&nbsp;{$LISTVIEW_ENTRY->getDisplayValue($LISTVIEW_HEADERNAME)}
						</td>
					{/if}
				{/foreach}
			</tr>
		{/foreach}
		</tbody>
	</table>

	{if $LISTVIEW_ENTRIES_COUNT eq '0'}
	<table class="emptyRecordsDiv">
		<tbody>
			<tr>
				<td>
					{"LBL_NO_RECORDS_FOUND"|t:$MODULE}
				</td>
			</tr>
		</tbody>
	</table>
	{/if}
</div>
<!--/layouts/basic/modules/DelayedEmails/ListViewContent.tpl -->
{/strip}
