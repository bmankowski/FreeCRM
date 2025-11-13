{*<!--
/*********************************************************************************
  ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
   * ("License"); You may not use this file except in compliance with the License
   * The Original Code is:  vtiger CRM Open Source
   * The Initial Developer of the Original Code is vtiger.
   * Portions created by vtiger are Copyright (C) vtiger.
   * All Rights Reserved.
   * Contributor(s): YetiForce.com
 ********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Base/dashboards/HistoryContents.tpl -->
<div style='padding:5px;'>
{if $HISTORIES neq false}
	{foreach key=$index item=HISTORY from=$HISTORIES}
		{assign var=MODELNAME value=get_class($HISTORY)}
		{if $MODELNAME == '\App\Modules\ModTracker\Models\Record'}
			{assign var=USER value=$HISTORY->getModifiedBy()}
			{assign var=TIME value=$HISTORY->getActivityTime()}
			{assign var=PARENT value=$HISTORY->getParent()}
			{assign var=MOD_NAME value=$HISTORY->getParent()->getModule()->getName()}
			{assign var=SINGLE_MODULE_NAME value='SINGLE_'|cat:$MOD_NAME}
			{assign var=TRANSLATED_MODULE_NAME value = $SINGLE_MODULE_NAME|t:$MOD_NAME}
			{assign var=PROCEED value= TRUE}
			{if ($HISTORY->isRelationLink()) or ($HISTORY->isRelationUnLink())}
				{assign var=RELATION value=$HISTORY->getRelationInstance()}
				{if !($RELATION->getLinkedRecord())}
					{assign var=PROCEED value= FALSE}
				{/if}
			{/if}
			{if $PROCEED}
				<div class="row">
					<div class='col-md-1'>
						{if vimage_path($MOD_NAME|cat:'.png')}
							<img width='24px' src="{vimage_path($MOD_NAME|cat:'.png')}" alt="{$TRANSLATED_MODULE_NAME}" title="{$TRANSLATED_MODULE_NAME}" />&nbsp;&nbsp;
						{else}
							<span class="glyphicon glyphicon-menu-hamburger icon-in-history-widget" title="{$TRANSLATED_MODULE_NAME}"></span>
						{/if}
					</div>
					<div class="col-md-11">
					<p class="pull-right muted" style="padding-right:5px;"><small title="{\App\Modules\Base\Helpers\Util::formatDateTimeIntoDayString("$TIME")}">{\App\Modules\Base\Helpers\Util::formatDateDiffInStrings("$TIME")}</small></p>
					{assign var=DETAILVIEW_URL value=$PARENT->getDetailViewUrl()}
					{if $HISTORY->isUpdate()}
						{assign var=FIELDS value=$HISTORY->getFieldInstances()}
						<div class="">
							<div><strong>{$USER->getName()}&nbsp;</strong> {"LBL_UPDATED"|t}&nbsp; <a class="cursorPointer" {if stripos($DETAILVIEW_URL, 'javascript:')===0}
								onclick='{$DETAILVIEW_URL|substr:strlen("javascript:")}' {else} onclick='window.location.href="{$DETAILVIEW_URL}"' {/if}>
								{$PARENT->getName()}</a>
							</div>
							{foreach from=$FIELDS key=INDEX item=FIELD}
							{if $INDEX lt 2}
								{if $FIELD && $FIELD->getFieldInstance() && $FIELD->getFieldInstance()->isViewableInDetailView()}
								<div class='font-x-small'>
									<span>{$FIELD->getName()|t:$FIELD->getModuleName()}</span>
									{if $FIELD->get('prevalue') neq '' && $FIELD->get('postvalue') neq '' && !($FIELD->getFieldInstance()->getFieldDataType() eq 'reference' && ($FIELD->get('postvalue') eq '0' || $FIELD->get('prevalue') eq '0'))}
										&nbsp;{"LBL_FROM"|t}&nbsp; <strong>{\App\Modules\Base\Helpers\Util::toVtiger6SafeHTML($FIELD->getDisplayValue(decode_html($FIELD->get('prevalue'))))}</strong>
									{else if $FIELD->get('postvalue') eq '' || ($FIELD->getFieldInstance()->getFieldDataType() eq 'reference' && $FIELD->get('postvalue') eq '0')}
	                                    &nbsp; <strong> {"LBL_DELETED"|t} </strong> ( <del>{\App\Modules\Base\Helpers\Util::toVtiger6SafeHTML($FIELD->getDisplayValue(decode_html($FIELD->get('prevalue'))))}</del> )
	                                {else}
										&nbsp;{"LBL_CHANGED"|t}
									{/if}
	                                {if $FIELD->get('postvalue') neq '' && !($FIELD->getFieldInstance()->getFieldDataType() eq 'reference' && $FIELD->get('postvalue') eq '0')}
										&nbsp;{"LBL_TO"|t}&nbsp;<strong>{\App\Modules\Base\Helpers\Util::toVtiger6SafeHTML($FIELD->getDisplayValue(decode_html($FIELD->get('postvalue'))))}</strong>
	                                {/if}    
								</div>
								{/if}
							{else}
								<a class="btn btn-info btn-xs moreBtn" href="{$PARENT->getUpdatesUrl()}">{"LBL_MORE"|t}</a>
								{break}
							{/if}
							{/foreach}
						</div>
					{else if $HISTORY->isCreate()}
						<div style='margin-top:5px'>
							<strong>{$USER->getName()}&nbsp;</strong> {"LBL_ADDED"|t} <a class="cursorPointer" {if stripos($DETAILVIEW_URL, 'javascript:')===0}
								onclick='{$DETAILVIEW_URL|substr:strlen("javascript:")}' {else} onclick='window.location.href="{$DETAILVIEW_URL}"' {/if}>
								&nbsp;{$PARENT->getName()}</a>
						</div>
					{else if $HISTORY->isDisplayed()}
						<div style='margin-top:5px'>
							<strong>{$USER->getName()}&nbsp;</strong> {"LBL_DISPLAYED"|t} <a class="cursorPointer" {if stripos($DETAILVIEW_URL, 'javascript:')===0}
								onclick='{$DETAILVIEW_URL|substr:strlen("javascript:")}' {else} onclick='window.location.href="{$DETAILVIEW_URL}"' {/if}>
								&nbsp;{$PARENT->getName()}</a>
						</div>
					{else if ($HISTORY->isRelationLink() || $HISTORY->isRelationUnLink())}
						{assign var=RELATION value=$HISTORY->getRelationInstance()}
						{assign var=LINKED_RECORD_DETAIL_URL value=$RELATION->getLinkedRecord()->getDetailViewUrl()}
						{assign var=PARENT_DETAIL_URL value=$RELATION->getParent()->getParent()->getDetailViewUrl()}
						<div class='' style='margin-top:5px'>
							<strong>{$USER->getName()}&nbsp;</strong>
								{if $HISTORY->isRelationLink()}
									{"LBL_ADDED"|t:$MODULE_NAME}&nbsp;
								{else}
									{"LBL_REMOVED"|t:$MODULE_NAME}
								{/if}
								{if $RELATION->getLinkedRecord()->getModuleName() eq 'Calendar'}
									{if \App\Privilege::isPermitted('Calendar', 'DetailView', $RELATION->getLinkedRecord()->getId())}
										<a class="cursorPointer" {if stripos($LINKED_RECORD_DETAIL_URL, 'javascript:')===0} onclick='{$LINKED_RECORD_DETAIL_URL|substr:strlen("javascript:")}'
											{else} onclick='window.location.href="{$LINKED_RECORD_DETAIL_URL}"' {/if}>{$RELATION->getLinkedRecord()->getName()}</a>
									{else}
										{$RELATION->getLinkedRecord()->getModuleName()|t:$RELATION->getLinkedRecord()->getModuleName()}
									{/if}
								{else}
								 <a class="cursorPointer" {if stripos($LINKED_RECORD_DETAIL_URL, 'javascript:')===0} onclick='{$LINKED_RECORD_DETAIL_URL|substr:strlen("javascript:")}'
									{else} onclick='window.location.href="{$LINKED_RECORD_DETAIL_URL}"' {/if}>{$RELATION->getLinkedRecord()->getName()|t:$RELATION->getLinkedRecord()->getModuleName()}</a>
								{/if}{"LBL_FOR"|t} <a class="cursorPointer" {if stripos($PARENT_DETAIL_URL, 'javascript:')===0}
								onclick='{$PARENT_DETAIL_URL|substr:strlen("javascript:")}' {else} onclick='window.location.href="{$PARENT_DETAIL_URL}"' {/if}>
								{$RELATION->getParent()->getParent()->getName()}</a>
						</div>
					{else if $HISTORY->isRestore()}
						<div class=''  style='margin-top:5px'>
							<strong>{$USER->getName()}&nbsp;</strong> {"LBL_RESTORED"|t} <a class="cursorPointer" {if stripos($DETAILVIEW_URL, 'javascript:')===0}
								onclick='{$DETAILVIEW_URL|substr:strlen("javascript:")}' {else} onclick='window.location.href="{$DETAILVIEW_URL}"' {/if}>
								{$PARENT->getName()}</a>
						</div>
					{else if $HISTORY->isDelete()}
						<div class=''  style='margin-top:5px'>
							<strong>{$USER->getName()}&nbsp;</strong> {"LBL_DELETED"|t} <a class="cursorPointer" {if stripos($DETAILVIEW_URL, 'javascript:')===0}
								onclick='{$DETAILVIEW_URL|substr:strlen("javascript:")}' {else} onclick='window.location.href="{$DETAILVIEW_URL}"' {/if}>
								{$PARENT->getName()}</a>
						</div>
					{/if}
					</div>
				</div>
			{/if}
			{else if $MODELNAME == '\App\Modules\ModComments\Models\Record'}
			{assign var=TRANSLATED_MODULE_NAME value = 'SINGLE_ModComments'|t:'ModComments'}
			<div class="row">
				<div class="col-md-1">
					<img width='24px' src="{vimage_path('ModComments.png')}" alt="{$TRANSLATED_MODULE_NAME}" title="{$TRANSLATED_MODULE_NAME}" />&nbsp;&nbsp;
				</div>
				<div class="col-md-11">
					{assign var=COMMENT_TIME value=$HISTORY->getCommentedTime()}
					<p class="pull-right muted" style="padding-right:5px;"><small title="{\App\Modules\Base\Helpers\Util::formatDateTimeIntoDayString("$COMMENT_TIME")}">{\App\Modules\Base\Helpers\Util::formatDateDiffInStrings("$COMMENT_TIME")}</small></p>
					<div>
						<strong>{$HISTORY->getCommentedByModel()->getName()}</strong> {"LBL_COMMENTED"|t} {"LBL_ON"|t} <a class="textOverflowEllipsis" href="{$HISTORY->getParentRecordModel()->getDetailViewUrl()}">{$HISTORY->getParentRecordModel()->getName()}</a>
					</div>
					<div class='font-x-small'><span>"{nl2br($HISTORY->get('commentcontent'))}"</span></div>
				</div>
			</div>
		{/if}
	{/foreach}

	{if $NEXTPAGE}
	<div class="row">
		<div class="col-md-12">
			<button class="load-more btn btn-xs btn-info" data-page="{$PAGE}" data-nextpage="{$NEXTPAGE}">{"LBL_MORE"|t}</button>
		</div>
	</div>
	{/if}

{else}
	<span class="noDataMsg">
		{"LBL_NO_UPDATES_OR_COMMENTS"|t:$MODULE_NAME}
	</span>
{/if}
</div>
<!--/layouts/basic/modules/Base/dashboards/HistoryContents.tpl -->
{/strip}
