{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/EditViewInventory.tpl -->
	{* All data is now prepared in controller - no function calls in template *}
	{if count($FIELDS) neq 0}
		<input type="hidden" class="aggregationTypeDiscount" value="{$DISCOUNTS_CONFIG['aggregation']}">
		<input type="hidden" class="aggregationTypeTax" value="{$TAXS_CONFIG['aggregation']}">
		<input name="inventoryItemsNo" id="inventoryItemsNo" type="hidden" value="{if $INVENTORY_ITEMS_NO}{$INVENTORY_ITEMS_NO}{else}1{/if}" />
		<input id="accountReferenceField" type="hidden" value="{$INVENTORY_FIELD->getReferenceField()}" />
		<input id="inventoryLimit" type="hidden" value="{$MAIN_PARAMS['limit']}" />
		<div class="table-responsive">
			<table class="table table-bordered inventoryHeader blockContainer">
				<thead>
					<tr data-rownumber="0">
						<th class="btn-toolbar">
							{if isset($MAIN_PARAMS['modules']) && is_array($MAIN_PARAMS['modules'])}
								{foreach item=MAIN_MODULE from=$MAIN_PARAMS['modules']}
									{if isset($INVENTORY_CRM_ENTITIES[$MAIN_MODULE]) && isset($INVENTORY_WYSIWYG_TYPES[$MAIN_MODULE])}
										{assign var="CRMENTITY" value=$INVENTORY_CRM_ENTITIES[$MAIN_MODULE]}
										<span class="btn-group">
											<button type="button" data-module="{$MAIN_MODULE}" data-field="{$CRMENTITY->table_index}" 
													data-wysiwyg="{$INVENTORY_WYSIWYG_TYPES[$MAIN_MODULE]}" class="btn btn-default addItem">
												<span class="glyphicon glyphicon-plus"></span>&nbsp;<strong>{"LBL_ADD"|t:$MODULE} {'SINGLE_'|cat:$MAIN_MODULE|t:$MAIN_MODULE}</strong>
											</button>
										</span>
									{/if}
								{/foreach}
							{/if}
						</th>
						{foreach item=FIELD from=$FIELDS[0]}
							<th {if !$FIELD->isEditable()}class="hide"{/if}>
								<span class="inventoryLineItemHeader">{$FIELD->get('label')|t:$MODULE}</span>&nbsp;&nbsp;
								{assign var="FIELD_TPL_NAME" value="inventoryfields/"|cat:$FIELD->getTemplateName('EditView',$MODULE)}
								{assign var="FIRST_ROW_VALUE" value=""}
								{if count($INVENTORY_ROWS) > 0 && isset($INVENTORY_ROWS[0][$FIELD->get('columnname')])}
									{assign var="FIRST_ROW_VALUE" value=$INVENTORY_ROWS[0][$FIELD->get('columnname')]}
								{/if}
								{include file=$FIELD_TPL_NAME|@vtemplate_path:$MODULE ITEM_VALUE=$FIRST_ROW_VALUE}
							</th>
						{/foreach}
					</tr>
				</thead>
			</table>
		</div>
		<div class="table-responsive">
			<table class="table blockContainer inventoryItems">
				{if count($FIELDS[1]) neq 0}
					<thead>
						<tr>
							<th style="width: 5%;">&nbsp;&nbsp;</th>
							{foreach item=FIELD from=$FIELDS[1]}
								<th {if $FIELD->get('colspan') neq 0 } style="width: {$FIELD->get('colspan') * 0.95}%"{/if} class="col{$FIELD->getName()} {if !$FIELD->isEditable()} hide{/if} textAlignCenter">
									{$FIELD->get('label')|t:$MODULE}
								</th>
							{/foreach}
						</tr>
					</thead>
				{/if}
				<tbody>
					{foreach key=KEY item=INVENTORY_ROW from=$INVENTORY_ROWS}
						{assign var="ROW_NO" value=$KEY+1}
						{include file='EditViewInventoryItem.tpl'|@vtemplate_path:$MODULE INVENTORY_ROW=$INVENTORY_ROW}
					{foreachelse}
						{assign var="KEY" value=0}
						{assign var="ROW_NO" value=1}
						{include file='EditViewInventoryItem.tpl'|@vtemplate_path:$MODULE INVENTORY_ROW=$DEFAULT_ITEM_DATA}
					{/foreach}
				</tbody>
				<tfoot>
					<tr>
						<td colspan="1" class="hideTd" style="min-width: 50px">&nbsp;&nbsp;</td>
						{foreach item=FIELD from=$FIELDS[1]}
							<td colspan="1" class="col{$FIELD->getName()}{if !$FIELD->isEditable()} hide{/if} textAlignRight 
								{if !$FIELD->isSummary()} hideTd{else} wisableTd{/if}" data-sumfield="{lcfirst($FIELD->get('invtype'))}">
								{if $FIELD->isSummary() && isset($INVENTORY_SUMMARY_VALUES[$FIELD->getName()])}
									{$INVENTORY_SUMMARY_VALUES[$FIELD->getName()]}
								{/if}
								{if $FIELD->getName() == 'Name' && isset($COLUMNS) && is_array($COLUMNS) && in_array("price",$COLUMNS)}
									{"LBL_SUMMARY"|t:$MODULE}
								{/if}
							</td>
						{/foreach}
					</tr>
				</tfoot>
			</table>
		</div>
		{include file='EditViewInventorySummary.tpl'|@vtemplate_path:$MODULE}
		<table id="blackIthemTable" class="noValidate hide">
			<tbody>
				{assign var="ROW_NO" value='_NUM_'}
				{include file='EditViewInventoryItem.tpl'|@vtemplate_path:$MODULE INVENTORY_ROW=$DEFAULT_ITEM_DATA}
			</tbody>
		</table>
		<br/>
	{/if}
<!--/layouts/basic/modules/Base/EditViewInventory.tpl -->
{/strip}
