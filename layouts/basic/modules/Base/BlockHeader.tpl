{strip}
<!-- layouts/basic/modules/Base/BlockHeader.tpl -->
	<span class="copyAddressLabel control-label">{"COPY_ADRESS_FROM"|t}</span>	
	<button class="btn btn-sm btn-primary copyAddressFromAccount" type="button" data-label="{$BLOCK_LABEL}">
		<strong>{"SINGLE_Accounts"|t:$MODULE}</strong>
	</button>
	<button class="btn btn-sm btn-primary copyAddressFromLead" type="button" data-label="{$BLOCK_LABEL}">
		<strong>{"SINGLE_Leads"|t:$MODULE}</strong>
	</button>
	<button class="btn btn-sm btn-primary copyAddressFromVendor" type="button" data-label="{$BLOCK_LABEL}">
		<strong>{"SINGLE_Vendors"|t:$MODULE}</strong>
	</button>
	{if {$MODULE_NAME} neq 'Contacts'}
		<button class="btn btn-sm btn-primary copyAddressFromContact" type="button" data-label="{$BLOCK_LABEL}">
			<strong>{"SINGLE_Contacts"|t:$MODULE}</strong>
		</button>
	{/if}
	{if $BLOCK_LABEL neq 'LBL_ADDRESS_INFORMATION' && array_key_exists('LBL_ADDRESS_INFORMATION',$RECORD_STRUCTURE) && $RECORD_STRUCTURE['LBL_ADDRESS_INFORMATION']|@count }
		<button class="btn btn-sm btn-primary copyAddressFromMain" type="button" data-label="LBL_ADDRESS_INFORMATION">
			<strong>{"LBL_ADDRESS_INFORMATION"|t:$MODULE}</strong>
		</button>
	{/if}
	{if $BLOCK_LABEL neq 'LBL_ADDRESS_MAILING_INFORMATION' && array_key_exists('LBL_ADDRESS_MAILING_INFORMATION',$RECORD_STRUCTURE) && $RECORD_STRUCTURE['LBL_ADDRESS_MAILING_INFORMATION']|@count}
		<button class="btn btn-sm btn-primary copyAddressFromMailing" type="button" data-label="LBL_ADDRESS_MAILING_INFORMATION">
			<strong>{"LBL_ADDRESS_MAILING_INFORMATION"|t:$MODULE}</strong>
		</button>
	{/if}
	{if $BLOCK_LABEL neq 'LBL_ADDRESS_DELIVERY_INFORMATION' && array_key_exists('LBL_ADDRESS_DELIVERY_INFORMATION',$RECORD_STRUCTURE) && $RECORD_STRUCTURE['LBL_ADDRESS_DELIVERY_INFORMATION']|@count}
		<button class="btn btn-sm btn-primary copyAddressFromDelivery" type="button" data-label="LBL_ADDRESS_DELIVERY_INFORMATION">
			<strong>{"LBL_ADDRESS_DELIVERY_INFORMATION"|t:$MODULE}</strong>
		</button>
	{/if}
<!--/layouts/basic/modules/Base/BlockHeader.tpl -->
{/strip}

