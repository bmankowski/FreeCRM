{strip}
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">
	<div class="row widget_header">
		<div class="col-xs-12">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			{if $RECORD_ID}
				{'LBL_AIPROMPTS_EDIT'|t:$QUALIFIED_MODULE}
			{else}
				{'LBL_AIPROMPTS_CREATE'|t:$QUALIFIED_MODULE}
			{/if}
		</div>
	</div>
	<div class="editViewContainer">
		<form name="EditAiPrompt" action="index.php" method="post" id="EditView" class="form-horizontal validateForm">
			<div class="alert alert-block alert-danger fade in" hidden="">
				<button type="button" class="close" data-dismiss="alert">×</button>
				<h4 class="alert-heading">{'LBL_ERROR'|t:$QUALIFIED_MODULE}</h4>
				<p></p>
			</div>
			<input type="hidden" name="module" value="AiPrompts">
			<input type="hidden" name="parent" value="Settings" />
			<input type="hidden" name="action" value="SaveAjax">
			<input type="hidden" name="record" value="{$RECORD_ID}">
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_NAME'|t:$QUALIFIED_MODULE} <span class="redColor">*</span>
				</label>
				<div class="controls col-md-8">
					<input class="form-control" type="text" name="name" value="{$RECORD_MODEL->get('name')}" data-validation-engine="validate[required]">
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_ACTION_KEY'|t:$QUALIFIED_MODULE} <span class="redColor">*</span>
				</label>
				<div class="controls col-md-8">
					<select class="select2 form-control js-action-key" name="action_key" data-validation-engine="validate[required]">
						{foreach from=$ACTION_OPTIONS item=OPTION}
							<option value="{$OPTION.key}" {if $RECORD_MODEL->get('action_key') eq $OPTION.key}selected{/if}>
								{$OPTION.label|t:$QUALIFIED_MODULE} ({$OPTION.key})
							</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_PLACEHOLDERS'|t:$QUALIFIED_MODULE}
				</label>
				<div class="controls col-md-8">
					<p class="form-control-static js-placeholders-help text-muted"></p>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_PROMPT_BODY'|t:$QUALIFIED_MODULE} <span class="redColor">*</span>
				</label>
				<div class="controls col-md-8">
					<textarea class="form-control" name="prompt_body" rows="16" data-validation-engine="validate[required]">{$RECORD_MODEL->get('prompt_body')}</textarea>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_ACTIVE'|t:$QUALIFIED_MODULE}
				</label>
				<div class="controls col-md-8">
					<input type="checkbox" name="active" value="1" {if $RECORD_MODEL->get('active') eq 1 || $RECORD_MODEL->get('active') === null || $RECORD_MODEL->get('active') === ''}checked{/if}>
				</div>
			</div>
			<div class="form-group">
				<div class="col-md-offset-3 col-md-8">
					<span class="pull-right">
						<button class="btn btn-success" type="submit">
							<strong>{'LBL_SAVE'|t:$QUALIFIED_MODULE}</strong>
						</button>
						<a class="cancelLink btn btn-warning" href="index.php?module=AiPrompts&parent=Settings&view=ListView">{'LBL_CANCEL'|t:$QUALIFIED_MODULE}</a>
					</span>
				</div>
			</div>
		</form>
	</div>
			</div>
		</div>
<script type="text/javascript">
	window.AiPromptsPlaceholders = {$PLACEHOLDERS_JSON nofilter};
</script>
{/block}
{/strip}
