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
<!-- layouts/basic/modules/Settings/CronTasks/AddAjax.tpl -->
<div class="modelContainer modal fade" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button data-dismiss="modal" class="close" title="{"LBL_CLOSE"|t}">x</button>
				<h3 class="modal-title">{"LBL_ADD_CRON"|t:$QUALIFIED_MODULE}</h3>
			</div>
			<form class="form-horizontal" id="cronJobSaveAjax" method="post" action="index.php">
				<input type="hidden" name="module" value="{$MODULE}" />
				<input type="hidden" name="parent" value="Settings" />
				<input type="hidden" name="action" value="AddCron" />

				<div class="modal-body tabbable">
					<div class="form-group">
						 <label class="col-sm-3 control-label">
							<span class="redColor">*</span> {"MODULE"|t:$QUALIFIED_MODULE}
						</label>
						<div class="col-sm-8 controls ">
							<select class="chzn-select form-control" name="cron_module">
								{foreach from=$MODULE_LIST  item=item key=key}
									<option value="{$item->name}">{vtranslate($item->name, $item->name)}</option>
								{/foreach}

							</select>
						</div>
					</div>
					<div class="form-group">
						 <label class="col-sm-3 control-label"><span class="redColor">*</span> {"LBL_NAME"|t:$QUALIFIED_MODULE}</label>
						<div class="col-sm-8 controls">
							<input value="" name="cron_name" class="form-control" data-validation-engine="validate[required]" id="name" />
						</div>
					</div>
					<div class="form-group">
						 <label class="col-sm-3 control-label"><span class="redColor">*</span> {"LBL_PATH_TO_FILE"|t:$QUALIFIED_MODULE}</label>
						<div class="col-sm-8 controls">
							<input value="" name="path" class="form-control" data-validation-engine="validate[required]" id="path" />
						</div>
					</div>
					<div class="form-group">
						 <label class="col-sm-3 control-label">{"LBL_STATUS"|t:$QUALIFIED_MODULE}</label>
						<div class="col-sm-8 controls">
							<select class="chzn-select form-control" name="status">
								<optgroup>
									<option value="1">{"LBL_ACTIVE"|t:$QUALIFIED_MODULE}</option>
									<option value="0">{"LBL_INACTIVE"|t:$QUALIFIED_MODULE}</option>
								</optgroup>
							</select>
						</div>
					</div>
					<div class="form-group">
						 <label class="col-sm-3 control-label">
							<span class="redColor">*</span> {"Frequency"|t:$QUALIFIED_MODULE}
						</label>
						<div class="controls col-sm-9">

							<div class="col-xs-6 col-sm-3 paddingLRZero">
								<input type="text" class="form-control" value="{$FIELD_VALUE}" id="frequency_value" name="frequency_value" />
							</div>
							<div class="col-xs-6 col-sm-8">
								<select class="chzn-select form-control" id="time_format" name="time_format">
									<optgroup>
										<option value="mins">{"LBL_MINUTES"|t:$QUALIFIED_MODULE}</option>
										<option value="hours">{"LBL_HOURS"|t:$QUALIFIED_MODULE}</option>
									</optgroup>
								</select>
							</div>
						</div>	
					</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">
										{"Description"|t:$QUALIFIED_MODULE}
									</label>
									<div class="col-sm-8 controls">
										<textarea name="description" class="form-control">

										</textarea>
									</div>
								</div>
				</div>
				{include file='ModalFooter.tpl'|@vtemplate_path:$MODULE}
			</form>
		</div>		
	</div>		
</div>		
<!--/layouts/basic/modules/Settings/CronTasks/AddAjax.tpl -->
{/strip}	
