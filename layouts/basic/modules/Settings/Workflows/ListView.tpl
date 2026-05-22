{*<!--
/*********************************************************************************
** FreeCRM - Workflows settings list (module-name filter values)
********************************************************************************/
-->*}
{strip}
	<!-- layouts/basic/modules/Settings/Workflows/ListView.tpl -->
	{extends file="MainLayout.tpl"|@vtemplate_path}

	{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">
				<div class="widget_header row marginBottom10px">
					<div class="col-md-6">
						{include file='BreadCrumbs.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
					</div>
					<div class="col-md-6">
						<b class="pull-right paddingTop10">
						{if $CRON_RECORD_MODEL}
							{if $CRON_RECORD_MODEL->isDisabled()}{"LBL_DISABLED"|t:$QUALIFIED_MODULE}{/if}
							{if $CRON_RECORD_MODEL->isRunning()}{"LBL_RUNNING"|t:$QUALIFIED_MODULE}{/if}
							{if $CRON_RECORD_MODEL->isEnabled()}
								{if $CRON_RECORD_MODEL->hadTimedout}
									{"LBL_LAST_SCAN_TIMED_OUT"|t:$QUALIFIED_MODULE}.
								{elseif $CRON_RECORD_MODEL->getLastEndDateTime() neq ''}
									{"LBL_LAST_SCAN_AT"|t:$QUALIFIED_MODULE}
									{$CRON_RECORD_MODEL->getLastEndDateTime()}
									&nbsp;&
									{"LBL_TIME_TAKEN"|t:$QUALIFIED_MODULE}:&nbsp;
									{$CRON_RECORD_MODEL->getTimeDiff()}&nbsp;
									{"LBL_SHORT_SECONDS"|t:$QUALIFIED_MODULE}
								{/if}
							{/if}
						{/if}
						</b>
					</div>
				</div>
				<div class="listViewActionsDiv row marginBottom10px">
					<div class="col-md-4 btn-toolbar">
						<button class="btn btn-success addButton" {if stripos($MODULE_MODEL->getCreateViewUrl(), 'javascript:')===0} onclick="{$MODULE_MODEL->getCreateViewUrl()|substr:strlen('javascript:')};"
							{else} onclick='window.location.href="{$MODULE_MODEL->getCreateViewUrl()}"' {/if}>
							<i class="glyphicon glyphicon-plus"></i>&nbsp;
							<strong>{"LBL_NEW"|t:$QUALIFIED_MODULE} {"LBL_WORKFLOW"|t:$QUALIFIED_MODULE}</strong>
						</button>
						<button class="btn btn-default importButton" id="importButton" data-url="{$IMPORT_VIEW_URL}" title="{"LBL_IMPORT_TEMPLATE"|t:$QUALIFIED_MODULE}">
							<i class="glyphicon glyphicon-import"></i>
						</button>
					</div>
					<div class="col-md-3 btn-toolbar marginLeftZero">
						<select class="chzn-select form-control" id="moduleFilter">
							<option value="">{"LBL_ALL"|t:$QUALIFIED_MODULE}</option>
							{foreach item=MODULE_MODEL key=TAB_ID from=$SUPPORTED_MODULE_MODELS}
								<option {if $SOURCE_MODULE eq $MODULE_MODEL->getName()} selected="" {/if} value="{$MODULE_MODEL->getName()}">
									{if $MODULE_MODEL->getName() eq 'Calendar'}
										{'LBL_TASK'|t:$MODULE_MODEL->getName()}
									{else}
										{$MODULE_MODEL->getName()|t:$MODULE_MODEL->getName()}
									{/if}
								</option>
							{/foreach}
						</select>
					</div>
					<div class="col-md-5">
						{include file='ListViewActions.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
					</div>
				</div>
				<div class="listViewContentDiv" id="listViewContents">
					{include file='ListViewContent.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
				</div>
			</div>
		</div>
	{/block}
	<!--/layouts/basic/modules/Settings/Workflows/ListView.tpl -->
{/strip}
