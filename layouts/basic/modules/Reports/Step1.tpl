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
<!-- layouts/basic/modules/Reports/Step1.tpl -->
	<div class="reportContents">
		<form class="form-horizontal recordEditView" id="report_step1" method="post" action="index.php">
			<input type="hidden" name="module" value="{$MODULE}" />
			<input type="hidden" name="view" value="{$VIEW}" />
			<input type="hidden" name="mode" value="step2" />
			<input type="hidden" class="step" value="1" />
			<input type="hidden" name="isDuplicate" value="{$IS_DUPLICATE}" />
			<input type="hidden" name="record" value="{$RECORD_ID}" />
			<input type="hidden" id="relatedModules" data-value="{\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($RELATED_MODULES))}" />
			<input type="hidden" id="weekStartDay" data-value='{$WEEK_START_ID}' />
			<div class="well contentsBackground">
				<div class="row marginBottom5px">
					<div class="col-md-3"><span class="redColor">*</span>{"LBL_REPORT_NAME"|t:$MODULE}</div>
					<div class="col-md-7"><input class="col-md-6 form-control" data-validation-engine='validate[required]' type="text" name="reportname" title="{"LBL_REPORT_NAME"|t:$MODULE}" value="{$REPORT_MODEL->get('reportname')}"/></div>
				</div>
				<div class="row marginBottom5px paddingTop10">
					<div class="col-md-3"><span class="redColor">*</span>{"LBL_REPORT_FOLDER"|t:$MODULE}</div>
					<div class="col-md-7">
						<select class="chzn-select col-md-6 form-control" name="folderid">
							<optgroup>
								{foreach item=REPORT_FOLDER from=$REPORT_FOLDERS}
									<option value="{$REPORT_FOLDER->getId()}"
											{if $REPORT_FOLDER->getId() eq $REPORT_MODEL->get('folderid')}
												selected=""
											{/if}
											>{$REPORT_FOLDER->getName()|t:$MODULE}</option>
								{/foreach}
							</optgroup>
						</select>
					</div>
				</div>
				<div class="row marginBottom5px paddingTop10">
					<div class="col-md-3"><span class="redColor">*</span>{"PRIMARY_MODULE"|t:$MODULE}</div>
					<div class="col-md-7">
						<select class="col-md-6 chzn-select form-control" title="{"PRIMARY_MODULE"|t:$MODULE}" id="primary_module" name="primary_module">
							<optgroup>
								{foreach key=RELATED_MODULE_KEY item=RELATED_MODULE from=$MODULELIST}
									<option value="{$RELATED_MODULE_KEY}" {if $REPORT_MODEL->getPrimaryModule() eq $RELATED_MODULE_KEY } selected="selected" {/if}>
										{$RELATED_MODULE_KEY|t:$RELATED_MODULE_KEY}
									</option>
								{/foreach}
							</optgroup>
						</select>
					</div>
				</div>
				<div class="row marginBottom5px paddingTop10">
					<div class="col-md-3">
						<div>{"LBL_SELECT_RELATED_MODULES"|t:$MODULE}</div>
						<div>({"LBL_MAX"|t:$MODULE}&nbsp;2)</div>
					</div>
					<div class="col-md-7">
						{assign var=SECONDARY_MODULES_ARR value=explode(':',$REPORT_MODEL->getSecondaryModules())}
						{assign var=PRIMARY_MODULE value=$REPORT_MODEL->getPrimaryModule()}

						{if $PRIMARY_MODULE eq ''}
							{foreach key=PARENT item=RELATED from=$RELATED_MODULES name=relatedlist}
								{if $smarty.foreach.relatedlist.index eq 0}
									{assign var=PRIMARY_MODULE value=$PARENT}
								{/if}
							{/foreach}
						{/if}
						{assign var=PRIMARY_RELATED_MODULES value=$RELATED_MODULES[$PRIMARY_MODULE]}
						<select class="col-md-6 select2-container form-control" id="secondary_module" multiple name="secondary_modules[]" title="{"LBL_SELECT_RELATED_MODULES"|t:$MODULE}" data-placeholder="{"LBL_SELECT_RELATED_MODULES"|t:$MODULE}">
							{foreach key=PRIMARY_RELATED_MODULE  item=PRIMARY_RELATED_MODULE_LABEL from=$PRIMARY_RELATED_MODULES}
								<option {if in_array($PRIMARY_RELATED_MODULE,$SECONDARY_MODULES_ARR)} selected="" {/if} value="{$PRIMARY_RELATED_MODULE}">{$PRIMARY_RELATED_MODULE_LABEL}</option>
							{/foreach}
						</select>
					</div>
				</div>
				<div class="row marginBottom5px paddingTop10">
					<div class="col-md-3">{"LBL_DESCRIPTION"|t:$MODULE}</div>
					<div class="col-md-7"><textarea class="col-md-6 form-control" type="text" title="{"LBL_DESCRIPTION"|t:$MODULE}" name="description" >{$REPORT_MODEL->get('description')}</textarea></div>
				</div>
				<div class="row paddingTop10">
					<div class="col-xs-12">
						<input type="checkbox" title="{"LBL_SCHEDULE_REPORTS"|t:$MODULE}"  {if $SCHEDULEDREPORTS->get('scheduleid') neq ''} checked="" {/if} value="{if $SCHEDULEDREPORTS->get('scheduleid') neq ''}true{/if}" name='enable_schedule' style="margin-top: 0px !important;"> &nbsp;
						<strong>{"LBL_SCHEDULE_REPORTS"|t:$MODULE}</strong>
					</div>
				</div>
				<div id="scheduleBox" class='well contentsBackground {if $SCHEDULEDREPORTS->get('scheduleid') eq ''} hide {/if}'>
					<div class="row" style="padding:5px 0px;">
						<div class="col-md-3 marginBottom5px" style="position:relative;top:5px;">{"LBL_FILE_TYPE"|t:$MODULE}</div>
						<div class="col-md-4">
							<select class="chzn-select form-control" title="{"LBL_FILE_TYPE"|t:$MODULE}" name="scheduleFileType">
								<option value="CSV" {if $SCHEDULEDREPORTS->get('scheduleFileType') eq 'CSV'}selected{/if}>{"LBL_CSV"|t:$MODULE}</option>
								<option value="EXCEL" {if $SCHEDULEDREPORTS->get('scheduleFileType') eq 'EXCEL'}selected{/if}>{"LBL_EXCEL"|t:$MODULE}</option>
							</select>
						</div>
					</div>
					<div class='row' style="padding:5px 0px;">
						<div class='col-md-3 marginBottom5px' style='position:relative;top:5px;'>{"LBL_RUN_REPORT"|t:$MODULE}</div>
						<div class='col-md-4'>
							{assign var=scheduleid value=$SCHEDULEDREPORTS->get('scheduleid')}
							<select class='chzn-select form-control' id='schtypeid' title="{"LBL_SCHEDULE_REPORTS"|t:$MODULE}" name='schtypeid'>
								<option value="1" {if $scheduleid eq 1}selected{/if}>{"LBL_DAILY"|t:$MODULE}</option>
								<option value="2" {if $scheduleid eq 2}selected{/if}>{"LBL_WEEKLY"|t:$MODULE}</option>
								<option value="5" {if $scheduleid eq 5}selected{/if}>{"LBL_SPECIFIC_DATE"|t:$QUALIFIED_MODULE}</option>
								<option value="3" {if $scheduleid eq 3}selected{/if}>{"LBL_MONTHLY_BY_DATE"|t:$MODULE}</option>
								<option value="4" {if $scheduleid eq 4}selected{/if}>{"LBL_YEARLY"|t:$MODULE}</option>
							</select>
						</div>
					</div>

					{* show weekdays for weekly option *}
					<div class='row {if $scheduleid neq 2} hide {/if}' id='scheduledWeekDay' style='padding:5px 0px;'>
						<div class='col-md-3 marginBottom5px' style='position:relative;top:5px;'>{"LBL_ON_THESE_DAYS"|t:$MODULE}</div>
						<div class='col-md-4'>
							{assign var=dayOfWeek value=\App\Utils\Json::decode($SCHEDULEDREPORTS->get('schdayoftheweek'))}
							<select style='width:230px;' multiple class='chosen form-control' title="{"LBL_ON_THESE_DAYS"|t:$MODULE}" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" name='schdayoftheweek' id='schdayoftheweek'>
								<option value="7" {if is_array($dayOfWeek) && in_array('7', $dayOfWeek)} selected {/if}>{"LBL_DAY0"|t:"Calendar"}</option>
								<option value="1" {if is_array($dayOfWeek) && in_array('1', $dayOfWeek)} selected {/if}>{"LBL_DAY1"|t:"Calendar"}</option>
								<option value="2" {if is_array($dayOfWeek) && in_array('2', $dayOfWeek)} selected {/if}>{"LBL_DAY2"|t:"Calendar"}</option>
								<option value="3" {if is_array($dayOfWeek) && in_array('3', $dayOfWeek)} selected {/if}>{"LBL_DAY3"|t:"Calendar"}</option>
								<option value="4" {if is_array($dayOfWeek) && in_array('4', $dayOfWeek)} selected {/if}>{"LBL_DAY4"|t:"Calendar"}</option>
								<option value="5" {if is_array($dayOfWeek) && in_array('5', $dayOfWeek)} selected {/if}>{"LBL_DAY5"|t:"Calendar"}</option>
								<option value="6" {if is_array($dayOfWeek) && in_array('6', $dayOfWeek)} selected {/if}>{"LBL_DAY6"|t:"Calendar"}</option>
							</select>
						</div>
					</div>

					{* show month view by dates *}
					<div class='row {if $scheduleid neq 3} hide {/if}' id='scheduleMonthByDates' style="padding:5px 0px;">
						<div class='col-md-3 marginBottom5px' style='position:relative;top:5px;'>{"LBL_ON_THESE_DAYS"|t:$MODULE}</div>
						<div class='col-md-4'>
							{assign var=dayOfMonth value=\App\Utils\Json::decode($SCHEDULEDREPORTS->get('schdayofthemonth'))}
							<select style="width: 281px !important;" multiple class="chosen-select col-md-6 form-control" title="{"LBL_ON_THESE_DAYS"|t:$MODULE}" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" name='schdayofthemonth' id='schdayofthemonth' >
								{section name=foo loop=31}
									<option value={$smarty.section.foo.iteration} {if is_array($dayOfMonth) && in_array($smarty.section.foo.iteration, $dayOfMonth)}selected{/if}>{$smarty.section.foo.iteration}</option>
								{/section}
							</select>
						</div>
					</div>
					{* show specific date *}
					<div class='row {if $scheduleid neq 5} hide {/if}' id='scheduleByDate' style="padding:5px 0px;">
						<div class='col-md-3 marginBottom5px' style='position:relative;top:5px;'><span class="redColor">*</span>{"LBL_CHOOSE_DATE"|t:$MODULE}</div>
						<div class='col-md-6'>
							<div class='input-group date' style='width: 185px;'>
								{assign var=specificDate value=\App\Utils\Json::decode($SCHEDULEDREPORTS->get('schdate'))}
								{if $specificDate[0] neq ''} {assign var=specificDate1 value=DateTime\App\Fields\Field::convertToUserFormat($specificDate[0])} {/if}
								<input  type="text" class="dateField form-control input-sm col-md-6" id="schdate" name="schdate" value="{$specificDate1}" data-date-format="{$CURRENT_USER->date_format}" data-validation-engine="validate[ required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"/>
								<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
							</div>
						</div>
					</div>
					{* show month view by anually *}
					<div class='row {if $scheduleid neq 4} hide {/if}' id='scheduleAnually' style='padding:5px 0px;'>
						<div class='col-md-3 ' style='position:relative;top:5px;'>
							{"LBL_SELECT_MONTH_AND_DAY"|t:$MODULE}
						</div>
						<div class='col-md-5'>
							<div id='annualDatePicker'></div>
						</div>
						<div class='col-md-2'>
							<div style='padding-bottom:5px;'>{"LBL_SELECTED_DATES"|t:$MODULE}</div>
							<div>
								<input type="hidden" id=hiddenAnnualDates value='{$SCHEDULEDREPORTS->get('schannualdates')}' />
								{assign var=ANNUAL_DATES value=\App\Utils\Json::decode($SCHEDULEDREPORTS->get('schannualdates'))}
								<select multiple class="chosen-select" id='annualDates' name='schannualdates' data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]">
									{foreach item=DATES from=$ANNUAL_DATES}
										<option value="{$DATES}" selected>{$DATES}</option>
									{/foreach}
								</select>
							</div>
						</div>
					</div>

					{* show time for all other than Hourly option*}
					<div class='row' id='scheduledTime' style='padding:5px 0px 10px 0px;'>
						<div class='col-md-3 marginBottom5px' style='position:relative;top:5px;'>
							<span class="redColor">*</span>{"LBL_AT_TIME"|t:$MODULE}
						</div>
						<div class='col-md-4' id='schtime'>
							<div class="input-group time">
								<input type='text' class='clockPicker input-sm form-control' data-format='24' name='schtime' value="{$SCHEDULEDREPORTS->get('schtime')}" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"/>
								<span class="input-group-addon cursorPointer"><i class="glyphicon glyphicon-time"></i></span>
							</div>
						</div>
					</div>
					{* show all the users,groups,roles and subordinat roles*}
					<div class='row' id='recipientsList' style='padding:5px 0px 10px 0px;'>
						<div class='col-md-3 marginBottom5px' style='position:relative;top:5px;'>
							<span class="redColor">*</span>{"LBL_SELECT_RECIEPIENTS"|t:$MODULE}
						</div>
						<div class='col-md-4'>
							{assign var=ALL_ACTIVEUSER_LIST value=\App\Fields\Owner::getInstance()->getAccessibleUsers()}
							{assign var=ALL_ACTIVEGROUP_LIST value=\App\Fields\Owner::getInstance()->getAccessibleGroups()}
							{assign var=recipients value=\App\Utils\Json::decode($SCHEDULEDREPORTS->get('recipients'))}
							<select multiple data-placeholder="{"LBL_SELECT_OPTION"|t}" title="{"LBL_SELECT_RECIEPIENTS"|t:$MODULE}" class="chosen-select col-md-6 form-control" id='recipients' name='recipients' data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" style="width: 281px !important;">
								<optgroup label="{"LBL_USERS"|t}">
									{foreach key=USER_ID item=USER_NAME from=$ALL_ACTIVEUSER_LIST}
										{assign var=USERID value="USER::{$USER_ID}"}
										<option value="{$USERID}" {if is_array($recipients) && in_array($USERID, $recipients)} selected {/if} data-picklistvalue= '{$USER_NAME}'> {$USER_NAME} </option>
									{/foreach}
								</optgroup>
								<optgroup label="{"LBL_GROUPS"|t}">
									{foreach key=GROUP_ID item=GROUP_NAME from=$ALL_ACTIVEGROUP_LIST}
										{assign var=GROUPID value="GROUP::{$GROUP_ID}"}
										<option value="{$GROUPID}" {if is_array($recipients) && in_array($GROUPID, $recipients)} selected {/if} data-picklistvalue= '{$GROUP_NAME}'>{$GROUP_NAME}</option>
									{/foreach}
								</optgroup>
								<optgroup label="{"Roles"|t:"Roles"}">
									{foreach key=ROLE_ID item=ROLE_OBJ from=$ROLES}
										{assign var=ROLEID value="ROLE::{$ROLE_ID}"}
										<option value="{$ROLEID}" {if is_array($recipients) && in_array($ROLEID, $recipients)} selected {/if} data-picklistvalue= '{$ROLE_OBJ->get('rolename')}'>{$ROLE_OBJ->get('rolename')|t}</option>
									{/foreach}
								</optgroup>
							</select>
						</div>
					</div>
					<div class='row' id='specificemailsids' style='padding:5px 0px 10px 0px;'>
						<div class='col-md-3 marginBottom5px' style='position:relative;top:5px;'>
							{"LBL_SPECIFIC_EMAIL_ADDRESS"|t:$MODULE}
						</div>
						<div class='col-md-4'>
							{assign var=specificemailids value=\App\Utils\Json::decode($SCHEDULEDREPORTS->get('specificemails'))}
							<input id="specificemails" style="width: 281px !important;" class="col-md-6 form-control" title="{"LBL_SPECIFIC_EMAIL_ADDRESS"|t:$MODULE}" type="text" value="{$specificemailids}" name="specificemails" data-validation-engine="validate[funcCall[Vtiger_MultiEmails_Validator_Js.invokeValidation]]"></input>
						</div>
					</div>
					<div class="row">
						<div class='col-md-3 marginBottom5px'>
							<span class=''>{"LBL_NEXT_TRIGGER_TIME"|t:$MODULE}</span>
						</div>
						<div class='col-md-4'>
							{DateTime\App\Fields\Field::convertToUserFormat($SCHEDULEDREPORTS->get('next_trigger_time'))}
							<span>&nbsp;({$ACTIVE_ADMIN->time_zone})</span>
						</div>
					</div>
				</div>
			</div>
			<div class="row pull-right no-margin">
				<button type="submit" class="btn btn-success nextStep"><strong>{"LBL_NEXT"|t:$MODULE}</strong></button>&nbsp;&nbsp;
				<button onclick='window.history.back()' type="reset" class="cancelLink cursorPointer btn btn-warning">{"LBL_CANCEL"|t:$MODULE}</button>
			</div>
		</form>
	</div>
<!--/layouts/basic/modules/Reports/Step1.tpl -->
{/strip}
