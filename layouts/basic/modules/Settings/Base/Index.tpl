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
<!-- layouts/basic/modules/Settings/Base/Index.tpl -->
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
	<div id="page">
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
						<li class="active" data-mode="index" data-params="{\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Json::encode(['count'=>$WARNINGS_COUNT|default:0]))}">
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
				<div class="indexContainer"></div>
				
			</div> <!-- close contentsDiv -->
		</div> <!-- close mainContainer -->
	</div> <!-- close page -->
{/block}
<!--/layouts/basic/modules/Settings/Base/Index.tpl -->
{/strip}
