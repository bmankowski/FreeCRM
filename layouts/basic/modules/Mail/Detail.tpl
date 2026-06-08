{strip}
{extends file="MainLayout.tpl"|@vtemplate_path}
{block name="content"}
<div class="mainContainer">
	<div class="contentsDiv">
		<div class="detailViewContainer">
			<div class="row detailViewTitle">
				<div class="col-md-12 marginBottom5px widget_header row no-margin">
					<div class="col-md-6 paddingLRZero">
						{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
					</div>
					<div class="col-md-6 col-xs-12 paddingLRZero">
						<div class="col-xs-12 detailViewToolbar paddingLRZero" style="text-align: right;">
							{if !$NO_PAGINATION}
								<div class="detailViewPagingButton pull-right">
									<span class="btn-group pull-right">
										<button type="button" class="btn btn-default" id="detailViewPreviousRecordButton"
											{if $PREVIOUS_RECORD_URL}data-record-url="{$PREVIOUS_RECORD_URL}" onclick="window.location.href='{$PREVIOUS_RECORD_URL|escape:'javascript'}'; return false;"{else}disabled="disabled"{/if}>
											<span class="glyphicon glyphicon-chevron-left"></span>
										</button>
										<button type="button" class="btn btn-default" id="detailViewNextRecordButton"
											{if $NEXT_RECORD_URL}data-record-url="{$NEXT_RECORD_URL}" onclick="window.location.href='{$NEXT_RECORD_URL|escape:'javascript'}'; return false;"{else}disabled="disabled"{/if}>
											<span class="glyphicon glyphicon-chevron-right"></span>
										</button>
									</span>
								</div>
							{/if}
						</div>
					</div>
				</div>
			</div>
			<div class="detailViewInfo row">
				<div class="col-md-12 details">
					<div class="contents mail-detail-contents">
						<h3>{$MESSAGE.subject|escape}</h3>
						<p><strong>From:</strong> {$MESSAGE.from_name|escape} &lt;{$MESSAGE.from_email|escape}&gt;</p>
						<p><strong>Date:</strong> {$MESSAGE.date_sent}</p>
						{if $MESSAGE.direction eq 'out'}
							<p><strong>{"LBL_MAIL_SEND_STATUS"|t:"Mail"}:</strong>
								{if $MESSAGE.send_status eq 'failed'}{"LBL_MAIL_SEND_STATUS_FAILED"|t:"Mail"}
								{elseif $MESSAGE.send_status eq 'prepared'}{"LBL_MAIL_SEND_STATUS_PREPARED"|t:"Mail"}
								{else}{"LBL_MAIL_SEND_STATUS_SENT"|t:"Mail"}{/if}
							</p>
							<p><strong>{"LBL_MAIL_OPENED_AT"|t:"Mail"}:</strong>
								{if $OPENED_AT_DISPLAY}{$OPENED_AT_DISPLAY|escape}{else}—{/if}
							</p>
						{/if}
						<div class="mail-body">{$BODY_HTML nofilter}</div>
						{if $ATTACHMENTS|@count gt 0}
							<h4>Attachments</h4>
							<ul>
							{foreach from=$ATTACHMENTS item=ATT}
								<li><a href="index.php?module=Mail&action=DownloadAttachment&id={$ATT.id}">{$ATT.original_name|escape}</a></li>
							{/foreach}
							</ul>
						{/if}
						<h4>Linked records</h4>
						<ul>
						{foreach from=$LINKS item=LINK}
							<li>{$LINK.crm_module} #{$LINK.crm_record_id} ({$LINK.link_type})</li>
						{/foreach}
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
{/block}
{/strip}
