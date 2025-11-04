{strip}
<!-- layouts/basic/modules/Settings/AdvancedPermission/ListViewHeader.tpl -->
	{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
	<div class="">
		<div class='widget_header row '>
			<div class="col-xs-12">
				{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			</div>
		</div>
		{if !\AppConfig::security('PERMITTED_BY_ADVANCED_PERMISSION')}
			<div class="alert alert-block alert-danger fade in">
				<button type="button" class="close" data-dismiss="alert">×</button>
				<h4 class="alert-heading">{"ERR_INACTIVE_ALERT_TITLE"|t:$QUALIFIED_MODULE}</h4>
				<p>{"ERR_INACTIVE_ALERT_DESC"|t:$QUALIFIED_MODULE}</p>
			</div>	
		{/if}
		{if !\AppConfig::security('CACHING_PERMISSION_TO_RECORD')}
			<div class="alert alert-block alert-danger fade in">
				<h4 class="alert-heading">{"ERR_INACTIVE_ALERT_TITLE"|t:$QUALIFIED_MODULE}</h4>
				<p>{"ERR_INACTIVE_CACHING_PERM_ALERT_DESC"|t:$QUALIFIED_MODULE}</p>
			</div>
		
		{/if}
		<div class="listViewActionsDiv row">
			<div class="col-md-8 btn-toolbar">
				{if \AppConfig::security('CACHING_PERMISSION_TO_RECORD')}
					{foreach item=LISTVIEW_BASICACTION from=$LISTVIEW_LINKS['LISTVIEWBASIC']}
						<button class="btn btn-success addButton" {if stripos($LISTVIEW_BASICACTION->getUrl(), 'javascript:')===0} onclick='{$LISTVIEW_BASICACTION->getUrl()|substr:strlen("javascript:")};'
								{else} onclick='window.location.href = "{$LISTVIEW_BASICACTION->getUrl()}"' {/if}>
										<span class="glyphicon glyphicon-plus"></span>&nbsp;
										<strong>{$LISTVIEW_BASICACTION->getLabel()|t:$QUALIFIED_MODULE}</strong>
									</button>
									{/foreach}
								{/if}
								</div>
								<div class="col-md-4">
									{include file='ListViewActions.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
								</div>
							</div>
<!--/layouts/basic/modules/Settings/AdvancedPermission/ListViewHeader.tpl -->
								{/strip}
