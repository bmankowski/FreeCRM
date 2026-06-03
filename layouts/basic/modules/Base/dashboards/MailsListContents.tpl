{strip}
<!-- layouts/basic/modules/Base/dashboards/MailsListContents.tpl -->
{if $ACCOUNTSLIST|@count > 0}
	<p class="textAlignCenter muted">{"LBL_MAILBOX"|t:"Mail"}</p>
{else}
	<span class="noDataMsg" style="position: relative; top: 115px; left: 133px;">
		{"LBL_NO_MAIL_ACCOUNT"|t:"Mail"}
	</span>
{/if}
<!--/layouts/basic/modules/Base/dashboards/MailsListContents.tpl -->
{/strip}
