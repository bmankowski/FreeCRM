{strip}
<!-- layouts/basic/modules/Settings/Workflows/Tasks/VTSendPdf.tpl -->
	{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
	<div id="VtVTEmailTemplateTaskContainer">
		<div class="row">
			<span class="col-md-4 control-label">{'LBL_SEND_PDF_TEMPLATE'|t:$QUALIFIED_MODULE}</span>
			<div class="col-md-4 padding-bottom1per">
			<select class="chzn-select form-control" name="pdfTemplate" data-validation-engine="validate[required]">
				<option value="none">{'LBL_SELECT_FIELD'|t:$MODULE}</option>
				{foreach from=$PDF_TEMPLATES item=item}
					<option {if $TASK_OBJECT->pdfTemplate eq $item->getId()}selected{/if} value="{$item->getId()}">{$item->getName()}</option>
				{/foreach}
			</select>
			</div>
		</div>
		<div class="row padding-bottom1per">
			<span class="col-md-4 control-label">{'LBL_SMTP'|t:$QUALIFIED_MODULE} {'LBL_SMTP'|t:$QUALIFIED_MODULE}</span>
			<div class="col-md-4">
			<select id="task_timefields" name="smtp" class="chzn-select form-control " data-placeholder="{'LBL_SELECT_OPTIONS'|t:$QUALIFIED_MODULE}">
				<option value="">{'LBL_DEFAULT'|t}</option>
				{foreach from=$SMTP_ACCOUNTS item=ITEM key=ID}
					<option value="{$ID}" {if $TASK_OBJECT->smtp == $ID}selected{/if}>{$ITEM['name']}({$ITEM['host']})</option>
				{/foreach}	
			</select>
			</div>
		</div>
		<div class="row padding-bottom1per">
			<span class="col-md-4 control-label">{'EmailTempleteList'|t:$QUALIFIED_MODULE}</span>
			<div class="col-md-4">
			<select class="chzn-select form-control" name="mailTemplate" data-validation-engine='validate[required]'>
				<option value="">{'LBL_NONE'|t:$QUALIFIED_MODULE}</option>
				{foreach from=$EMAIL_TEMPLATES key=key item=item}
					<option {if $TASK_OBJECT->mailTemplate eq $item['id']}selected=""{/if} value="{$item['id']}">{$item['name']|t:$QUALIFIED_MODULE}</option>
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
			<span class="col-md-4 control-label">{'Select e-mail address'|t:$QUALIFIED_MODULE}</span>
			<div class="col-md-4">
				<select class="chzn-select form-control" name="email" data-placeholder="{'LBL_SELECT_FIELD'|t:$QUALIFIED_MODULE}" multiple  data-validation-engine="validate[required]">
					<option value="none"></option>
					{assign var=TEXT_PARSER value=$TEXT_PARSER}
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
	</div>
<!--/layouts/basic/modules/Settings/Workflows/Tasks/VTSendPdf.tpl -->
{/strip}
