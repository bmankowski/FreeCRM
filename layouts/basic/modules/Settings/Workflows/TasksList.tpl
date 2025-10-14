{*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************}
{strip}
<!-- layouts/basic/modules/Settings/Workflows/TasksList.tpl -->
	<br>
	<div>
		<table class="table table-bordered table-condensed listViewEntriesTable">
			<thead>
				<tr class="listViewHeaders">
					<th width="10%">{"LBL_ACTIVE"|t:$QUALIFIED_MODULE}</th>
					<th width="30%">{"LBL_TASK_TYPE"|t:$QUALIFIED_MODULE}</th>
					<th>{"LBL_TASK_TITLE"|t:$QUALIFIED_MODULE}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$TASK_LIST item=TASK}
					<tr class="listViewEntries">
						<td><input type="checkbox" class="taskStatus" data-statusurl="{$TASK->getChangeStatusUrl()}" {if $TASK->isActive()} checked="" {/if} /></td>
						<td>{vtranslate($TASK->getTaskType()->getLabel(),$QUALIFIED_MODULE)}</td>
						<td>{$TASK->getName()}
							<div class="pull-right actions">
								<span class="actionImages">
									<a data-url="{$TASK->getEditViewUrl()}">
										<span class="glyphicon glyphicon-pencil alignMiddle" title="{"LBL_EDIT"|t:$QUALIFIED_MODULE}"></span>
									</a>&nbsp;&nbsp;
									<a class="deleteTask" data-deleteurl="{$TASK->getDeleteActionUrl()}">
										<span class="glyphicon glyphicon-trash alignMiddle" title="{"LBL_DELETE"|t:$QUALIFIED_MODULE}"></span>
									</a>
								</span>
							</div>
						</td>
					<tr>
				{/foreach}
			</tbody>
		</table>
		{if empty($TASK_LIST)}
			<table class="emptyRecordsDiv">
				<tbody>
					<tr>
						<td>
							{"LBL_NO_TASKS_ADDED"|t:$QUALIFIED_MODULE}
						</td>
					</tr>
				</tbody>
			</table>
		{/if}
	</div>
<!--/layouts/basic/modules/Settings/Workflows/TasksList.tpl -->
{/strip}
