{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
<div class="form-group">
	<label class="col-md-4 control-label">{"LBL_LABEL_NAME"|t:$QUALIFIED_MODULE}:</label>
	<div class="col-md-7">
		{assign var='LABEL' value=$FIELD_INSTANCE->getDefaultLabel()}
		{if $FIELD_INSTANCE->get('label') }
			{assign var='LABEL' value=$FIELD_INSTANCE->get('label')}
		{/if}
		<input name="label" class="form-control" type="text" value="{$LABEL}" data-validation-engine="validate[required]" />
	</div>
</div>
<div class="form-group">
	<label class="col-md-4 control-label">{"LBL_DISPLAY_TYPE"|t:$QUALIFIED_MODULE}:</label>
	<div class="col-md-7">
		<select class='form-control select2' name="displayType" data-validation-engine="validate[required]">
			{foreach from=$FIELD_INSTANCE->displayTypeBase() item=ITEM key=KEY}
				<option value="{$ITEM}" {if $ITEM eq $FIELD_INSTANCE->get('displaytype')} selected {/if}>{$KEY|t:$QUALIFIED_MODULE}</option>
			{/foreach}
		</select>
	</div>
</div>
<div class="form-group">
	<label class="col-md-4 control-label">{"LBL_COLSPAN"|t:$QUALIFIED_MODULE}:</label>
	<div class="col-md-7">
		<input name="colSpan" class="form-control" type="text" value="{$FIELD_INSTANCE->getColSpan()}" data-validation-engine="validate[required]" />
	</div>
</div>
{if $FIELD_INSTANCE->getParams()}
	<div class="paramsJson">
		<input id="params" class="" type="hidden" value='{\App\Json::encode($FIELD_INSTANCE->getParams())}'/>
		{assign var='PARAMS' value=\App\Json::decode($FIELD_INSTANCE->get('params'))}
		{foreach from=$FIELD_INSTANCE->getParams() item=ITEM key=KEY}
			<div class="form-group paramsJson">
				<label class="col-md-4 control-label">{'LBL_PARAMS_'|cat:strtoupper($ITEM)|t:$QUALIFIED_MODULE}
					{if $ITEM eq 'limit'}
						{assign var="GROSS_PRICE" value=Vtiger_InventoryField_Model::getFieldInstance($MODULE, 'GrossPrice')}
						<a href="#" class="HelpInfoPopover" data-placement="top" data-content="{"LBL_PARAMS_LIMIT_CONDITIONS"|t:$QUALIFIED_MODULE}: {$GROSS_PRICE->getDefaultLabel()|t:$QUALIFIED_MODULE}">
							<span class="glyphicon glyphicon-info-sign"></span>
						</a>
					{/if}:</label>
					{assign var='functionName' value=$ITEM|cat:'Values'}
				<div class="col-md-7">
					<select class="form-control select2" name="{$ITEM}" {if $ITEM eq 'modules'} data-validation-engine="validate[required]" multiple {/if}>
						{foreach from=$FIELD_INSTANCE->$functionName() item=ITEMS key=KEY}
							{assign var='CONDITION' value=0}
							{if $PARAMS[$ITEM]|is_array && in_array($ITEMS.id,$PARAMS[$ITEM])}
								{assign var='CONDITION' value=1}
							{elseif !($PARAMS[$ITEM]|is_array) && $ITEMS.id eq $PARAMS[$ITEM]}
								{assign var='CONDITION' value=1}
							{/if}
							<option value="{$ITEMS['id']}" {if $CONDITION}selected{/if}>{$ITEMS['name']|t:$ITEMS['module']}</option>
						{/foreach}
					</select>
				</div>
			</div>
		{/foreach}
	</div>
{/if}
