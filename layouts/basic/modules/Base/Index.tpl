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
<!-- layouts/basic/modules/Base/Index.tpl -->
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
	<div id="page">
		<div id="pjaxContainer" class="hide noprint"></div>
		<div class="bodyContents">
			<div class="mainContainer">
				<div class="contentsDiv col-md-12 marginLeftZero" id="centerPanel" style="min-height:550px;">
					<div class="mainContainer container">
						{* Index view content here *}
					</div> <!-- close mainContainer container -->
				</div> <!-- close contentsDiv -->
			</div> <!-- close mainContainer -->
		</div> <!-- close bodyContents -->
	</div> <!-- close page -->
{/block}
<!--/layouts/basic/modules/Base/Index.tpl -->
{/strip}