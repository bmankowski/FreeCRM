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
<!-- layouts/basic/modules/Settings/TimeControlProcesses/IndexContent.tpl -->
<div class="processesContainer">
	<div class="widget_header row">
		<div class="col-xs-12">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			<h5>{"LBL_TIMECONTROL_PROCESSES_DESCRIPTION"|t:$QUALIFIED_MODULE}</h5>
		</div>
	</div>
	<ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
		<li class="active"><a href="#general" data-toggle="tab">{"LBL_GENERAL_SETTINGS"|t:$QUALIFIED_MODULE}</a></li>
		<li><a href="#timeControlWidget" data-toggle="tab">{"LBL_TIME_CONTROL_WIDGET"|t:$QUALIFIED_MODULE}</a></li>
	</ul>
	<br/>
	<div class="tab-content">
		<div class='editViewContainer tab-pane active' id="general" data-type="general">
			{assign var=GENERAL_FIELDS value=$MODULE_MODEL->get('general')}
			<div class="" data-toggle="buttons">
				<label class="btn {if $GENERAL_FIELDS.oneDay eq 'true'}btn-success active{else}btn-default{/if} btn-block">
					<input autocomplete="off" type="checkbox" name="oneDay" {if $GENERAL_FIELDS.oneDay eq 'true'}checked{/if}>{"LBL_ONEDAY_VALID"|t:$QUALIFIED_MODULE}
					<span class="glyphicon {if $GENERAL_FIELDS.oneDay eq 'true'}glyphicon-check{else}glyphicon-unchecked{/if} pull-left"></span>
				</label>
				<label class="btn {if $GENERAL_FIELDS.timeOverlap eq 'true'}btn-success active{else}btn-default{/if} btn-block">
					<input autocomplete="off" type="checkbox" name="timeOverlap" {if $GENERAL_FIELDS.timeOverlap eq 'true'}checked{/if}>{"LBL_TIMEOVERLAP_VALID"|t:$QUALIFIED_MODULE}
					<span class="glyphicon {if $GENERAL_FIELDS.timeOverlap eq 'true'}glyphicon-check{else}glyphicon-unchecked{/if} pull-left"></span>
				</label>
			</div>
		</div>
		<div class="tab-pane editViewContainer" id="timeControlWidget" data-type="timeControlWidget">
			<div class="alert alert-info" role="alert">{"LBL_TCW_INFO"|t:$QUALIFIED_MODULE}</div>
			{assign var=TCW_FIELDS value=$MODULE_MODEL->get('timeControlWidget')}
			<div class="" data-toggle="buttons">
				<label class="btn {if $TCW_FIELDS.holidays eq 'true'}btn-success active{else}btn-default{/if} btn-block">
					<input autocomplete="off" type="checkbox" name="holidays" {if $TCW_FIELDS.holidays eq 'true'}checked{/if}> {"LBL_HOLIDAYS"|t:$QUALIFIED_MODULE}
					<span class="glyphicon {if $TCW_FIELDS.holidays eq 'true'}glyphicon-check{else}glyphicon-unchecked{/if} pull-left"></span>
				</label>
				<label class="btn {if $TCW_FIELDS.workingDays eq 'true'}btn-success active{else}btn-default{/if} btn-block">
					<input autocomplete="off" type="checkbox" name="workingDays" {if $TCW_FIELDS.workingDays eq 'true'}checked{/if}> {"LBL_WORKING_DAYS"|t:$QUALIFIED_MODULE}
					<span class="glyphicon {if $TCW_FIELDS.workingDays eq 'true'}glyphicon-check{else}glyphicon-unchecked{/if} pull-left"></span>
				</label>
				<label class="btn {if $TCW_FIELDS.workingTime eq 'true'}btn-success active{else}btn-default{/if} btn-block">
					<input autocomplete="off" type="checkbox" name="workingTime" {if $TCW_FIELDS.workingTime eq 'true'}checked{/if}> {"LBL_WORKING_TIME"|t:$QUALIFIED_MODULE}
					<span class="glyphicon {if $TCW_FIELDS.workingTime eq 'true'}glyphicon-check{else}glyphicon-unchecked{/if} pull-left"></span>
				</label>
			</div>
		
		</div>
	</div>
</div>
<!--/layouts/basic/modules/Settings/TimeControlProcesses/IndexContent.tpl -->
{/strip}
