{strip}
<!-- layouts/basic/modules/Vtiger/widgets/WYSIWYG.tpl -->
	<div class="summaryWidgetContainer">
		<div class="widget_header row">
			<span class="col-md-5 margin0px"><h4>{$WIDGET['label']|t:$MODULE_NAME}</h4></span>
		</div>
		<div class="defaultMarginP">
			{assign var=FULL_TEXT value=vtlib\Functions::removeHtmlTags(array('link', 'style', 'img', 'script', 'base'),decode_html($RECORD->get($WIDGET['data']['field_name'])))}
			<div class="moreContent table-responsive">
				<span class="teaserContent">
					{\App\Modules\Vtiger\Helpers\Util::toVtiger6SafeHTML($FULL_TEXT)|substr:0:600}
				</span>
				{if $FULL_TEXT|strlen > 600}
					<span class="fullContent hide">
						{$FULL_TEXT}
					</span>
					<button type="button" class="btn btn-info btn-xs moreBtn" data-on="{"LBL_MORE_BTN"|t}" data-off="{"LBL_HIDE_BTN"|t}">{"LBL_MORE_BTN"|t}</button>
				{/if}
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Vtiger/widgets/WYSIWYG.tpl -->
{/strip}
