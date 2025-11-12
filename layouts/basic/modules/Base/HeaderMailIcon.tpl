{strip}
	{*<!-- {[The file is published on the basis of FreeCRM Public License that can be found in the following directory: licenses/FreeCRMLicense.html]} --!>*}
<!-- layouts/basic/modules/Base/HeaderMailIcon.tpl -->
	{if !\App\Modules\Settings\ModuleManager\Models\Library::checkLibrary('roundcube')}
		<div class="pull-right">
			{assign var=CONFIG value=App\Modules\Settings\Mail\Models\Config::getConfig('mailIcon')}
			{if $CONFIG['showMailIcon']=='true' && App\Privilege::isPermitted('OSSMail')}
				{assign var=AUTOLOGINUSERS value=OSSMail_Autologin_Model::getAutologinUsers()}
				{if count($AUTOLOGINUSERS) > 0}
					{assign var=MAIN_MAIL value=OSSMail_Module_Model::getDefaultMailAccount($AUTOLOGINUSERS)}
					<div class="headerLinksMails" id="OSSMailBoxInfo" {if $CONFIG['showNumberUnreadEmails']=='true'}data-numberunreademails="true" data-interval="{$CONFIG['timeCheckingMail']}"{/if}>
						<div class="btn-group">
							{if count($AUTOLOGINUSERS) eq 1}
								<a type="button" class="btn btn-sm btn-default" title="{$MAIN_MAIL.username}" href="index.php?module=OSSMail&view=index">
									<div class="hidden-xs">
										{$ITEM.username}
										<span class="mail_user_name">{$MAIN_MAIL.username}</span>
										<span data-id="{$MAIN_MAIL.rcuser_id}" class="noMails"></span>
									</div>
									<div class="visible-xs-block">
										<span class="glyphicon glyphicon-list-alt"></span>
									</div>
								</a>
							{elseif $CONFIG['showMailAccounts']=='true'}
								<select class="form-control" title="{"LBL_SEARCH_MODULE"|t:$MODULE_NAME}">
									{foreach key=KEY item=ITEM from=$AUTOLOGINUSERS}
										<option value="{$KEY}" {if $ITEM.active}selected{/if} data-id="{$KEY}" data-nomail="" class="noMails">
											{$ITEM.username}
										</option>
									{/foreach}
								</select>
							{/if}
						</div>
					</div>
				{/if}
			{/if}
		</div>
	{/if}
<!--/layouts/basic/modules/Base/HeaderMailIcon.tpl -->
{/strip}

