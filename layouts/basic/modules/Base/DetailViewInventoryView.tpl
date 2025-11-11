{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/DetailViewInventoryView.tpl -->
	{* All data is now prepared in controller - no function calls in template *}
	{if count($FIELDS) neq 0}
		{if count($FIELDS[0]) neq 0}
			<table class="table table-bordered inventoryHeader blockContainer">
				<thead>
					<tr>
						<th style="width: 40%;"></th>
						{foreach item=FIELD from=$FIELDS[0]}
							<th>
								<span class="inventoryLineItemHeader">{$FIELD->get('label')|t:$MODULE_NAME}:</span>&nbsp;
								{assign var="FIELD_TPL_NAME" value="inventoryfields/"|cat:$FIELD->getTemplateName('DetailView',$MODULE_NAME)}
								{include file=$FIELD_TPL_NAME|@vtemplate_path:$MODULE_NAME ITEM_VALUE=$INVENTORY_ROWS[0][$FIELD->get('columnname')]}
							</th>
						{/foreach}
					</tr>
				</thead>
			</table>
		{/if}
		{* FIELDS_TEXT_ALIGN_RIGHT is now prepared in controller *}
		<table class="table blockContainer inventoryItems">
			<thead>
				<tr>
					{foreach item=FIELD from=$FIELDS[1]}
						<th {if $FIELD->get('colspan') neq 0 } style="width: {$FIELD->get('colspan')}%" {/if} class="textAlignCenter">
							{$FIELD->get('label')|t:$MODULE_NAME}
						</th>
					{/foreach}
				</tr>
			</thead>
			<tbody>
				{foreach key=KEY item=INVENTORY_ROW from=$INVENTORY_ROWS}
					{assign var="ROW_NO" value=$KEY+1}
					{* ROW_MODULE is now pre-calculated in controller *}
					{if isset($INVENTORY_ROW_MODULES[$KEY])}
						{assign var="ROW_MODULE" value=$INVENTORY_ROW_MODULES[$KEY]}
					{/if}
					<tr>
						{foreach item=FIELD from=$FIELDS[1]}
							<td {if in_array($FIELD->getName(), $FIELDS_TEXT_ALIGN_RIGHT)}class="textAlignRight"{/if}>
								{assign var="FIELD_TPL_NAME" value="inventoryfields/"|cat:$FIELD->getTemplateName('DetailView',$MODULE_NAME)}
								{include file=$FIELD_TPL_NAME|@vtemplate_path:$MODULE_NAME ITEM_VALUE=$INVENTORY_ROW[$FIELD->get('columnname')]}
							</td>
						{/foreach}
					</tr>
				{/foreach}
			</tbody>
			<tfoot>
				<tr>
					{foreach item=FIELD from=$FIELDS[1]}
						<td {if $FIELD->get('colspan') neq 0 } style="width: {$FIELD->get('colspan')}%" {/if}  class="col{$FIELD->getName()} textAlignRight {if !$FIELD->isSummary()}hideTd{else}wisableTd{/if}" data-sumfield="{lcfirst($FIELD->get('invtype'))}">
							{if $FIELD->isSummary()}
								{$INVENTORY_SUMMARY_VALUES[$FIELD->getName()]}
							{/if}
						</td>
					{/foreach}
				</tr>
			</tfoot>
		</table>
		{include file='DetailViewInventorySummary.tpl'|@vtemplate_path:$MODULE_NAME}
	{/if}
<!--/layouts/basic/modules/Base/DetailViewInventoryView.tpl -->
{/strip}
