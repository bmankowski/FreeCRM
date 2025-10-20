{strip}
<!-- layouts/basic/modules/Vtiger/menu/CustomFilter.tpl -->
{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
	{if (isset($ACTIVE_MODULES[$MENU.mod]) && $ACTIVE_MODULES[$MENU.mod]) AND ($PRIVILEGESMODEL->isAdminUser() || $PRIVILEGESMODEL->hasGlobalReadPermission() || $PRIVILEGESMODEL->hasModulePermission($MENU['tabid']) ) }
		{assign var=ICON value=\App\Modules\Vtiger\Models\Menu::getMenuIcon($MENU, $MENU['name']|t:$MENU_MODULE)}
		<li class="menuCustomFilter .moduleColor_{$MENU.mod}" data-id="{$MENU['id']}" role="menuitem" tabindex="{$TABINDEX}" aria-haspopup="{$HASCHILDS}">
			<a class="{if isset($MENU['hotkey'])}hotKey{/if} {if $ICON}hasIcon{/if}" {if isset($MENU['hotkey'])}data-hotkeys="{$MENU['hotkey']}"{/if} href="{$MENU['dataurl']}" {if $MENU['newwindow'] eq 1}target="_blank" {/if}>
				{if $ICON}
					<div  {if $DEVICE == 'Desktop'}class='iconContainer'{/if}>
						<div {if $DEVICE == 'Desktop'}class="iconImage" {/if}>{$ICON}</div>
					</div>
				{/if}
				<span class="menuName">{$MENU['name']|t:$MENU_MODULE}</span>
			</a>
			{include file='menu/SubMenu.tpl'|@vtemplate_path:$MODULE}
		</li>
	{/if}
<!--/layouts/basic/modules/Vtiger/menu/CustomFilter.tpl -->
{/strip}
