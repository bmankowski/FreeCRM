{*<!-- {[The file is published on the basis of FreeCRM Public License 1.1 that can be found in the following directory: licenses/License.html]} -->*}
{strip}
<!-- layouts/basic/modules/Base/uitypes/MailSmtpSelect.tpl -->
	{assign var="FIELD_INFO" value=\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($FIELD_MODEL->getFieldInfo()))}
	{assign var=PICKLIST_VALUES value=$FIELD_MODEL->getPicklistValues()}
	{assign var=FIELD_VALUE value=$FIELD_MODEL->get('fieldvalue')}
	<input type="hidden" name="{$FIELD_MODEL->getFieldName()}" value="" />
	<select id="{$MODULE}_{$VIEW}_fieldName_{$FIELD_MODEL->get('name')}" title="{$FIELD_MODEL->get('label')|t:$MODULE}" class="chzn-select form-control col-md-12" name="{$FIELD_MODEL->getFieldName()}" data-fieldinfo='{$FIELD_INFO}' {if $FIELD_MODEL->isMandatory() eq true} {/if} {if $FIELD_MODEL->isEditableReadOnly()}readonly="readonly"{/if}>
		<option value="">{'LBL_SELECT_OPTION'|t:'Vtiger'}</option>
		{foreach item=PICKLIST_VALUE key=KEY from=$PICKLIST_VALUES}
			<option value="{$KEY}" {if $KEY eq $FIELD_VALUE} selected {/if}>
				{if $PICKLIST_VALUE['default']}
					{$PICKLIST_VALUE['name']} ({'LBL_DEFAULT'|t:'Settings:MailSmtp'})
				{else}
					{$PICKLIST_VALUE['name']}
				{/if}
			</option>
		{/foreach}
	</select>
<!--/layouts/basic/modules/Base/uitypes/MailSmtpSelect.tpl -->
{/strip}
