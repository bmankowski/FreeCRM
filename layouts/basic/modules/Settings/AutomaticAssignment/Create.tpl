{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} -->*}
{strip}
<!-- layouts/basic/modules/Settings/AutomaticAssignment/Create.tpl -->
	{if isset($WIZARD_BASE)}
		<form class="form-horizontal" action="{$MODULE_MODEL->getEditViewUrl()}" id="createForm">
			<div class="modal-header">
				<div class="pull-left">
					<h3 class="modal-title">{'LBL_CREATE_RECORD'|t:$QUALIFIED_MODULE}</h3>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="modal-body">
				<div class="">
					<div class="verticalBottomSpacing">
						<label class="control-label">
							{'LBL_SELECT_MODULE'|t:$QUALIFIED_MODULE}<span class="redColor"> *</span>
						</label>
						<select class="select2 form-control sourceModule" name="tabid" id="supportedModules">
							<option value="">{'LBL_SELECT_OPTION'|t:$QUALIFIED_MODULE}</option>
							{foreach item=SUPPORTED_MODULE key=TAB_ID from=$SUPPORTED_MODULES}
								<option value="{$TAB_ID}">{$SUPPORTED_MODULE->getName()|t:$SUPPORTED_MODULE->getName()}</option>
							{/foreach}
						</select>
					</div>
					<div class="fieldList">
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="submit" class="btn btn-success hide submitButton">{'BTN_NEXT'|t:$QUALIFIED_MODULE}</button>
				<button type="button" class="btn btn-warning dismiss" data-dismiss="modal">{'BTN_CLOSE'|t:$QUALIFIED_MODULE}</button>
			</div>
		</form>
	{else}
		<label class="control-label">
			{'LBL_SELECT_FIELD'|t:$QUALIFIED_MODULE}<span class="redColor"> *</span>
		</label>
		<div class="controls">
			<select class="select2 form-control" name="field" id="supportedFields">
				{foreach key=BLOCK_NAME item=FIELDS from=$SUPPORTED_FIELDS}
					<optgroup label="{$BLOCK_NAME|t:$SELECTED_MODULE}">
						{foreach key=FIELD_NAME item=FIELD_OBJECT from=$FIELDS name=fieldsLoop}
							<option value="{$FIELD_NAME}">{$FIELD_OBJECT->getFieldLabel()|t:$SELECTED_MODULE}</option>
						{/foreach}
					</optgroup>
				{/foreach}
			</select>
		</div>
	{/if}

<!--/layouts/basic/modules/Settings/AutomaticAssignment/Create.tpl -->
{/strip}
