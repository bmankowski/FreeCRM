{strip}
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">
	<div class="row widget_header">
		<div class="col-xs-12">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			{'LBL_AI_PROVIDER_DESCRIPTION'|t:$QUALIFIED_MODULE}
		</div>
	</div>
	<div class="editViewContainer">
			<form name="EditAiProvider" action="index.php" method="post" id="EditAiProvider" class="form-horizontal validateForm"
			data-has-api-key="{if $PROVIDER_CONFIG.has_api_key}1{else}0{/if}">
			<div class="alert alert-block alert-danger fade in js-provider-alert-error" hidden="">
				<button type="button" class="close" data-dismiss="alert">×</button>
				<h4 class="alert-heading">{'LBL_ERROR'|t:$QUALIFIED_MODULE}</h4>
				<p></p>
			</div>
			<div class="alert alert-block alert-success fade in js-provider-alert-success" hidden="">
				<button type="button" class="close" data-dismiss="alert">×</button>
				<p></p>
			</div>
			<input type="hidden" name="module" value="AiPrompts">
			<input type="hidden" name="parent" value="Settings" />
			<input type="hidden" name="action" value="SaveProviderAjax">
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_AI_PROVIDER_NAME'|t:$QUALIFIED_MODULE}
				</label>
				<div class="controls col-md-8">
					<p class="form-control-static">OpenAI</p>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_AI_API_KEY'|t:$QUALIFIED_MODULE}
				</label>
				<div class="controls col-md-8">
					<input class="form-control js-api-key" type="password" name="api_key" value="" autocomplete="new-password" placeholder="{if $PROVIDER_CONFIG.has_api_key}{'LBL_AI_API_KEY_STORED_PLACEHOLDER'|t:$QUALIFIED_MODULE}{else}{'LBL_AI_API_KEY_PLACEHOLDER'|t:$QUALIFIED_MODULE}{/if}">
					<label class="checkbox" style="margin-top:8px;">
						<input type="checkbox" name="clear_api_key" value="1" class="js-clear-api-key">
						{'LBL_AI_CLEAR_API_KEY'|t:$QUALIFIED_MODULE}
					</label>
					{if $PROVIDER_CONFIG.has_api_key}
						<p class="help-block">{'LBL_AI_API_KEY_KEEP_HINT'|t:$QUALIFIED_MODULE}</p>
					{/if}
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_AI_MODEL'|t:$QUALIFIED_MODULE} <span class="redColor">*</span>
				</label>
				<div class="controls col-md-8">
					<div class="row">
						<div class="col-sm-8" style="margin-bottom:8px;">
							<select class="form-control js-ai-model select2" name="model" data-validation-engine="validate[required]">
								{assign var=CURRENT_MODEL value=$PROVIDER_CONFIG.model}
								{assign var=MODEL_FOUND value=false}
								{foreach from=$SUGGESTED_MODELS item=M}
									<option value="{$M|escape}" {if $CURRENT_MODEL eq $M}selected{assign var=MODEL_FOUND value=true}{/if}>{$M|escape}</option>
								{/foreach}
								{if !$MODEL_FOUND && $CURRENT_MODEL}
									<option value="{$CURRENT_MODEL|escape}" selected>{$CURRENT_MODEL|escape}</option>
								{/if}
							</select>
						</div>
						<div class="col-sm-4" style="margin-bottom:8px;">
							<button type="button" class="btn btn-default btn-block js-fetch-models" title="{'LBL_AI_FETCH_MODELS'|t:$QUALIFIED_MODULE}">
								<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
								&nbsp;{'LBL_AI_FETCH_MODELS'|t:$QUALIFIED_MODULE}
							</button>
						</div>
					</div>
					<p class="help-block js-models-status text-muted">{'LBL_AI_MODEL_HELP'|t:$QUALIFIED_MODULE}</p>
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
{/block}
{/strip}
