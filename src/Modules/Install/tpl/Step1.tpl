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
<!-- install/tpl/Step1.tpl -->
<div class="row main-container">
		<div class="inner-container">
			<form class="form-horizontal" name="step1" method="post" action="Install.php">
				<div class="row">
					<div class="col-md-9">
						<h4>{"LBL_WELCOME"|t:"Install"}</h4>
					</div>
					<div class="col-md-3">
						<select name="lang" class="chzn-select" style="width: 250px;">
							{foreach key=key item=item from=$LANGUAGES}
								<option value="{$key}" {if $LANG eq $key}selected{/if}>{$item}</option>
							{/foreach}
						</select>
					</div>
				</div>
				<hr>
				<div class="pull-right">
					<a class="helpBtn" href="https://yetiforce.com/en/implementer/installation-updates.html" target="_blank">
						<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
					</a>
				</div>
				<input type="hidden" name="mode" value="Step2" />
				<div class="col-md-4 welcome-image">
					<img src="../layouts/basic/skins/images/wizard_screen.png" alt="Vtiger Logo"/>
				</div>
				<div class="col-md-8">
					<div class="welcome-div">
						<h3>{"LBL_WELCOME_TO_VTIGER6_SETUP_WIZARD"|t:"Install"}</h3>
						<p>{"LBL_VTIGER6_SETUP_WIZARD_DESCRIPTION"|t:"Install"}</p>
					</div>
				</div>
				<div class="row">
					<div class="button-container">
						<a href="#" class="btn btn-sm btn-primary bt_install">
							{"LBL_INSTALL_BUTTON"|t:"Install"}
						</a>
						{if $IS_MIGRATE}
							<a style="" href="#" class="btn btn-sm btn-primary bt_migrate">
								{"LBL_MIGRATION"|t:"Install"}
							</a>
						{/if}
					</div>
				</div>
			</form>
		</div>
	</div>
<!--/install/tpl/Step1.tpl -->
{/block}
{/strip}
