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
<select id="groupbyfield_element">
	<option value="">{"LBL_NONE"|t:$MODULE}</option>
	{foreach key=PRIMARY_MODULE_NAME item=PRIMARY_MODULE from=$PRIMARY_MODULE_FIELDS}
		{foreach key=BLOCK_LABEL item=BLOCK from=$PRIMARY_MODULE}
			<optgroup label='{$PRIMARY_MODULE_NAME|t:$MODULE}-{$BLOCK_LABEL|t:$PRIMARY_MODULE_NAME}'>
				{foreach key=FIELD_KEY item=FIELD_LABEL from=$BLOCK}
					{assign var=FIELD_INFO value=explode(':', $FIELD_KEY)}
					{if $FIELD_INFO[4] eq 'D' or $FIELD_INFO[4] eq 'DT'}
						<option value="{$FIELD_KEY}:Y">{$FIELD_LABEL|t:$PRIMARY_MODULE_NAME} ({'LBL_YEAR'|t:$PRIMARY_MODULE_NAME})</option>
						<option value="{$FIELD_KEY}:MY">{$FIELD_LABEL|t:$PRIMARY_MODULE_NAME} ({'LBL_MONTH'|t:$PRIMARY_MODULE_NAME})</option>
						<option value="{$FIELD_KEY}">{$FIELD_LABEL|t:$PRIMARY_MODULE_NAME}</option>
					{else if $FIELD_INFO[4] neq 'I' and $FIELD_INFO[4] neq 'N' and $FIELD_INFO[4] neq 'NN'}
						<option value="{$FIELD_KEY}">{$FIELD_LABEL|t:$PRIMARY_MODULE_NAME}</option>
					{/if}
				{/foreach}
			</optgroup>
		{/foreach}
	{/foreach}
	{foreach key=SECONDARY_MODULE_NAME item=SECONDARY_MODULE from=$SECONDARY_MODULE_FIELDS}
		{foreach key=BLOCK_LABEL item=BLOCK from=$SECONDARY_MODULE}
			<optgroup label='{$SECONDARY_MODULE_NAME|t:$MODULE}-{$BLOCK_LABEL|t:$SECONDARY_MODULE_NAME}'>
				{foreach key=FIELD_KEY item=FIELD_LABEL from=$BLOCK}
					{assign var=FIELD_INFO value=explode(':', $FIELD_KEY)}
					{if $FIELD_INFO[4] eq 'D' or $FIELD_INFO[4] eq 'DT'}
						<option value="{$FIELD_KEY}:Y">{$SECONDARY_MODULE_NAME|t:$SECONDARY_MODULE_NAME} {$FIELD_LABEL|t:$SECONDARY_MODULE_NAME} ({'LBL_YEAR'|t:$SECONDARY_MODULE_NAME})</option>
						<option value="{$FIELD_KEY}:MY">{$SECONDARY_MODULE_NAME|t:$SECONDARY_MODULE_NAME} {$FIELD_LABEL|t:$SECONDARY_MODULE_NAME} ({'LBL_MONTH'|t:$SECONDARY_MODULE_NAME})</option>
						<option value="{$FIELD_KEY}">{$SECONDARY_MODULE_NAME|t:$SECONDARY_MODULE_NAME} {$FIELD_LABEL|t:$SECONDARY_MODULE_NAME}</option>
					{else if $FIELD_INFO[4] neq 'I' and $FIELD_INFO[4] neq 'N' and $FIELD_INFO[4] neq 'NN'}
						<option value="{$FIELD_KEY}">{$SECONDARY_MODULE_NAME|t:$SECONDARY_MODULE_NAME} {$FIELD_LABEL|t:$SECONDARY_MODULE_NAME}</option>
					{/if}
				{/foreach}
			</optgroup>
		{/foreach}
	{/foreach}
</select>

<select id="datafields_element">
	<option value='count(*)'>{"LBL_RECORD_COUNT"|t:$MODULE}</option>
	{foreach key=CALCULATION_FIELDS_MODULE_LABEL item=CALCULATION_FIELDS_MODULE from=$CALCULATION_FIELDS}
		<optgroup label="{$CALCULATION_FIELDS_MODULE_LABEL|t:$CALCULATION_FIELDS_MODULE_LABEL}">
		{foreach key=CALCULATION_FIELD_KEY item=CALCULATION_FIELD_TRANSLATED_LABEL from=$CALCULATION_FIELDS_MODULE}
			<option value="{$CALCULATION_FIELD_KEY}">{$CALCULATION_FIELD_TRANSLATED_LABEL}</option>
		{/foreach}
		</optgroup>
	{/foreach}
</select>