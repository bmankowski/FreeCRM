{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/HistoryRelation.tpl -->
	<div class="recentActivitiesContainer row no-margin" >
		<input type="hidden" id="relatedHistoryCurrentPage" value="{$PAGING_MODEL->get('page')}" />
		<input type="hidden" id="relatedHistoryPageLimit" value="{$PAGING_MODEL->getPageLimit()}" />
		{if !empty($HISTORIES)}
			<ul class="timeline" id="relatedUpdates">
				{foreach item=HISTORY from=$HISTORIES}
					<li>
						<span class="glyphicon {$HISTORY['class']} userIcon-{$HISTORY['type']}" aria-hidden="true"></span>
						<div class="timeline-item">
							<div class="pull-left paddingRight15 imageContainer">
								{if !$HISTORY['isGroup']}
									<img class="userImage img-circle" src="{$HISTORY['userModel']->getImagePath()}">
								{else}
									<img class="userImage img-circle" src="{vimage_path('DefaultUserIcon.png')}">
								{/if}
							</div>
							<div class="timeline-body row no-margin">
								<div class="pull-right">
									<span class="time">
										<span title="{$HISTORY['time']}">{\App\Modules\Base\Helpers\Util::formatDateDiffInStrings($HISTORY['time'])}</span>
									</span>
								</div>
								<strong>{$HISTORY['userModel']->getName()}&nbsp;</strong>
								<a href="{$HISTORY['url']}" target="_blank">{$HISTORY['content']}</a>
								{if $HISTORY['attachments_exist'] eq 1}
									&nbsp;<span class="body-icon glyphicon glyphicon-paperclip"></span>
								{/if}
								{if !$IS_READ_ONLY && $HISTORY['type'] eq 'Mail'}
									<div class="pull-right marginRight10 btn-group" role="group">
										<button data-url="{$HISTORY['url']}" type="button" title="{"LBL_SHOW_PREVIEW_EMAIL"|t:"Mail"}" class="showModal btn btn-xs btn-default">
											<span class="body-icon glyphicon glyphicon-search"></span>
										</button>
									{if \App\Core\AppConfig::main('isActiveSendingMails') && \App\Modules\Mail\Models\Module::canUserSend($USER_MODEL->getId())}
										{assign var=COMPOSE_URL value=\App\Modules\Mail\Models\Module::getComposeUrl($MODULE_NAME, $RECORD_ID)}
										<button type="button" class="btn btn-xs btn-default sendMailBtn" data-url="{$COMPOSE_URL}&replyTo={$HISTORY['id']}" title="{"LBL_COMPOSE"|t:"Mail"}">
											<span class="glyphicon glyphicon-envelope"></span>
										</button>
									{/if}
									</div>
								{/if}<br>
								{$HISTORY['body']}
							</div>
						</div>
					</li>
				{/foreach}
			</ul>
			{if !$IS_READ_ONLY && count($HISTORIES) eq $PAGING_MODEL->getPageLimit() && !$NO_MORE}
				<div id="moreRelatedUpdates">
					<div class="pull-right">
						<button type="button" class="btn btn-primary btn-xs moreRelatedUpdates cursorPointer">{"LBL_MORE"|t:$MODULE_NAME}..</button>
					</div>
				</div>
			{/if}
		{else}
			{if $PAGING_MODEL->get('page') eq 1}
				<div class="summaryWidgetContainer">
					<p class="textAlignCenter">{"LBL_NO_RECENT_UPDATES"|t}</p>
				</div>
			{/if}
		{/if}
	</div>
<!--/layouts/basic/modules/Base/HistoryRelation.tpl -->
{/strip}
