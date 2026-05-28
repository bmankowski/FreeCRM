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
<!-- install/tpl/Step4.tpl -->
	<form class="form-horizontal" name="step4" method="post" action="index.php?module=Install&view=Index">
		<input type="hidden" name="mode" value="Step5" />
		<input type="hidden" name="lang" value="{$LANG}" />
		<div class="row main-container">
			<div class="inner-container">
				<h4>{"LBL_SYSTEM_CONFIGURATION"|t:"Install"} </h4>
				<hr>
				<div class="row hide" id="errorMessage"></div>
				<div class="row">
					<div class="col-md-6">
						<table class="config-table input-table">
							<thead>
								<tr><th colspan="2">{"LBL_DATABASE_INFORMATION"|t:"Install"}</th></tr>
							</thead>
							<tbody>
								<tr>
									<td>{"LBL_DATABASE_TYPE"|t:"Install"}<span class="no">*</span></td>
									<td>{"MySQL"|t:"Install"}<input type="hidden" value="mysql" name="db_type"></td>
								</tr>
								<tr>
									<td>{"LBL_HOST_NAME"|t:"Install"}<span class="no">*</span></td>
									<td><input type="text" class="form-control" value="{$DB_HOSTNAME}" name="db_hostname"></td>
								</tr>
								<tr>
									<td>{"LBL_HOST_PORT"|t:"Install"}<span class="no">*</span></td>
									<td><input type="text" class="form-control" value="3306" name="db_port"></td>
								</tr>
								<tr>
									<td>{"LBL_USERNAME"|t:"Install"}<span class="no">*</span></td>
									<td><input type="text" class="form-control" value="{$DB_USERNAME}" name="db_username"></td>
								</tr>
								<tr>
									<td>{"LBL_PASSWORD"|t:"Install"}</td>
									<td><input type="password" class="form-control" value="{$DB_PASSWORD}" name="db_password"></td>
								</tr>
								<tr>
									<td>{"LBL_DB_NAME"|t:"Install"}<span class="no">*</span></td>
									<td><input type="text" class="form-control" value="{$DB_NAME}" name="db_name"></td>
								</tr>
								<tr>
									<td colspan="2"><input type="checkbox" name="create_db"/><div class="chkbox"></div><label for="checkbox-1">{"LBL_CREATE_NEW_DB"|t:"Install"}</label></td>
								</tr>
								<tr class="hide" id="root_user"><td>{"LBL_ROOT_USERNAME"|t:"Install"}<span class="no">*</span></td>
									<td><input type="text" class="form-control" value="" name="db_root_username"></td>
								</tr>
								<tr class="hide" id="root_password"><td>{"LBL_ROOT_PASSWORD"|t:"Install"}</td>
									<td><input type="password" class="form-control" value="" name="db_root_password"></td>
								</tr>
								<!--tr><td colspan="2"><input type="checkbox" checked name="populate"/><div class="chkbox"></div><label for="checkbox-1"> Populate database with demo data</label></td-->
								</tr>
							</tbody>
						</table>
					</div>
					<div class="col-md-6">
						<table class="config-table input-table">
							<thead>
								<tr><th colspan="2">{"LBL_SYSTEM_INFORMATION"|t:"Install"}</th></tr>
							</thead>
							<tbody>
								<tr><td>{"LBL_CURRENCIES"|t:"Install"}<span class="no">*</span></td>
									<td><select name="currency_name" class="select2" style="width:220px;">
											{foreach key=CURRENCY_NAME item=CURRENCY_INFO from=$CURRENCIES}
												<option value="{$CURRENCY_NAME}" {if $CURRENCY_NAME eq 'Euro'} selected {/if}>{$CURRENCY_NAME} ({$CURRENCY_INFO.1})</option>
											{/foreach}
										</select>
									</td>
								</tr>
							</tbody>
						</table>
						<table class="config-table input-table">
							<thead><tr><th colspan="2">{"LBL_ADMIN_INFORMATION"|t:"Install"}</th></tr>
							</thead>
							<tbody>
								<tr><td>{"LBL_USERNAME"|t:"Install"}</td>
									<td>admin<input type="hidden" class="form-control" name="{$ADMIN_NAME}" value="admin" /></td>
								</tr>
								<tr><td>{"LBL_PASSWORD"|t:"Install"}<span class="no">*</span></td>
									<td><input type="password" class="form-control" value="{$ADMIN_PASSWORD}" name="password" /></td>
								</tr>
								<tr><td>{"LBL_RETYPE_PASSWORD"|t:"Install"} <span class="no">*</span></td>
									<td><input type="password" class="form-control" value="{$ADMIN_PASSWORD}" name="retype_password" />
										<span id="passwordError" class="no"></span></td>
								</tr>
								<tr><td>{"First Name"|t:"Install"}</td>
									<td><input type="text" value="" class="form-control" name="firstname" /></td>
								</tr>
								<tr><td>
										{"Last Name"|t:"Install"} <span class="no">*</span>
									</td><td>
										<input type="text" class="form-control" value="{$ADMIN_LASTNAME}" name="lastname" />
									</td>
								</tr>
								<tr>
									<td>
										{"LBL_EMAIL"|t:"Install"} <span class="no">*</span>
									</td><td>
										<input type="text" class="form-control" value="{$ADMIN_EMAIL}" name="admin_email">
									</td>
								</tr>
								<tr>
									<td>
										{"LBL_DATE_FORMAT"|t:"Install"} <span class="no">*</span>
									</td>
									<td><select class="select2 form-control" style="width:220px;" name="dateformat">
											<option>yyyy-mm-dd</option>
											<option>dd-mm-yyyy</option>
											<option>mm-dd-yyyy</option>
											<option>yyyy.mm.dd</option>
											<option>dd.mm.yyyy</option>
											<option>mm.dd.yyyy</option>
											<option>yyyy/mm/dd</option>
											<option>dd/mm/yyyy</option>
											<option>mm/dd/yyyy</option>
										</select>
									</td>
								</tr>
								<tr>
									<td>
										{"LBL_TIME_ZONE"|t:"Install"} <span class="no">*</span>
									</td>
									<td><select class="select2 form-control" name="timezone">
											{foreach item=TIMEZONE from=$TIMEZONES}
												<option value="{$TIMEZONE}" {if $TIMEZONE eq 'Europe/London'}selected{/if}>{$TIMEZONE|t:"Users"}</option>
											{/foreach}
										</select>
									</td>
								</tr>
							</tbody>
						</table>
						<div class="button-container">
							<a class="btn btn-sm btn-default" href="index.php?module=Install&view=Index&mode=Step3">{"LBL_BACK"|t:"Install"}</a>
							<button type="submit" class="btn btn-sm btn-primary">{"LBL_NEXT"|t:"Install"}</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
<!--/install/tpl/Step4.tpl -->
{/block}
{/strip}
