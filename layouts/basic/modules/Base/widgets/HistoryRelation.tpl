{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/widgets/HistoryRelation.tpl -->
	<div class="summaryWidgetContainer">
		<div class="widgetContainer_{$key} widgetContentBlock" data-url="{$WIDGET['url']}" data-name="{$WIDGET['label']}" data-type="{$WIDGET['type']}">
			<div class="widget_header">
				<div class="widgetTitle row">
					<div class="col-xs-4">
						<h4 class="moduleColor_{$WIDGET['label']}">{$WIDGET['label']|t:$MODULE_NAME}</h4>
					</div>
					<div class="col-xs-7">
						<select class="select2 relatedHistoryTypes" multiple>
							{foreach from=$WIDGET['data']['historyActions'] item=ACTIONS}
								<option selected value="{$ACTIONS}">{$ACTIONS|t:$ACTIONS}</option>	
							{/foreach}
						</select>
					</div>
					<div class="col-xs-1 text-right">
						<button type="button" title="{'LBL_FULLSCREEN'|t}" data-title="{$WIDGET['label']|t:$MODULE_NAME}" class="widgetFullscreen btn btn-sm btn-default">
							<span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
						</button>
					</div>	
				</div>
			</div>
			<hr class="widgetHr">
			<div class="widget_contents widgetContent">
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Base/widgets/HistoryRelation.tpl -->
{/strip}
