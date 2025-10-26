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
<!-- layouts/basic/modules/Base/SendSMSForm.tpl -->
	<div id="sendSmsContainer" class='modelContainer modal fade' tabindex="-1">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header contentsBackground">
					<button data-dismiss="modal" class="close" title="{"LBL_CLOSE"|t}">&times;</button>
					<h3 class="modal-title">{"LBL_SEND_SMS_TO_SELECTED_NUMBERS"|t:$MODULE}</h3>
				</div>
				<form class="form-horizontal" id="massSave" method="post" action="index.php">
					<input type="hidden" name="module" value="{$MODULE}" />
					<input type="hidden" name="source_module" value="{$SOURCE_MODULE}" />
					<input type="hidden" name="action" value="MassSaveAjax" />
					<input type="hidden" name="viewname" value="{$VIEWNAME}" />
					<input type="hidden" name="selected_ids" value={\App\Json::encode($SELECTED_IDS)}>
					<input type="hidden" name="excluded_ids" value={\App\Json::encode($EXCLUDED_IDS)}>
					<input type="hidden" name="search_key" value= "{$SEARCH_KEY}" />
					<input type="hidden" name="operator" value="{$OPERATOR}" />
					<input type="hidden" name="search_value" value="{$ALPHABET_VALUE}" />
					<input type="hidden" name="search_params" value='{\App\Json::encode($SEARCH_PARAMS)}' />

					<div class="modal-body tabbable">
						<div>
							<span><strong>{"LBL_STEP_1"|t:$MODULE}</strong></span>
							&nbsp;:&nbsp;
							{"LBL_SELECT_THE_PHONE_NUMBER_FIELDS_TO_SEND"|t:$MODULE}
						</div>
						<select name="fields[]" data-placeholder="{"LBL_ADD_MORE_FIELDS"|t:$MODULE}" multiple class="chzn-select form-control">
							<optgroup>
								{foreach item=PHONE_FIELD from=$PHONE_FIELDS}
									{if $PHONE_FIELD->isEditable() eq false} {continue} {/if}
									{assign var=PHONE_FIELD_NAME value=$PHONE_FIELD->get('name')}
									<option value="{$PHONE_FIELD_NAME}">
										{if !empty($SINGLE_RECORD)}
											{assign var=FIELD_VALUE value=$SINGLE_RECORD->get($PHONE_FIELD_NAME)}
										{/if}
										{$PHONE_FIELD->get('label')|t:$SOURCE_MODULE}{if !empty($FIELD_VALUE)} ({$FIELD_VALUE}){/if}
									</option>
								{/foreach}
							</optgroup>
						</select>
						<hr>
						<div>
							<span><strong>{"LBL_STEP_2"|t:$MODULE}</strong></span>
							&nbsp;:&nbsp;
							{"LBL_TYPE_THE_MESSAGE"|t:$MODULE}&nbsp;(&nbsp;{"LBL_SMS_MAX_CHARACTERS_ALLOWED"|t:$MODULE}&nbsp;)
						</div>
						<textarea class="input-xxlarge form-control" name="message" id="message" placeholder="{"LBL_WRITE_YOUR_MESSAGE_HERE"|t:$MODULE}"></textarea>
					</div>
					<div class="modal-footer">
						<div class=" pull-right cancelLinkContainer">
							<a class="cancelLink" type="reset" data-dismiss="modal">{"LBL_CANCEL"|t:$MODULE}</a>
						</div>
						<button class="btn btn-success" type="submit" name="saveButton"><strong>{"LBL_SEND"|t:$MODULE}</strong></button>
					</div>
				</form>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Base/SendSMSForm.tpl -->
{/strip}
