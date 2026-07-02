{strip}
<!-- layouts/basic/modules/Base/BodyLeft.tpl -->
{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
	{assign var="COMPANY_DETAILS" value=App\Core\Company::getInstanceById()}
	{assign var="COMPANY_LOGO" value=$COMPANY_DETAILS->getLogo()}
	<div class="container-fluid userDetailsContainer">
		<div class="row padding0">
			<div class="col-md-2 noSpaces">
				<a class="companyLogoContainer" href="index.php">
					<img class="img-responsive logo" src="{$COMPANY_LOGO->get('imageUrl')}" title="{$COMPANY_DETAILS->get('name')}" alt="{$COMPANY_LOGO->get('alt')}"/>
				</a>
			</div>
			<div class="col-md-10 userDetails">
				<div class="col-xs-12 noSpaces userName{if $USER_MODEL->getImageRelativePath()} userName--withPhoto{/if}">
					{if $USER_MODEL->getImageRelativePath()}
						<img class="userNamePhoto img-circle" src="{$USER_MODEL->getImageWebUrl()}" alt="{$USER_MODEL->getDisplayName()}" title="{$USER_MODEL->getDisplayName()}">
					{/if}
					<div class="userNameText">
						{assign var=USER_NAME_ARRAY value=explode(' ',$USER_MODEL->getDisplayName())}
						{foreach from=$USER_NAME_ARRAY item=NAME name=userNameIterator}
							{if $smarty.foreach.userNameIterator.iteration <= 2}
								<p class="noSpaces name textOverflowEllipsis">{$NAME}&nbsp;</p>
							{/if}
						{/foreach}
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="menuContainer {if $DEVICE == 'Desktop'}slimScrollMenu{/if}">
		{include file='Menu.tpl'|@vtemplate_path:$MODULE DEVICE=$DEVICE}
	</div>
<!--/layouts/basic/modules/Base/BodyLeft.tpl -->
{/strip}

