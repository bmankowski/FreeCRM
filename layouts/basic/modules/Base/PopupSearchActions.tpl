{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} -->*}
{strip}
<!-- layouts/basic/modules/Base/PopupSearchActions.tpl -->
	<div class="col-md-2 form-group pull-left">
		{if $MULTI_SELECT}
			{if !empty($LISTVIEW_ENTRIES)}<button class="select btn btn-default"><strong>{"LBL_SELECT"|t:$MODULE}</strong></button>&nbsp;{/if}
		{/if}
	</div>
	{if $SWITCH && !empty($RELATED_PARENT_ID) && !empty($LISTVIEW_ENTRIES)}
		<div class="col-md-4 form-group pull-left">
			<div class="btn-group">
				<input class="switchPopup switchBtn" type="checkbox" checked title="{"LBL_POPUP_SWITCH_BUTTON"|t:$MODULE}" data-size="normal" data-label-width="5" data-on-text="{$POPUP_SWITCH_ON_TEXT|default:''}" data-off-text="{"LBL_ALL"|t:$MODULE}" data-on-val="{$RELATED_PARENT_ID|default:''}" data-off-val="0" data-field="relatedParentId">
			</div>
			<div class="btn-group">
				&nbsp;<a href="#" class="popoverTooltip pull-right-xs pull-right-sm pull-right" title="" data-placement="auto bottom" data-content="{"LBL_POPUP_NARROW_DOWN_RECORDS_LIST"|t:$MODULE}" data-original-title="{"LBL_POPUP_SWITCH_BUTTON"|t:$MODULE}"><span class="glyphicon glyphicon-info-sign"></span></a>
			</div>
		</div>
	{/if}
<!--/layouts/basic/modules/Base/PopupSearchActions.tpl -->
{/strip}
