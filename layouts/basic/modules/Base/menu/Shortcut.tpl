{strip}
<!-- layouts/basic/modules/Base/menu/Shortcut.tpl -->
{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
	{assign var=ICON value=\App\Modules\Base\Models\Menu::getMenuIcon($MENU, $MENU['name']|t:$MENU_MODULE)}
	<li class="menuShortcut {if !$HASCHILDS}hasParentMenu{/if}" data-id="{$MENU.id}" role="menuitem" tabindex="{$TABINDEX}" {if $HASCHILDS}aria-haspopup="{$HASCHILDS}"{/if}>
		<a class="{if isset($MENU['hotkey'])}hotKey{/if} {if (isset($MENU['active']) && $MENU['active']) || $PARENT_MODULE == $MENU['id']}active{/if}{if $ICON} hasIcon{/if}" {if isset($MENU['hotkey'])} data-hotkeys="{$MENU['hotkey']}"{/if} href="{$MENU['dataurl']}" {if $MENU.newwindow eq 1 OR $MENU['name'] eq 'LBL_YETIFORCE_SHOP'}target="_blank" {/if}>
			{$ICON}
			<span class="menuName">
				{$MENU['name']|t:$MENU_MODULE}
			</span>
		</a>
		{if $DEVICE == 'Desktop'}
			{include file='menu/SubMenu.tpl'|@vtemplate_path:$MODULE DEVICE=$DEVICE}
		{/if}
	</li>
	{if $DEVICE == 'Desktop'}
		{include file='menu/SubMenu.tpl'|@vtemplate_path:$MODULE DEVICE=$DEVICE}
	{/if}
<!--/layouts/basic/modules/Base/menu/Shortcut.tpl -->
{/strip}

