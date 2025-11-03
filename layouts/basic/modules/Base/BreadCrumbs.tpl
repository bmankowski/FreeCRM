{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/BreadCrumbs.tpl -->
	<div class="breadCrumbs" >
		{assign var=HOMEICON value='userIcon-Home'}
		{if $BREADCRUMBS}
			<div class="breadcrumbsContainer">
				<h2 class="breadcrumbsLinks textOverflowEllipsis">
					<a href="{AppConfig::main('site_URL')}">
						<span class="{$HOMEICON}"></span>
					</a>
					&nbsp;|&nbsp;
					{foreach key=key item=item from=$BREADCRUMBS name=breadcrumbs}
						{if $key != 0 && $ITEM_PREV}
							<span class="separator">&nbsp;{$BREADCRUMBS_SEPARATOR}&nbsp;</span>
						{/if}
						{if isset($item['url'])}
							<a href="{$item['url']}">
								<span>{$item['name']}</span>
							</a>
						{else}
							<span>{$item['name']}</span>
						{/if}
						{assign var="ITEM_PREV" value=$item['name']}
					{/foreach}
				</h2>
			</div>
		{/if}
		{assign var="MENUSCOLOR" value=\App\Modules\Users\Models\Colors::getModulesColors(true)}
		{if $MENUSCOLOR}
			<div class="menusColorContainer">
				<style>
					{foreach item=item from=$MENUSCOLOR}
						.moduleColor_{$item.module}{
							color: {$item.color} !important;
						}
					{/foreach}
				</style>
			</div>
		{/if}
	</div>
<!--/layouts/basic/modules/Base/BreadCrumbs.tpl -->
{/strip}
