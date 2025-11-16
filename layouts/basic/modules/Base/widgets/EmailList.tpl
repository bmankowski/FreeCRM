{strip}
<!-- layouts/basic/modules/Base/widgets/EmailList.tpl -->
	{assign var=CONFIG value=OSSMail_Module_Model::getComposeParameters()}
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
							<button type="button" class="btn btn-sm btn-default showMailsModal" data-url="index.php?module=OSSMailView&view=MailsPreview&smodule={$MODULE_NAME}&srecord={$RECORD->getId()}&mode=showEmailsList">
								<span class="body-icon glyphicon glyphicon-search" title="{"LBL_SHOW_PREVIEW_EMAILS"|t:"OSSMailView"}"></span>
							</button>
							&nbsp;
							{if \App\Core\AppConfig::main('isActiveSendingMails') && \App\Modules\Users\Models\Privileges::isPermitted('OSSMail')}
								{if $USER_MODEL->get('internal_mailer') == 1}
									{assign var=URLDATA value=OSSMail_Module_Model::getComposeUrl($MODULE_NAME, $RECORD->getId(), 'Detail', 'new')}
									<button type="button" class="btn btn-sm btn-default sendMailBtn" data-url="{$URLDATA}" data-module="{$MODULE_NAME}" data-record="{$RECORD->getId()}" data-popup="{$CONFIG['popup']}" title="{"LBL_CREATEMAIL"|t:"OSSMailView"}">
										<span class="glyphicon glyphicon-envelope" title="{"LBL_CREATEMAIL"|t:"OSSMailView"}"></span>
									</button>&nbsp;
								{else}
									{assign var=URLDATA value=OSSMail_Module_Model::getExternalUrl($MODULE_NAME, $RECORD->getId(), 'Detail', 'new')}
									{if $URLDATA}
										<a class="btn btn-sm btn-default" href="{$URLDATA}" title="{"LBL_CREATEMAIL"|t:"OSSMailView"}">
											<span class="glyphicon glyphicon-envelope" title="{"LBL_CREATEMAIL"|t:"OSSMailView"}"></span>
										</a>&nbsp;
									{/if}
								{/if}
							{/if}
							{if \App\Security\Privilege::isPermitted('OSSMailView', 'ReloadRelationRecord')}
								<button type="button" class="btn btn-sm btn-default resetRelationsEmail">
									<span class="body-icon glyphicon glyphicon-retweet" title="{"BTN_RESET_RELATED_MAILS"|t:"OSSMailView"}"></span>
								</button>
							{/if}
						</div>
					</div>
				</div>
				<hr class="rowHr"/>
				<div class="row">
					<div class="col-xs-6 paddingRightZero">
						<select name="mail-type" title="{"LBL_CHANGE_MAIL_TYPE"|t}" class="form-control input-sm">
							<option value="All" {if isset($TYPE) && $TYPE eq 'all'} selected="selected"{/if}>
								{"LBL_ALL"|t:"OSSMailView"}
							</option>
							<option value="0" {if isset($TYPE) && $TYPE eq '0'} selected="selected"{/if}>
								{"LBL_OUTCOMING"|t:"OSSMailView"}
							</option>
							<option value="1" {if isset($TYPE) && $TYPE eq '1'} selected="selected"{/if}>
								{"LBL_INCOMING"|t:"OSSMailView"}
							</option>
							<option value="2" {if isset($TYPE) && $TYPE eq '2'} selected="selected"{/if}>
								{"LBL_INTERNAL"|t:"OSSMailView"}
							</option>
						</select>
					</div>
					<div class="col-xs-6">
						{if $MODULE_NAME == 'Accounts'}
							<select name="mailFilter" title="{"LBL_CHANGE_FILTER"|t:"OSSMailView"}" class="form-control input-sm">
								<option value="All">{"LBL_FILTER_ALL"|t:"OSSMailView"}</option>
								<option value="Accounts">{"LBL_FILTER_ACCOUNTS"|t:"OSSMailView"}</option>
								<option value="Contacts">{"LBL_FILTER_CONTACTS"|t:"OSSMailView"}</option>
							</select>
						{/if}
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
