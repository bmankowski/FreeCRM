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
<!-- layouts/basic/modules/Base/FindDuplicates.tpl -->
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
	<div id="page">
		<div id="pjaxContainer" class="hide noprint"></div>
		<div class="bodyContents">
			<div class="mainContainer">
				<div class="contentsDiv">
					
					{* Find Duplicates Header and Search Form *}
					{include file="FindDuplicateHeader.tpl"|vtemplate_path:$MODULE}
					
					{* Duplicate Records Results *}
					{include file="FindDuplicateContents.tpl"|vtemplate_path:$MODULE}
					
				</div> <!-- close contentsDiv -->
			</div> <!-- close mainContainer -->
		</div> <!-- close bodyContents -->
	</div> <!-- close page -->
{/block}
<!--/layouts/basic/modules/Base/FindDuplicates.tpl -->
{/strip}

