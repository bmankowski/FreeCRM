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
<!-- layouts/basic/modules/Settings/Groups/DeleteTransferForm.tpl -->
<div class="modelContainer modal fade" tabinedx="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button class="close vtButton" data-dismiss="modal">×</button>
				<h3 class="modal-title">{"LBL_DELETE_RECORD"|t:$QUALIFIED_MODULE} {vtranslate('SINGLE_'|cat:$MODULE, $QUALIFIED_MODULE)} - {$RECORD_MODEL->getName()}</h3>
			</div>
			<form class="form-horizontal" id="DeleteModal" name="AddComment" method="post" action="index.php">
				<input type="hidden" name="module" value="{$MODULE}" />
				<input type="hidden" name="parent" value="Settings" />
				<input type="hidden" name="action" value="DeleteAjax" />
				<input type="hidden" name="record" id="record" value="{$RECORD_MODEL->getId()}" />
				<div class="modal-body tabbable">
					<div class="form-group ">
						<div class="col-md-4">
							<strong>
								{"LBL_TRANSFORM_OWNERSHIP"|t:$QUALIFIED_MODULE} {"LBL_TO"|t:$QUALIFIED_MODULE}<span class="redColor">*</span>
							</strong>
						</div>
						<div class="controls col-md-8">
							<select id="transfer_record" name="transfer_record" class="chzn-select form-control">
								<optgroup label="{"LBL_USERS"|t:$QUALIFIED_MODULE}">
									{foreach from=$ALL_USERS key=USER_ID item=USER_MODEL}
										<option value="{$USER_ID}">{$USER_MODEL->getName()}</option>
									{/foreach}
								</optgroup>
								<optgroup label="{"LBL_GROUPS"|t:$QUALIFIED_MODULE}">
									{foreach from=$ALL_GROUPS key=GROUP_ID item=GROUP_MODEL}
										{if $RECORD_MODEL->getId() != $GROUP_ID }
											<option value="{$GROUP_ID}">{$GROUP_MODEL->getName()}</option>
										{/if}
									{/foreach}
								</optgroup>
							</select>
						</div>
					</div>
				</div>

				<div class="modal-footer">
					<div class=" pull-right cancelLinkContainer"><a class="cancelLink btn btn-warning" type="reset" data-dismiss="modal">{"LBL_CANCEL"|t:$MODULE}</a></div>
					<button class="btn btn-success" type="submit">{"LBL_SAVE"|t:$MODULE}</button>
				</div>
			</form>
		</div>
	</div>
</div>
<!--/layouts/basic/modules/Settings/Groups/DeleteTransferForm.tpl -->
{/strip}
