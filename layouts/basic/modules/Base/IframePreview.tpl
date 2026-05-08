{strip}
<!-- layouts/basic/modules/Base/IframePreview.tpl -->
{include file="modules/Base/Header.tpl"}
<style>
	{literal}
	/* This template is rendered inside an iframe (related list ListPreview). */
	html, body { height: auto !important; min-height: 0 !important; overflow-x: hidden; overflow-y: visible; }
	body { background: #fff; }
	.footerContainer, .infoUser, #yetiforceDetails { display: none !important; }
	/* Avoid extra padding/margins that belong to the full app chrome */
	.container-fluid-main, .baseContainer, .leftPanel, .mobileLeftPanel { display: none !important; }
	/* Provide a simple, scrollable container for summary content */
	.c-iframe-preview { padding: 10px 12px; }
	{/literal}
</style>
<div class="c-iframe-preview">
	{$PREVIEW_HTML nofilter}
</div>
{include file="modules/Base/Footer.tpl"}
<!--/layouts/basic/modules/Base/IframePreview.tpl -->
{/strip}

