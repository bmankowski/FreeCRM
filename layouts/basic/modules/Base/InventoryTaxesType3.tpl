{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/InventoryTaxesType3.tpl -->
	{if $GROUP_TAXS}
		<div class="panel panel-default">
			<div class="panel-heading">
				<strong>{"LBL_REGIONAL_TAX"|t:$MODULE}</strong>
				<div class="pull-right">
					<input type="{$AGGREGATION_INPUT_TYPE}" name="aggregationType" value="account" class="activeCheckbox">
				</div>
			</div>
			<div class="panel-body" style="display: none;">
				<div>
					<p>
						{"LBL_TAX_FOR_ACCOUNT"|t:$MODULE}: {$ACCOUNT_NAME}
					</p>
					<select class="select2 regionalTax" name="regionalTax">
						{foreach item=TAX from=$GROUP_TAXS}
							{assign var=VALUE value=\App\Fields\CurrencyField::convertToUserFormat($TAX['value'], null, true)}
							<option value="{$VALUE}">
								{$VALUE}% - {{$TAX['name']}|t:$MODULE}
							</option>
						{/foreach}
					</select>
				</div>
			</div>
		</div>
	{/if}
<!--/layouts/basic/modules/Base/InventoryTaxesType3.tpl -->
{/strip}
