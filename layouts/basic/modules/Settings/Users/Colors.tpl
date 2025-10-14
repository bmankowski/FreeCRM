{*<!--
/*+***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 *************************************************************************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Settings/Users/Colors.tpl -->
<div class="UserColors">
	<div class="widget_header row">
		<div class="col-md-12">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			{"LBL_COLORS_DESCRIPTION"|t:$QUALIFIED_MODULE}
		</div>		
	</div>
	<div class="contents tabbable">
		<ul class="nav nav-tabs layoutTabs massEditTabs">
			<li class="active"><a data-toggle="tab" href="#userColors"><strong>{"LBL_USERS_COLORS"|t:$QUALIFIED_MODULE}</strong></a></li>
			<li ><a data-toggle="tab" href="#groupsColors"><strong>{"LBL_GROUPS_COLORS"|t:$QUALIFIED_MODULE}</strong></a></li>
			<li ><a data-toggle="tab" href="#modulesColors"><strong>{"LBL_MODULES"|t:$QUALIFIED_MODULE}</strong></a></li>
			<li ><a data-toggle="tab" href="#marketing"><strong>{"LBL_MARKETING_PROCESSES"|t:$QUALIFIED_MODULE}</strong></a></li>
			<li ><a data-toggle="tab" href="#financial"><strong>{"LBL_SALES_PROCESSES"|t:$QUALIFIED_MODULE}</strong></a></li>
			<li ><a data-toggle="tab" href="#realization"><strong>{"LBL_REALIZATION_PROCESSES"|t:$QUALIFIED_MODULE}</strong></a></li>
			<li ><a data-toggle="tab" href="#support"><strong>{"LBL_SUPPORT_PROCESSES"|t:$QUALIFIED_MODULE}</strong></a></li>
			<li ><a data-toggle="tab" href="#timecontrol"><strong>{"LBL_TIMECONTROL_PROCESSES"|t:$QUALIFIED_MODULE}</strong></a></li>
		</ul>
		<div class="tab-content layoutContent" style="padding-top: 10px;">
			<div class="tab-pane active" id="userColors">
				<table class="table customTableRWD table-bordered table-condensed listViewEntriesTable">
					<thead>
						<tr class="blockHeader">
							<th><strong>{"First Name"|t:$QUALIFIED_MODULE}</strong></th>
							<th><strong>{"Last Name"|t:$QUALIFIED_MODULE}</strong></th>
							<th><strong>{"LBL_COLOR"|t:$QUALIFIED_MODULE}</strong></th>
							<th data-hide='phone'><strong>{"LBL_TOOLS"|t:$QUALIFIED_MODULE}</strong></th>
						</tr>
					</thead>
					<tbody>
						{foreach from=Users_Colors_Model::getUserColors() item=item key=key}
							<tr data-id="{$item.id}" data-color="{$item.color}">
								<td>{$item.first}</td>
								<td>{$item.last}</td>
								<td class="calendarColor" style="background: {$item.color};"></td>
								<td>
									<button class="btn btn-sm btn-primary marginLeft10 updateColor" data-metod="updateUserColor">{"LBL_UPDATE_COLOR"|t:$QUALIFIED_MODULE}</button>&ensp;
									<button class="btn btn-sm btn-info generateColor" data-metod="generateUserColor">{"LBL_GENERATE_COLOR"|t:$QUALIFIED_MODULE}</button>
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
			<div class="tab-pane" id="groupsColors">
				<table class="table customTableRWD table-bordered table-condensed listViewEntriesTable">
					<thead>
						<tr class="blockHeader">
							<th><strong>{"LBL_GROUP_NAME"|t:$QUALIFIED_MODULE}</strong></th>
							<th><strong>{"LBL_COLOR"|t:$QUALIFIED_MODULE}</strong></th>
							<th data-hide='phone'><strong>{"LBL_TOOLS"|t:$QUALIFIED_MODULE}</strong></th>
						</tr>
					</thead>
					<tbody>
						{foreach from=Users_Colors_Model::getGroupColors() item=item key=key}
							<tr data-id="{$item.id}" data-color="{$item.color}">
								<td>{$item.groupname}</td>
								<td class="calendarColor" style="background: {$item.color};"></td>
								<td>
									<button class="btn btn-sm btn-primary marginLeft10 updateColor" data-metod="updateGroupColor">{"LBL_UPDATE_COLOR"|t:$QUALIFIED_MODULE}</button>&ensp;
									<button class="btn btn-sm btn-info generateColor" data-metod="generateGroupColor">{"LBL_GENERATE_COLOR"|t:$QUALIFIED_MODULE}</button>
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
			<div class="tab-pane" id="modulesColors">
				<table  class="table customTableRWD table-bordered table-condensed listViewEntriesTable">
					<thead>
						<tr class="blockHeader">
							<th><strong>{"LBL_MODULE"|t:$QUALIFIED_MODULE}</strong></th>
							<th><strong>{"LBL_ACTIVE"|t:$QUALIFIED_MODULE}</strong></th>
							<th><strong>{"LBL_COLOR"|t:$QUALIFIED_MODULE}</strong></th>
							<th data-hide='phone'><strong>{"LBL_TOOLS"|t:$QUALIFIED_MODULE}</strong></th>
						</tr>
					</thead>
					<tbody>
						{foreach from=Users_Colors_Model::getModulesColors() item=item key=key}
							<tr data-id="{$item.id}" data-color="{$item.color}">
								<td>{vtranslate($item.module,$item.module)}</td>
								<td>
									<input class="activeColor" type="checkbox" name="active" value="1" {if $item.active}checked=""{/if}>
								</td>
								<td class="calendarColor" style="background: {$item.color};"></td>
								<td>
									<button class="btn btn-sm btn-primary marginLeft10 updateColor" data-metod="updateModuleColor">{"LBL_UPDATE_COLOR"|t:$QUALIFIED_MODULE}</button>&ensp;
									<button class="btn btn-sm btn-info generateColor" data-metod="generateModuleColor">{"LBL_GENERATE_COLOR"|t:$QUALIFIED_MODULE}</button>
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
			{foreach from=$TABLES_ALL item=ELEMENTS key=PROCESS}
				<div class="tab-pane" id="{$PROCESS}">
					<div class="accordion">
						{foreach from=$ELEMENTS item=ITEM name=ELEMENT}
							{if $ITEM eq ''}
								{continue}
							{/if}
							<div class="accordion-group">
								<div class="accordion-heading">
									{assign var=TABLE value='vtiger_'|cat:$ITEM.fieldname}
									<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#{$TABLE}">
										{assign var=MODULE_NAME value=vtlib\Functions::getModuleName($ITEM.tabid)}
										{$MODULE_NAME|t:$MODULE_NAME}
										:&ensp;
										{$ITEM.fieldlabel|t:$MODULE_NAME}
									</a>
								</div>
								<div id="{$TABLE}" class="accordion-body collapse {if	$smarty.foreach.ELEMENT.index eq 0 } in {/if}">
									<div class="accordion-inner">
										<table class="table customTableRWD table-bordered table-condensed listViewEntriesTable" data-fieldname="{$ITEM.fieldname}">
											<thead>
												<tr class="blockHeader">
													<th><strong>{$ITEM.fieldlabel|t:$MODULE_NAME}</strong></th>
													<th><strong>{"LBL_COLOR"|t:$QUALIFIED_MODULE}</strong></th>
													<th data-hide='phone'><strong>{"LBL_TOOLS"|t:$QUALIFIED_MODULE}</strong></th>
												</tr>
											</thead>
											<tbody>
												{assign var=FIELD value=Users_Colors_Model::getValuesFromField($ITEM.fieldname)}
												{foreach from=$FIELD item=INNER_ITEM key=INNER_KEY}
													<tr data-table="{$TABLE}" data-id="{$INNER_ITEM['id']}" data-color="{$INNER_ITEM['color']}">
														<td>{$INNER_ITEM['value']|t:$MODULE_NAME}</td>
														<td class="calendarColor" style="background: {$INNER_ITEM['color']};"></td>
														<td>
															<button class="btn btn-sm marginLeft10 btn-primary updateColor" data-metod="updateColorForProcesses">{"LBL_UPDATE_COLOR"|t:$QUALIFIED_MODULE}</button>&ensp;
															<button class="btn btn-sm btn-info generateColor" data-metod="generateColorForProcesses">{"LBL_GENERATE_COLOR"|t:$QUALIFIED_MODULE}</button>
														</td>
													</tr>
												{/foreach}
											</tbody>
										</table>
									</div>
								</div>
							</div>
						{/foreach}
					</div>
				</div>
			{/foreach}
		</div>
	</div>
	<div class="modal editColorContainer fade" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header contentsBackground">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h3 class="modal-title">{"LBL_EDIT_COLOR"|t:$QUALIFIED_MODULE}</h3>
				</div>
				<div class="modal-body">
					<form class="form-horizontal">
						<input type="hidden" class="selectedColor" value="" />
						<div class="form-group">
							<label class=" col-sm-3 control-label">{"LBL_SELECT_COLOR"|t:$QUALIFIED_MODULE}</label>
							<div class=" col-sm-8 controls">
								<p class="calendarColorPicker"></p>
							</div>
						</div>
					</form>
				</div>
				{include file='ModalFooter.tpl'|@vtemplate_path:$MODULE}
			</div>
		</div>
	</div>
</div>
<!--/layouts/basic/modules/Settings/Users/Colors.tpl -->
{/strip}
