{strip}
<!-- layouts/basic/modules/Users/PreferenceMailboxContent.tpl -->
<div class="editViewContainer">
	<div class="widget_header row">
		<div class="col-md-8">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
		</div>
		<div class="col-md-4">
			<div class="pull-right-md pull-left-sm pull-right-lg">
				<div class="btn-toolbar" style="padding-right: 15px">
					<span class="btn-group">
						<button type="button" class="btn btn-default js-personal-mail-test"><strong>{"LBL_TEST_CONNECTION"|t:"Mail"}</strong></button>
					</span>
					<span class="btn-group">
						<button type="button" class="btn btn-success js-personal-mail-save"><strong>{"LBL_SAVE_MAILBOX"|t:"Mail"}</strong></button>
					</span>
					<span class="btn-group">
						<a href="{$PREFERENCE_DETAIL_URL}" class="cancelLink btn btn-warning">{"LBL_CANCEL"|t:$MODULE}</a>
					</span>
				</div>
			</div>
		</div>
	</div>
	{include file='PreferenceMailbox.tpl'|@vtemplate_path:'Mail'}
</div>
<!--/layouts/basic/modules/Users/PreferenceMailboxContent.tpl -->
{/strip}
