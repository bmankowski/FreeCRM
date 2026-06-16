{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/dashboards/DashBoardButtons.tpl -->
	<div class="dashboardHeading col-xs-3 col-sm-8 col-md-6">
		<input type="hidden" name="selectedModuleName" value="{$MODULE_NAME}">
		<div class="marginLeftZero">
			<div class="pull-right">
				<div class="btn-toolbar">
					<div class="btn-group">
						{assign var="SPECIAL_WIDGETS" value=\App\Modules\Settings\WidgetsManagement\Models\Module::getSpecialWidgets('Home')}
						{assign var="DASHBOARD_JS" value="Vtiger_DashBoard_Js"}
						{if $MODULE_NAME eq 'Home'}
							{assign var="DASHBOARD_JS" value="Home_Index_Js"}
						{/if}
						{if $HEADER_WIDGETS|count gt 0}
							<button class="btn btn-default addButton dropdown-toggle" data-toggle="dropdown">
								<p class="hidden-xs no-margin">
									<strong>{"LBL_ADD_WIDGET"|t}</strong>
									<span class="caret"></span>
								</p>
								<span class="glyphicon glyphicon-th visible-xs-block"></span>
							</button>
							<ul class="dropdown-menu widgetsList pull-left addWidgetDropDown" style="min-width:100%;text-align:left;">
								<li class="visible-xs-block">
									<a href="#" class="addFilter" data-linkid="{$SPECIAL_WIDGETS['Mini List']->get('linkid')}" data-block-id="0" data-width="4" data-height="3">
										{"LBL_ADD_FILTER"|t}
									</a>
								</li>
								{assign var="WIDGET" value=""}
								{foreach from=$HEADER_WIDGETS item=WIDGET}
									<li><a class="pull-left" onclick="{$DASHBOARD_JS}.addWidget(this, '{$WIDGET->getUrl()}')" href="javascript:void(0);"
										   data-linkid="{$WIDGET->get('linkid')}" data-name="{$WIDGET->getName()}" data-width="{$WIDGET->getWidth()}" data-height="{$WIDGET->getHeight()}" data-id="{$WIDGET->get('widgetid')}">
											{$WIDGET->getTitle()|t:$MODULE_NAME} </a>
										{if $WIDGET->get('deleteFromList')}
											<button data-widget-id="{$WIDGET->get('widgetid')}" class="removeWidgetFromList btn btn-xs btn-danger pull-right">
												<span class='glyphicon glyphicon-trash'></span>
											</button>
										{/if}
									</li>
								{/foreach}
							</ul>
						{else if $MODULE_PERMISSION}
							<button class="btn btn-default addButton dropdown-toggle" data-toggle="dropdown">
								<strong class="hidden-xs">{"LBL_ADD_WIDGET"|t}</strong>
								<span class="hidden-xs caret"></span>
								<span class="glyphicon glyphicon-th visible-xs-block"></span>
							</button>
							<ul class="dropdown-menu widgetsList pull-left" style="min-width:100%;text-align:left;">
								<li class="visible-xs-block">
									<a href="#" class="addFilter" data-linkid="{$SPECIAL_WIDGETS['Mini List']->get('linkid')}" data-block-id="0" data-width="4" data-height="3">
										{"LBL_ADD_FILTER"|t}
									</a>
								</li>
								<li class="hidden-xs">
									<a href="#">
										{"LBL_NONE"|t}
									</a>
								</li>
							</ul>
						{/if}
					</div>
					{if $MODULE_MODEL && $USER_PRIVILEGES_MODEL->hasModuleActionPermission($MODULE_MODEL->getId(),'CreateDashboardFilter')}
						<div class="btn-group hidden-xs">
							<a class="btn btn-default addFilter" data-linkid="{$SPECIAL_WIDGETS['Mini List']->get('linkid')}" data-block-id="0" data-width="4" data-height="4">
								<strong>{"LBL_ADD_FILTER"|t}</strong>
							</a>
						</div>
					{/if}
					{if $MODULE_MODEL && $USER_PRIVILEGES_MODEL->hasModuleActionPermission($MODULE_MODEL->getId(),'CreateDashboardChartFilter')}
						<div class="btn-group hidden-xs">
							<a class="btn btn-default addChartFilter" data-linkid="{$SPECIAL_WIDGETS['ChartFilter']->get('linkid')}" data-block-id="0" data-width="4" data-height="4">
								<strong>{"LBL_ADD_CHART_FILTER"|t}</strong>
							</a>
						</div>
					{/if}
				</div>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Base/dashboards/DashBoardButtons.tpl -->
{/strip}
