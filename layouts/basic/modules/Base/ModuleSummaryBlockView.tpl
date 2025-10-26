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
<!-- layouts/basic/modules/Base/ModuleSummaryBlockView.tpl -->
	<div class="recordDetails">
		<div>
			<h4> {"LBL_RECORD_SUMMARY"|t:$MODULE_NAME}	</h4>
			<hr>
		</div>
		{include file='SummaryViewContents.tpl'|@vtemplate_path}
	</div>
<!--/layouts/basic/modules/Base/ModuleSummaryBlockView.tpl -->
{/strip}
