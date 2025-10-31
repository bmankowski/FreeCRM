{*<!--
/*********************************************************************************
 * FreeCRM - Open Source CRM
 * This template is part of FreeCRM.
 ********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Settings/Base/IndexContent.tpl -->
	{if $WARNINGS}
		<div id="systemWarningAletrs">
			<div class="modal fade static">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title" id="myModalLabel">
								<span class="glyphicon glyphicon-warning-sign redColor" aria-hidden="true"></span>&nbsp;&nbsp;
								{'LBL_SYSTEM_WARNINGS'|t:'Settings:Vtiger'}
							</h4>
						</div>
						<div class="modal-body">
							<div class="warnings">
								{foreach from=$WARNINGS item=ITEM}
									<div class="warning hide" data-id="{get_class($ITEM)}">
										{if $ITEM->getTpl()}
											{include file=$ITEM->getTpl()}
										{else}
											<h3 class="marginTB3">
												{$ITEM->getTitle()|t:'Settings:SystemWarnings'}
											</h3>
											<p>
												{$ITEM->getDescription()}
											</p>
											<div class="pull-right">
												{if $ITEM->getStatus() != 1 && $ITEM->getPriority() < 8}
													<button type="button" class="btn btn-warning ajaxBtn" data-params="{$ITEM->getStatus()}">
														<span class="glyphicon glyphicon-minus-sign" aria-hidden="true"></span>
														&nbsp;&nbsp;{'BTN_SET_IGNORE'|t:'Settings:SystemWarnings'}
													</button>&nbsp;&nbsp;
												{/if}
												{if $ITEM->getLink()}
													<a class="btn btn-success" href="{$ITEM->getLink()}" target="_blank">
														<span class="glyphicon glyphicon-link" aria-hidden="true"></span>
														&nbsp;&nbsp;{$ITEM->linkTitle}
													</a>&nbsp;&nbsp;
												{/if}
												<button type="button" class="btn btn-danger cancel">
													<span class="glyphicon glyphicon-ban-circle" aria-hidden="true"></span>
													&nbsp;&nbsp;{'LBL_REMIND_LATER'|t:'Settings:SystemWarnings'}
												</button>
											</div>
										{/if}
										<div class="clearfix"></div>
									</div>
								{/foreach}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	{/if}
	<div class="settingsIndexPage">
		<div class="">
			<span class="col-md-3 settingsSummary">
				<a href="index.php?module=Users&parent=Settings&view=ListView">
					<h2 style="font-size: 44px" class="summaryCount">{$USERS_COUNT}</h2> 
					<p class="summaryText" style="margin-top:20px;">{"LBL_ACTIVE_USERS"|t:$QUALIFIED_MODULE}</p> 
				</a>
			</span>
			<span class="col-md-3 settingsSummary">
				<a href="javascript:Settings_Vtiger_Index_Js.showWarnings()">
					<h2 style="font-size: 44px" class="summaryCount">{$WARNINGS_COUNT}</h2> 
                    <p class="summaryText" style="margin-top:20px;">{"LBL_SYSTEM_WARNINGS"|t:$QUALIFIED_MODULE}</p> 
				</a>
			</span>
			<span class="col-md-3 settingsSummary">
				<a href="index.php?module=Workflows&parent=Settings&view=ListView">
					<h2 style="font-size: 44px" class="summaryCount">{$ALL_WORKFLOWS}</h2> 
                    <p class="summaryText" style="margin-top:20px;">{"LBL_WORKFLOWS_ACTIVE"|t:$QUALIFIED_MODULE}</p> 
				</a>
			</span>
			<span class="col-md-3 settingsSummary">
				<a href="index.php?module=ModuleManager&parent=Settings&view=ListView">
					<h2 style="font-size: 44px" class="summaryCount">{$ACTIVE_MODULES}</h2> 
					<p class="summaryText" style="margin-top:20px;">{"LBL_MODULES"|t:$QUALIFIED_MODULE}</p>
				</a>
			</span>
		</div>
		<br><br>
		<h3>{"LBL_SETTINGS_SHORTCUTS"|t:$QUALIFIED_MODULE}</h3>
		<hr>
		{assign var=SPAN_COUNT value=1}
		<div class="row">
			<div class="col-md-1">&nbsp;</div>
			<div id="settingsShortCutsContainer" class="col-md-11">
				<div  class="row">
					{foreach item=SETTINGS_SHORTCUT from=$SETTINGS_SHORTCUTS name=shortcuts}
						{include file='SettingsShortCut.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
					{if $SPAN_COUNT==3}</div>{$SPAN_COUNT=1}{if not $smarty.foreach.shortcuts.last}<div class="row">{/if}{continue}{/if}
					{$SPAN_COUNT=$SPAN_COUNT+1}
				{/foreach}
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Settings/Base/IndexContent.tpl -->
{/strip}

