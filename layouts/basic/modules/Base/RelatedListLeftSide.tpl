{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/RelatedListLeftSide.tpl -->
	{* Optional per-record left icon (map: record id → CSS class); e.g. RelatedList::prepareDocumentsRelatedListData *}
	{if isset($RECORD_LEFT_ICON_CLASSES) && isset($RECORD_LEFT_ICON_CLASSES[$RELATED_RECORD->getId()])}
		{assign var=RECORD_LEFT_ICON_CLASS value=$RECORD_LEFT_ICON_CLASSES[$RELATED_RECORD->getId()]}
		<span class="{$RECORD_LEFT_ICON_CLASS} fa-lg">{if $RECORD_LEFT_ICON_CLASS neq 'userIcon-Documents'}&nbsp;{/if}</span>
	{/if}
	{if isset($IS_FAVORITES) && $IS_FAVORITES}
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
			{foreach from=$RELATED_LIST_LEFT_LINKS[$RELATED_RECORD->getId()]|default:[] item=LINK}
				{include file=vtemplate_path('RelatedListLeftSideLink.tpl','Base')}&nbsp;
			{/foreach}
		</span>
	</div>
	{if \App\Core\AppConfig::module('ModTracker', 'UNREVIEWED_COUNT') && $RELATED_MODULE->isPermitted('ReviewingUpdates') && $RELATED_MODULE->isTrackingEnabled() && $RELATED_RECORD->isViewable()}
		<div>
			<a href="{$RELATED_RECORD->getUpdatesUrl()}" class="unreviewed alignMiddle">
				<span class="badge bgDanger all" title="{"LBL_NUMBER_UNREAD_CHANGES"|t:"ModTracker"}"></span>
				<span class="badge bgBlue mail noLeftRadius noRightRadius" title="{"LBL_NUMBER_UNREAD_MAILS"|t:"ModTracker"}"></span>
			</a>
		</div>
	{/if}
<!--/layouts/basic/modules/Base/RelatedListLeftSide.tpl -->
{/strip}
