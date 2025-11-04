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
	<!-- layouts/basic/modules/Base/DashBoard.tpl -->
	{extends file="MainLayout.tpl"|@vtemplate_path}

	{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv col-md-12 marginLeftZero" id="centerPanel" style="min-height:550px;">

				{include file="dashboards/DashBoardHeader.tpl"|vtemplate_path:$MODULE_NAME DASHBOARDHEADER_TITLE=$MODULE|t:$MODULE}

				<div class="dashboardViewContainer">
					{if count($DASHBOARD_TYPES) > 1}
						<ul class="nav nav-tabs massEditTabs selectDashboard">
							{foreach from=$DASHBOARD_TYPES item=DASHBOARD}
								<li {if $CURRENT_DASHBOARD eq $DASHBOARD['dashboard_id']}class="active" {/if}
									data-id="{$DASHBOARD['dashboard_id']}">
									<a data-toggle="tab"><strong>{$DASHBOARD['name']|t}</strong></a>
								</li>
							{/foreach}
						</ul>
					{/if}

					{include file='dashboards/DashBoardButtons.tpl'|@vtemplate_path:$MODULE}

					<div class="gridster">
						<ul>
							{assign var=COLUMNS value=2}
							{assign var=ROW value=1}
							{assign var=COLCOUNT value=1}
							{assign var=SPECIAL_WIDTGETS value=['ChartFilter', 'MiniList', 'Notebook', 'Charts', 'Rss']}
							{foreach from=$WIDGETS item=WIDGET name=count}
								{if $WIDGET->get('active') eq 0}
									{continue}
								{/if}
								{assign var=WIDGETDOMID value=$WIDGET->get('linkid')}
								{if in_array($WIDGET->getName(), $SPECIAL_WIDTGETS)}
									{assign var=WIDGETDOMID value=$WIDGET->get('linkid')|cat:'-':$WIDGET->get('widgetid')}
								{/if}
								<li id="{$WIDGETDOMID}"
									{if $smarty.foreach.count.index % $COLUMNS == 0 and $smarty.foreach.count.index != 0}
									data-row="{$WIDGET->getPositionRow($ROW)}" {else} data-row="{$WIDGET->getPositionRow($ROW)}"
									{/if} {assign var=ROW value=$ROW+1}
									{assign var=COLCOUNT value=($smarty.foreach.count.index % $COLUMNS)+1}
									data-col="{$WIDGET->getPositionCol($COLCOUNT)}" data-sizex="{$WIDGET->getWidth()}"
									data-sizey="{$WIDGET->getHeight()}"
									class="dashboardWidget dashboardWidget_{$smarty.foreach.count.index}"
									data-url="{$WIDGET->getUrl()}" data-mode="open" data-name="{$WIDGET->getName()}"
									data-cache="{$WIDGET->get('cache')}">
								</li>
							{/foreach}
						</ul>
						<input type="hidden" id=row value="{$ROW}" />
						<input type="hidden" id=col value="{$COLCOUNT}" />
					</div>

				</div> 
			</div>
		</div>
	{/block}
	<!--/layouts/basic/modules/Base/DashBoard.tpl -->
{/strip}