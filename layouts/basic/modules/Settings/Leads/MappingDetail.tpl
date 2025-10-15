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
<!-- layouts/basic/modules/Settings/Leads/MappingDetail.tpl -->
	<div class="row widget_header">
		<div class="col-md-8">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
		</div>
		<div class="col-md-4 btn-toolbar marginLeftZero">
			<div class="pull-right">
				{foreach item=LINK_MODEL from=$MODULE_MODEL->getDetailViewLinks()}
					<button type="button" class="btn btn-info" onclick={$LINK_MODEL->getUrl()}><strong>{$LINK_MODEL->getLabel()|t:$QUALIFIED_MODULE}</strong></button>
				{/foreach}
			</div>
		</div>
	</div>
	<div class='clearfix'></div>
	<div class=" contents" id="detailView">			
		<table class="table customTableRWD table-bordered" id="convertLeadMapping">
			<thead>
				<tr class="blockHeader">
					<th class="blockHeader">{"LBL_FIELD_LABEL"|t:$QUALIFIED_MODULE}</th>
					<th class="blockHeader">{"LBL_FIELD_TYPE"|t:$QUALIFIED_MODULE}</th>
					<th data-hide='phone' class="blockHeader">{"LBL_MAPPING_WITH_OTHER_MODULES"|t:$QUALIFIED_MODULE}</th>
				</tr>
			</thead>
		</table>
		<table class="table customTableRWD table-bordered" id="convertLeadMapping">
			<thead>
				<tr>
					{foreach key=key item=LABEL from=$MODULE_MODEL->getHeaders() name=index}
						<th {if $smarty.foreach.index.iteration > 2}data-hide='phone'{/if} ><b>{$LABEL|t:$LABEL}</b></th>
							{/foreach}
				</tr>
			</thead>
			<tbody>
				{foreach key=MAPPING_ID item=MAPPING from=$MODULE_MODEL->getMapping()}
					<tr class="listViewEntries" data-cfmid="{$MAPPING_ID}">
						<td>{$MAPPING['Leads']['label']|t:'Leads'}</td>
						<td>{$MAPPING['Leads']['fieldDataType']|t:$QUALIFIED_MODULE}</td>
						<td>{$MAPPING['Accounts']['label']|t:'Accounts'}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
<!--/layouts/basic/modules/Settings/Leads/MappingDetail.tpl -->
{/strip}
