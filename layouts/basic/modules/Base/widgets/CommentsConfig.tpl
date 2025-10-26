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
<!-- layouts/basic/modules/Base/widgets/CommentsConfig.tpl -->
<div class="modal fade" tabindex="-1">
	<div class="modal-dialog">
        <div class="modal-content">
			<form class="form-modalAddWidget">  
				<input type="hidden" name="wid" value="{$WID}">
				<input type="hidden" name="type" value="{$TYPE}">
				<div class="modal-header">
					<button type="button" data-dismiss="modal" class="close" title="{"LBL_CLOSE"|t:$QUALIFIED_MODULE}">×</button>
					<h3 id="massEditHeader" class="modal-title">{"Add widget"|t:$QUALIFIED_MODULE}</h3>
				</div>
				<div class="modal-body">
					<div class="modal-Fields">
						<div class="form-horizontal">
							<div class="form-group">
								<div class="col-md-3"><strong> {"Type widget"|t:$QUALIFIED_MODULE}</strong>:</div>
								<div class="col-md-7">
									{$TYPE|t:$QUALIFIED_MODULE}
								</div>
							</div>
							<div class="form-group">
								<div class="col-md-3"><label class="control-label">{"Label"|t:$QUALIFIED_MODULE}:</label></div>
								<div class="col-md-7"><input name="label" class="form-control" type="text" value="{$WIDGETINFO['label']}" /></div>
							</div>
							<div class="form-group">
								<div class="col-md-3"><label>{"No left margin"|t:$QUALIFIED_MODULE}:</label></div>
								<div class="col-md-7">
									<input name="nomargin" class="" type="checkbox" value="1" {if $WIDGETINFO['nomargin'] == 1}checked{/if}/>
									<a href="#" class="HelpInfoPopover" title="" data-placement="top" data-content="{"No left margin info"|t:$QUALIFIED_MODULE}" data-original-title="{"No left margin"|t:$QUALIFIED_MODULE}"><i class="glyphicon glyphicon-info-sign"></i></a>
								</div>
							</div>
							<div class="form-group">
								<div class="col-md-3"><label class="control-label">{"Limit entries"|t:$QUALIFIED_MODULE}:</label></div>
								<div class="col-md-7">
									<div class="col-xs-3 paddingLRZero">
										<input name="limit" class="form-control" type="text" value="{$WIDGETINFO['data']['limit']}"/>
									</div>
									<a href="#" class="HelpInfoPopover" title="" data-placement="top" data-content="{"Limit entries info"|t:$QUALIFIED_MODULE}" data-original-title="{"Limit entries"|t:$QUALIFIED_MODULE}"><i class="glyphicon glyphicon-info-sign"></i></a>
								</div>
							</div>
						</div>
					</div>
				</div>
				{include file='ModalFooter.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
			</form>
		</div>
	</div>
</div>
<!--/layouts/basic/modules/Base/widgets/CommentsConfig.tpl -->
{/strip}
