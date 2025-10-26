{strip}
<!-- layouts/basic/modules/Base/menu/QuickCreate.tpl -->
	{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
	{assign var='MODULEMODEL' value=\App\Modules\Base\Models\Module::getInstance($MENU.tabid)}
	{assign var='QUICKCREATEMODULE' value=$MODULEMODEL->isQuickCreateSupported()}
	{assign var='SINGULAR_LABEL' value=$MODULEMODEL->getSingularLabelKey()}
	{assign var='NAME' value=$MODULEMODEL->getName()}
	{assign var=ICON value=\App\Modules\Base\Models\Menu::getMenuIcon($MENU, $MENU['name']|t:$MENU_MODULE)}
	{if $QUICKCREATEMODULE == '1' && (isset($ACTIVE_MODULES[$NAME]) && $ACTIVE_MODULES[$NAME]) && ($PRIVILEGESMODEL->isAdminUser() || $PRIVILEGESMODEL->hasGlobalWritePermission() || $PRIVILEGESMODEL->hasModuleActionPermission($MENU.tabid, 'CreateView') ) }
		<li class="quickCreateModules quickCreate {if !$HASCHILDS}hasParentMenu{/if} " data-id="{$MENU.id}" role="menuitem" tabindex="{$TABINDEX}" {if $HASCHILDS}aria-haspopup="{$HASCHILDS}"{/if}>
			<a class="quickCreateModule {if $ICON}hasIcon{/if} {if isset($MENU['hotkey'])}hotKey{/if}" {if isset($MENU['hotkey'])}data-hotkeys="{$MENU['hotkey']}"{/if} data-name="{$NAME}" data-url="{$MODULEMODEL->getQuickCreateUrl()}" href="javascript:void(0)">
				{if $ICON}
					<div  {if $DEVICE == 'Desktop'}class="iconContainer"{/if}>
						<div {if $DEVICE == 'Desktop'}class="iconImage" {/if}>{$ICON}</div>
					</div>
				{/if}
				<div {if $DEVICE == 'Desktop'}class="labelConstainer"{/if}>
					<div {if $DEVICE == 'Desktop'}class="labelValue" {/if}>
						<span class="menuName">
							{if $MENU.name != ''}
								{$MENU.name|t:"Menu"}
							{else}
								{'LBL_QUICK_CREATE_MODULE'|t:$NAME}: {$SINGULAR_LABEL|t:$NAME}
							{/if}
						</span>
					</div>
				</div>
			</a>
			{if $DEVICE == 'Desktop'}
				{include file='menu/SubMenu.tpl'|@vtemplate_path:$MODULE DEVICE=$DEVICE}
			{/if}
		</li>
		{if $DEVICE == 'Mobile'}
			{include file='menu/SubMenu.tpl'|@vtemplate_path:$MODULE DEVICE=$DEVICE}
		{/if}
	{/if}
<!--/layouts/basic/modules/Base/menu/QuickCreate.tpl -->
{/strip}
