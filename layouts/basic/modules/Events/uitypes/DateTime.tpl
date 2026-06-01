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
<!-- layouts/basic/modules/Events/uitypes/DateTime.tpl -->
{if $FIELD_MODEL->getName() == 'date_start'}
	{assign var=DATE_FIELD value=$FIELD_MODEL}
	{assign var=MODULE_MODEL value=$RECORD_STRUCTURE_MODEL->getModule()}
	{assign var=TIME_FIELD value=$MODULE_MODEL->getField('time_start')}
	{assign var=TIME_NAME value='time_start'}
{else if $FIELD_MODEL->getName() == 'due_date'}
	{assign var=DATE_FIELD value=$FIELD_MODEL}
	{assign var=MODULE_MODEL value=$RECORD_STRUCTURE_MODEL->getModule()}
	{assign var=TIME_FIELD value=$MODULE_MODEL->getField('time_end')}
	{assign var=TIME_NAME value='time_end'}
{/if}

{assign var=DATE_TIME_VALUE value=$FIELD_MODEL->get('fieldvalue')}
{if ($DATE_TIME_VALUE|default:'')|trim neq ''}
	{assign var=DATE_TIME_COMPONENTS value=explode(' ' ,$DATE_TIME_VALUE)}
	{if count($DATE_TIME_COMPONENTS) eq 2}
		{assign var=TIME_FIELD value=$TIME_FIELD->set('fieldvalue',$DATE_TIME_COMPONENTS[1])}
	{elseif $RECORD}
		{assign var=TIME_FIELD value=$TIME_FIELD->set('fieldvalue',$RECORD->get($TIME_NAME))}
	{/if}
	{assign var=DATE_TIME_CONVERTED_VALUE value=\App\Fields\DateTimeField::convertToUserTimeZone($DATE_TIME_VALUE)->format('Y-m-d H:i:s')}
	{assign var=DATE_TIME_COMPONENTS value=explode(' ' ,$DATE_TIME_CONVERTED_VALUE)}
	{assign var=DATE_FIELD value=$DATE_FIELD->set('fieldvalue',$DATE_TIME_COMPONENTS[0])}
{else}
	{assign var=DATE_FIELD value=$DATE_FIELD->set('fieldvalue','')}
	{assign var=TIME_FIELD value=$TIME_FIELD->set('fieldvalue','')}
{/if}

<div class="dateTimeField">
	<div class="col-xs-7 paddingLRZero">
		{include file=vtemplate_path('uitypes/Date.tpl','Calendar') FIELD_MODEL=$DATE_FIELD}
	</div>
	<div class="col-xs-5 paddingLRZero">
		{include file=vtemplate_path('uitypes/Time.tpl','Calendar') FIELD_MODEL=$TIME_FIELD}
	</div>
</div>
