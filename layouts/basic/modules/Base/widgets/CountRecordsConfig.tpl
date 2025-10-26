{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/widgets/CountRecordsConfig.tpl -->
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
									<select multiple name="relatedModules" class="select2 form-control marginLeftZero" data-validation-engine="validate[required]">
										{foreach from=$RELATEDMODULES item=item key=key}
											<option value="{$item['related_tabid']}" {if in_array($item['related_tabid'], $WIDGETINFO['data']['relatedModules']) }selected{/if} >{$item['label']|t:$item['name']}</option>
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
						</div>
					</div>
					{include file='ModalFooter.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
				</form>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Base/widgets/CountRecordsConfig.tpl -->
{/strip}
