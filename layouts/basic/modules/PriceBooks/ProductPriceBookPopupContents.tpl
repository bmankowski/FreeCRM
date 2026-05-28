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
<!-- layouts/basic/modules/PriceBooks/ProductPriceBookPopupContents.tpl -->
<div class="contents-topscroll">
    <div class="topscroll-div">
        &nbsp;
    </div>
</div>
<div class="popupEntriesDiv relatedContents contents-bottomscroll">
	<input type="hidden" value="{$ORDER_BY}" id="orderBy">
	<input type="hidden" value="{$SORT_ORDER}" id="sortOrder">
	<input type="hidden" value="{$SOURCE_FIELD}" id="sourceField">
	<input type="hidden" value="{$SOURCE_RECORD}" id="sourceRecord">
	<input type="hidden" value="{$SOURCE_MODULE}" id="parentModule">
	<input type="hidden" value="Product_PriceBooks_Popup_Js" id="popUpClassName"/>
	{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
	<div class="bottomscroll-div">
		<table class="table table-bordered listViewEntriesTable">
			<thead>
				<tr class="listViewHeaders">
					<th class="{$WIDTHTYPE}">
						<input type="checkbox" title="{"LBL_SELECT_ALL_CURRENTPAGE"|t}"  class="selectAllInCurrentPage" />
					</th>
					{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
					<th class="{$WIDTHTYPE}">
						<a class="listViewHeaderValues cursorPointer" data-nextsortorderval="{if $ORDER_BY eq $LISTVIEW_HEADER->get('column')}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-columnname="{$LISTVIEW_HEADER->get('column')}">{$LISTVIEW_HEADER->get('label')|t:$MODULE_NAME}
							{if $ORDER_BY eq $LISTVIEW_HEADER->get('column')}<img class="sortImage" src="{vimage_path( $SORT_IMAGE, $MODULE_NAME)}">{else}<img class="hide sortingImage" src="{vimage_path( 'downArrowSmall.png', $MODULE_NAME)}">{/if}</a>
					</th>
					{/foreach}
					<th class="listViewHeaderValues noSorting {$WIDTHTYPE}">{"LBL_UNIT_PRICE"|t:$MODULE_NAME}</th>
					<th class="listViewHeaderValues noSorting {$WIDTHTYPE}">{"LBL_LIST_PRICE"|t:$MODULE_NAME}</th>
				</tr>
			</thead>
			{foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES name=popupListView}
			<tr class="listViewEntries" data-id="{$LISTVIEW_ENTRY->getId()}" data-name='{$LISTVIEW_ENTRY->getName()}' data-currency='{$LISTVIEW_ENTRY->get('currency_id')}'
				{if $GETURL neq '' } data-url='{$LISTVIEW_ENTRY->$GETURL()}' {/if} id="{$MODULE}_popUpListView_row_{$smarty.foreach.popupListView.index+1}">
				<td class="{$WIDTHTYPE}">
					<input class="entryCheckBox" title="{"LBL_SELECT_RECORD"|t}" type="checkbox" />
				</td>
				{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
				{assign var=LISTVIEW_HEADERNAME value=$LISTVIEW_HEADER->get('name')}
				<td class="listViewEntryValue {$WIDTHTYPE}">
					{if ($LISTVIEW_HEADER->isNameField() eq true or $LISTVIEW_HEADER->get('uitype') eq '4')}
						<a {if $LISTVIEW_HEADER->isNameField() eq true}class="moduleColor_{$MODULE}"{/if} href="{$LISTVIEW_ENTRY->getDetailViewUrl()}">
							{$LISTVIEW_ENTRY->getListViewDisplayValue($LISTVIEW_HEADERNAME)}
						</a>
					{else}
						{$LISTVIEW_ENTRY->getListViewDisplayValue($LISTVIEW_HEADERNAME)}
					{/if}
				</td>
				{/foreach}
				<td class="listViewEntryValue {$WIDTHTYPE}">
					<a>{$LISTVIEW_ENTRY->get('unit_price')}</a>
				</td>
				<td class="listViewEntryValue {$WIDTHTYPE}">
					<div class="row">
						<input type="text" value="{$LISTVIEW_ENTRY->get('unit_price')}" name="listPrice" class="invisible col-md-10 zeroPaddingAndMargin" data-validation-engine="validate[funcCall[Vtiger_Currency_Validator_Js.invokeValidation]]"
							   data-decimal-separator='{$USER_MODEL->get('currency_decimal_separator')}' data-group-separator='{$USER_MODEL->get('currency_grouping_separator')}'/>
					</div>
				</td>
			</tr>
			{/foreach}
		</table>
	</div>
	<!--added this div for Temporarily -->
	{if $LISTVIEW_ENTRIES_COUNT eq '0'}
		<div class="row">
			<div class="emptyRecordsDiv">{"LBL_RECORDS_NO_FOUND"|t}.{if $IS_MODULE_EDITABLE} <a href="{$MODULE_MODEL->getCreateRecordUrl()}">{"LBL_CREATE_SINGLE_RECORD"|t}</a>.{/if}</div>
		</div>
	{/if}
</div>
<div class="clearfix form-actions pushDown">
	<button class="cancelLink pull-right btn btn-warning" type="button">{"LBL_CANCEL"|t:$MODULE_NAME}</button>
	<button class="btn btn-success addButton select pull-right"><i class="glyphicon glyphicon-plus"></i>&nbsp;<strong>{"LBL_ADD_TO"|t:$MODULE_NAME}&nbsp;{$SOURCE_MODULE|t:$SOURCE_MODULE}</strong></button>
</div>
<!--/layouts/basic/modules/PriceBooks/ProductPriceBookPopupContents.tpl -->
{/strip}
