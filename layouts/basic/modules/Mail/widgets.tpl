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
		</div>
		<hr/>
	{/foreach}
{else}
	<p class="textAlignCenter">{"LBL_NO_RECORDS"|t:"Vtiger"}</p>
{/if}
<!--/layouts/basic/modules/Mail/widgets.tpl -->
{/strip}
