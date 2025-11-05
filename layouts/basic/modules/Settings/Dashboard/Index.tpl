{*<!--
/*********************************************************************************
FreeCRM - Customer Relationship Management System
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
*
********************************************************************************/
-->*}
{strip}
	{extends file="MainLayout.tpl"|@vtemplate_path}

	{block name="content"}
		<!-- layouts/basic/modules/Settings/Dashboard/Index.tpl -->
		<div class="mainContainer">
			<div class="contentsDiv">
				{* Header with breadcrumbs *}
				<div class="widget_header row">
					<div class="col-xs-12">
						{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
					</div>
				</div>
				{* Tab Navigation *}
				<div class="row no-margin">
					<ul class="nav nav-tabs massEditTabs">
						<li class="active" data-mode="index"
							data-params="{\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Json::encode(['count'=>$WARNINGS_COUNT|default:0]))}">
							<a data-toggle="tab"><strong>{"LBL_START"|t:$QUALIFIED_MODULE}</strong></a>
						</li>
						<li data-mode="github">
							<a data-toggle="tab"><strong>{"LBL_GITHUB"|t:$QUALIFIED_MODULE}</strong></a>
						</li>
						<li data-mode="systemWarnings">
							<a data-toggle="tab"><strong>{"LBL_SYSTEM_WARNINGS"|t:$QUALIFIED_MODULE}</strong></a>
						</li>
					</ul>
				</div>
				{* Index Container for dynamic content *}
				<div class="indexContainer">
					{include file='IndexContent.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
				</div>

			</div> <!-- close contentsDiv -->
		</div> <!-- close mainContainer -->
		<!--/layouts/basic/modules/Settings/Dashboard/Index.tpl -->
	{/block}
{/strip}

