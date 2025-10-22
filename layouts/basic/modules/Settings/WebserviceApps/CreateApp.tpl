{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/WebserviceApps/CreateApp.tpl -->
	<div class="modal-content validationEngineContainer" id="EditView">
		<form>
			<input class="recordEditView" type="hidden">
			<input type="hidden" name="mappingRelatedField" value="{\App\Modules\Vtiger\Helpers\Util::toSafeHTML($MAPPING_RELATED_FIELD)}"/>
			<div class="modal-header row no-margin">
				<div class="col-xs-12 paddingLRZero">
					<div class="col-xs-8 paddingLRZero">
						{if $RECORD_MODEL}
							<h4>{"LBL_TITLE_EDIT"|t:$QUALIFIED_MODULE}</h4>
						{else}
							<h4>{"LBL_TITLE_ADDED"|t:$QUALIFIED_MODULE}</h4>
						{/if}
					</div>
					<div class="pull-right">
						<button class="btn btn-warning marginLeft10" type="button" data-dismiss="modal" aria-label="Close" aria-hidden="true">&times;</button>
					</div>
				</div>
			</div>
			<div class="modal-body row">
				<div class="col-xs-12 marginBottom10px">
					<div class="col-xs-4 fieldLabel">
						<span class="redColor">*</span>{"LBL_APP_NAME"|t:$QUALIFIED_MODULE}
					</div>
					<div class="col-xs-8">
						<input type="text" name="name" data-validation-engine="validate[required]" value="{if $RECORD_MODEL}{$RECORD_MODEL->getName()}{/if}" class="form-control">
					</div>
				</div>
				<div class="col-xs-12 marginBottom10px">
					<div class="col-xs-4 fieldLabel">
						{"LBL_ADDRESS_URL"|t:$QUALIFIED_MODULE}
					</div>
					<div class="col-xs-8">
						<input type="text" name="addressUrl" value="{if $RECORD_MODEL}{$RECORD_MODEL->get('acceptable_url')}{/if}" class="form-control">
					</div>
				</div>
				<div class="col-xs-12 marginBottom10px">
					<div class="col-xs-4 fieldLabel">
						<span class="redColor">*</span>{"LBL_PASS"|t:$QUALIFIED_MODULE}
					</div>
					<div class="col-xs-8">
						<input type="text" name="pass" data-validation-engine="validate[required]" value="{if $RECORD_MODEL}{$RECORD_MODEL->get('pass')}{/if}" class="form-control">
					</div>
				</div>
				<div class="col-xs-12 marginBottom10px">
					<div class="col-xs-4 fieldLabel">
						{"Status"|t:$QUALIFIED_MODULE}
					</div>
					<div class="col-xs-8">
						<input type="checkbox" {if $RECORD_MODEL && $RECORD_MODEL->get('status') eq 1}checked{/if} name="status">
					</div>
				</div>
				<div class="col-xs-12 marginBottom10px">
					<div class="col-xs-4 fieldLabel">
						{"LBL_TYPE_SERVER"|t:$QUALIFIED_MODULE}
					</div>
					<div class="col-xs-8">
						<select class="select2 typeServer" {if $RECORD_MODEL} disabled {/if}>
							{foreach from=$TYPES_SERVERS item=TYPE}
								<option value="{$TYPE}"
										{if $RECORD_MODEL && $TYPE eq  $RECORD_MODEL->get('type')}
											selected	
										{/if}
										>
									{$TYPE}
								</option>
							{/foreach}
						</select>
					</div>
				</div>
				<div class="col-xs-12 marginBottom10px">
					<div class="col-xs-4 fieldLabel">
						{"SINGLE_Accounts"|t:$QUALIFIED_MODULE}
					</div>
					<div class="col-xs-8">
						<div class="fieldValue">
							<input name="popupReferenceModule" type="hidden" 
								   data-multi-reference="0" title="{"Accounts"|t:$QUALIFIED_MODULE}" 
								   value="Accounts">
							<input name="accountsid" type="hidden" value="{if $RECORD_MODEL}{$RECORD_MODEL->get('accounts_id')}{/if}"
								   title="" class="sourceField" data-fieldtype="reference" 
								   data-displayvalue="">
							<div class="input-group referenceGroup">
								<input id="accountsid_display" name="accountsid_display" type="text" title=""
									   class="marginLeftZero form-control autoComplete ui-autocomplete-input" 
									   value="{if $RECORD_MODEL && $RECORD_MODEL->get('accountsModel')}{$RECORD_MODEL->get('accountsModel')->getName()}{/if}"
									   {if $RECORD_MODEL} readonly {/if}
									   data-validation-engine="validate[funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
									   placeholder="{"LBL_TYPE_SEARCH"|t:$QUALIFIED_MODULE}" 
									   autocomplete="off">
								<span class="input-group-btn cursorPointer">
									<button class="btn btn-default clearReferenceSelection" type="button">
										<span class="glyphicon glyphicon-remove-sign" 
											  title="{"LBL_CLEAR"|t:$QUALIFIED_MODULE}"></span>
									</button>
									<button class="btn btn-default relatedPopup" type="button">
										<span class="glyphicon glyphicon-search" 
											title="{"LBL_SELECT"|t:$QUALIFIED_MODULE}"></span>
									</button>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>
		{include file='ModalFooter.tpl'|@vtemplate_path}
	</div>
<!--/layouts/basic/modules/Settings/WebserviceApps/CreateApp.tpl -->
{/strip}
