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
<!-- layouts/basic/modules/Calendar/Import.tpl -->
<div id="importRecordsContainer" class='modelContainer modal fade'>
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button data-dismiss="modal" class="close" title="{"LBL_CLOSE"|t}">x</button>
				<h3 id="importRecordHeader" class="modal-title">{"LBL_IMPORT_RECORDS"|t:$MODULE}</h3>
			</div>
			<form method="POST" action="index.php" enctype="multipart/form-data" id="ical_import" name="ical_import">
				<input type="hidden" value="{$MODULE}" name="module">		
				<div name='importRecordsContent'>
					<input type="hidden" value="Import" name="view">
					<input type="hidden" value="importResult" name="mode">
					<div class="modal-body tabbable">
						<div class="tab-content massEditContent">
							<table class="massEditTable table table-bordered">
								<tr>
									<td class="fieldLabel alignMiddle">{"LBL_IMPORT_RECORDS"|t:$MODULE}</td>
									<td class="fieldValue">
										<input type="file" data-validation-engine="validate[required]" id="import_file" name="import_file" accept="{$SUPPORTED_FILE_TYPES_TEXT}" class="small">
										{'LBL_IMPORT_SUPPORTED_FILE_TYPES'|@vtranslate:'Import'}: {$SUPPORTED_FILE_TYPES_TEXT}
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
				<div class="modal-footer">
				<div class=" pull-right cancelLinkContainer">
					<button class="cancelLink btn btn-warning" type="reset" data-dismiss="modal">{"LBL_CANCEL"|t:$MODULE}</button>
				</div>
				<button class="btn btn-success" type="submit" name="saveButton"><strong>{"LBL_IMPORT"|t:$MODULE}</strong></button>
			</div>
			</form>
		</div>
	</div>
</div>
<!--/layouts/basic/modules/Calendar/Import.tpl -->
{/strip}
