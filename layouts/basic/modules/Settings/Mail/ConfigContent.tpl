{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}

<div class=" configContainer">
	<div class="widget_header row">
		<div class="col-xs-12">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			&nbsp;{"LBL_MAIL_GENERAL_CONFIGURATION_DESCRIPTION"|t:$QUALIFIED_MODULE}
		</div>
	</div>
	<br />
	{assign var=CONFIG value=$MODULE_MODEL->getConfig('mailIcon')}
	<div class="col-xs-12">
		<div class="pull-left pagination-centered">
			<input class="configCheckbox" type="checkbox" name="showMailIcon" id="showMailIcon" data-type="mailIcon" value="1" {if $CONFIG['showMailIcon']=='true'}checked=""{/if}>
		</div>
		<div class="col-md-11 col-sm-10 col-xs-10">
			<label for="showMailIcon">{"LBL_SHOW_MAIL_ICON"|t:$QUALIFIED_MODULE}</label>
		</div>
	</div>
	<div class="col-xs-12">
		<div class="pull-left pagination-centered">
			<input class="configCheckbox" type="checkbox" name="showMailAccounts" id="showMailAccounts" data-type="mailIcon" value="1" {if $CONFIG['showMailAccounts']=='true'}checked=""{/if}>
		</div>
		<div class="col-md-11 col-sm-10 col-xs-10">
			<label for="showMailAccounts">{"LBL_SHOW_MAIL_ACCOUNTS"|t:$QUALIFIED_MODULE}</label>
		</div>
	</div>
	<div class="col-xs-12">
		<div class="pull-left pagination-centered">
			<input class="configCheckbox" type="checkbox" name="showNumberUnreadEmails" id="showNumberUnreadEmails" data-type="mailIcon" value="1" {if $CONFIG['showNumberUnreadEmails']=='true'}checked=""{/if}>
		</div>
		<div class="col-md-11 col-sm-10 col-xs-10">
			<label for="showNumberUnreadEmails">{"LBL_NUMBER_UNREAD_EMAILS"|t:$QUALIFIED_MODULE}</label>
		</div>
	</div>
</div>
