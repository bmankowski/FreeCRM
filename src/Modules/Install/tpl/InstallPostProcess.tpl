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
<!-- install/tpl/InstallPostProcess.tpl -->
	<br>
	<center>
		<footer class="noprint">
			<div class="vtFooter">
				<p>
					{"POWEREDBY"|t} {$YETIFORCE_VERSION} &nbsp;
					&copy; 2004 - {$CURRENT_YEAR}&nbsp&nbsp;
					<a href="http://yetiforce.com" target="_blank">yetiforce.com</a>
					&nbsp;|&nbsp;
					<a href="#" onclick="window.open('../licenses/License.html', 'License', 'height=615,width=875').moveTo(110, 120)">{"LBL_READ_LICENSE"|t}</a>
				</p>
			</div>
		</footer>
	</center>
	{include file='JSResources.tpl'}
</div>
<!--/install/tpl/InstallPostProcess.tpl -->
{/strip}
