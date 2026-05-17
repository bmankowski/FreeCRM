{*<!-- {[The file is published on the basis of FreeCRM Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
	<!-- layouts/basic/modules/DocumentTemplates/Edit.tpl -->
	{extends file="MainLayout.tpl"|@vtemplate_path}

	{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">
				{include file='EditHeader.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
				{include file=$STEP_TEMPLATE|@vtemplate_path:$QUALIFIED_MODULE}
			</div>
		</div>
	{/block}
	<!--/layouts/basic/modules/DocumentTemplates/Edit.tpl -->
{/strip}
