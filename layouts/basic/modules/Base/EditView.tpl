{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/EditView.tpl -->
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
	<div id="page">
		<div id="pjaxContainer" class="hide noprint"></div>
		<div class="bodyContents">
			<div class="mainContainer">
				<div class="contentsDiv col-md-12 marginLeftZero" id="centerPanel" style="min-height:550px;">
					{include file="EditViewBlocks.tpl"|@vtemplate_path:$MODULE}
					{if !empty($MODULE_TYPE) && $MODULE_TYPE == '1'}
						{include file='EditViewInventory.tpl'|@vtemplate_path:$MODULE}
					{/if}
					{include file="EditViewActions.tpl"|@vtemplate_path:$MODULE}
				</div> <!-- close contentsDiv -->
			</div> <!-- close mainContainer -->
		</div> <!-- close bodyContents -->
	</div> <!-- close page -->
{/block}
<!--/layouts/basic/modules/Base/EditView.tpl -->
{/strip}
