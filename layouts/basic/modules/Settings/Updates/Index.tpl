{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}

<style>
	.blockHeader th{
		text-align:center !important; 
		vertical-align:middle !important; 
	}
	.confTable td, label, span{
		text-align:center !important; 
		vertical-align:middle !important; 
	}	
</style>
<div class="">
	<div class="widget_header row">
		<div class="col-md-7">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			{if isset($SELECTED_PAGE)}
				{vtranslate($SELECTED_PAGE->get('description'),$QUALIFIED_MODULE)}
			{/if}
		</div>
		<div class="col-md-5">
			<div class="pull-right">
				<a class="btn btn-success addMenu" href="{Settings_ModuleManager_Module_Model::getUserModuleImportUrl()}"><strong>{"LBL_IMPORT_UPDATE"|t:$QUALIFIED_MODULE}</strong></a>
			</div>
		</div>
	</div>
	<hr>
	<table class="table tableRWD table-bordered table-condensed themeTableColor">
		<thead>
			<tr class="blockHeader">
				<th colspan="1" class="mediumWidthType">
					<span>{"LBL_TIME"|t:$MODULE}</span>
				</th>
				<th colspan="1" class="mediumWidthType">
					<span>{"LBL_USER"|t:$MODULE}</span>
				</th>
				<th colspan="1" class="mediumWidthType">
					<span>{"LBL_NAME"|t:$MODULE}</span>
				</th>
				</th>
				<th colspan="1" class="mediumWidthType">
					<span>{"LBL_FROM_VERSION"|t:$MODULE}</span>
				</th>
				<th colspan="1" class="mediumWidthType">
					<span>{"LBL_TO_VERSION"|t:$MODULE}</span>
				</th>
				<th colspan="1" class="mediumWidthType">
					<span>{"LBL_RESULT"|t:$MODULE}</span>
				</th>
			</tr>
		</thead>
		<tbody>
			{foreach from=$UPDATES key=key item=foo}
				<tr>
					<td width="16%"><label class="marginRight5px">{$foo.time}</label></td>
					<td width="16%"><label class="marginRight5px">{$foo.user}</label></td>
					<td width="16%"><label class="marginRight5px">{$foo.name}</label></td>
					<td width="16%"><label class="marginRight5px">{$foo.from_version}</label></td>
					<td width="16%"><label class="marginRight5px">{$foo.to_version}</label></td>
					<td width="16%"><label class="marginRight5px">{if $foo.result eq 1}{"LBL_YES"|t:$MODULE}{else}{"LBL_NO"|t:$MODULE}{/if}</label></td>
				</tr>
			{/foreach}
		</tbody>
	</table>
</div>
