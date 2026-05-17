{strip}
<input type="hidden" name="conditions" id="advanced_filter" value='' />
{if !empty($CONDITIONS_RECORD_STRUCTURE)}
	{assign var=RECORD_STRUCTURE value=$CONDITIONS_RECORD_STRUCTURE}
{/if}
{if !empty($CONDITIONS_MODULE_MODEL)}
	{assign var=MODULE_MODEL value=$CONDITIONS_MODULE_MODEL}
{/if}
{include file='AdvanceFilterExpressions.tpl'|@vtemplate_path:$MODULE}
{/strip}
