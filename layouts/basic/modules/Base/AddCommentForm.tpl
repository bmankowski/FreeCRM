{*<!--
/*********************************************************************************
  ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
   * ("License"); You may not use this file except in compliance with the License
   * The Original Code is:  vtiger CRM Open Source
   * The Initial Developer of the Original Code is vtiger.
   * Portions created by vtiger are Copyright (C) vtiger.
   * All Rights Reserved.
  *
 ********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Base/AddCommentForm.tpl -->

{* Change to this also refer: RecentComments.tpl *}
{assign var="COMMENT_TEXTAREA_DEFAULT_ROWS" value="2"}

<div id="addCommentContainer" class='modelContainer modal fade' tabindex="-1">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header contentsBackground">
				<button data-dismiss="modal" class="close" title="{"LBL_CLOSE"|t}">&times;</button>
				<h3 class="modal-title">{"LBL_ADDING_COMMENT"|t:$MODULE}</h3>
			</div>
			<form class="form-horizontal" id="massSave" method="post" action="index.php">
				<input type="hidden" name="module" value="{$MODULE}" />
				<input type="hidden" name="source_module" value="{$SOURCE_MODULE}" />
				<input type="hidden" name="action" value="MassSaveAjax" />
				<input type="hidden" name="viewname" value="{$CVID}" />
				<input type="hidden" name="selected_ids" value={\App\Utils\Json::encode($SELECTED_IDS)}>
				<input type="hidden" name="excluded_ids" value={\App\Utils\Json::encode($EXCLUDED_IDS)}>
				<input type="hidden" name="search_key" value= "{$SEARCH_KEY}" />
				<input type="hidden" name="operator" value="{$OPERATOR}" />
				<input type="hidden" name="search_value" value="{$ALPHABET_VALUE}" />
				<input type="hidden" name="search_params" value='{\App\Utils\Json::encode($SEARCH_PARAMS)}' />

				<div class="modal-body tabbable">
					<textarea class="input-lg form-control" name="commentcontent" id="commentcontent" title="{"LBL_WRITE_YOUR_COMMENT_HERE"|t:$MODULE}" rows="{$COMMENT_TEXTAREA_DEFAULT_ROWS}" placeholder="{"LBL_WRITE_YOUR_COMMENT_HERE"|t:$MODULE}..."></textarea>
				</div>
				{include file='ModalFooter.tpl'|@vtemplate_path:$MODULE}
			</form>
		</div>
	</div>
</div>
<!--/layouts/basic/modules/Base/AddCommentForm.tpl -->
{/strip}
