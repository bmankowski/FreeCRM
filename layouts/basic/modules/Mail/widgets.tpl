{strip}
<!-- layouts/basic/modules/Mail/widgets.tpl -->
{if $MAIL_ROWS|@count > 0}
	{foreach from=$MAIL_ROWS item=ROW}
		<div class="row mailRow" data-mailId="{$ROW.id}">
			<div class="col-md-12">
				<div class="pull-right muted">
					<small title="{$ROW.date}">{\App\Modules\Base\Helpers\Util::formatDateDiffInStrings($ROW.date)}</small>
				</div>
				<h5>
					<a href="{$ROW.url}">{$ROW.subject|escape}</a>
					{if $ROW.attachments_exist}
						<span class="glyphicon glyphicon-paperclip pull-right" title="{"LBL_ATTACHMENT"|t:"Vtiger"}"></span>
					{/if}
				</h5>
			</div>
			<div class="col-md-12 marginLeftZero">
				<span>{"From"|t:"Vtiger"}: {$ROW.from_email|escape}</span>
				<span class="pull-right">
					{if $ROW.direction eq 'out'}{"LBL_OUTBOUND"|t:"Mail"}{else}{"LBL_INBOUND"|t:"Mail"}{/if}
				</span>
			</div>
			{if $ROW.direction eq 'out'}
				<div class="col-md-12 marginLeftZero muted">
					<small>
						{"LBL_MAIL_SEND_STATUS"|t:"Mail"}:
						{if $ROW.send_status eq 'failed'}{"LBL_MAIL_SEND_STATUS_FAILED"|t:"Mail"}
						{elseif $ROW.send_status eq 'prepared'}{"LBL_MAIL_SEND_STATUS_PREPARED"|t:"Mail"}
						{else}{"LBL_MAIL_SEND_STATUS_SENT"|t:"Mail"}{/if}
						{if $ROW.opened_at_display}| {"LBL_MAIL_OPENED_AT"|t:"Mail"}: {$ROW.opened_at_display|escape}{/if}
					</small>
				</div>
			{/if}
		</div>
		<hr/>
	{/foreach}
{else}
	<p class="textAlignCenter">{"LBL_NO_RECORDS"|t:"Vtiger"}</p>
{/if}
<!--/layouts/basic/modules/Mail/widgets.tpl -->
{/strip}
