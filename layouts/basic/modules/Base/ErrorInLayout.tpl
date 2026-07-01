{*<!-- {[The file is published on the basis of FreeCRM Public License that can be found in the following directory: licenses/LicenseEN.txt]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/ErrorInLayout.tpl -->
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
	<div class="mainContainer">
		<div class="contentsDiv col-md-12 marginLeftZero" id="centerPanel" style="min-height:550px;">
			<div class="widget_header row marginBottom10px">
				<div class="col-md-12">
					<h4 class="no-margin">{$ERROR_TITLE|t}</h4>
				</div>
			</div>
			<div class="detailViewContainer">
				<div class="panel panel-default" style="max-width: 640px; margin: 24px auto 0;">
					<div class="panel-body text-center" style="padding: 48px 32px 32px;">
						{if $ERROR_CLASS eq 'alert-warning'}
							{assign var=ERROR_ICON value='fa-lock'}
						{elseif $ERROR_CLASS eq 'alert-info'}
							{assign var=ERROR_ICON value='fa-search'}
						{else}
							{assign var=ERROR_ICON value='fa-exclamation-triangle'}
						{/if}
						<div style="font-size: 3em; color: #b0b8c0; margin-bottom: 20px; line-height: 1;">
							<span class="fas {$ERROR_ICON}" aria-hidden="true"></span>
						</div>
						<p class="text-muted" style="max-width: 480px; margin: 0 auto 28px; font-size: 14px; line-height: 1.6;">
							{$MESSAGE|t}
						</p>
						<div class="btn-toolbar" style="display: inline-block;">
							<div class="btn-group">
								<a class="btn btn-default" href="javascript:window.history.back();">{"LBL_GO_BACK"|t}</a>
								<a class="btn btn-primary" href="index.php">{"LBL_MAIN_PAGE"|t}</a>
							</div>
						</div>
					</div>
					{if $STACK_TRACE}
						<div class="panel-footer text-right" style="background: #fafafa; border-top: 1px solid #ededed;">
							{include file='ErrorStackTraceButton.tpl'|@vtemplate_path:'Base'}
						</div>
					{/if}
				</div>
			</div>
		</div>
	</div>
{/block}
<!--/layouts/basic/modules/Base/ErrorInLayout.tpl -->
{/strip}
