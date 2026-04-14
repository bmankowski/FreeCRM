{*<!-- FreeCRM: compact {@see Link} for RelatedList wrench menu (same data model as ButtonLink.tpl) -->*}
{strip}
{assign var=LINK_URL value=$LINK->getUrl()}
{assign var=BTN_MODULE value=$LINK->getRelatedModuleName($MODULE)}
{assign var=LINK_CLASS value=$LINK->getClassName()|trim}
<!-- layouts/basic/modules/Base/RelatedListLeftSideLink.tpl -->
<a class="{if $LINK->get('modalView')}showModal {/if}{$LINK_CLASS}"
	{if $LINK->get('linkhref')}href="{$LINK_URL}"
	{else}href="#"
	{/if}
	{if $LINK->get('linktarget')}target="{$LINK->get('linktarget')}"{/if}
	{if $LINK->get('modalView')}data-url="{$LINK_URL}"{/if}
	{if $LINK->get('linkdata') neq '' && is_array($LINK->get('linkdata'))}
		{foreach from=$LINK->get('linkdata') key=NAME item=DATA}
			data-{$NAME}="{$DATA}"
		{/foreach}
	{/if}
	{if $LINK_URL neq '' && !$LINK->get('linkhref') && !$LINK->get('modalView')}
		{if stripos($LINK_URL, 'javascript:') === 0}
			onclick='{$LINK_URL|substr:strlen("javascript:")};'
		{elseif stripos($LINK_URL, 'javascript:') !== 0 && $LINK_URL neq '#'}
			onclick='window.location.href = "{$LINK_URL}"'
		{/if}
	{/if}
	>
	{if $LINK->get('linkicon') neq ''}
		<span class="{$LINK->get('linkicon')} alignMiddle" title="{$LINK->getLabel()|t:$BTN_MODULE}"></span>
	{/if}
</a>
{/strip}
<!--/layouts/basic/modules/Base/RelatedListLeftSideLink.tpl -->