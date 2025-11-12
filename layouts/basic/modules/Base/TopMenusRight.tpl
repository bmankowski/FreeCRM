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
			<a class="btn btn-sm popoverTooltip {if $OBJ_CLASS && $OBJ_CLASS|strrpos:"btn-" === false}btn-default {$OBJ_CLASS}{elseif $OBJ_CLASS}{$OBJ_CLASS}{else}btn-default{/if} {if !empty($CHILD_LINKS)}dropdownMenu{/if}" data-content="{$TITLE|t}" href="{$HREF}"
			   {if isset($obj->linkdata) && $obj->linkdata && is_array($obj->linkdata)}
				   {foreach item=DATA_VALUE key=DATA_NAME from=$obj->linkdata}
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
						{if $obj->getLabel() eq NULL}
							<li class="divider"></li>
						{else}
							{assign var="id" value=$obj->getId()}
							{assign var="href" value=$obj->getUrl()}
							{assign var="label" value=$obj->getLabel()}
							{assign var="onclick" value=""}
							{if stripos($obj->getUrl(), 'javascript:') === 0}
								{assign var="onclick" value="onclick="|cat:$href}
								{assign var="href" value="javascript:;"}
							{/if}
						<li>
							<a target="{$obj->target}" id="menubar_item_right_{\App\Modules\Base\Helpers\Util::replaceSpaceWithUnderScores($label)}" {if $label=='Switch to old look'}switchLook{/if} href="{$href}" {$onclick}
							   {if $obj->linkdata && is_array($obj->linkdata)}
								   {foreach item=DATA_VALUE key=DATA_NAME from=$obj->linkdata}
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
