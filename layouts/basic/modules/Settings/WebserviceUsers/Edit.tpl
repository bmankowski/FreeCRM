{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/WebserviceUsers/Edit.tpl -->
	<input type="hidden" id="typeApi" name="typeApi" value="{$TYPE_API}">
	<input type="hidden" id="record" name="record" value="{$RECORD_MODEL->getId()}">
	<form class="form-horizontal validateForm" id="editForm">
		<div class="modal-header">
			<button class="close" data-dismiss="modal" title="{'LBL_CLOSE'|t}">x</button>
			{if !$RECORD_MODEL->getId()}{assign var="TITLE" value="LBL_CREATE_RECORD"}{else}{assign var="TITLE" value="LBL_EDIT_RECORD"}{/if}
			<h3 class="modal-title">{$TITLE|t:$QUALIFIED_MODULE}</h3>
		</div>
		<div class="modal-body">
			<div class="">
				{foreach from=$RECORD_MODEL->getEditFields() item=LABEL key=FIELD_NAME name=fields}
					{assign var="FIELD_MODEL" value=$RECORD_MODEL->getFieldInstanceByName($FIELD_NAME)->set('fieldvalue',$RECORD_MODEL->get($FIELD_NAME))}
					<div class="form-group">
						<label class="control-label col-md-3">
							{$LABEL|t:$QUALIFIED_MODULE}
							{if $FIELD_MODEL->isMandatory()}<span class="redColor"> *</span>{/if}:
						</label>
						<div class="col-md-8 fieldValue">
							{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(), $QUALIFIED_MODULE) FIELD_MODEL=$FIELD_MODEL MODULE=$QUALIFIED_MODULE}
						</div>
					</div>
				{/foreach}
			</div>
		</div>
		<div class="modal-footer">
			<button type="submit" class="btn btn-success">{'BTN_SAVE'|t:$QUALIFIED_MODULE}</button>
			<button type="button" class="btn btn-warning dismiss" data-dismiss="modal">{'BTN_CLOSE'|t:$QUALIFIED_MODULE}</button>
		</div>
	</form>
<!--/layouts/basic/modules/Settings/WebserviceUsers/Edit.tpl -->
{/strip}
