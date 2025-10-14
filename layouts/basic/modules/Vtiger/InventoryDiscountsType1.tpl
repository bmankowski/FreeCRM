{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Vtiger/InventoryDiscountsType1.tpl -->
	{if $GROUP_DISCOUNT != 0}
		<div class="panel panel-default">
			<div class="panel-heading">
				<strong>{"LBL_GROUP_DISCOUNTS"|t:$MODULE}</strong>
				<div class="pull-right">
					<input type="{$AGGREGATION_INPUT_TYPE}" name="aggregationType" value="group" class="activeCheckbox">
				</div>
			</div>
			<div class="panel-body" style="display: none;">
				<div>
					<p>
						{"LBL_DISCOUNT_FOR_ACCOUNT"|t:$MODULE} {$ACCOUNT_NAME}
					</p>
					<div class="input-group">
						<span class="input-group-addon">
							<input type="checkbox" name="groupCheckbox" value="on" class="groupCheckbox">
						</span>
						<input type="text" class="form-control groupValue" name="groupDiscount" value="{CurrencyField::convertToUserFormat($GROUP_DISCOUNT, null, true)}" readonly="true">
						<span class="input-group-addon">%</span>
					</div>
				</div>
			</div>
		</div>
	{/if}
<!--/layouts/basic/modules/Vtiger/InventoryDiscountsType1.tpl -->
{/strip}
