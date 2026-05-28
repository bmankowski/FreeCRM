{*<!--
/************************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Migration/MigrationStep2.tpl -->
	{include file="Header.tpl"|vtemplate_path:$MODULE}
	<div class=" page-container">
		<div class="row">
			<div class="col-md-6">
				<div class="logo">
					<img src="{vimage_path('vt1.png')}" alt="Vtiger Logo"/>
				</div>
			</div>
			<div class="col-md-6">
				<div class="head pull-right">
					<h3> {"LBL_MIGRATION_WIZARD"|t:$MODULE}</h3>
				</div>
			</div>
		</div>
		<div class="row main-container">
			<div class="col-md-12 inner-container">
					<div class="row">
						<div class="col-md-10">
							<h4> {"LBL_MIGRATION_COMPLETED"|t:$MODULE} </h4> 
						</div>
					</div>
					<hr>
					<div class="row">
						<div class="col-md-4 welcome-image">
							<img src="{vimage_path('migration_screen.png')}" alt="Vtiger Logo"/>
						</div>
						<div class="col-md-1"></div>
						<div class="col-md-6">
							<br><br>
							<h5>{"LBL_MIGRATION_COMPLETED_SUCCESSFULLY"|t:$MODULE}  </h5><br><br>
								{"LBL_RELEASE_NOTES"|t:$MODULE}<br>
								{"LBL_CRM_DOCUMENTATION"|t:$MODULE}<br>
								{"LBL_TALK_TO_US_AT_FORUMS"|t:$MODULE}<br>
								{"LBL_DISCUSS_WITH_US_AT_BLOGS"|t:$MODULE}<br><br>
								Connect with us &nbsp;&nbsp;
								<a href="https://www.facebook.com/vtiger" target="_blank"><img src="{vimage_path('facebook.png')}"></a> 
	                            &nbsp;&nbsp;<a href="https://twitter.com/vtigercrm" target="_blank"><img src="{vimage_path('twitter.png')}"></a> 
	                            &nbsp;&nbsp;<a href="//www.vtiger.com/products/crm/privacy_policy.html" target="_blank"><img src="{vimage_path('linkedin.png')}"></a> 
								<br><br>
							<div class="button-container">
								<input type="button" onclick="window.location.href='index.php'" class="btn btn-lg btn-primary" value="Finish"/>
							</div>
					</div>
				</div>
			</div>
		</div>
		</div>
	</div>
