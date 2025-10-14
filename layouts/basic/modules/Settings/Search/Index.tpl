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
<!-- layouts/basic/modules/Settings/Search/Index.tpl -->
	{assign var="ModulesEntity" value=$MODULE_MODEL->getModulesEntity(false, true)}
	{assign var="Fields" value=$MODULE_MODEL->getFieldFromModule()}
	<div class=" SearchFieldsEdit">
		<div class="widget_header row">
			<div class="col-md-12">
			    {include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			    {"LBL_Module_desc"|t:$QUALIFIED_MODULE}
			</div>
			
		</div>
		<div class="btn-toolbar">
			<span class="pull-right group-desc ">
				<button class="btn btn-success saveModuleSequence hide" type="button">
					<strong>{"LBL_SAVE_MODULE_SEQUENCE"|t:$QUALIFIED_MODULE}</strong>
				</button>
			</span>
			<div class="clearfix"></div>
		</div>
		<div class="contents tabbable table-responsive">
			<table class="table customTableRWD table-bordered table-condensed listViewEntriesTable" id="modulesEntity">
				<thead>
					<tr class="blockHeader">
						<th><strong>{"Module"|t:$QUALIFIED_MODULE}</strong></th>
						<th data-hide='phone'><strong>{"LabelFields"|t:$QUALIFIED_MODULE}</strong></th>
						<th data-hide='phone'><strong>{"SearchFields"|t:$QUALIFIED_MODULE}</strong></th>
						<th data-hide='tablet' colspan="2"><strong>{"Tools"|t:$QUALIFIED_MODULE}</strong></th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$ModulesEntity item=item key=key}
						{assign var="Field" value=$Fields[$key]}
						<tr data-tabid="{$key}">
							<td><span>&nbsp;
									<a>
										<img src="{vimage_path('drag.png')}" border="0" title="{"LBL_DRAG"|t:$QUALIFIED_MODULE}"/>
									</a>&nbsp;
								</span>
								{vtranslate($item['modulename'],$item['modulename'])}
							</td>
							<td>
								<select multiple class="chzn-select form-control col-md-4 fieldname" name="fieldname">
									<optgroup>
										{foreach from=$Field item=fieldTab }
											<option value="{$fieldTab['columnname']}" {if $MODULE_MODEL->compare_vale($item['fieldname'],$fieldTab['columnname'])}selected{/if}>
												{vtranslate($fieldTab['fieldlabel'],$item['modulename'])}
											</option>
										{/foreach}
									</optgroup>
								</select>
							</td>
							<td>
								<select multiple class="chzn-select form-control col-md-4 searchcolumn" name="searchcolumn">
									<optgroup>
										{foreach from=$Field item=fieldTab }
											<option value="{$fieldTab['columnname']}" {if $MODULE_MODEL->compare_vale($item['searchcolumn'],$fieldTab['columnname'])}selected{/if}>
												{vtranslate($fieldTab['fieldlabel'],$item['modulename'])}
											</option>
										{/foreach}
									</optgroup>
								</select>
							</td>
							<td>
								<button class="btn marginLeftZero updateLabels btn-info" data-tabid="{$key}">{"Update labels"|t:$QUALIFIED_MODULE}</button>
							</td>
							<td>
								<button name="turn_off" class="btn marginLeftZero turn_off {if $item['turn_off'] eq 1}btn-danger{else}btn-success{/if}" style="min-width:40px" value="{$item['turn_off']}" >{if $item['turn_off'] eq 1}{"LBL_TURN_OFF"|t:$QUALIFIED_MODULE}{else}{"LBL_TURN_ON"|t:$QUALIFIED_MODULE}{/if}</button>
							</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
		<div class="clearfix"></div>
<!--/layouts/basic/modules/Settings/Search/Index.tpl -->
	{/strip}
