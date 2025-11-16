{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/uitypes/UserRoleFieldSearchView.tpl -->
	{assign var=FIELD_INFO value=\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($FIELD_MODEL->getFieldInfo()))}
	{if isset($SEARCH_INFO['searchValue'])}
		{assign var=SEARCH_VALUE value=explode(',',$SEARCH_INFO['searchValue'])}
	{else}
		{assign var=SEARCH_VALUE value=[]}
	{/if}
    {assign var=SEARCH_VALUES value=array_map("trim",$SEARCH_VALUE)}
	<div class="picklistSearchField">
		<select class="select2noactive listSearchContributor form-control" title="{$FIELD_MODEL->get('label')|t:$MODULE}"  name="{$FIELD_MODEL->getFieldName()}" multiple{/strip} {strip}
				{if AppConfig::performance('SEARCH_ROLES_BY_AJAX')}
					data-ajax-search="1" data-ajax-url="index.php?module={$MODULE}&action=Fields&mode=searchValues&fld={$FIELD_MODEL->getId()}" data-minimum-input="{AppConfig::performance('ROLE_MINIMUM_INPUT_LENGTH')}"
				{/if}
				data-fieldinfo="{$FIELD_INFO}">
			{if AppConfig::performance('SEARCH_ROLES_BY_AJAX')}
				{assign var=ALL_ROLE_NAMES value=$FIELD_MODEL->getPicklistValues()}
				{foreach from=$SEARCH_VALUES item=PICKLIST_VALUE}
					<option value="{$PICKLIST_VALUE}" selected>{if isset($ALL_ROLE_NAMES[$PICKLIST_VALUE])}{$ALL_ROLE_NAMES[$PICKLIST_VALUE]}{else}{$PICKLIST_VALUE}{/if}</option>
				{/foreach}
			{else}
				{foreach key=PICKLIST_VALUE item=PICKLIST_NAME from=$FIELD_MODEL->getPicklistValues()}
					<option value="{$PICKLIST_VALUE}" {if in_array($PICKLIST_VALUE, $SEARCH_VALUES)} selected {/if}>{$PICKLIST_NAME}</option>
				{/foreach}
			{/if}
		</select>
	</div>
<!--/layouts/basic/modules/Base/uitypes/UserRoleFieldSearchView.tpl -->
{/strip}
