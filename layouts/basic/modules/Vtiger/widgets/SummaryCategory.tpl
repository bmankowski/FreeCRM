{strip}
<!-- layouts/basic/modules/Vtiger/widgets/SummaryCategory.tpl -->
<div class="summaryWidgetContainer recordDetails">
	{if $WIDGET['label'] neq ' ' && $WIDGET['label'] neq ''}
		<div class="widget_header marginBottom10px">
			<span class="margin0px"><h4>{$WIDGET['label']|t:$MODULE_NAME}</h4></span>
		</div>
		<hr class="widgetHr">
	{/if}
	{foreach item=SUMMARY_CATEGORY from=$RECORD->getSummaryInfo()}
		<div class="row textAlignCenter roundedCorners">
			{foreach item=FIELD_VALUE from=$SUMMARY_CATEGORY}
				<div class="col-md-3" data-reference="{$FIELD_VALUE.reference}">
					<div class="well squeezedWell">
						<div>
							<label class="font-x-small">
								{$FIELD_VALUE.name|t:$MODULE_NAME}
							</label>
						</div>
						<div>
							<label class="font-x-x-large">
								{if !empty($FIELD_VALUE.data)}{$FIELD_VALUE.data}{else}0{/if}
							</label>
						</div>
					</div>
				</div>
			{/foreach}
		</div>
	{/foreach}
</div>
<!--/layouts/basic/modules/Vtiger/widgets/SummaryCategory.tpl -->
{/strip}
