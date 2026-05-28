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
<!-- layouts/basic/modules/Base/widgets/RelatedModuleConfig.tpl -->
	<div class="modal fade" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<form class="form-modalAddWidget form-horizontal validateForm">
					<input type="hidden" name="wid" value="{$WID}">
					<input type="hidden" name="type" value="{$TYPE}">
					<div class="modal-header">
						<button type="button" data-dismiss="modal" class="close" title="{"LBL_CLOSE"|t:$QUALIFIED_MODULE}">×</button>
						<h3 id="massEditHeader" class="modal-title">{"Add widget"|t:$QUALIFIED_MODULE}</h3>
					</div>
					<div class="modal-body">
						<div class="form-container-sm">
							<div class="form-group form-group-sm">
								<label class="col-md-4 control-label">{"Type widget"|t:$QUALIFIED_MODULE}:</label>
								<div class="col-md-7 form-control-static">
									{$TYPE|t:$QUALIFIED_MODULE}
								</div>
							</div>
							<div class="form-group form-group-sm">
								<label class="col-md-4 control-label">{"Label"|t:$QUALIFIED_MODULE}:</label>
								<div class="col-md-7 controls"><input name="label" class="form-control" type="text" value="{$WIDGETINFO['label']}" /></div>
							</div>
							<div class="form-group form-group-sm">
								<label class="col-md-4 control-label">{"Related module"|t:$QUALIFIED_MODULE}<a href="#" class="HelpInfoPopover" title="" data-placement="top" data-content="{"Related module info"|t:$QUALIFIED_MODULE}" data-original-title="{"Related module"|t:$QUALIFIED_MODULE}"><i class="glyphicon glyphicon-info-sign"></i></a>:</label>
								<div class="col-md-7 controls">
									<select name="relatedmodule" class="select2 form-control marginLeftZero" data-validation-engine="validate[required]">
										{foreach from=$RELATEDMODULES item=item key=key}
											<option value="{$item['related_tabid']}" {if $WIDGETINFO['data']['relatedmodule'] == $item['related_tabid']}selected{/if} >{$item['label']|t:$item['name']}</option>
										{/foreach}
									</select>
								</div>
							</div>
							<div class="form-group form-group-sm">
								<label class="col-md-4 control-label">{"Limit entries"|t:$QUALIFIED_MODULE}<a href="#" class="HelpInfoPopover" title="" data-placement="top" data-content="{"Limit entries info"|t:$QUALIFIED_MODULE}" data-original-title="{"Limit entries"|t:$QUALIFIED_MODULE}"><i class="glyphicon glyphicon-info-sign"></i></a>:</label>
								<div class="col-md-7 controls">
									<input name="limit" class="form-control" type="text" value="{$WIDGETINFO['data']['limit']}"/>
								</div>
							</div>
							<div class="form-group form-group-sm">
								<label class="col-md-4 control-label">{"Columns"|t:$QUALIFIED_MODULE}<a href="#" class="HelpInfoPopover" title="" data-placement="top" data-content="{"Columns info"|t:$QUALIFIED_MODULE}" data-original-title="{"Columns"|t:$QUALIFIED_MODULE}"><i class="glyphicon glyphicon-info-sign"></i></a>:</label>
								<div class="col-md-7 controls">
									<select name="columns" class="select2 form-control marginLeftZero">
										{foreach from=$MODULE_MODEL->getColumns() item=item key=key}
											<option value="{$item}" {if $WIDGETINFO['data']['columns'] == $item}selected{/if} >{$item}</option>
										{/foreach}
									</select>
								</div>
							</div>
							<div class="form-group form-group-sm form-switch-mini">
								<label class="col-md-4 control-label">{"No left margin"|t:$QUALIFIED_MODULE}<a href="#" class="HelpInfoPopover" title="" data-placement="top" data-content="{"No left margin info"|t:$QUALIFIED_MODULE}" data-original-title="{"No left margin"|t:$QUALIFIED_MODULE}"><i class="glyphicon glyphicon-info-sign"></i></a>:</label>
								<div class="col-md-7 controls">
									<input name="nomargin" class="switchBtn switchBtnReload" type="checkbox" {if $WIDGETINFO['nomargin'] == 1}checked{/if} data-size="mini" data-label-width="5" data-on-text="{"LBL_YES"|t:$QUALIFIED_MODULE}" data-off-text="{"LBL_NO"|t:$QUALIFIED_MODULE}" value="1">
								</div>
							</div>
							<div class="form-group form-group-sm form-switch-mini">
								<label class="col-md-4 control-label">{"Add button"|t:$QUALIFIED_MODULE}<a href="#" class="HelpInfoPopover" title="" data-placement="top" data-content="{"Add button info"|t:$QUALIFIED_MODULE}" data-original-title="{"Add button"|t:$QUALIFIED_MODULE}"><i class="glyphicon glyphicon-info-sign"></i></a>:</label>
								<div class="col-md-7">
									<input name="action" class="switchBtn switchBtnReload" type="checkbox" {if isset($WIDGETINFO['data']['action']) && $WIDGETINFO['data']['action'] == 1}checked{/if} data-size="mini" data-label-width="5" data-on-text="{"LBL_YES"|t:$QUALIFIED_MODULE}" data-off-text="{"LBL_NO"|t:$QUALIFIED_MODULE}" value="1">
								</div>
							</div>
							<div class="form-group form-group-sm form-switch-mini">
								<label class="col-md-4 control-label">{"Select button"|t:$QUALIFIED_MODULE}<a href="#" class="HelpInfoPopover" title="" data-placement="top" data-content="{"LBL_SELECT_BUTTON_INFO"|t:$QUALIFIED_MODULE}" data-original-title="{"Select button"|t:$QUALIFIED_MODULE}"><i class="glyphicon glyphicon-info-sign"></i></a>:</label>
								<div class="col-md-7 controls form-switch-mini">
									<input name="actionSelect" class="switchBtn switchBtnReload" type="checkbox" {if isset($WIDGETINFO['data']['actionSelect']) && $WIDGETINFO['data']['actionSelect'] == 1}checked{/if} data-size="mini" data-label-width="5" data-on-text="{"LBL_YES"|t:$QUALIFIED_MODULE}" data-off-text="{"LBL_NO"|t:$QUALIFIED_MODULE}" value="1">
								</div>
							</div>
							<div class="form-group form-group-sm form-switch-mini">
								<label class="col-md-4 control-label">{"No message"|t:$QUALIFIED_MODULE}<a href="#" class="HelpInfoPopover" title="" data-placement="top" data-content="{"No message info"|t:$QUALIFIED_MODULE}" data-original-title="{"No message"|t:$QUALIFIED_MODULE}"><i class="glyphicon glyphicon-info-sign"></i></a>:</label>
								<div class="col-md-7 controls">
									<input name="no_result_text" class="switchBtn switchBtnReload" type="checkbox" {if isset($WIDGETINFO['data']['no_result_text']) && $WIDGETINFO['data']['no_result_text'] == 1}checked{/if} data-size="mini" data-label-width="5" data-on-text="{"LBL_YES"|t:$QUALIFIED_MODULE}" data-off-text="{"LBL_NO"|t:$QUALIFIED_MODULE}" value="1">
								</div>
							</div>
							{*<div class="form-group form-group-sm form-switch-mini">
							<label class="col-md-4 control-label">{"LBL_SHOW_ALL_RECORDS"|t:$QUALIFIED_MODULE}<a href="#" class="HelpInfoPopover" title="" data-placement="top" data-content="{"LBL_SHOW_ALL_RECORDS_INFO"|t:$QUALIFIED_MODULE}" data-original-title="{"LBL_SHOW_ALL_RECORDS"|t:$QUALIFIED_MODULE}"><i class="glyphicon glyphicon-info-sign"></i></a>:</label>
							<div class="col-md-7 controls">
							<input name="showAll" class="switchBtn switchBtnReload" type="checkbox" {if $WIDGETINFO['data']['showAll'] == 1}checked{/if} data-size="mini" data-label-width="5" data-on-text="{"LBL_YES"|t:$QUALIFIED_MODULE}" data-off-text="{"LBL_NO"|t:$QUALIFIED_MODULE}" value="1">
							</div>
							</div>*}
							<div class="form-group form-group-sm hide">
								<label class="col-md-4 control-label">{"LBL_SHITCH_HEADER"|t:$QUALIFIED_MODULE}<a href="#" class="HelpInfoPopover" title="" data-placement="top" data-content="{"LBL_SHITCH_HEADER_INFO"|t:$QUALIFIED_MODULE}" data-original-title="{"LBL_SHITCH_HEADER"|t:$QUALIFIED_MODULE}"><i class="glyphicon glyphicon-info-sign"></i></a>:</label>
								<div class="col-md-7">
									<input type="hidden" id="switchHeader_selected" value="{$WIDGETINFO['data']['switchHeader']}">
									<select name="switchHeader" class="select2 form-control marginLeftZero">
										<option value="-">{"None"|t:$QUALIFIED_MODULE}</option>
									</select>
								</div>
							</div>
							<div class="form-group form-group-sm">
								<label class="col-md-4 control-label">{"Filter"|t:$QUALIFIED_MODULE}<a href="#" class="HelpInfoPopover" title="" data-placement="top" data-content="{"Filter info"|t:$QUALIFIED_MODULE}" data-original-title="{"Filter"|t:$QUALIFIED_MODULE}"><i class="glyphicon glyphicon-info-sign"></i></a>:</label>
								<div class="col-md-7 controls">
									<input type="hidden" id="filter_selected" value="{$WIDGETINFO['data']['filter']}">
									<select name="filter" class="select2 form-control marginLeftZero">
										<option value="-">{"None"|t:$QUALIFIED_MODULE}</option>
									</select>
								</div>
							</div>
							<div class="form-group form-group-sm">
								<label class="col-md-4 control-label">{"Switch"|t:$QUALIFIED_MODULE}<a href="#" class="HelpInfoPopover" title="" data-placement="top" data-content="{"Switch info"|t:$QUALIFIED_MODULE}" data-original-title="{"Switch"|t:$QUALIFIED_MODULE}"><i class="glyphicon glyphicon-info-sign"></i></a>:</label>
								<div class="col-md-7 controls">
									<input type="hidden" id="checkbox_selected" value="{$WIDGETINFO['data']['checkbox']}">
									<select name="checkbox" class="select2 form-control marginLeftZero">
										<option value="-">{"None"|t:$QUALIFIED_MODULE}</option>
									</select>
								</div>
							</div>
						</div>
					</div>
					{include file='ModalFooter.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
				</form>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Base/widgets/RelatedModuleConfig.tpl -->
{/strip}
