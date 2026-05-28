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
<!-- layouts/basic/modules/Reports/EditFolder.tpl -->
	<div id="addFolderContainer" class="modelContainer modal fade" tabindex="-1">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<button data-dismiss="modal" class="close" title="{"LBL_CLOSE"|t}">x</button>
					<h3 class="modal-title">{"LBL_ADD_NEW_FOLDER"|t:$MODULE}</h3>
				</div>
				<form class="form-horizontal contentsBackground" id="addFolder" method="post" action="index.php">
					<input type="hidden" name="module" value="{$MODULE}" />
					<input type="hidden" name="action" value="Folder" />
					<input type="hidden" name="mode" value="save" />
					<input type="hidden" name="folderid" value="{$FOLDER_MODEL->getId()}" />
					<div class="modal-body">
						<div class="row verticalBottomSpacing">
							<span class="col-md-4"><span class="redColor">*</span>{"LBL_FOLDER_NAME"|t:$MODULE}</span>
							<span class="col-md-7 row"><input data-validation-engine='validate[required]' id="foldername" title="{"LBL_FOLDER_NAME"|t:$MODULE}" name="foldername" class="form-control" type="text" value="{$FOLDER_MODEL->getName()|t:$MODULE}"/></span>
						</div>
						<div class="row">
							<span class="col-md-4">{"LBL_FOLDER_DESCRIPTION"|t:$MODULE}</span>
							<span class="col-md-7 row">
								<textarea class="form-control" name="description" title="{"LBL_DESCRIPTION"|t:$MODULE}" placeholder="{"LBL_WRITE_YOUR_DESCRIPTION_HERE"|t:$MODULE}">{$FOLDER_MODEL->getDescription()|t:$MODULE}</textarea>
							</span>
						</div>
					</div>
					{include file='ModalFooter.tpl'|@vtemplate_path:$MODULE}
				</form>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Reports/EditFolder.tpl -->
{/strip}
