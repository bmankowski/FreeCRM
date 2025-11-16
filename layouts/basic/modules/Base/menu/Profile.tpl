{strip}
<!-- layouts/basic/modules/Base/menu/Profile.tpl -->
{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{if \App\Core\AppConfig::security('CHANGE_LOGIN_PASSWORD')}
	{assign var=ICON value=\App\Modules\Base\Models\Menu::getMenuIcon($MENU, $MENU['name']|t:$MENU_MODULE)}
	<li class="hovernav menuLabel {if !$HASCHILDS}hasParentMenu{/if}" data-id="{$MENU['id']}" role="menuitem" tabindex="{$TABINDEX}" {if $HASCHILDS == 'true'}aria-haspopup="{$HASCHILDS}"{/if}>
		<a class="{if (isset($MENU['active']) && $MENU['active']) || $PARENT_MODULE == $MENU['id']}active {/if}{if $ICON}hasIcon{/if}" {if $HASCHILDS == 'true'}role="button"{/if} href="#">
			{if $ICON}
				<div  {if $DEVICE == 'Desktop'}class='iconContainer'{/if}>
					<div {if $DEVICE == 'Desktop'}class="iconImage" {/if}>{$ICON}</div>
				</div>
			{/if}
			<div {if $DEVICE == 'Desktop'}class='labelConstainer'{/if}>
				<div {if $DEVICE == 'Desktop'}class="labelValue" {/if}>
					<span class="menuName">{$MENU['name']|t:$MENU_MODULE}</span>
				</div>
			</div>
		</a>
		{if $DEVICE == 'Desktop'}
			<ul class="slimScrollSubMenu nav subMenu {if (isset($MENU['active']) && $MENU['active']) || $PARENT_MODULE == $MENU['id']}in{/if}" role="menu" aria-hidden="true">
				<li class="menuPanel">
					<button name="changePass" data-url="index.php?module=Users&view=ChangePassword&record={$USER_MODEL->getRealId()}" class="btn btn-block btn-default showModal" type="button">
						{'LBL_CHANGE_LOGIN_PASSWORD'|t:$MENU_MODULE}
					</button>
				</li>
			</ul>
		{/if}
	</li>
{/if}
<!--/layouts/basic/modules/Base/menu/Profile.tpl -->
{/strip}
