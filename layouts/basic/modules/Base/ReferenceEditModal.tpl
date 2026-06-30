{strip}
<!-- layouts/basic/modules/Base/ReferenceEditModal.tpl -->
<div class="modal-header">
	<h3 class="modal-title">{$FIELD_MODEL->get('label')|t:$MODULE_NAME}</h3>
</div>
<div class="modal-body">
	<form id="referenceEditForm" data-record="{$RECORD->getId()}" data-module="{$MODULE_NAME}" data-field="{$FIELD_MODEL->getName()}">
		<div class="fieldValue">
			{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE_NAME) FIELD_MODEL=$FIELD_MODEL USER_MODEL=$USER_MODEL MODULE=$MODULE_NAME VIEW='Edit'}
		</div>
	</form>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-success js-reference-modal-save">{"LBL_SAVE"|t:"Vtiger"}</button>
	<button type="button" class="btn btn-warning" data-dismiss="modal">{"LBL_CANCEL"|t:"Vtiger"}</button>
</div>
<!--/layouts/basic/modules/Base/ReferenceEditModal.tpl -->
{/strip}
