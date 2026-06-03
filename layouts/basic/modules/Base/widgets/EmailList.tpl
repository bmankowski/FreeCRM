{strip}
<!-- layouts/basic/modules/Base/widgets/EmailList.tpl -->
	<div class="summaryWidgetContainer">
		<div class="widgetContainer_{$key} widgetContentBlock" data-url="{$WIDGET['url']}" data-name="{$WIDGET['label']}" data-type="{$WIDGET['type']}">
			<div class="widget_header">
				<input type="hidden" name="relatedModule" value="{$WIDGET['data']['relatedmodule']}" />
				<div class="widgetTitle row">
					<div class="col-xs-7">
						<h4 class="moduleColor_{$WIDGET['label']}">{$WIDGET['label']|t:$MODULE_NAME}</h4>
					</div>
					<div class="col-xs-5">
						<div class="pull-right">
							{if \App\Core\AppConfig::main('isActiveSendingMails') && \App\Modules\Mail\Models\Module::canUserSend($USER_MODEL->getId())}
								{assign var=COMPOSE_URL value=\App\Modules\Mail\Models\Module::getComposeUrl($MODULE_NAME, $RECORD->getId())}
								<button type="button" class="btn btn-sm btn-default sendMailBtn" data-url="{$COMPOSE_URL}" data-module="{$MODULE_NAME}" data-record="{$RECORD->getId()}" title="{"LBL_COMPOSE"|t:"Mail"}">
									<span class="glyphicon glyphicon-envelope" title="{"LBL_COMPOSE"|t:"Mail"}"></span>
								</button>&nbsp;
							{/if}
						</div>
					</div>
				</div>
				<hr class="rowHr"/>
				<div class="row">
					<div class="col-xs-6 paddingRightZero">
						<select name="mail-type" title="{"LBL_CHANGE_MAIL_TYPE"|t:"Vtiger"}" class="form-control input-sm">
							<option value="All">{"LBL_ALL"|t:"Vtiger"}</option>
							<option value="0">{"LBL_OUTBOUND"|t:"Mail"}</option>
							<option value="1">{"LBL_INBOUND"|t:"Mail"}</option>
						</select>
					</div>
				</div>
			</div>
			<div class="hide modalView">
				<div class="modelContainer modal fade" tabindex="-1">
					<div class="modal-dialog modal-blg">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
								<h4 class="modal-title">{$WIDGET['label']|t:$MODULE_NAME}</h4>
							</div>
							<div class="modal-body modalViewBody">
								_modalContent_
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="widget_contents widgetContent mailsList"></div>
		</div>
	</div>
<!--/layouts/basic/modules/Base/widgets/EmailList.tpl -->
{/strip}
