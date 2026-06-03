{strip}
{extends file="MainLayout.tpl"|@vtemplate_path}
{block name="content"}
<div class="mainContainer"><div class="contentsDiv">
	<h3>{$MESSAGE.subject|escape}</h3>
	<p><strong>From:</strong> {$MESSAGE.from_name|escape} &lt;{$MESSAGE.from_email|escape}&gt;</p>
	<p><strong>Date:</strong> {$MESSAGE.date_sent}</p>
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
</div></div>
{/block}
{/strip}
