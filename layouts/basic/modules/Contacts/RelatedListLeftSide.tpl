{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Contacts/RelatedListLeftSide.tpl -->
	{if $IS_FAVORITES}
		{assign var=RECORD_IS_FAVORITE value=(int)in_array($RELATED_RECORD->getId(),$FAVORITES)}
		<div>
			<a class="favorites" data-state="{$RECORD_IS_FAVORITE}">
				<span title="{"LBL_REMOVE_FROM_FAVORITES"|t:$MODULE}" class="glyphicon glyphicon-star alignMiddle {if !$RECORD_IS_FAVORITE}hide{/if}"></span>
				<span title="{"LBL_ADD_TO_FAVORITES"|t:$MODULE}" class="glyphicon glyphicon-star-empty alignMiddle {if $RECORD_IS_FAVORITE}hide{/if}"></span>
			</a>
		</div>
	{/if}
	<div class="actions">
		<span class="glyphicon glyphicon-wrench toolsAction alignMiddle"></span>
		<span class="actionImages hide">
			{if $CAN_SEND_MAILS}
				{if $USER_MODEL->get('internal_mailer') == 1}
					{if isset($OSSMail_URLS[$RELATED_RECORD->getId()]) && $OSSMail_URLS[$RELATED_RECORD->getId()]['type'] == 'compose'}
						<a target="_blank" href="{$OSSMail_URLS[$RELATED_RECORD->getId()]['url']}" title="{"LBL_SEND_EMAIL"|t}">
							<span class="glyphicon glyphicon-envelope alignMiddle" aria-hidden="true"></span>
						</a>&nbsp;
					{/if}
				{else}
					{if isset($OSSMail_URLS[$RELATED_RECORD->getId()]) && $OSSMail_URLS[$RELATED_RECORD->getId()]['type'] == 'external'}
						<a href="{$OSSMail_URLS[$RELATED_RECORD->getId()]['url']}" title="{"LBL_CREATEMAIL"|t:"OSSMailView"}">
							<span class="glyphicon glyphicon-envelope alignMiddle" title="{"LBL_CREATEMAIL"|t:"OSSMailView"}"></span>
						</a>&nbsp;
					{/if}
				{/if}
			{/if}
			{if $RELATED_MODULE->isPermitted('WatchingRecords') && $RELATED_RECORD->isViewable()}
				{assign var=WATCHING_STATE value=(!$RELATED_RECORD->isWatchingRecord())|intval}
				<a href="#" onclick="Vtiger_Index_Js.changeWatching(this)" title="{"BTN_WATCHING_RECORD"|t:$MODULE}" data-record="{$RELATED_RECORD->getId()}" data-value="{$WATCHING_STATE}" class="noLinkBtn{if !$WATCHING_STATE} info-color{/if}" data-on="info-color" data-off="" data-icon-on="glyphicon-eye-open" data-icon-off="glyphicon-eye-close" data-module="{$RELATED_MODULE_NAME}">
					<span class="glyphicon {if $WATCHING_STATE}glyphicon-eye-close{else}glyphicon-eye-open{/if} alignMiddle"></span>
				</a>&nbsp;
			{/if}
			<a href="{$RELATED_RECORD->getFullDetailViewUrl()}">
				<span title="{"LBL_SHOW_COMPLETE_DETAILS"|t:$MODULE}" class="glyphicon glyphicon-th-list alignMiddle"></span>
			</a>&nbsp;
			{if $IS_EDITABLE && $RELATED_RECORD->isEditable()}
				{if $RELATED_MODULE_NAME eq 'PriceBooks'}
					<a data-url="index.php?module=PriceBooks&view=ListViewPriceUpdate&record={$PARENT_RECORD->getId()}&relid={$RELATED_RECORD->getId()}&currentPrice={$LISTPRICE}"
					   class="editListPrice cursorPointer" data-related-recordid='{$RELATED_RECORD->getId()}' data-list-price={$LISTPRICE}>
						<span class="glyphicon glyphicon-pencil alignMiddle" title="{"LBL_EDIT"|t:$MODULE}"></span>
					</a>&nbsp;
				{elseif $RELATED_MODULE_NAME eq 'Calendar'}
					{if $RELATED_RECORD->isEditable()}
						<a href='{$RELATED_RECORD->getEditViewUrl()}'>
							<span title="{"LBL_EDIT"|t:$MODULE}" class="glyphicon glyphicon-pencil alignMiddle"></span>
						</a>&nbsp;
					{/if}
				{else}
					<a href='{$RELATED_RECORD->getEditViewUrl()}'>
						<span title="{"LBL_EDIT"|t:$MODULE}" class="glyphicon glyphicon-pencil alignMiddle"></span>
					</a>&nbsp;
				{/if}
			{/if}
			{if ($IS_EDITABLE && $RELATED_RECORD->isEditable() && $RELATED_RECORD->editFieldByModalPermission()) || $RELATED_RECORD->editFieldByModalPermission(true)}
				{assign var=FIELD_BY_EDIT_DATA value=$RELATED_RECORD->getFieldToEditByModal()}
				<a class="showModal {$FIELD_BY_EDIT_DATA['listViewClass']}" data-url="{$RELATED_RECORD->getEditFieldByModalUrl()}">
					<span title="{{$FIELD_BY_EDIT_DATA['titleTag']}|t:$MODULE}" class="glyphicon {$FIELD_BY_EDIT_DATA['iconClass']} alignMiddle"></span>
				</a>&nbsp;
			{/if}
			{if $IS_DELETABLE && $RELATED_RECORD->isDeletable()}
				<a class="relationDelete">
					<span title="{"LBL_DELETE"|t:$MODULE}" class="glyphicon glyphicon-trash alignMiddle"></span>
				</a>&nbsp;
			{/if}
		</span>
	</div>
	{if $SHOW_MODTRACKER_UNREVIEWED && $RELATED_RECORD->isViewable()}
		<div>
			<a href="{$RELATED_RECORD->getUpdatesUrl()}" class="unreviewed alignMiddle">
				<span class="badge bgDanger all" title="{"LBL_NUMBER_UNREAD_CHANGES"|t:"ModTracker"}"></span>
				<span class="badge bgBlue mail noLeftRadius noRightRadius" title="{"LBL_NUMBER_UNREAD_MAILS"|t:"ModTracker"}"></span>
			</a>
		</div>
	{/if}
<!--/layouts/basic/modules/Contacts/RelatedListLeftSide.tpl -->
{/strip}
