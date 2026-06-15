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
<!-- layouts/basic/modules/Base/DocumentsSummaryWidgetContents.tpl -->
	<div class="row">
		<span class="col-md-7">
			<strong>{"Title"|t:"Documents"}</strong>
		</span>
		<span class="col-md-4 horizontalLeftSpacingForSummaryWidgetHeader">
			<span class="pull-right">
				<strong>{"File Name"|t:"Documents"}</strong>
			</span>
		</span>
	</div>
	{foreach item=RELATED_RECORD from=$RELATED_RECORDS}
		{assign var=DOWNLOAD_FILE_URL value=$RELATED_RECORD->getDownloadFileURL()}
		{assign var=DOWNLOAD_STATUS value=$RELATED_RECORD->get('active')}
		{assign var=DOWNLOAD_LOCATION_TYPE value=$RELATED_RECORD->get('location_type')}
		<div class="recentActivitiesContainer" id="relatedDocuments">
			<ul class="unstyled">
				<li>
					<div class="row" id="documentRelatedRecord">
						<span class="col-md-7 textOverflowEllipsis">
							<a href="{$RELATED_RECORD->getDetailViewUrl()}" id="{$MODULE}_{$RELATED_MODULE}_Related_Record_{$RELATED_RECORD->get('id')}" title="{$RELATED_RECORD->getDisplayValue('notes_title')}">
								{$RELATED_RECORD->getDisplayValue('notes_title')}
							</a>
						</span>
						<span class="col-md-5 textOverflowEllipsis" id="DownloadableLink">
							{if $DOWNLOAD_STATUS eq 1}
								{$RELATED_RECORD->getDisplayValue('original_name', $RELATED_RECORD->getId(), $RELATED_RECORD)}
							{else}
								{$RELATED_RECORD->get('original_name')} 
							{/if}
						</span>
					</div>
				</li>
			</ul>
		</div>
	{/foreach}
	{assign var=NUMBER_OF_RECORDS value=count($RELATED_RECORDS)}
	{if $NUMBER_OF_RECORDS eq 5}
		<div class="row">
			<div class="pull-right">
				<a class="moreRecentDocuments cursorPointer">{"LBL_MORE"|t:$MODULE_NAME}</a>
			</div>
		</div>
	{/if}
<!--/layouts/basic/modules/Base/DocumentsSummaryWidgetContents.tpl -->
{/strip}