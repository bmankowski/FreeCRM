{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/InventoryTaxesType1.tpl -->
	{if $TAX_TYPE == '0' && $TAX_FIELD && $RECORD}
		{assign var=RECORD_MODEL value=\App\Modules\Base\Models\Record::getInstanceById($RECORD)}
		{assign var=SELECTED_TAXES value=\App\Modules\Base\UiTypes\Taxes::getValues($RECORD_MODEL->get($TAX_FIELD))}
		{if count($SELECTED_TAXES) > 0}
			<div class="panel panel-default">
				<div class="panel-heading">
					<strong>{"LBL_GROUP_TAXS"|t:$MODULE}</strong>
					<div class="pull-right">
						<input type="{$AGGREGATION_INPUT_TYPE}" name="aggregationType" value="group" class="activeCheckbox">
					</div>
				</div>
				<div class="panel-body" style="display: none;">
					<div>
						<p>
							{"LBL_TAX_FOR_MODULE"|t:$MODULE} {$RECORD_MODULE|t:$RECORD_MODULE}: {$RECORD_MODEL->getDisplayName()}
						</p>
						<select class="select2 groupTax" name="groupTax">
							{foreach item=TAX from=$SELECTED_TAXES}
								{assign var=VALUE value=CurrencyField::convertToUserFormat($TAX['value'], null, true)}
								<option value="{$VALUE}">
									{$VALUE}% - {{$TAX['name']}|t:$MODULE}
								</option>
							{/foreach}
						</select>
					</div>
				</div>
			</div>
		{/if}
	{/if}			
<!--/layouts/basic/modules/Base/InventoryTaxesType1.tpl -->
{/strip}
