{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (c) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Settings/CronTasks/EditAjax.tpl -->
<div class="modelContainer modal fade" tabindex="-1">
	<div class="modal-dialog modal-lg">
        <div class="modal-content">
			<div class="modal-header">
				<button data-dismiss="modal" class="close" title="{"LBL_CLOSE"|t}">x</button>
				<h3 class="modal-title">{"LBL_EDIT_TASK"|t:$QUALIFIED_MODULE}: {$RECORD_MODEL->get('name')|t:$QUALIFIED_MODULE}</h3>
			</div>
			<form class="form-horizontal" id="cronJobSaveAjax" method="post" action="index.php">
				<input type="hidden" name="module" value="{$MODULE}" />
				<input type="hidden" name="parent" value="Settings" />
				<input type="hidden" name="action" value="SaveAjax" />
				<input type="hidden" name="record" value="{$RECORD}" />
				<input type="hidden" id="minimumFrequency" value="{$RECORD_MODEL->getMinimumFrequency()}" />
				<input type="hidden" id="frequency" name="frequency" value="" />

				<div class="modal-body tabbable">
					<div class="form-group">
						<label class="col-sm-3 control-label"><span class="redColor">*</span> {"MODULE"|t:$QUALIFIED_MODULE}</label>
						<div class="col-sm-8 controls">
							<select class="chzn-select form-control" name="cron_module">
								{foreach from=$MODULE_LIST item=item key=key}
									<option value="{$item->name}" {if $RECORD_MODEL->get('module') eq $item->name}selected="selected"{/if}>{$item->name|t:$item->name}</option>
								{/foreach}
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><span class="redColor">*</span> {"LBL_NAME"|t:$QUALIFIED_MODULE}</label>
						<div class="col-sm-8 controls">
							<input value="{$RECORD_MODEL->get('name')|escape}" name="name" class="form-control" data-validation-engine="validate[required]" id="cron_edit_name" />
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><span class="redColor">*</span> {"LBL_HANDLER_CLASS"|t:$QUALIFIED_MODULE}</label>
						<div class="col-sm-8 controls">
							<input value="{$RECORD_MODEL->get('handler_class')|escape}" name="handler_class" class="form-control" data-validation-engine="validate[required]" id="cron_edit_handler_class" />
						</div>
					</div>
					{if $RECORD_MODEL->get('handler_params')|default:'' neq ''}
					<div class="form-group">
						<label class="col-sm-3 control-label">{"LBL_HANDLER_PARAMS"|t:$QUALIFIED_MODULE}</label>
						<div class="col-sm-8 controls">
							<pre class="form-control-static" style="max-height:120px;overflow:auto;">{$RECORD_MODEL->get('handler_params')|escape}</pre>
							<p class="help-block text-muted">{"LBL_HANDLER_PARAMS_READONLY"|t:$QUALIFIED_MODULE}</p>
						</div>
					</div>
					{/if}
					<div class="form-group">
						<label class="col-sm-3 control-label">{"LBL_STATUS"|t:$QUALIFIED_MODULE}</label>
						<div class="col-sm-8 controls">
							<select class="chzn-select form-control" name="status">
								<optgroup>
									<option {if $RECORD_MODEL->get('status') eq 1} selected="" {/if} value="1">{"LBL_ACTIVE"|t:$QUALIFIED_MODULE}</option>
									<option {if $RECORD_MODEL->get('status') eq 0} selected="" {/if} value="0">{"LBL_INACTIVE"|t:$QUALIFIED_MODULE}</option>
								</optgroup>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label">
							<span class="redColor">*</span> {"Frequency"|t:$QUALIFIED_MODULE}
						</label>
						<div class="controls col-sm-9">
							{assign var=VALUES value=':'|explode:$RECORD_MODEL->getDisplayValue('frequency')}
							{if $VALUES[0] == '00' && $VALUES[1] == '00'}
								{assign var=MINUTES value="true"}
								{assign var=FIELD_VALUE value=$VALUES[1]}
							{elseif $VALUES[0] == '00'}
								{assign var=MINUTES value="true"}
								{assign var=FIELD_VALUE value=$VALUES[1]}
							{elseif $VALUES[1] == '00'}
								{assign var=MINUTES value="false"}
								{assign var=FIELD_VALUE value=($VALUES[0])}
							{else}
								{assign var=MINUTES value="true"}
								{assign var=FIELD_VALUE value=($VALUES[0]*60)+$VALUES[1]}
							{/if}
							<div class="col-xs-6 col-sm-3 paddingLRZero">
								<input type="text" class="form-control" value="{$FIELD_VALUE}" data-validation-engine="validate[required,funcCall[Vtiger_WholeNumberGreaterThanZero_Validator_Js.invokeValidation]]" id="frequencyValue"/>
							</div>
							<div class="col-xs-6 col-sm-8">
								<select class="chzn-select form-control" id="time_format">
									<optgroup>
										<option value="mins" {if $MINUTES eq 'true'} selected="" {/if}>{"LBL_MINUTES"|t:$QUALIFIED_MODULE}</option>
										<option value="hours" {if $MINUTES eq 'false'}selected="" {/if}>{"LBL_HOURS"|t:$QUALIFIED_MODULE}</option>
									</optgroup>
								</select>
							</div>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label">{"Description"|t:$QUALIFIED_MODULE}</label>
						<div class="col-sm-8 controls">
							<textarea name="description" class="form-control" rows="3">{$RECORD_MODEL->get('description')|escape}</textarea>
						</div>
					</div>
				</div>
				{include file='ModalFooter.tpl'|@vtemplate_path:$MODULE}
			</form>
		</div>
	</div>
</div>
<!--/layouts/basic/modules/Settings/CronTasks/EditAjax.tpl -->
{/strip}
