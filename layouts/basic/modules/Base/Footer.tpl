{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
{script_time assign=SCRIPT_TIME}
<!-- layouts/basic/modules/Base/Footer.tpl -->
<div class="clearfix"></div>
<input id="activityReminder" class="hide noprint" type="hidden" value="{$ACTIVITY_REMINDER}"/>
{if \App\Core\AppConfig::module('Users', 'IS_VISIBLE_USER_INFO_FOOTER')}
	<div class="infoUser">
		{$USER_MODEL->getName()}&nbsp;(
		{$USER_MODEL->get('email1')}&nbsp;
		{if !empty({$USER_MODEL->get('phone_crm_extension')})} 
			,&nbsp; {$USER_MODEL->get('phone_crm_extension')}
		{/if}
		)
	</div>
{/if}
<footer class="footerContainer navbar-default navbar-fixed-bottom noprint">
	<div class="vtFooter">
		<div class="pull-left">
			<a class="iconsInFooter" href="https://www.facebook.com/ITCPeopleinIT/">
				<span class="fab fa-facebook-square" title="Facebook"></span>
			</a>
			<a class="iconsInFooter" href="https://www.instagram.com/itcpeopleinit/">
				<span class="fab fa-instagram" title="Instagram"></span>
			</a>
			<a class="iconsInFooter" href="https://pl.linkedin.com/company/it-connect-people-in-it">
				<span class="fab fa-linkedin" title="LinkedIn"></span>
			</a>
			<a class="iconsInFooter" href="https://www.youtube.com/@ITCONNECT-PeopleinIT">
				<span class="fab fa-youtube" title="YouTube"></span>
			</a>
			<a class="iconsInFooter" href="https://www.tiktok.com/@itconnect2007">
				<span class="fab fa-tiktok" title="TikTok"></span>
			</a>
		</div>
		{if $USER_MODEL && \App\Modules\Users\Models\Privileges::isPermitted('HelpDesk', 'CreateView')}
			<div class="pull-right">
				<a class="reportIssueInFooter js-report-issue-btn" href="#" role="button" title="{"LBL_REPORT_ISSUE"|t:"ReportIssue"}">
					<span class="fas fa-exclamation-circle" title="{"LBL_REPORT_ISSUE"|t:"ReportIssue"}"></span>
					<span class="reportIssueInFooterLabel">{"LBL_REPORT_ISSUE"|t:"ReportIssue"}</span>
				</a>
			</div>
		{/if}
		{if $USER_MODEL->isAdminUser()}
			{assign var=FOOTVR value= '[ver. '|cat:$YETIFORCE_VERSION|cat:'] ['|cat:'WEBLOADTIME'|t|cat:': '|cat:$SCRIPT_TIME|cat:'s.]'}
			{assign var=FOOTVRM value= '['|cat:$SCRIPT_TIME|cat:'s.]'}
			{assign var=FOOTOSP value= '<u><a href="index.php?module=Home&view=Credits&parent=Settings">open source project</a></u>'}
			<p class="hidden-xs">{sprintf( 'LBL_FOOTER_CONTENT'|t , $FOOTVR ,$FOOTOSP)}</p>
			<p class="visible-xs-block">{sprintf( 'LBL_FOOTER_CONTENT'|t , $FOOTVRM ,$FOOTOSP)}</p>
		{else}
			<p>{sprintf( 'LBL_FOOTER_CONTENT'|t , '['|cat:'WEBLOADTIME'|t|cat:': '|cat:$SCRIPT_TIME|cat:'s.]', 'open source project' )}</p>
		{/if}
	</div>
</footer>
{if $USER_MODEL && \App\Modules\Users\Models\Privileges::isPermitted('HelpDesk', 'CreateView')}
	{include file='ReportIssueModal.tpl'|@vtemplate_path:'Base'}
{/if}
<div class="modal fade" id="freecrmDetails" tabindex="-1" role="dialog" aria-labelledby="freecrmDetails">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel">FreeCRM v{$YETIFORCE_VERSION}</h4>
			</div>
			<div class="modal-body">
				<p class="text-center"><img src="storage/Logo/itconnect_logo.png" title="FreeCRM" alt="FreeCRM" style="height: 80px;"/></p>
				<p>Copyright &copy; FreeCRM All rights reserved.</p>
				<p>The Program is provided AS IS, without warranty. Licensed under <a href="licenses/LicenseEN.txt" target="_blank"><strong>FreeCRM Public License 1.1</strong></a>.</p>
				<p>FreeCRM is based on <strong>VtigerCRM</strong> and <strong>YetiForce</strong> open source projects.<br><br></p>
				<p><span class="label label-default">License:</span> <a href="licenses/LicenseEN.txt" target="_blank"><strong>FreeCRM Public License 1.1</strong></a></p>
				<p><span class="label label-primary">WWW:</span> <a href="https://www.itconnect.pl" target="_blank"><strong>https://www.itconnect.pl</strong></a></p>
				<p class="text-center">
					<a class="yetiforceDetailsLink" href="https://www.facebook.com/ITCPeopleinIT/">
						<span class="fab fa-facebook-square" title="Facebook"></span>
					</a>
					<a class="yetiforceDetailsLink" href="https://www.instagram.com/itcpeopleinit/">
						<span class="fab fa-instagram" title="Instagram"></span>
					</a>
					<a class="yetiforceDetailsLink" href="https://pl.linkedin.com/company/it-connect-people-in-it">
						<span class="fab fa-linkedin" title="LinkedIn"></span>
					</a>
					<a class="yetiforceDetailsLink" href="https://www.youtube.com/@ITCONNECT-PeopleinIT">
						<span class="fab fa-youtube" title="YouTube"></span>
					</a>
					<a class="yetiforceDetailsLink" href="https://www.tiktok.com/@itconnect2007">
						<span class="fab fa-tiktok" title="TikTok"></span>
					</a>
				</p>
			</div>
			<div class="modal-footer">
				{if isset($MODULE) && $MODULE}
					{assign var=CANCEL_MODULE value=$MODULE}
				{elseif isset($MODULE_NAME) && $MODULE_NAME}
					{assign var=CANCEL_MODULE value=$MODULE_NAME}
				{else}
					{assign var=CANCEL_MODULE value='Base'}
				{/if}
				<button class="btn btn-warning" type="reset" data-dismiss="modal"><strong>{"LBL_CANCEL"|t:$CANCEL_MODULE}</strong></button>
			</div>
		</div>
	</div>
</div>
{* javascript files *}
{include file='JSResources.tpl'|@vtemplate_path}
{if \App\Debug\Debugger::isDebugBar()}
	{\App\Debug\Debugger::getDebugBar()->getJavascriptRenderer()->render()}
{/if}
</body>
</html>
<!--/layouts/basic/modules/Base/Footer.tpl -->
{/strip}
