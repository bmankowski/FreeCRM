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
<!-- layouts/basic/modules/Users/EditView.tpl -->
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
	<div class="mainContainer">
		<div class="contentsDiv col-md-12 marginLeftZero" id="centerPanel" style="min-height:550px;">
			{include file='EditViewContent.tpl'|@vtemplate_path:$MODULE}
		</div> <!-- close contentsDiv -->
	</div> <!-- close mainContainer -->
{/block}
<!--/layouts/basic/modules/Users/EditView.tpl -->
{/strip}
