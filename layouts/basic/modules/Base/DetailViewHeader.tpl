{strip}
<!-- layouts/basic/modules/Base/DetailViewHeader.tpl -->
	{*<!--
	/*********************************************************************************
	** The contents of this file are subject to the vtiger CRM Public License Version 1.0
	* ("License"); You may not use this file except in compliance with the License
	* The Original Code is:  vtiger CRM Open Source
	* The Initial Developer of the Original Code is vtiger.
	* Portions created by vtiger are Copyright (C) vtiger.
	* All Rights Reserved.
	* Contributor(s): YetiForce.com
	********************************************************************************/
	-->*}
	{assign var="MODULE_NAME" value=$MODULE_MODEL->get('name')}
	<input id="recordId" type="hidden" value="{$RECORD->getId()}" />
	<div class="detailViewContainer">
		<div class="row detailViewTitle">
			<div class="">
				<div class="row">
					<div class="col-md-12 marginBottom5px widget_header row no-margin">
						<div class="">
							<div class="col-md-6 paddingLRZero">
								{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
							</div>
							<div class="col-md-6 col-xs-12 paddingLRZero">
								<div class="col-xs-12 detailViewToolbar paddingLRZero" style="text-align: right;">
									{if !{$NO_PAGINATION}}
										<div class="detailViewPagingButton pull-right">
											<span class="btn-group pull-right">
												<button type="button" class="btn btn-default" id="detailViewPreviousRecordButton" data-record-url="{$PREVIOUS_RECORD_URL}" onclick="(window.Vtiger_Detail_Js && Vtiger_Detail_Js.getInstance().navigateUsingListContext('prev')) || (window.location.href='index.php?module={$MODULE}&view=ListView'); return false;"><span class="glyphicon glyphicon-chevron-left"></span></button>
												<button type="button" class="btn btn-default" id="detailViewNextRecordButton" data-record-url="{$NEXT_RECORD_URL}" onclick="(window.Vtiger_Detail_Js && Vtiger_Detail_Js.getInstance().navigateUsingListContext('next')) || (window.location.href='index.php?module={$MODULE}&view=ListView'); return false;"><span class="glyphicon glyphicon-chevron-right"></span></button>
											</span>
										</div>
									{/if}
									<div class="pull-right-md pull-left-sm pull-right-lg">
										<div class="btn-toolbar">
											<span class="btn-group ">
												{foreach item=LINK from=$DETAILVIEW_LINKS['DETAILVIEWBASIC']}	
													{include file='ButtonLink.tpl'|@vtemplate_path:$MODULE BUTTON_VIEW='detailViewBasic'}
												{/foreach}
											</span>
											{if $DETAILVIEW_LINKS['DETAILVIEW']|@count gt 0}
												<span class="btn-group">
													{foreach item=LINK from=$DETAILVIEW_LINKS['DETAILVIEW']}
														{include file='ButtonLink.tpl'|@vtemplate_path:$MODULE BUTTON_VIEW='detailView'}
													{/foreach}
												</span>
											{/if}
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		{if !empty($PROCESSED_HEADER_WIDGETS)}
			{foreach item=WIDGET_HTML from=$PROCESSED_HEADER_WIDGETS}
				<div class="col-md-12 paddingLRZero">
					{$WIDGET_HTML}
				</div>
			{/foreach}
		{/if}
			{include file="DetailViewHeaderTitle.tpl"|vtemplate_path:$MODULE}
		</div> <!-- close detailViewTitle -->
		<div class="detailViewInfo row">
			{include file="RelatedListButtons.tpl"|vtemplate_path:$MODULE}
			<div class="col-md-12 {if !empty($DETAILVIEW_LINKS['DETAILVIEWTAB']) || !empty($DETAILVIEW_LINKS['DETAILVIEWRELATED']) } details {/if}">
				<form id="detailView" data-name-fields='{\App\Utils\Json::encode($MODULE_MODEL->getNameFields())}' method="POST">
					{if !empty($PICKLIST_DEPENDENCY_DATASOURCE)} 
						<input type="hidden" name="picklistDependency" value="{\App\Modules\Base\Helpers\Util::toSafeHTML($PICKLIST_DEPENDENCY_DATASOURCE)}"> 
					{/if} 
					<div class="contents">
						{* Content will be inserted here by parent template *}
						{$DETAIL_VIEW_CONTENT}
					</div> <!-- close contents -->
				</form> <!-- close detailView form -->
			</div> <!-- close col-md-12 details -->
		</div> <!-- close detailViewInfo row -->
	</div> <!-- close detailViewContainer -->
<!--/layouts/basic/modules/Base/DetailViewHeader.tpl -->
{/strip}
