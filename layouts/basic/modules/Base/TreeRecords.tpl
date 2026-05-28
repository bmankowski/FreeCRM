{*<!--
/**
 * Basic TreeView View Template
 * @package YetiForce.TreeView
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
-->*}
{strip}
<!-- layouts/basic/modules/Base/TreeRecords.tpl -->
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
		<div class="bodyContents">
			<div class="mainContainer">
				<div class="contentsDiv">
					
					{* Tree view specific content *}
					<div class="treeRecordsContainer">
						{* Tree navigation and records will be loaded here *}
					</div>
					
				</div> <!-- close contentsDiv -->
			</div> <!-- close mainContainer -->
		</div> <!-- close bodyContents -->
{/block}
<!--/layouts/basic/modules/Base/TreeRecords.tpl -->
{/strip}
