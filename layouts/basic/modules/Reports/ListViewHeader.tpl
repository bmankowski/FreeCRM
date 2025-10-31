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
<!-- layouts/basic/modules/Reports/ListViewHeader.tpl -->
<div class="listViewPageDiv">
	<div class="listViewTopMenuDiv">
		<div class="listViewActionsDiv row">
			<span class="btn-toolbar col-md-4">
				<span class="btn-group listViewMassActions">
					<button class="btn btn-default dropdown-toggle" data-toggle="dropdown"><strong>{"LBL_ACTIONS"|t:$MODULE}</strong>&nbsp;&nbsp;<span class="caret"></span></button>
					<ul class="dropdown-menu">
						{foreach item="LISTVIEW_MASSACTION" from=$LISTVIEW_MASSACTIONS}
							<li id="{$MODULE}_listView_massAction_{\App\Modules\Base\Helpers\Util::replaceSpaceWithUnderScores($LISTVIEW_MASSACTION->getLabel())}"><a href="javascript:void(0);" {if stripos($LISTVIEW_MASSACTION->getUrl(), 'javascript:')===0}onclick='{$LISTVIEW_MASSACTION->getUrl()|substr:strlen("javascript:")};'{else} onclick="Vtiger_ListView_Js.triggerMassAction('{$LISTVIEW_MASSACTION->getUrl()}')"{/if} >{$LISTVIEW_MASSACTION->getLabel()|t:$MODULE}</a></li>
						{/foreach}
						{if $LISTVIEW_LINKS['LISTVIEW']|@count gt 0}
							<li class="divider"></li>
							{foreach item=LISTVIEW_ADVANCEDACTIONS from=$LISTVIEW_LINKS['LISTVIEW']}
								<li id="{$MODULE}_listView_advancedAction_{\App\Modules\Base\Helpers\Util::replaceSpaceWithUnderScores($LISTVIEW_ADVANCEDACTIONS->getLabel())}"><a {if stripos($LISTVIEW_ADVANCEDACTIONS->getUrl(), 'javascript:')===0} href="javascript:void(0);" onclick='{$LISTVIEW_ADVANCEDACTIONS->getUrl()|substr:strlen("javascript:")};'{else} href='{$LISTVIEW_ADVANCEDACTIONS->getUrl()}' {/if}>{$LISTVIEW_ADVANCEDACTIONS->getLabel()|t:$MODULE}</a></li>
							{/foreach}
						{/if}
					</ul>
				</span>
				{foreach item=LISTVIEW_BASICACTION from=$LISTVIEW_LINKS['LISTVIEWBASIC']}
					{if $LISTVIEW_BASICACTION->getLabel() eq 'LBL_ADD_RECORD'}
						{assign var="childLinks" value=$LISTVIEW_BASICACTION->getChildLinks()}
						<span class="btn-group">
							<button class="btn btn-default dropdown-toggle addButton" data-toggle="dropdown" id="{$MODULE}_listView_basicAction_Add">
								<span class="glyphicon glyphicon-plus"></span>&nbsp;
								<strong>{$LISTVIEW_BASICACTION->getLabel()|t:$MODULE}</strong>&nbsp;
								<span class="caret icon-white"></span></button>
							<ul class="dropdown-menu">
								{foreach item="childLink" from=$childLinks}
									<li id="{$MODULE}_listView_basicAction_{\App\Modules\Base\Helpers\Util::replaceSpaceWithUnderScores($childLink->getLabel())}">
										<a href="javascript:void(0);" onclick='{$childLink->getUrl()|substr:strlen("javascript:")};'>{$childLink->getLabel()|t:$MODULE}</a>
									</li>
								{/foreach}
							</ul>
						</span>
					{else}
						<span class="btn-group">
							<button id="{$MODULE}_listView_basicAction_{\App\Modules\Base\Helpers\Util::replaceSpaceWithUnderScores($LISTVIEW_BASICACTION->getLabel())}" class="btn addButton btn-default" {if stripos($LISTVIEW_BASICACTION->getUrl(), 'javascript:')===0}onclick='{$LISTVIEW_BASICACTION->getUrl()|substr:strlen("javascript:")};'{else} onclick='window.location.href="{$LISTVIEW_BASICACTION->getUrl()}"'{/if}><span class="glyphicon glyphicon-plus"></span>&nbsp;<strong>{$LISTVIEW_BASICACTION->getLabel()|t:$MODULE}</strong></button>
						</span>
					{/if}
				{/foreach}
			</span>
			<span class="foldersContainer btn-toolbar col-md-4">{include file='ListViewFolders.tpl'|@vtemplate_path:$MODULE}</span>
			<span class="col-md-4 btn-toolbar">
				{include file='ListViewActions.tpl'|@vtemplate_path:$MODULE}
			</span>
		</div>
	</div>
<div class="listViewContentDiv" id="listViewContents">
<!--/layouts/basic/modules/Reports/ListViewHeader.tpl -->
{/strip}
