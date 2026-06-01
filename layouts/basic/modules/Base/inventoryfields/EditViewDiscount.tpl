{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/inventoryfields/EditViewDiscount.tpl -->
	{assign var=VALUE value=$FIELD->getValue($ITEM_VALUE)}
	<div>
		<div class="input-group input-group-sm">
			<input type="text" name="discount{$ROW_NO}" value="{$FIELD->getEditValue($VALUE)}" class="discount form-control input-sm" readonly="readonly" />
			{if $DISCOUNTS_CONFIG['discounts'][0] != ''}
				<input name="discountparam{$ROW_NO}" type="hidden" value="{\App\Modules\Base\Helpers\Util::toSafeHTML($INVENTORY_ROW['discountparam'])}" class="discountParam" {if $FIELD->get('displaytype') == 10}readonly="readonly"{/if} />
				<span class="input-group-addon cursorPointer changeDiscount {if $INVENTORY_ROW['discountmode'] == 0}hide{/if}">
					<img src="{vimage_path('Discount24.png')}" alt="{"LBL_DISCOUNT"|t:$MODULE}" />
				</span>
			{/if}
		</div>
	</div>
<!--/layouts/basic/modules/Base/inventoryfields/EditViewDiscount.tpl -->
{/strip}
