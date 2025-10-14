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
<!-- layouts/basic/modules/Vtiger/FieldExpressions.tpl -->
	<div class="popupUi modal fade" data-backdrop="false">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" aria-hidden="true" data-close-modal="modal">×</button>
					<h3 class="modal-title">{"LBL_SET_VALUE"|t:$QUALIFIED_MODULE}</h3>
				</div>
				<div class="modal-body">
					<div class="row">
						<span class="col-md-4">
							<select class="textType form-control">
								<optgroup>
									<option data-ui="textarea" value="rawtext">{"LBL_RAW_TEXT"|t:$QUALIFIED_MODULE}</option>
									<option data-ui="textarea" value="fieldname">{"LBL_FIELD_NAME"|t:$QUALIFIED_MODULE}</option>
									<option data-ui="textarea" value="expression">{"LBL_EXPRESSION"|t:$QUALIFIED_MODULE}</option>
								</optgroup>	
							</select>
						</span>
						<span class="col-md-4 hide useFieldContainer">
							<span name="{$MODULE_MODEL->get('name')}" class="useFieldElement">
								{assign var=MODULE_FIELDS value=$MODULE_MODEL->getFields()}
								<select class="useField form-control" data-placeholder="{"LBL_USE_FIELD"|t:$QUALIFIED_MODULE}">
									<option></option>
									<optgroup>
										{foreach from=$MODULE_FIELDS item=MODULE_FIELD}
											<option value="{$MODULE_FIELD->getName()}">{vtranslate($MODULE_FIELD->get('label'),$MODULE_MODEL->get('name'))}</option>
										{/foreach}
									</optgroup>
								</select>
							</span>
							{if $RELATED_MODULE_MODEL neq ''}
								<span name="{$RELATED_MODULE_MODEL->get('name')}" class="useFieldElement">
									{assign var=MODULE_FIELDS value=$RELATED_MODULE_MODEL->getFields()}
									<select class="useField form-control" data-placeholder="{"LBL_USE_FIELD"|t:$QUALIFIED_MODULE}">
										<option></option>
										<optgroup>
											{foreach from=$MODULE_FIELDS item=MODULE_FIELD}
												<option value="{$MODULE_FIELD->getName()}">{vtranslate($MODULE_FIELD->get('label'),$QUALIFIED_MODULE)}</option>
											{/foreach}
										</optgroup>
									</select>
								</span>
							{/if}
						</span>
						<span class="col-md-4 hide useFunctionContainer">
							<select class="useFunction form-control" data-placeholder="{"LBL_USE_FUNCTION"|t:$QUALIFIED_MODULE}">
								<option></option>
								<optgroup>
									{foreach from=$FIELD_EXPRESSIONS key=FIELD_EXPRESSION_VALUE item=FIELD_EXPRESSIONS_KEY}
										<option value="{$FIELD_EXPRESSIONS_KEY}">{vtranslate($FIELD_EXPRESSION_VALUE,$QUALIFIED_MODULE)}</option>
									{/foreach}
								</optgroup>
							</select>
						</span>
					</div><br>
					<div class="fieldValueContainer">
						<textarea data-textarea="true" class="fieldValue form-control"></textarea>
					</div><br>
					<div id="rawtext_help" class="alert alert-info helpmessagebox hide">
						<p><h5>{"LBL_RAW_TEXT"|t:$QUALIFIED_MODULE}</h5></p>
						<p>2000</p>
						<p>{"LBL_VTIGER"|t:$QUALIFIED_MODULE}</p>
					</div>
					<div id="fieldname_help" class="helpmessagebox alert alert-info hide">
						<p><h5>{"LBL_EXAMPLE_FIELD_NAME"|t:$QUALIFIED_MODULE}</h5></p>
						<p>{"LBL_ANNUAL_REVENUE"|t:$QUALIFIED_MODULE}</p>
						<p>{"LBL_NOTIFY_OWNER"|t:$QUALIFIED_MODULE}</p>
					</div>
					<div id="expression_help" class="alert alert-info helpmessagebox hide">
						<p><h5>{"LBL_EXAMPLE_EXPRESSION"|t:$QUALIFIED_MODULE}</h5></p>
						<p>{"LBL_ANNUAL_REVENUE"|t:$QUALIFIED_MODULE}/12</p>
						<p>{"LBL_EXPRESSION_EXAMPLE2"|t:$QUALIFIED_MODULE}</p>
					</div>
				</div>
				<div class="modal-footer">
					<button class="btn btn-success" type="button" name="saveButton"><strong>{"LBL_SAVE"|t:$MODULE}</strong></button>&nbsp;&nbsp;
					<button class="btn btn-warning cancelLink" type="button" data-close-modal="modal">{"LBL_CANCEL"|t:$MODULE}</button>
				</div>
			</div>
		</div>
	</div>
	<div class="clonedPopUp"></div>
<!--/layouts/basic/modules/Vtiger/FieldExpressions.tpl -->
{/strip}
