{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
********************************************************************************/
-->*}
{strip}
	<!-- layouts/basic/modules/Base/DetailView.tpl -->
	{extends file="MainLayout.tpl"|@vtemplate_path}

	{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">

				{* DetailViewHeader.tpl is now self-contained - all divs it opens are closed within it *}
				{include file="DetailViewHeader.tpl"|vtemplate_path:$MODULE_NAME DETAIL_VIEW_CONTENT=$DETAIL_CONTENT}

			</div>
		</div> 
	{/block}
	<!--/layouts/basic/modules/Base/DetailView.tpl -->
{/strip}