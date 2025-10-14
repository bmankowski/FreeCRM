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
<!-- install/tpl/Step3.tpl -->
	<form class="form-horizontal" name="step3" method="post" action="Install.php">
		<input type="hidden" name="mode" value="Step4" />
		<input type="hidden" name="lang" value="{$LANG}" />

		<div class="row main-container">
			<div class="inner-container">
				<div class="pull-right">
					<a class="helpBtn" href="https://yetiforce.com/en/implementer/installation-updates/103-web-server-requirements.html" target="_blank">
						<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
					</a>
				</div>
				<h4>{"LBL_INSTALL_PREREQUISITES"|t:"Install"}</h4>
				<hr>
				<div>
					<div class="offset2">
						<div class="pull-right">
							<div class="button-container">
								<a href ="#">
									<input type="button" class="btn btn-default" value="{"LBL_RECHECK"|t:"Install"}" id='recheck'/>
								</a>
							</div>
						</div>
					</div>
					<div class="clearfix"></div>
					<div class="offset2">
						<div>
							<table class="config-table table">
								<thead>
									<tr>
										<th><label>{"LBL_LIBRARY"|t:"Settings::ConfReport"}</label></th>
										<th><label>{"LBL_INSTALLED"|t:"Settings::ConfReport"}</label></th>
										<th><label>{"LBL_MANDATORY"|t:"Settings::ConfReport"}</label></th>
									</tr>
								</thead>
								<tbody>
									{foreach from=Settings_ConfReport_Module_Model::getConfigurationLibrary() key=key item=item}
										<tr {if $item.status == 'LBL_NO'}class="danger"{/if}>
											<td>{vtranslate($key, 'Settings::ConfReport')}</td>
											<td>{vtranslate($item.status, 'Settings::ConfReport')}</td>
											<td>
												{if $item.mandatory}
													{"LBL_MANDATORY"|t:"Settings::ConfReport"}
												{else}
													{"LBL_OPTIONAL"|t:"Settings::ConfReport"}
												{/if}
											</td>
										</tr>
									{/foreach}
								</tbody>
							</table>
							<br>
							<table class="config-table table">
								<thead>
									<tr>
										<th>{"LBL_PHP_RECOMMENDED_SETTINGS"|t:"Install"}</th>
										<th>{"LBL_REQUIRED_VALUE"|t:"Install"}</th>
										<th>{"LBL_PRESENT_VALUE"|t:"Install"}</th>
									</tr>
								</thead>
								<tbody>
									{foreach from=Settings_ConfReport_Module_Model::getConfigurationValue(true) key=key item=item}
										{if $item.status}
											<tr class="danger">
												<td><label>{$key}</label></td>
												<td><label>{$item.prefer|t:$MODULE}</label></td>
												<td><label>{$item.current|t:$MODULE}</label></td>
											</tr>
										{/if}
									{/foreach}
								</tbody>
							</table>
							{if $FAILED_FILE_PERMISSIONS}
								<table class="config-table table">
									<thead>
										<tr class="blockHeader">
											<th colspan="1" class="mediumWidthType">
												<span>{"LBL_READ_WRITE_ACCESS"|t:"Install"}</span>
											</th>
											<th colspan="1" class="mediumWidthType">
												<span>{"LBL_PATH"|t:"Settings::ConfReport"}</span>
											</th> 							
											<th colspan="1" class="mediumWidthType">
												<span>{"LBL_PERMISSION"|t:"Settings::ConfReport"}</span>
											</th>  				
										</tr>
									</thead>
									<tbody>
										{foreach from=$FAILED_FILE_PERMISSIONS key=key item=item}			
											<tr {if $item.permission eq 'FailedPermission'}class="danger"{/if}>
												<td width="23%"><label class="marginRight5px">{vtranslate($key, 'Settings::ConfReport')}</label></td>
												<td width="23%"><label class="marginRight5px">{vtranslate($item.path, 'Settings::ConfReport')}</label></td>
												<td width="23%"><label class="marginRight5px">
														{if $item.permission eq 'FailedPermission'}
															{"LBL_FAILED_PERMISSION"|t:"Settings::ConfReport"}
														{else}
															{"LBL_TRUE_PERMISSION"|t:"Settings::ConfReport"}
														{/if}
													</label></td>			
											</tr>
										{/foreach}
									</tbody>
								</table>
							{/if}
						</div>
					</div>
				</div>
				<div class="row">
					<div class="button-container">
						<a class="btn btn-sm btn-default" href="Install.php" >{"LBL_BACK"|t:"Install"}</a>
						<input type="button" class="btn btn-sm btn-primary" value="{"LBL_NEXT"|t:"Install"}" name="step4"/>
					</div>
				</div>
			</div>
		</div>
	</form>
<!--/install/tpl/Step3.tpl -->
{/strip}
