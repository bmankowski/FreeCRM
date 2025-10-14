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
<!-- layouts/basic/modules/OSSEmployees/EmployeeHierarchy.tpl -->
<div id="accountHierarchyContainer" class="modelContainer modal fade" taindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button class="close" data-dismiss="modal" title="{"LBL_CLOSE"|t}">x</button>
				<h3 class="modal-title">{"LBL_SHOW_EMPLOYEES_HIERARCHY"|t:$MODULE}</h3>
			</div>
			<div class="modal-body">
				<div id ="hierarchyScroll" style="margin-right: 8px;">
					<table class="table table-bordered">
						<thead>
							<tr class="blockHeader">
							{foreach item=HEADERNAME from=$EMPLOYEES_HIERARCHY['header']}
								<th>{$HEADERNAME|t:$MODULE}</th>
							{/foreach}
							</tr>
						</thead>
					{foreach item=ENTRIES from=$EMPLOYEES_HIERARCHY['entries']}
						<tbody>
							<tr>
							{foreach item=LISTFIELDS from=$ENTRIES}
								<td>{$LISTFIELDS}</td>
							{/foreach}
							</tr>
						</tbody>
					{/foreach}
					</table>
				</div>
			</div>
			<div class="modal-footer">
				<div class=" pull-right cancelLinkContainer">
					<button class="btn btn-primary" type="reset" data-dismiss="modal"><strong>{"LBL_CLOSE"|t:$MODULE}</strong></button>
				</div>
			</div>
		</div>
	</div>
</div>
<!--/layouts/basic/modules/OSSEmployees/EmployeeHierarchy.tpl -->
    {/strip}
