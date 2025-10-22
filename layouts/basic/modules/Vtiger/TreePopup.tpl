{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Vtiger/TreePopup.tpl -->
<div id="treePopupContainer" class="contentsDiv paddingLeftRight10px">
	<input type="hidden" class="triggerEventName" value="{$TRIGGER_EVENT_NAME}"/>
	<input type="hidden" name="src_record" value="{$SRC_RECORD}" />
	<input type="hidden" name="src_field" value="{$SRC_FIELD}" />
	<input type="hidden" name="template" value="{$TEMPLATE}" />
	<input type="hidden" id="treeLastID" value="{$LAST_ID}" />
	<input type="hidden" name="tree" id="treePopupValues" value="{\App\Modules\Vtiger\Helpers\Util::toSafeHTML($TREE)}" />
	<input type="hidden" name="isMultiple" id="isMultiple" value="{$IS_MULTIPLE}" />
	{assign var="MODULE_INSTANCE" value=\App\Modules\Vtiger\Models\Module::getInstance($MODULE)}
	{assign var="FIELD_INSTANCE" value=Vtiger_Field_Model::getInstance($SRC_FIELD,$MODULE_INSTANCE)}
	<div class="panel panel-default marginTop10">
		<div class="panel-heading">
			<h3 class="no-margin">{"LBL_SELECT_TREE_ITEM"|t:$MODULE} {$FIELD_INSTANCE->get('label')|t:$MODULE}</h3>
		</div>
		<div class="panel-body">
			<div class="contentsBackground">
			<div id="treePopupContents"></div>
		</div>
		</div>
	</div>
	{if $IS_MULTIPLE}
	<div class="pull-right">
		<button class="btn btn-success" type="submit" name="saveButton"><strong>{"LBL_SAVE"|t:$MODULE}</strong></button>
	</div>
	{/if}
</div>
{* javascript files *}
{include file='JSResources.tpl'|@vtemplate_path}
</body>
</html>
<!--/layouts/basic/modules/Vtiger/TreePopup.tpl -->
{/strip}
