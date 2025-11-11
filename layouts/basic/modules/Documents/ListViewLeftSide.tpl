{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Documents/ListViewLeftSide.tpl -->
	<div>
		<input type="checkbox" value="{$LISTVIEW_ENTRY->getId()}" class="listViewEntriesCheckBox" title="{"LBL_SELECT_SINGLE_ROW"|t}" />
	</div>&nbsp;
	{assign var=IMAGE_CLASS value=$DOCUMENT_IMAGE_CLASSES[$LISTVIEW_ENTRY->getId()]}
	<span class="{$IMAGE_CLASS} fa-lg middle {if $IMAGE_CLASS eq 'userIcon-Documents'}back4RightMargin{/if}"></span>
	{assign var=LINKS value=$LISTVIEW_ENTRY->getRecordListViewLinksLeftSide()}
	{if count($LINKS) > 0}
		{assign var=ONLY_ONE value=count($LINKS) eq 1}
		<div class="actions">
			<div class="{if !$ONLY_ONE}actionImages hide{/if}">
				{foreach from=$LINKS item=LINK}
					{include file='ButtonLink.tpl'|@vtemplate_path:$MODULE BUTTON_VIEW='listViewBasic'}
				{/foreach}
			</div>
			{if !$ONLY_ONE}
				<button type="button" class="btn btn-sm btn-default toolsAction">
					<span class="glyphicon glyphicon-wrench"></span>
				</button>
			{/if}
		</div>
	{/if}
	<div>
		{if $SHOW_TIMELINE_IN_LISTVIEW}
			<a type="button" data-url="{$LISTVIEW_ENTRY->getTimeLineUrl()}" class="timeLineIconList hide">
				<span class="glyphicon" aria-hidden="true"></span>
			</a>
		{/if}
		{if $SHOW_MODTRACKER_UNREVIEWED && $LISTVIEW_ENTRY->isViewable()}
			<a href="{$LISTVIEW_ENTRY->getUpdatesUrl()}" class="unreviewed">
				<span class="badge bgDanger all" title="{"LBL_NUMBER_UNREAD_CHANGES"|t:"ModTracker"}"></span>
				<span class="badge bgBlue mail noLeftRadius noRightRadius" title="{"LBL_NUMBER_UNREAD_MAILS"|t:"ModTracker"}"></span>
			</a>
		{/if}
	</div>
<!--/layouts/basic/modules/Documents/ListViewLeftSide.tpl -->
{/strip}

