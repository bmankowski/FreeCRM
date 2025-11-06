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
{extends file='MainLayout.tpl'|@vtemplate_path:$MODULE}

{block name="content"}
<!-- layouts/basic/modules/Rss/ListView.tpl -->
<div class="listViewTopMenuDiv">
	<div class="listViewActionsDiv row">
	</div>
	<div class="listViewContentDiv" id="listViewContents">
		<input type="hidden" id="sourceModule" value="{$SOURCE_MODULE}" />
		<div class="listViewEntriesDiv">
			<span class="listViewLoadingImageBlock hide modal" id="loadingListViewModal">
				<img class="listViewLoadingImage" src="{vimage_path('loading.gif')}" alt="no-image" title="{'LBL_LOADING'|t}"/>
				<p class="listViewLoadingMsg">{'LBL_LOADING_LISTVIEW_CONTENTS'|t}........</p>
			</span>
			<div class="feedContainer">
				{if $RECORD}
					<input id="recordId" type="hidden" value="{$RECORD->getId()}">
					<div class="row">
						<div class="col-md-8" id="rssFeedHeading">
							<h3> {"LBL_FEEDS_LIST_FROM"|t:$MODULE}: {$RECORD->getName()} </h3>
						</div>
						<div class="btn-toolbar col-md-4">
							<span class="btn-group pull-right">
								<button id="deleteButton" class="btn btn-danger" title="{"LBL_DELETE"|t:$MODULE}"><span class="glyphicon glyphicon-trash"></span></button>
							</span>
							<span class="btn-group pull-right">
								<button id="makeDefaultButton" class="btn btn-info" title="{"LBL_SET_AS_DEFAULT"|t:$MODULE}">&nbsp;<strong>{"LBL_SET_AS_DEFAULT"|t:$MODULE}</strong></button>
							</span>
							<span class="btn-group pull-right">
								<button id="rssAddButton" class="rssAddButton btn btn-success" title="{"LBL_ADD_FEED_SOURCE"|t:$MODULE}"><span class="glyphicon glyphicon-plus"></span>&nbsp;<span class="userIcon-Rss"></span></button>
							</span>
							<span class="btn-group pull-right">
								<button id="changeFeedSource" class="changeFeedSource btn btn-primary" title="{"LBL_CHANGE_RSS_CHANNEL"|t:$MODULE}"><span class="glyphicon glyphicon-transfer"></span>&nbsp;<span class="userIcon-Rss"></span></button>
							</span>
						</div>
					</div>
					<div class="feedListContainer pushDown" style="overflow: auto;"> 
						{include file='RssFeedContents.tpl'|@vtemplate_path:$MODULE}
					</div>
				{else}
					<table class="emptyRecordsDiv">
						<tbody>
							<tr>
								<td>
									{assign var=SINGLE_MODULE value="SINGLE_$MODULE"}
									<button class="rssAddButton btn btn-link tdUnderline">{"LBL_RECORDS_NO_FOUND"|t}. {"LBL_CREATE"|t} {$SINGLE_MODULE|t:$MODULE}</button>
								</td>
							</tr>
						</tbody>
					</table>
				{/if}
			</div>
		</div>
		<br />
		<div class="feedFrame">
		</div>
	</div>
</div>
<!--/layouts/basic/modules/Rss/ListView.tpl -->
{/block}
{/strip}

