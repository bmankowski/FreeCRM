{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/DocumentTemplates/ListViewContent.tpl -->
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

	<div class="listViewEntriesDiv overflowXAuto">
		{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
		<table class="table tableRWD table-bordered table-condensed listViewEntriesTable">
			<thead>
				<tr class="listViewHeaders">
					<th width="1%" class="{$WIDTHTYPE}">
						<input type="checkbox" id="listViewEntriesMainCheckBox" title="{'LBL_SELECT_ALL'|t}" />
					</th>
						{assign var=WIDTH value={99/(count($LISTVIEW_HEADERS))}}
						{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
						<th width="{$WIDTH}%" nowrap {if $LISTVIEW_HEADER@last}colspan="2" {/if} class="{$WIDTHTYPE}">
							<a  {if !($LISTVIEW_HEADER->has('sort'))} class="listViewHeaderValues cursorPointer" data-nextsortorderval="{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-columnname="{$LISTVIEW_HEADER->get('name')}" {/if}>{$LISTVIEW_HEADER->get('label')|t:$QUALIFIED_MODULE}
								{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}&nbsp;&nbsp;<span class="{$SORT_IMAGE}"></span>{/if}</a>
						</th>
					{/foreach}
				</tr>
			</thead>
			<tbody>
				{foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES}
					<tr class="listViewEntries" data-id="{$LISTVIEW_ENTRY->getId()}" 
						{if method_exists($LISTVIEW_ENTRY,'getDetailViewUrl')}data-recordurl="{$LISTVIEW_ENTRY->getDetailViewUrl()}"{/if}>
						<td width="1%" nowrap class="{$WIDTHTYPE}">
							<input type="checkbox" value="{$LISTVIEW_ENTRY->getId()}" class="listViewEntriesCheckBox" title="{'LBL_SELECT_SINGLE_ROW'|t}" />
						</td>
						{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
							{assign var=LISTVIEW_HEADERNAME value=$LISTVIEW_HEADER->get('name')}
							{assign var=LAST_COLUMN value=$LISTVIEW_HEADER@last}
							<td class="listViewEntryValue {$WIDTHTYPE}" data-name="{$LISTVIEW_HEADERNAME}" width="{$WIDTH}%" nowrap>
								&nbsp;{$LISTVIEW_ENTRY->getDisplayValue($LISTVIEW_HEADERNAME)|t:$QUALIFIED_MODULE}
								{if $LAST_COLUMN && $LISTVIEW_ENTRY->getRecordLinks()}
								</td><td nowrap class="{$WIDTHTYPE} rightRecordActions tdActions">
									{assign var=LINKS value=$LISTVIEW_ENTRY->getRecordLinks()}
									{if count($LINKS) > 0}
										<div class="actions">
											<div class="pull-right">
											{foreach from=$LINKS item=LINK}
												{if $LINK->getLabel() eq 'LBL_DELETE_RECORD'}
													<div class="btn-group {if $USER_MODEL->get('rowheight') eq 'narrow'}btn-group-sm{/if}">
														<button type="button" class="btn btn-default templateDelete popoverTooltip" data-id="{$LISTVIEW_ENTRY->getId()}" aria-label="{$LINK->getLabel()|t:$QUALIFIED_MODULE}" data-placement="bottom" data-content="{$LINK->getLabel()|t:$QUALIFIED_MODULE}" onclick="if (typeof event.stopPropagation === 'function') {ldelim}event.stopPropagation();{rdelim} else {ldelim}event.cancelBubble = true;{rdelim} if (typeof event.preventDefault === 'function') {ldelim}event.preventDefault();{rdelim} var recordId = {$LISTVIEW_ENTRY->getId()}; if (typeof DocumentTemplates_ListView_Js !== 'undefined' && DocumentTemplates_ListView_Js.deletePdfTemplate) {ldelim}return DocumentTemplates_ListView_Js.deletePdfTemplate(recordId, event);{rdelim} Vtiger_Helper_Js.showConfirmationBox({ldelim}message: app.vtranslate('LBL_DELETE_CONFIRMATION'){rdelim}).then(function () {ldelim}AppConnector.request({ldelim}module: app.getModuleName(), parent: app.getParentModuleName(), action: 'DeleteAjax', record: recordId{rdelim}).then(function () {ldelim}window.location.reload();{rdelim});{rdelim}); return false;">
															<span class="{$LINK->getIcon()}"></span>
														</button>
													</div>
												{else}
													{include file='ButtonLink.tpl'|@vtemplate_path:$QUALIFIED_MODULE BUTTON_VIEW='listViewBasic' MODULE=$QUALIFIED_MODULE}
												{/if}
											{/foreach}
											</div>
										</div>
									{/if}
								</td>
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
							{"LBL_NO_RECORDS_FOUND"|t:$QUALIFIED_MODULE}
						</td>
					</tr>
				</tbody>
			</table>
		{/if}
	</div>
<!--/layouts/basic/modules/DocumentTemplates/ListViewContent.tpl -->
{/strip}
