{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} -->*}
{strip}
<!-- layouts/basic/modules/Base/dashboards/ProductsSoldToRenew.tpl -->
{assign var=ACCESSIBLE_USERS value=\App\Fields\Owner::getInstance()->getAccessibleUsers()}
{assign var=ACCESSIBLE_GROUPS value=\App\Fields\Owner::getInstance()->getAccessibleGroups()}
{assign var=CURRENTUSERID value=$CURRENTUSER->getId()}
<div class="dashboardWidgetHeader">
	<div class="row">
		<div class="col-md-8">
			<div class="dashboardTitle" title="{$WIDGET->getTitle()|t}"><strong>{$WIDGET->getTitle()|t}</strong></div>
		</div>
		<div class="col-md-4">
			<div class="box pull-right">
				{include file="dashboards/DashboardHeaderIcons.tpl"|@vtemplate_path:$MODULE_NAME}
			</div>
		</div>
	</div>
	<hr class="widgetHr"/>
	<div class="row" >
		<div class="col-md-12">
			{if $LISTVIEWLINKS}
				<div class="pull-right">&nbsp;
					<button class="btn btn-default btn-sm goToListView" data-url="{$WIDGET_MODEL->getTargetModuleModel()->getListViewUrl()}" title="{"LBL_GO_TO_RECORDS_LIST"|t:$MODULE_NAME}" >
						<span class="glyphicon glyphicon-th-list"></span>
					</button>
				</div>
			{/if}
			<div class="pull-right">&nbsp;
				<button class="btn btn-default btn-sm changeRecordSort" title="{"LBL_SORT_DESCENDING"|t:$MODULE_NAME}" alt="{"LBL_SORT_DESCENDING"|t:$MODULE_NAME}" data-sort="{if $DATA['sortorder'] eq 'desc'}asc{else}desc{/if}" data-asc="{"LBL_SORT_ASCENDING"|t:$MODULE_NAME}" data-desc="{"LBL_SORT_DESCENDING"|t:$MODULE_NAME}">
					<span class="glyphicon glyphicon-sort-by-attributes" aria-hidden="true" ></span>
				</button>
			</div>
			<div class="pull-right">
				<select class="widgetFilter form-control orderby input-sm" name="orderby" title="{"LBL_CUSTOM_FILTER"|t}">
					{foreach item=FIELD from=$WIDGET_MODEL->getHeaders()}
						{assign var="FIELD_VALUE" value=$FIELD->get('name')}
						<option value="{$FIELD_VALUE}" {if $DATA['orderby'] eq $FIELD_VALUE} selected {/if}>{$FIELD->get('label')|t:$BASE_MODULE}</option>
					{/foreach}
				</select>
			</div>
		</div>
	</div>
</div>

<div class="dashboardWidgetContent">
	{include file="dashboards/ProductsSoldToRenewContents.tpl"|@vtemplate_path:$MODULE_NAME}
</div>
<!--/layouts/basic/modules/Base/dashboards/ProductsSoldToRenew.tpl -->
{/strip}
