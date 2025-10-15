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
<!-- layouts/basic/modules/Settings/ModTracker/List.tpl -->
	<div id="modTrackerContainer">		
		{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}			    
		{"LBL_MODTRACKER_SETTINGS_DESCRIPTION"|t:$QUALIFIED_MODULE}		
		<hr>
		<div class="contents">
			<div class="contents tabbable">
				<table class="table table-bordered table-condensed listViewEntriesTable">
					<thead>
						<tr class="blockHeader">
							<th><strong>{"LBL_MODULE"|t:$QUALIFIED_MODULE}</strong></th>
							<th><strong>{"LBL_ACTIVE"|t:$QUALIFIED_MODULE}</strong></th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$MODULE_MODEL->getModTrackerModules() item=item key=key}
							<tr data-id="{$item.id}">
								<td>{$item.module|t:$item.module}</td>
								<td>
									<input class="activeModTracker" type="checkbox" name="active" value="1" {if $item.active}checked=""{/if}>
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
			<div class="clearfix"></div>
		</div>	
	</div>
<!--/layouts/basic/modules/Settings/ModTracker/List.tpl -->
{/strip}
