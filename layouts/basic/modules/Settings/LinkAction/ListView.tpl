{strip}
	{extends file="MainLayout.tpl"|@vtemplate_path}

	{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">
				<div class="widget_header row marginBottom10px">
					<div class="col-xs-12">
						{include file='BreadCrumbs.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
					</div>
				</div>
				<div class="listViewActionsDiv row marginBottom10px">
					<div class="col-md-4 btn-toolbar">
						<select class="chzn-select form-control" id="moduleFilter">
							<option value="">{"LBL_ALL"|t:$QUALIFIED_MODULE}</option>
							{foreach item=MODULE_LABEL key=MODULE_NAME from=$MODULE_FILTER_OPTIONS}
								<option value="{$MODULE_NAME}" {if $MODULE_NAME eq $SELECTED_MODULE}selected{/if}>{$MODULE_LABEL}</option>
							{/foreach}
						</select>
					</div>
					<div class="col-md-8">
						{include file='ListViewActions.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
					</div>
				</div>

				<div class="listViewContentDiv" id="listViewContents">
					{include file='ListViewContent.tpl'|@vtemplate_path:'Settings:Base'}
				</div>
			</div>
		</div>
	{/block}
{/strip}
