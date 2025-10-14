{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/Workflows/Tasks/VTAutoAssign.tpl -->
	{assign var=ENTRIES value=$TASK_OBJECT->getAutoAssignEntries($WORKFLOW_MODEL->get('module_name'))}
	<div class="row">
		<label class="col-md-4 control-label">{'LBL_SELECT_TEMPLATE'|t:$QUALIFIED_MODULE}</label>
		<div class="col-md-5">
			<select class="chzn-select form-control" name="template" data-validation-engine='validate[required]'>
				<option value="">{'LBL_NONE'|t:$QUALIFIED_MODULE}</option>
				{foreach from=$ENTRIES key=KEY item=ITEM}
					<option  value="{$KEY}">{$ITEM->getDisplayValue('field')|t:$ITEM->getSourceModuleName()}</option>
				{/foreach}	
			</select>
		</div>
	</div>
<!--/layouts/basic/modules/Settings/Workflows/Tasks/VTAutoAssign.tpl -->
{/strip}	
