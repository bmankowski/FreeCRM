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
<!-- layouts/basic/modules/Reports/ReportHeader.tpl -->
    <div>
		<div class="widget_header row marginBottom10px">
			<div class="col-sm-6">
				{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			</div>
			<div class="col-sm-6">
				<div class="btn-toolbar pull-right">
					{if $REPORT_MODEL->isEditable() eq true}
						<div class="btn-group">
							<button onclick='window.location.href = "{$REPORT_MODEL->getEditViewUrl()}"' type="button" class="cursorPointer btn btn-success">
								<span class="glyphicon glyphicon-pencil"></span>&nbsp;&nbsp;
								<strong>{"LBL_CUSTOMIZE"|t:$MODULE}</strong>&nbsp;
							</button>
						</div>
					{/if}
					<div class="btn-group">
						<button onclick='window.location.href = "{$REPORT_MODEL->getDuplicateRecordUrl()}"' type="button" class="cursorPointer btn btn-primary">
							<span class="fa fa-files-o"></span>&nbsp;&nbsp;
							<strong>{"LBL_DUPLICATE"|t:$MODULE}</strong>
						</button>
					</div>
				</div>
			</div>
		</div>
        <div class="reportsDetailHeader">
			<input type="hidden" name="date_filters" data-value='{\App\Modules\Vtiger\helpers\Util::toSafeHTML(\App\Json::encode($DATE_FILTERS))}' />
            <form id="detailView" onSubmit="return false;">
				<input type="hidden" name="date_filters" data-value='{\App\Modules\Vtiger\helpers\Util::toSafeHTML(\App\Json::encode($DATE_FILTERS))}' />
				<div class="reportHeader row">
					<div class="col-md-8">
						<h3 class="noSpaces" >{$REPORT_MODEL->getName()}</h3>
						<div id="noOfRecords" class="marginTop10">{"LBL_NO_OF_RECORDS"|t:$MODULE} <span id="countValue">{$COUNT}</span>
							{if $COUNT > 1000}
								<span class="redColor" id="moreRecordsText"> ({"LBL_MORE_RECORDS_TXT"|t:$MODULE})</span>
							{else}
								<span class="redColor hide" id="moreRecordsText"> ({"LBL_MORE_RECORDS_TXT"|t:$MODULE})</span>
							{/if}
						</div>
					</div>
					<div class="col-md-4">
						<span class="pull-right">
							<div class="btn-toolbar">
								{foreach item=DETAILVIEW_LINK from=$DETAILVIEW_LINKS}
									{assign var=LINKNAME value=$DETAILVIEW_LINK->getLabel()}
									<div class="btn-group">
										<button class="btn reportActions btn-default" name="{$LINKNAME}" data-href="{$DETAILVIEW_LINK->getUrl()}">
											{if $DETAILVIEW_LINK->getIcon()}<span class="{$DETAILVIEW_LINK->getIcon()}"></span>{/if}&nbsp;&nbsp;
											<strong>{$LINKNAME}</strong>
										</button>
									</div>
								{/foreach}
							</div>
						</span>
					</div>
				</div>
				<br>
				<div>
					<input type="hidden" id="recordId" value="{$RECORD_ID}" />
					{assign var=RECORD_STRUCTURE value=array()}
					{assign var=PRIMARY_MODULE_LABEL value=$PRIMARY_MODULE|t:$PRIMARY_MODULE}
					{foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$PRIMARY_MODULE_RECORD_STRUCTURE}
						{assign var=PRIMARY_MODULE_BLOCK_LABEL value=$BLOCK_LABEL|t:$PRIMARY_MODULE}
						{assign var=key value="$PRIMARY_MODULE_LABEL $PRIMARY_MODULE_BLOCK_LABEL"}
						{if $LINEITEM_FIELD_IN_CALCULATION eq false && $BLOCK_LABEL eq 'LBL_ITEM_DETAILS'}
							{* dont show the line item fields block when Inventory fields are selected for calculations *}
						{else}
							{$RECORD_STRUCTURE[$key] = $BLOCK_FIELDS}
						{/if}
					{/foreach}
					{foreach key=MODULE_LABEL item=SECONDARY_MODULE_RECORD_STRUCTURE from=$SECONDARY_MODULE_RECORD_STRUCTURES}
						{assign var=SECONDARY_MODULE_LABEL value=$MODULE_LABEL|t:$MODULE_LABEL}
						{foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$SECONDARY_MODULE_RECORD_STRUCTURE}
							{assign var=SECONDARY_MODULE_BLOCK_LABEL value=$BLOCK_LABEL|t:$MODULE_LABEL}
							{assign var=key value="$SECONDARY_MODULE_LABEL $SECONDARY_MODULE_BLOCK_LABEL"}
							{$RECORD_STRUCTURE[$key] = $BLOCK_FIELDS}
						{/foreach}
					{/foreach}
					{include file='AdvanceFilter.tpl'|@vtemplate_path RECORD_STRUCTURE=$RECORD_STRUCTURE ADVANCE_CRITERIA=$SELECTED_ADVANCED_FILTER_FIELDS COLUMNNAME_API=getReportFilterColumnName}
					<div class="row">
						<div class="textAlignCenter">
							<button class="btn generateReport btn-primary" data-mode="generate" value="{"LBL_GENERATE_NOW"|t:$MODULE}"/>
							<strong>{"LBL_GENERATE_NOW"|t:$MODULE}</strong>
							</button>&nbsp;
							<button class="btn btn-success generateReport" data-mode="save" value="{"LBL_SAVE"|t:$MODULE}"/>
							<strong>{"LBL_SAVE"|t:$MODULE}</strong>
							</button>
						</div>
					</div>
					<br>
				</div>
			</form>
		</div>
		<div id="reportContentsDiv">
<!--/layouts/basic/modules/Reports/ReportHeader.tpl -->
		{/strip}
