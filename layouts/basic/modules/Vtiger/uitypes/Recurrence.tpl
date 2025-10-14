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
<!-- Recurrence.tpl -->
	<div class="typeSavingModal" tabindex="-1">
		<div  class="modal fade">
			<div class="modal-dialog modal-lg ">
				<div class="modal-content">
					<div class="modal-header row no-margin">
						<div class="col-xs-12 paddingLRZero">
							<div class="col-xs-8 paddingLRZero">
								<h4>{'LBL_TITLE_TYPE_SAVING'|t:$MODULE}</h4>
							</div>
							<div class="pull-right">
								<button class="btn btn-warning marginLeft10" type="button" data-dismiss="modal" aria-label="Close" aria-hidden="true">&times;</button>
							</div>
						</div>
					</div>
					<div class="modal-body row">
						<div class="col-xs-12">
							<div class="col-xs-12 paddingLRZero marginBottom10px">
								<div class="col-xs-4">
									<button class="btn btn-primary btn-sm typeSavingBtn" data-value="2">
										{'LBL_UPDATE_THIS_EVENT'|t:$MODULE}
									</button>
								</div>
								<div class="col-xs-8">
									{'LBL_UPDATE_THIS_EVENT_DESCRIPTION'|t:$MODULE}
								</div>
							</div>
							<div class="col-xs-12 paddingLRZero marginBottom10px">	
								<div class="col-xs-4">
									<button class="btn btn-primary btn-sm typeSavingBtn" data-value="3">
										{'LBL_UPDATE_FUTURE_EVENTS'|t:$MODULE}
									</button>
								</div>
								<div class="col-xs-8">
									{'LBL_UPDATE_FUTURE_EVENTS_DESCRIPTION'|t:$MODULE}
								</div>
							</div>
							<div class="col-xs-12 paddingLRZero marginBottom10px">	
								<div class="col-xs-4">
									<button class="btn btn-primary btn-sm typeSavingBtn" data-value="1">
										{'LBL_UPDATE_ALL_EVENTS'|t:$MODULE}
									</button>
								</div>
								<div class="col-xs-8">
									{'LBL_UPDATE_ALL_EVENTS_DESCRIPTION'|t:$MODULE}
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="hide repeatUI" >
		<input type="hidden" name="typeSaving">
		<input id="{$MODULE}_editView_fieldName_{$FIELD_MODEL->get('name')}" type="hidden" name="{$FIELD_MODEL->getFieldName()}" 
			   value="{$FIELD_MODEL->get('fieldvalue')}" />
		{assign var="RECURRING_INFORMATION" value=Vtiger_Recurrence_UIType::getRecurringInfo($FIELD_MODEL->get('fieldvalue'))}
		<div class="clearfix">
			<div class="col-xs-4 paddingLRZero marginBottom10px">
				<span class="control-label pull-left alignMiddle">{'LBL_RECURRING_TYPE'|t:$MODULE}</span>
			</div>
			<div class="col-xs-8 paddingLRZero marginBottom10px">
				<select class="select2 form-control recurringType" title="{'LBL_RECURRING_TYPE'|t:$MODULE} {$MODULE}">
					<option title="{'LBL_DAYS_TYPE'|t:$MODULE}" value="DAILY" {if $RECURRING_INFORMATION['FREQ'] eq 'DAILY'} selected {/if}>{'LBL_DAYS_TYPE'|t:$MODULE}</option>
					<option title="{'LBL_WEEKS_TYPE'|t:$MODULE}" value="WEEKLY" {if $RECURRING_INFORMATION['FREQ'] eq 'WEEKLY'} selected {/if}>{'LBL_WEEKS_TYPE'|t:$MODULE}</option>
					<option title="{'LBL_MONTHS_TYPE'|t:$MODULE}" value="MONTHLY" {if $RECURRING_INFORMATION['FREQ'] eq 'MONTHLY'} selected {/if}>{'LBL_MONTHS_TYPE'|t:$MODULE}</option>
					<option title="{'LBL_YEAR_TYPE'|t:$MODULE}" value="YEARLY" {if $RECURRING_INFORMATION['FREQ'] eq 'YEARLY'} selected {/if}>{'LBL_YEAR_TYPE'|t:$MODULE}</option>
				</select>
			</div>
			<div class="col-xs-4 paddingLRZero marginBottom10px">
				<span class="control-label pull-left alignMiddle">{'LBL_REPEAT_INTERVAL'|t:$MODULE}</span>
			</div>
			<div class="col-xs-8 paddingLRZero marginBottom10px">
				<select class="select2 form-control repeatFrequency" title="{'LBL_REPEAT_FOR'|t:$MODULE}">
					{for $FREQUENCY = 1 to 31}
						<option value="{$FREQUENCY}" title="{$FREQUENCY}" {if $FREQUENCY eq $RECURRING_INFORMATION['INTERVAL']}selected{/if}>{$FREQUENCY}</option>
					{/for}
				</select>
			</div>
			<div class="{if $RECURRING_INFORMATION['FREQ'] neq 'WEEKLY'}hide{/if} repeatWeekUI">
				<span class="col-md-4 paddingLRZero">
					<span class="medium">{'LBL_REAPEAT_IN'|t:$MODULE}</span>
				</span>
				<span class="col-md-8 paddingLRZero marginBottom10px">
					<div class="btn-group" data-toggle="buttons">
						<label title="{'LBL_DAY0'|t:$MODULE}" class="btn btn-primary {if isset($RECURRING_INFORMATION['BYDAY']) && strpos($RECURRING_INFORMATION['BYDAY'], 'SU') !== false}active{/if}">
							<input type="checkbox" autocomplete="off"  {if isset($RECURRING_INFORMATION['BYDAY']) && strpos($RECURRING_INFORMATION['BYDAY'], 'SU') !== false}checked{/if} value="SU">
							{'LBL_SM_SUN'|t:$MODULE}
						</label>
						<label title="{'LBL_DAY1'|t:$MODULE}"  class="btn btn-primary {if isset($RECURRING_INFORMATION['BYDAY']) && strpos($RECURRING_INFORMATION['BYDAY'], 'MO') !== false}active{/if}">
							<input type="checkbox" autocomplete="off" {if isset($RECURRING_INFORMATION['BYDAY']) && strpos($RECURRING_INFORMATION['BYDAY'], 'MO') !== false}checked{/if} value="MO">
							{'LBL_SM_MON'|t:$MODULE}
						</label>
						<label title="{'LBL_DAY2'|t:$MODULE}" class="btn btn-primary {if isset($RECURRING_INFORMATION['BYDAY']) && strpos($RECURRING_INFORMATION['BYDAY'], 'TU') !== false}active{/if}">
							<input type="checkbox" autocomplete="off"  {if isset($RECURRING_INFORMATION['BYDAY']) && strpos($RECURRING_INFORMATION['BYDAY'], 'TU') !== false}checked{/if} value="TU">
							{'LBL_SM_TUE'|t:$MODULE}
						</label>
						<label title="{'LBL_DAY3'|t:$MODULE}"  class="btn btn-primary {if isset($RECURRING_INFORMATION['BYDAY']) && strpos($RECURRING_INFORMATION['BYDAY'], 'WE') !== false}active{/if}">
							<input type="checkbox" autocomplete="off" {if isset($RECURRING_INFORMATION['BYDAY']) && strpos($RECURRING_INFORMATION['BYDAY'], 'WE') !== false}checked{/if} value="WE">
							{'LBL_SM_WED'|t:$MODULE}
						</label>
						<label title="{'LBL_DAY4'|t:$MODULE}" class="btn btn-primary {if isset($RECURRING_INFORMATION['BYDAY']) && strpos($RECURRING_INFORMATION['BYDAY'], 'TH') !== false}active{/if}">
							<input type="checkbox" autocomplete="off"  {if isset($RECURRING_INFORMATION['BYDAY']) && strpos($RECURRING_INFORMATION['BYDAY'], 'TH') !== false}checked{/if} value="TH">
							{'LBL_SM_THU'|t:$MODULE}
						</label>
						<label title="{'LBL_DAY5'|t:$MODULE}"  class="btn btn-primary {if isset($RECURRING_INFORMATION['BYDAY']) && strpos($RECURRING_INFORMATION['BYDAY'], 'FR') !== false}active{/if}">
							<input type="checkbox" autocomplete="off" {if isset($RECURRING_INFORMATION['BYDAY']) && strpos($RECURRING_INFORMATION['BYDAY'], 'FR') !== false}checked{/if} value="FR">
							{'LBL_SM_FRI'|t:$MODULE}
						</label>
						<label title="{'LBL_DAY6'|t:$MODULE}" class="btn btn-primary {if isset($RECURRING_INFORMATION['BYDAY']) && strpos($RECURRING_INFORMATION['BYDAY'], 'SA') !== false}active{/if}">
							<input type="checkbox" autocomplete="off"  {if isset($RECURRING_INFORMATION['BYDAY']) && strpos($RECURRING_INFORMATION['BYDAY'], 'SA') !== false}checked{/if} value="SA">
							{'LBL_SM_SAT'|t:$MODULE}
						</label>
					</div>
				</span>
			</div>

			<div class="{if $RECURRING_INFORMATION['FREQ'] neq 'MONTHLY'}hide{/if} col-xs-12 paddingLRZero repeatMonthUI">
				<span class="col-md-4 paddingLRZero">
					<span class="medium">{'LBL_REAPEAT_BY'|t:$MODULE}</span>
				</span>
				<span class="col-md-8 paddingLRZero">
					<div class="input-group marginBottom10px">
						<span class="input-group-addon">
							<input type="radio" name="calendarMontlyType" class="calendarMontlyType" value="DATE" {if isset($RECURRING_INFORMATION['BYMONTHDAY'])}checked{/if}>
						</span>
						<input type="text" class="form-control" aria-label="{'LBL_DAY_IN_MONTH'|t:$MODULE}" value="{'LBL_DAY_IN_MONTH'|t:$MODULE}" readonly="readonly">
					</div>
					<div class="input-group marginBottom10px">
						<span class="input-group-addon">
							<input type="radio" name="calendarMontlyType" class="calendarMontlyType" value="DAY" {if isset($RECURRING_INFORMATION['BYDAY'])}checked{/if}>
						</span>
						<input type="text" class="form-control" aria-label="{'LBL_DAY_IN_WEEK'|t:$MODULE}" value="{'LBL_DAY_IN_WEEK'|t:$MODULE}" readonly="readonly">
					</div>
				</span>
			</div>
			<div class="col-xs-4 paddingLRZero marginBottom10px">
				<span class="control-label pull-left alignMiddle">{'LBL_REPEAT_END'|t:$MODULE}</span>
			</div>
			<div class="col-xs-8 paddingLRZero marginBottom10px">
				<div class="input-group marginBottom10px">
					<span class="input-group-addon">
						<input type="radio" name="calendarEndType" value="never" {if !isset($RECURRING_INFORMATION['COUNT']) && !isset($RECURRING_INFORMATION['UNTIL'])}checked{/if}>
					</span>
					<input type="text" class="form-control" aria-label="" value="{'LBL_NEVER'|t:$MODULE}" readonly="readonly">
				</div>
				<div class="input-group marginBottom10px">
					<span class="input-group-addon">
						<input type="radio" name="calendarEndType" value="count" {if isset($RECURRING_INFORMATION['COUNT'])}checked{/if}>
						&nbsp;{'LBL_COUNT'|t:$MODULE}
					</span>
					<input type="text" class="form-control countEvents" {if isset($RECURRING_INFORMATION['COUNT'])}value="{$RECURRING_INFORMATION['COUNT']}"{else}disabled="disabled" {/if}>
				</div>
				<div class="input-group marginBottom10px date">
					<span class="input-group-addon">
						<input type="radio" name="calendarEndType" value="until" {if isset($RECURRING_INFORMATION['UNTIL'])}checked{/if}>
						&nbsp;{'LBL_UNTIL'|t:$MODULE}
					</span>
					<input type="text" class="dateField form-control calendarUntil" {if isset($RECURRING_INFORMATION['UNTIL'])} value="{$RECURRING_INFORMATION['UNTIL']}"  {else} disabled="disabled"{/if}name="calendarUntil" data-date-format="{$USER_MODEL->get('date_format')}" 
						   title="{'LBL_UNTIL'|t:$MODULE}"
						   data-validation-engine='validate[required,funcCall[Vtiger_Date_Validator_Js.invokeValidation]]' data-validator='{\App\Json::encode([['name' => 'greaterThanDependentField', 'params' => ['date_start']]])}'/>
					<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
				</div>
			</div>
		</div>
	</div>
<!--/Recurrence.tpl -->
{/strip}
