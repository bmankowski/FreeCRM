{strip}
<!-- layouts/basic/modules/Base/TopMenusRight.tpl -->
	{foreach key=index item=obj from=$MENU_HEADER_LINKS}
		{if $obj->linktype == 'HEADERLINK'}
			{assign var="HREF" value='#'}
			{assign var="ICON_PATH" value=$obj->getIconPath()}
			{assign var="LINK" value=$obj->convertToNativeLink()}
			{assign var="GLYPHICON" value=$obj->getGlyphiconIcon()}
			{assign var="TITLE" value=$obj->getLabel()}
			{assign var="CHILD_LINKS" value=$obj->getChildLinks()}
			{if !empty($LINK)}
				{assign var="HREF" value=$LINK}
			{/if}
			{assign var="OBJ_CLASS" value=$obj->getClassName()}
			{assign var="LINK_DATA" value=$obj->getLinkData()}
			{assign var="HAS_MODAL" value=false}
			{if $OBJ_CLASS && $OBJ_CLASS|strpos:"showModal" !== false}
				{assign var="HAS_MODAL" value=true}
				{if $HREF == '#'}
					{assign var="HREF" value="javascript:;"}
				{/if}
			{/if}
			<a class="btn btn-sm {if empty($CHILD_LINKS)}popoverTooltip{/if} {if $OBJ_CLASS && $OBJ_CLASS|strrpos:"btn-" === false}btn-default {$OBJ_CLASS}{elseif $OBJ_CLASS}{$OBJ_CLASS}{else}btn-default{/if} {if !empty($CHILD_LINKS)}dropdownMenu{/if}" {if empty($CHILD_LINKS)}data-content="{$TITLE|t}"{/if} href="{$HREF}"
			   {if !empty($LINK_DATA) && is_array($LINK_DATA)}
				   {foreach item=DATA_VALUE key=DATA_NAME from=$LINK_DATA}
					   data-{$DATA_NAME}="{$DATA_VALUE}" 
				   {/foreach}
			   {/if}>
				{if $GLYPHICON}
					<span class="{$GLYPHICON}" aria-hidden="true"></span>
				{/if}
				{if $ICON_PATH}
					<img src="{$ICON_PATH}" alt="{$TITLE|t:$MODULE}" title="{$TITLE|t:$MODULE}" />
				{/if}
			</a>
			{if !empty($CHILD_LINKS)}
				<ul class="dropdown-menu">
					{foreach key=index item=obj from=$CHILD_LINKS}
						{if is_object($obj) && $obj->getLabel() eq NULL}
							<li class="divider"></li>
						{elseif is_object($obj)}
							{assign var="id" value=$obj->getId()}
							{assign var="href" value=$obj->getUrl()}
							{assign var="label" value=$obj->getLabel()}
							{assign var="onclick" value=""}
							{if stripos($obj->getUrl(), 'javascript:') === 0}
								{assign var="onclick" value="onclick="|cat:$href}
								{assign var="href" value="javascript:;"}
							{/if}
						<li>
							{assign var="LINK_DATA" value=$obj->getLinkData()}
							<a target="{$obj->target}" id="menubar_item_right_{\App\Modules\Base\Helpers\Util::replaceSpaceWithUnderScores($label)}" {if $label=='Switch to old look'}switchLook{/if} href="{$href}" {$onclick}
							   {if !empty($LINK_DATA) && is_array($LINK_DATA)}
								   {foreach item=DATA_VALUE key=DATA_NAME from=$LINK_DATA}
									   data-{$DATA_NAME}="{$DATA_VALUE}" 
								   {/foreach}
							   {/if}>{$label|t:$MODULE}</a>
						</li>
						{/if}
					{/foreach}
				</ul>
			{/if}
		{/if}
	{/foreach}
<!--/layouts/basic/modules/Base/TopMenusRight.tpl -->
{/strip}
