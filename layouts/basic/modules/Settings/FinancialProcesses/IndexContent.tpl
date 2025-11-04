{*<!--
/*********************************************************************************
FreeCRM - Customer Relationship Management System
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
**************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Settings/FinancialProcesses/IndexContent.tpl -->
<div class="supportProcessesContainer">
	<div class="widget_header row">
		<div class="col-xs-12">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}	
			{"LBL_FINANCIAL_PROCESSES_DESCRIPTION"|t:$QUALIFIED_MODULE}
		</div>
	</div>
	<ul id="tabs" class="nav nav-tabs " data-tabs="tabs">
		<li class="active"><a href="#configuration" data-toggle="tab">{"LBL_GENERAL"|t:$QUALIFIED_MODULE} </a></li>
	</ul>
	<br />
	<div class="tab-content">
		<div class='editViewContainer tab-pane active' id="configuration">
		</div>
	</div>
</div>
<!--/layouts/basic/modules/Settings/FinancialProcesses/IndexContent.tpl -->
{/strip}
