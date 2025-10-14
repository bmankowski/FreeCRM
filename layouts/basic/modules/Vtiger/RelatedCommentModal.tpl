{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Vtiger/RelatedCommentModal.tpl -->
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3 id="massEditHeader" class="modal-title">{"LBL_EDIT_RELATED_COMMENT"|t:$MODULE}</h3>
	</div>
	<div class="modal-body">
		<input type="hidden" class="relatedRecord" value="{$RELATED_RECORD}" />
		<input type="hidden" class="relatedModuleName" value="{$RELATED_MODULE}" />
		<textarea class="form-control comment" rows="4">{$COMMENT}</textarea>
	</div>
	<div class="modal-footer">
		<div class="pull-right">
			<button class="btn btn-success" type="submit" name="saveButton"><strong>{"LBL_SAVE"|t:$MODULE}</strong></button>
			<button class="btn btn-warning" type="reset" data-dismiss="modal"><strong>{"LBL_CANCEL"|t:$MODULE}</strong></button>
		</div>
	</div>
<!--/layouts/basic/modules/Vtiger/RelatedCommentModal.tpl -->
{/strip}
