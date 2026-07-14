{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Base/DetailViewProductsServicesContents.tpl -->
<div>
	{* Summary View Products Widget*}
	{if $ACTIVE_MODULES.Products}
		<div class="summaryWidgetContainer">
			<div class="widgetContainer_products hideActionImages" data-url="module={$MODULE_NAME}&view=Detail&record={$RECORDID}&relatedModule=Products&mode=showRelatedRecords&page=1&limit={$LIMIT}" data-name="LBL_RELATED_PRODUCTS">
				<div class="widget_header row">
					<input type="hidden" name="relatedModule" value="Products" />
					<div class="col-xs-10 col-sm-10 col-md-9 margin0px"><h4>{"Interested products"|t:$MODULE_NAME}</h4></div>
					<div class="col-xs-1 col-md-3 summaryWidgetIcon">
						<div class="pull-right">
							<button class="btn btn-default showModal" type="button" data-url="index.php?module=Products&view=TreeCategoryModal&src_module={$MODULE_NAME}&src_record={$RECORDID}">
								<span class="glyphicon glyphicon-zoom-in" title="{"LBL_SELECT"|t:$MODULE_NAME}"></span>
							</button>
						</div>
					</div>
				</div>
				<div class="widget_contents">
				</div>
			</div>
			<div class="widgetContainer_productsCategory" data-url="module={$MODULE_NAME}&view=Detail&record={$RECORDID}&relatedModule=Products&mode=showRelatedTree" data-name="LBL_RELATED_PRODUCTS">
				<div class="widget_header row">
					<input type="hidden" name="relatedModule" value="Products" />
				</div>
				<div class="widget_contents">
				</div>
			</div>
		</div>
	{/if}
	{* Summary View OutsourcedProducts Widget*}
	{if $ACTIVE_MODULES.OutsourcedProducts}
		<div class="summaryWidgetContainer">
			<div class="widgetContainer_assets" data-url="module={$MODULE_NAME}&view=Detail&record={$RECORDID}&relatedModule=OutsourcedProducts&mode=showRelatedRecords&page=1&limit={$LIMIT}" data-name="LBL_RELATED_OP">
				<div class="widget_header row">
					<input type="hidden" name="relatedModule" value="OutsourcedProducts" />
					<div class="col-xs-10 col-sm-10 col-md-9 margin0px"><h4>{"LBL_RELATED_OP"|t:$MODULE_NAME}</h4></div>
					<div class="col-xs-1 col-md-3 summaryWidgetIcon">
						<div class="pull-right">
							<button class="btn btn-default showModal" type="button" data-module="OutsourcedProducts" data-url="index.php?module=OutsourcedProducts&view=TreeCategoryModal&src_module={$MODULE_NAME}&src_record={$RECORDID}">
								<span class="glyphicon glyphicon-zoom-in" title="{"LBL_SELECT"|t:$MODULE_NAME}"></span>
							</button>
						</div>
					</div>
				</div>
				<div class="widget_contents">
				</div>
			</div>
			<div class="widgetContainer_productsCategory" data-url="module={$MODULE_NAME}&view=Detail&record={$RECORDID}&relatedModule=OutsourcedProducts&mode=showRelatedTree" data-name="LBL_RELATED_OP">
				<div class="widget_header row">
					<input type="hidden" name="relatedModule" value="OutsourcedProducts" />
				</div>
				<div class="widget_contents">
				</div>
			</div>
		</div>
	{/if}
	{* Summary View Assets Widget*}
	{if $MODULE_NAME != 'Leads' && $ACTIVE_MODULES.Assets}
		<div class="summaryWidgetContainer">
			<div class="widgetContainer_assets2" data-url="module={$MODULE_NAME}&view=Detail&record={$RECORDID}&relatedModule=Assets&mode=showRelatedRecords&page=1&limit={$LIMIT}" data-name="LBL_RELATED_ASSETS">
				<div class="widget_header row">
					<input type="hidden" name="relatedModule" value="Assets" />
					<div class="col-xs-10 col-sm-10 col-md-9 margin0px"><h4>{"LBL_RELATED_ASSETS"|t:$MODULE_NAME}</h4></div>
					<div class="col-xs-1 col-md-3 summaryWidgetIcon">
						{if $IS_ASSETS_CREATE_PERMITTED}
							<span class="pull-right">
								<button class="btn btn-default createRecord" type="button" data-url="index.php?module=Assets&view=QuickCreateAjax">
									<span class="glyphicon glyphicon-plus-sign" title="{"LBL_ADD"|t:$MODULE_NAME}"></span>
								</button>
							</span>
						{/if}
					</div>
				</div>
				<div class="widget_contents">
				</div>
			</div>
		</div>
	{/if}
	{* Summary View Services Widget Ends Here*}
	{if $ACTIVE_MODULES.Services}
		<div class="summaryWidgetContainer">
			<div class="widgetContainer_service hideActionImages" data-url="module={$MODULE_NAME}&view=Detail&record={$RECORDID}&relatedModule=Services&mode=showRelatedRecords&page=1&limit={$LIMIT}" data-name="LBL_RELATED_SERVICES">
				<div class="widget_header row">
					<input type="hidden" name="relatedModule" value="Services" />
					<div class="col-xs-10 col-sm-10 col-md-9 margin0px"><h4>{"Interested services"|t:$MODULE_NAME}</h4></div>
					<div class="col-xs-1 col-md-3 summaryWidgetIcon">
						<span class="pull-right">
							<button class="btn btn-default showModal" type="button" data-url="index.php?module=Services&view=TreeCategoryModal&src_module={$MODULE_NAME}&src_record={$RECORDID}">
								<span class="glyphicon glyphicon-zoom-in" title="{"LBL_SELECT"|t:$MODULE_NAME}"></span>
							</button>
						</span>
					</div>
				</div>
				<div class="widget_contents">
				</div>
			</div>
			<div class="widgetContainer_productsCategory" data-url="module={$MODULE_NAME}&view=Detail&record={$RECORDID}&relatedModule=Services&mode=showRelatedTree" data-name="LBL_RELATED_SERVICES">
				<div class="widget_header row">
					<input type="hidden" name="relatedModule" value="Services" />
				</div>
				<div class="widget_contents">
				</div>
			</div>
		</div>
	{/if}
	{* Summary View OSSOutsourcedServices Widget Start Here*}
	{if $ACTIVE_MODULES.OSSOutsourcedServices}
	<div class="summaryWidgetContainer">
		<div class="widgetContainer_service" data-url="module={$MODULE_NAME}&view=Detail&record={$RECORDID}&relatedModule=OSSOutsourcedServices&mode=showRelatedRecords&page=1&limit={$LIMIT}" data-name="LBL_RELATED_OSSOS">
			<div class="widget_header row">
				<input type="hidden" name="relatedModule" value="OSSOutsourcedServices" />
				<div class="col-xs-10 col-sm-10 col-md-9 margin0px"><h4>{"LBL_RELATED_OSSOS"|t:$MODULE_NAME}</h4></div>
				<div class="col-xs-1 col-md-3 summaryWidgetIcon">
					<div class="pull-right">
						<button class="btn btn-default showModal" type="button" data-module="OSSOutsourcedServices" data-url="index.php?module=OSSOutsourcedServices&view=TreeCategoryModal&src_module={$MODULE_NAME}&src_record={$RECORDID}">
							<span class="glyphicon glyphicon-zoom-in" title="{"LBL_SELECT"|t:$MODULE_NAME}"></span>
						</button>
					</div>
				</div>
			</div>
			<div class="widget_contents">
			</div>
		</div>
		<div class="widgetContainer_productsCategory" data-url="module={$MODULE_NAME}&view=Detail&record={$RECORDID}&relatedModule=OSSOutsourcedServices&mode=showRelatedTree" data-name="LBL_RELATED_OSSOS">
			<div class="widget_header row">
				<input type="hidden" name="relatedModule" value="OSSOutsourcedServices" />
			</div>
			<div class="widget_contents">
			</div>
		</div>
	</div>
	{/if}
	{if $MODULE_NAME != 'Leads' && $ACTIVE_MODULES.OSSSoldServices}
		<div class="summaryWidgetContainer">
			<div class="widgetContainer_service" data-url="module={$MODULE_NAME}&view=Detail&record={$RECORDID}&relatedModule=OSSSoldServices&mode=showRelatedRecords&page=1&limit={$LIMIT}" data-name="LBL_RELATED_OSSSS">
				<div class="widget_header row">
					<input type="hidden" name="relatedModule" value="OSSSoldServices" />
					<div class="col-xs-10 col-sm-10 col-md-9 margin0px"><h4>{"LBL_RELATED_OSSSS"|t:$MODULE_NAME}</h4></div>
					<div class="col-xs-1 col-md-3 summaryWidgetIcon">
						{if $IS_OSSSOLD_SERVICES_CREATE_PERMITTED}
							<span class="pull-right">
								<button class="btn btn-default createRecord" type="button" data-url="index.php?module=OSSSoldServices&view=QuickCreateAjax">
									<span class="glyphicon glyphicon-plus-sign" title="{"LBL_ADD"|t:$MODULE_NAME}"></span>
								</button>
							</span>
						{/if}
					</div>
				</div>
				<div class="widget_contents">
				</div>
			</div>
		</div>
	{/if}
</div>
<!--/layouts/basic/modules/Base/DetailViewProductsServicesContents.tpl -->
{/strip}
