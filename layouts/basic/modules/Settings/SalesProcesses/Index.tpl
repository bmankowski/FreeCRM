{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/SalesProcesses/Index.tpl -->
	<div class="" id="salesProcessesContainer">
		<div class="widget_header row">
			<div class="col-xs-12">
				{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
				{"LBL_SALES_PROCESSES_DESCRIPTION"|t:$QUALIFIED_MODULE}
			</div>
		</div>
		<ul id="tabs" class="nav nav-tabs layoutTabs massEditTabs" data-tabs="tabs">
			<li class="active"><a href="#popup" data-toggle="tab">{"LBL_PRODUCTS_AND_SERVICES_POPUP"|t:$QUALIFIED_MODULE} </a></li>
		</ul>
		{assign var=CONFIG value=$MODULE_MODEL->getConfig()}
		<div class="tab-content layoutContent">
			<div class="tab-pane active" id="popup">
				{assign var=POPUP value=$CONFIG['popup']}
				<div data-toggle="buttons">
					<label class="btn {if $POPUP['limit_product_service'] eq 'true'}btn-success active{else}btn-default{/if} btn-block">
						<span class="glyphicon {if $POPUP['limit_product_service'] eq 'true'}glyphicon-check{else}glyphicon-unchecked{/if} pull-left"></span>
						<input id="limit_product_service" autocomplete="off" class="configField" type="checkbox" name="limit_product_service" data-type="popup" {if $POPUP['limit_product_service'] eq 'true'}checked=""{/if}>{"LBL_LIMIT_PRODUCT_AND_SERVICE"|t:$QUALIFIED_MODULE}
					</label>
				</div>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Settings/SalesProcesses/Index.tpl -->
{/strip}
