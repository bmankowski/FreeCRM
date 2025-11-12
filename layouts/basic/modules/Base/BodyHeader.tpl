{strip}
<!-- layouts/basic/modules/Base/BodyHeader.tpl -->
	{*<!-- {[The file is published on the basis of FreeCRM Public License that can be found in the following directory: licenses/FreeCRMLicense.html]} --!>*}
	<div class="container-fluid bodyHeader noSpaces commonActionsContainer{if $LEFTPANELHIDE} menuOpen{/if}">
		<div class="row noSpaces">
			<div class="rightHeader paddingRight10">
				<div class="pull-right rightHeaderBtn">
					{include file='HeaderQuickCreate.tpl'|@vtemplate_path:$MODULE}
					{include file='HeaderActionButtons.tpl'|@vtemplate_path:$MODULE}
					{include file='TopMenusRight.tpl'|@vtemplate_path:$MODULE}
				</div>
				{include file='HeaderGlobalSearch.tpl'|@vtemplate_path:$MODULE}
				{include file='HeaderMenuButtons.tpl'|@vtemplate_path:$MODULE}
				{include file='HeaderMailIcon.tpl'|@vtemplate_path:$MODULE}
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Base/BodyHeader.tpl -->
{/strip}
