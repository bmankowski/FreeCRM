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
<!-- layouts/basic/modules/Settings/LayoutEditor/InactiveFieldModal.tpl -->
	<div class="modal inactiveFieldsModal fade" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h3 class="modal-title">{'LBL_INACTIVE_FIELDS'|t:$QUALIFIED_MODULE}</h3>
				</div>
				<form class="form-horizontal inactiveFieldsForm" method="POST">
					<div class="modal-body">
						<div class="row inActiveList"></div>
					</div>
					<div class="modal-footer">
						<div class=" pull-right cancelLinkContainer">
							<a class="cancelLink btn btn-warning" type="reset" data-dismiss="modal">{'LBL_CANCEL'|t:$QUALIFIED_MODULE}</a>
						</div>
						<button class="btn btn-success" type="submit" name="reactivateButton">
							<strong>{'LBL_REACTIVATE'|t:$QUALIFIED_MODULE}</strong>
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Settings/LayoutEditor/InactiveFieldModal.tpl -->
{/strip}