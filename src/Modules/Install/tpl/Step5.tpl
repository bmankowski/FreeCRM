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
{extends file='InstallLayout.tpl'}

{block name="content"}
<!-- install/tpl/Step5.tpl -->
	<form class="form-horizontal" name="step5" method="post" action="index.php?module=Install&view=Index">
		<input type="hidden" name="mode" value="Step6" />
		<input type="hidden" name="auth_key" value="{$AUTH_KEY}" />
		<input type="hidden" name="lang" value="{$LANG}" />
		<div class="row main-container">
			<div class="inner-container">
				<h4>{"LBL_CONFIRM_CONFIGURATION_SETTINGS"|t:"Install"}</h4>
				<hr>
				{if $DB_CONNECTION_INFO['flag'] neq true}
					<div class="offset2 row" id="errorMessage">
						<div class="col-md-12">
							<div class="alert alert-danger">
								{$DB_CONNECTION_INFO['error_msg']}<br/>
								{$DB_CONNECTION_INFO['error_msg_info']}
							</div>
						</div>
					</div>
				{/if}
				<div class="offset2 ">
					<table class="config-table input-table">
						<thead>
							<tr>
								<th colspan="2">{"LBL_DATABASE_INFORMATION"|t:"Install"}</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
									{"LBL_DATABASE_TYPE"|t:"Install"}
								</td>
								<td>
									{"MySQL"|t:"Install"}
								</td>
							</tr>
							<tr>
								<td>
									{"LBL_HOST_NAME"|t:"Install"}
								</td><td>
									{$INFORMATION['db_hostname']}
								</td>
							</tr>
							<tr>
								<td>
									{"LBL_HOST_PORT"|t:"Install"}
								</td><td>
									{$INFORMATION['db_port']}
								</td>
							</tr>
							<tr>
								<td>
									{"LBL_DB_NAME"|t:"Install"}
								</td><td>
									{$INFORMATION['db_name']}
								</td>
							</tr>
						</tbody>
					</table>
					<table class="config-table input-table">
						<thead>
							<tr>
								<th colspan="2">{"LBL_SYSTEM_INFORMATION"|t:"Install"}</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
									{"LBL_URL"|t:"Install"}
								</td>
								<td>
									<a href="#">{$SITE_URL}</a>
								</td>
							</tr>
							<tr>
								<td>
									{"LBL_CURRENCY"|t:"Install"}
								</td>
								<td>
									{$INFORMATION['currency_name']}
								</td>
							</tr>
						</tbody>
					</table>
					<table class="config-table input-table">
						<thead>
							<tr>
								<th colspan="2">{"LBL_ADMIN_USER_INFORMATION"|t:"Install"}</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
									{"LBL_USERNAME"|t:"Install"}
								</td>
								<td>
									{$INFORMATION['admin']}
								</td>
							</tr>
							<tr>
								<td>
									{"LBL_EMAIL"|t:"Install"}
								</td><td>
									{$INFORMATION['admin_email']}
								</td>
							</tr>
							<tr>
								<td>
									{"LBL_TIME_ZONE"|t:"Install"}
								</td>
								<td>
									{$INFORMATION['timezone']}
								</td>
							</tr>
							<tr>
								<td>
									{"LBL_DATE_FORMAT"|t:"Install"}
								</td>
								<td>
									{$INFORMATION['dateformat']}
								</td>
							</tr>
						</tbody>
					</table>
					<div class="row">
						<div class="col-md-12">
							<div class="button-container">
								<input type="button" class="btn btn-sm btn-default" value="{"LBL_BACK"|t:"Install"}" {if $DB_CONNECTION_INFO['flag'] eq true} disabled= "disabled"{else} onclick="window.history.back()"{/if} />
								{if $DB_CONNECTION_INFO['flag'] eq true}
									<input type="button" class="btn btn-sm btn-primary" value="{"LBL_NEXT"|t:"Install"}" name="step6"/>
								{/if}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
<!--/install/tpl/Step5.tpl -->
{/block}
{/strip}
