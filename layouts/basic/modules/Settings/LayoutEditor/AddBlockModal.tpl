{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
* Contributor(s): YetiForce.com
********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Settings/LayoutEditor/AddBlockModal.tpl -->
	<div class="modal addBlockModal fade">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header contentsBackground">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h3>{'LBL_ADD_CUSTOM_BLOCK'|t:$QUALIFIED_MODULE}</h3>
				</div>
				<form class="form-horizontal addCustomBlockForm">
					<div class="modal-body">
						<div class="form-group">
							<div class="col-md-3 control-label">
								<span class="redColor">*</span>
								<span>{'LBL_BLOCK_NAME'|t:$QUALIFIED_MODULE}</span>
							</div>
							<div class="col-md-8 controls">
								<input type="text" name="label" class="form-control" data-validation-engine="validate[required]" />
							</div>
						</div>
						<div class="form-group">
							<div class="col-md-3 control-label">
								{'LBL_ADD_AFTER'|t:$QUALIFIED_MODULE}
							</div>
							<div class="col-md-8 controls">
								<select class="form-control" name="beforeBlockId">
									{foreach key=BLOCK_ID item=BLOCK_LABEL from=$ALL_BLOCK_LABELS}
										<option value="{$BLOCK_ID}" data-label="{$BLOCK_LABEL}">{$BLOCK_LABEL|t:$SELECTED_MODULE_NAME}</option>
									{/foreach}
								</select>
							</div>
						</div>
					</div>
					{include file='ModalFooter.tpl'|@vtemplate_path:'Vtiger'}
				</form>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Settings/LayoutEditor/AddBlockModal.tpl -->
{/strip}