{strip}
<!-- VTEmailTemplateTask.tpl -->
	{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
	<div id="VtVTEmailTemplateTaskContainer">
		<div class="">
			<div class="row padding-bottom1per">
				<span class="col-md-4 control-label">{'LBL_SMTP'|t:$QUALIFIED_MODULE}</span>
				<div class="col-md-4">
					<select id="task_timefields" name="smtp" class="chzn-select form-control" data-validation-engine="validate[required]" data-placeholder="{'LBL_SELECT_OPTIONS'|t:$QUALIFIED_MODULE}">
						<option value="">{'LBL_DEFAULT'|t}</option>
						{foreach from=App\Mail::getAll() item=ITEM key=ID}
							<option value="{$ID}" {if $TASK_OBJECT->smtp == $ID}selected{/if}>{$ITEM['name']}({$ITEM['host']})</option>
						{/foreach}	
					</select>
				</div>
			</div>
			<div class="row padding-bottom1per">
				<span class="col-md-4 control-label">{'EmailTempleteList'|t:$QUALIFIED_MODULE}</span>
				<div class="col-md-4">
					<select class="chzn-select form-control" name="template" data-validation-engine="validate[required]">
						<option value="">{'LBL_NONE'|t:$QUALIFIED_MODULE}</option>
						{foreach from=App\Mail::getTempleteList($SOURCE_MODULE,'PLL_RECORD') key=key item=item}
							<option {if $TASK_OBJECT->template eq $item['id']}selected=""{/if} value="{$item['id']}">{$item['name']|t:$QUALIFIED_MODULE}</option>
						{/foreach}	
					</select>
				</div>
			</div>
			<div class="row padding-bottom1per">
				<span class="col-md-4"></span>
				<span class="col-md-4">
					<label><input type="checkbox" class="alignTop" value="true" name="emailoptout" {if $TASK_OBJECT->emailoptout}checked{/if}>&nbsp;{'LBL_CHECK_EMAIL_OPTOUT'|t:$QUALIFIED_MODULE}</label>
				</span>
			</div>
			<div class="row padding-bottom1per">
				{assign var=EMAIL value=settype($TASK_OBJECT->email, 'array')}
				<span class="col-md-4 control-label">{'Select e-mail address'|t:$QUALIFIED_MODULE}</span>
				<div class="col-md-4">
					<select class="chzn-select form-control" name="email" data-placeholder="{'LBL_SELECT_FIELD'|t:$QUALIFIED_MODULE}" multiple  data-validation-engine="validate[required]">
						<option value="none"></option>
						{assign var=TEXT_PARSER value=App\TextParser::getInstance($SOURCE_MODULE)}
						{foreach item=FIELDS key=BLOCK_NAME from=$TEXT_PARSER->getRecordVariable('email')}
							<optgroup label="{$BLOCK_NAME|t:$SOURCE_MODULE}">
								{foreach item=ITEM from=$FIELDS}
									<option value="{$ITEM['var_value']}" data-label="{$ITEM['var_label']}" {if $TASK_OBJECT->email && in_array($ITEM['var_value'],$TASK_OBJECT->email)}selected=""{/if}>
										{$ITEM['label']|t:$SOURCE_MODULE}
									</option>
								{/foreach}
							</optgroup>
						{/foreach}
						{foreach item=FIELDS from=$TEXT_PARSER->getRelatedVariable('email')}
							{foreach item=RELATED_FIELDS key=BLOCK_NAME from=$FIELDS}
								<optgroup label="{$BLOCK_NAME}">
									{foreach item=ITEM from=$RELATED_FIELDS}
										<option value="{$ITEM['var_value']}" data-label="{$ITEM['var_label']}" {if $TASK_OBJECT->email && in_array($ITEM['var_value'],$TASK_OBJECT->email)}selected=""{/if}>
											{$ITEM['label']}
										</option>
									{/foreach}
								</optgroup> 
							{/foreach}
						{/foreach}
					</select>
				</div>
			</div>
			<div class="row padding-bottom1per">
				<span class="col-md-4 control-label">{'LBL_BCC'|t}</span>
				<div class="col-md-4">
					<input class="form-control" data-validation-engine="validate[funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" name="copy_email" value="{$TASK_OBJECT->copy_email}">
				</div>
			</div>
		</div>
	</div>	
<!--/VTEmailTemplateTask.tpl -->
{/strip}	
