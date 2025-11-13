{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
********************************************************************************/
-->*}
{strip}
<!DOCTYPE html>
<html lang="{$HTMLLANG}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>FreeCRM - Login</title>
	{foreach key=index item=cssModel from=$STYLES}
		<link rel="{$cssModel->getRel()}" href="{vresource_url($cssModel->getHref())}" />
	{/foreach}
</head>
<body>
<!-- layouts/basic/modules/Users/Login.Default.tpl -->
	{assign var="COMPANY_DETAILS" value=App\Company::getInstanceById()}
	{assign var="MODULE" value='Users'}
	<div class="login-wrapper">
		<div id="login-area" class="login-area">
			<div class="login-card">
				<div class="login-logo">
					<img title="{$COMPANY_DETAILS->get('name')}" height="{$COMPANY_DETAILS->get('logo_login_height')}px" src="{$COMPANY_DETAILS->getLogo('logo_login')->get('imageUrl')}" alt="{$COMPANY_DETAILS->get('name')}">
					{if $CURRENT_VERSION}
						<p class="login-meta text-muted">Powered by FreeCRM&nbsp;{$CURRENT_VERSION}</p>
					{/if}
				</div>
				<div class="login-section" id="loginDiv">
					<form class="login-form" action="index.php?module=Users&action=Login" method="POST" {if !AppConfig::security('LOGIN_PAGE_REMEMBER_CREDENTIALS')}autocomplete="off"{/if}>
						<div class="login-fields">
							<div class="form-group has-feedback">
								<label for="username" class="sr-only">{"LBL_USER"|t:$MODULE}</label>
								<input name="username" type="text" id="username" class="form-control input-lg" {if $SYSTEM_MODE == 'demo'}value="demo"{/if} placeholder="{"LBL_USER"|t:$MODULE}" required {if !AppConfig::security('LOGIN_PAGE_REMEMBER_CREDENTIALS')}autocomplete="off"{/if} autofocus>
								<span class="adminIcon-user form-control-feedback" aria-hidden="true"></span>
							</div>
							<div class="form-group has-feedback">
								<label for="password" class="sr-only">{"Password"|t:$MODULE}</label>
								<input name="password" type="password" class="form-control input-lg" title="{"Password"|t:$MODULE}" id="password" {if $SYSTEM_MODE == 'demo'}value="demo"{/if} {if !AppConfig::security('LOGIN_PAGE_REMEMBER_CREDENTIALS')}autocomplete="off"{/if} placeholder="{"Password"|t:$MODULE}">
								<span class="userIcon-OSSPasswords form-control-feedback" aria-hidden="true"></span>
							</div>
							{if $LANGUAGE_SELECTION}
								{assign var=DEFAULT_LANGUAGE value=AppConfig::main('default_language')}
								<div class="form-group">
									<select class="input-lg form-control" title="{"LBL_CHOOSE_LANGUAGE"|t:$MODULE}" name="loginLanguage">
										{foreach item=VALUE key=KEY from=Vtiger_Language_Handler::getAllLanguages()}
											<option {if $KEY eq $DEFAULT_LANGUAGE} selected {/if}  value="{\App\Modules\Base\Helpers\Util::toSafeHTML($KEY)}">{$VALUE}</option>
										{/foreach}
									</select>
								</div>
							{/if}
							{if $LAYOUT_SELECTION}
								<div class="form-group">
									<select class="input-lg form-control" title="{"LBL_SELECT_LAYOUT"|t:$MODULE}" name="layout">
										{foreach item=VALUE key=KEY from=\App\Runtime\Yeti_Layout::getAllLayouts()}
											<option value="{\App\Modules\Base\Helpers\Util::toSafeHTML($KEY)}">{$VALUE}</option>
										{/foreach}
									</select>
								</div>
							{/if}
						</div>
						<button class="btn btn-primary btn-block login-submit" type="submit" title="{"LBL_SIGN_IN"|t:$MODULE_NAME}">
							{"LBL_SIGN_IN"|t:$MODULE_NAME}
						</button>
					</form>
					{if AppConfig::security('RESET_LOGIN_PASSWORD')}
						<div class="login-footer">
							<a href="#" id="forgotpass" class="login-link">{"ForgotPassword"|t:$MODULE}?</a>
						</div>
					{/if}
					<div class="login-alerts">
						{if $ERROR eq 1}
							<div class="alert alert-warning">
								<p>{'Invalid username or password.'|t:$MODULE}</p>
							</div>
						{/if}
						{if $ERROR eq 2}
							<div class="alert alert-warning">
								<p>{'Too many failed login attempts.'|t:$MODULE}</p>
							</div>
						{/if}
						{if $FPERROR}
							<div class="alert alert-warning">
								<p>{'Invalid Username or Email address.'|t:$MODULE}</p>
							</div>
						{/if}
						{if $STATUS}
							<div class="alert alert-success">
								<p>{"LBL_MAIL_WAITING_TO_SENT"|t:$MODULE}</p>
							</div>
						{/if}
						{if $STATUS_ERROR}
							<div class="alert alert-warning">
								<p>{'Outgoing mail server was not configured.'|t:$MODULE}</p>
							</div>
						{/if}
					</div>
				</div>
				{if AppConfig::security('RESET_LOGIN_PASSWORD')}
					<div class="login-section hide" id="forgotPasswordDiv">
						<form class="login-form" action="modules/Users/actions/ForgotPassword.php" method="POST">
							<div class="login-fields">
								<div class="form-group has-feedback">
									<label for="username" class="sr-only">{"LBL_USER"|t:$MODULE}</label>
									<input type="text" class="form-control input-lg" title="{"LBL_USER"|t:$MODULE}" id="username" name="user_name" placeholder="{"LBL_USER"|t:$MODULE}">
									<span class="adminIcon-user form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group has-feedback">
									<label for="emailId" class="sr-only">{"LBL_EMAIL"|t:$MODULE}</label>
									<input type="text" class="form-control input-lg" autocomplete="off" title="{"LBL_EMAIL"|t:$MODULE}" id="emailId" name="emailId" placeholder="Email">
									<span class="glyphicon glyphicon-envelope form-control-feedback" aria-hidden="true"></span>
								</div>
							</div>
							<button type="submit" id="retrievePassword" class="btn btn-primary btn-block login-submit" title="Retrieve Password">
								{'LBL_SEND'|t:$MODULE}
							</button>
						</form>
						<div class="login-footer">
							<a href="#" id="backButton" class="login-link">{"LBL_TO_CRM"|t:$MODULE}</a>
						</div>
					</div>
				{/if}
			</div>
		</div>
	</div>
	<script>
		jQuery(document).ready(function () {
			jQuery("button.close").click(function () {
				jQuery(".visible-phone").css('visibility', 'hidden');
			});
			jQuery("a#forgotpass").click(function () {
				jQuery("#loginDiv").hide();
				jQuery("#forgotPasswordDiv").removeClass('hide');
				jQuery("#forgotPasswordDiv").show();
			});

			jQuery("a#backButton").click(function () {
				jQuery("#loginDiv").removeClass('hide');
				jQuery("#loginDiv").show();
				jQuery("#forgotPasswordDiv").hide();
			});

			jQuery("input[name='retrievePassword']").click(function () {
				var username = jQuery('#user_name').val();
				var email = jQuery('#emailId').val();
				var email1 = email.replace(/^\s+/, '').replace(/\s+$/, '');
				var emailFilter = /^[^@]+@[^@.]+\.[^@]*\w\w$/;
				var illegalChars = /[\(\)\<\>\,\;\:\\\"\[\]]/;

				if (username == '') {
					alert('Please enter valid username');
					return false;
				} else if (!emailFilter.test(email1) || email == '') {
					alert('Please enater valid email address');
					return false;
				} else if (email.match(illegalChars)) {
					alert("The email address contains illegal characters.");
					return false;
				} else {
					return true;
				}
			});
		});
	</script>
</body>
</html>
<!--/layouts/basic/modules/Users/Login.Default.tpl -->
{/strip}
