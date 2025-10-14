{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Vtiger/RecentActivitiesTimeLine.tpl -->
	<div class="recentActivitiesContainer row no-margin">
		<input type="hidden" id="updatesCurrentPage" value="{$PAGING_MODEL->get('page')}" />
		<input type="hidden" id="updatesPageLimit" value="{$PAGING_MODEL->getPageLimit()}" />
		{if !empty($RECENT_ACTIVITIES)}
			<div id="updates">
				<ul class="timeline">
					{assign var=COUNT value=0}
					{foreach item=RECENT_ACTIVITY from=$RECENT_ACTIVITIES name=recentActivites}
						{assign var=PROCEED value= TRUE}
						{if ($RECENT_ACTIVITY->isRelationLink()) or ($RECENT_ACTIVITY->isRelationUnLink())}
							{assign var=RELATION value=$RECENT_ACTIVITY->getRelationInstance()}
							{if !($RELATION->getLinkedRecord())}
								{assign var=PROCEED value= FALSE}
							{/if}
						{/if}
						{if $PROCEED}
							<li>
								{if $RECENT_ACTIVITY->isReviewed() && !($COUNT eq 0 && $PAGING_MODEL->get('page') eq 1)}
									{$NEW_CHANGE = false}
									<div class="lineOfText marginLeft15">
										<div>{vtranslate('LBL_REVIEWED', $MODULE_BASE_NAME)}</div>
									</div>
								{/if}
								{$COUNT=$COUNT+1}
								{if $RECENT_ACTIVITY->isCreate()}
									<span class="glyphicon glyphicon-plus bgGreen"></span>
									<div class="timeline-item{if $NEW_CHANGE} bgWarning{/if}">
										<div class="pull-left paddingRight15 imageContainer">
											<img class="userImage img-circle" src="{$RECENT_ACTIVITY->getModifiedBy()->getImagePath()}">
										</div>
										<div class="timeline-body row no-margin">
											<span class="time pull-right">
												<span title="{$RECENT_ACTIVITY->getDisplayActivityTime()}">{Vtiger_Util_Helper::formatDateDiffInStrings($RECENT_ACTIVITY->getParent()->get('createdtime'))}</span>
											</span>
											<strong>{$RECENT_ACTIVITY->getModifiedBy()->getName()}</strong> 
												&nbsp;{"LBL_CREATED"|t:$MODULE_NAME}
												{foreach item=FIELDMODEL from=$RECENT_ACTIVITY->getFieldInstances()}
													{if $FIELDMODEL && $FIELDMODEL->getFieldInstance() && $FIELDMODEL->getFieldInstance()->isViewable() && $FIELDMODEL->getFieldInstance()->getDisplayType() neq '5'}
														<div class='font-x-small updateInfoContainer'>
															<span>{vtranslate($FIELDMODEL->getName(),$MODULE_NAME)}</span>:&nbsp;
															{if $FIELDMODEL->get('postvalue') neq ''}
																<strong class="moreContent">
																	<span class="teaserContent">
																		{Vtiger_Util_Helper::toVtiger6SafeHTML($FIELDMODEL->getNewValue())}
																	</span>
																	{if $FIELDMODEL->has('fullPostValue')}
																		<span class="fullContent hide">
																			{$FIELDMODEL->get('fullPostValue')}
																		</span>
																		<button type="button" class="btn btn-info btn-xs moreBtn" data-on="{"LBL_MORE_BTN"|t}" data-off="{"LBL_HIDE_BTN"|t}">{"LBL_MORE_BTN"|t}</button>
																	{/if}
																</strong>
															{/if}
														</div>
													{/if}
												{/foreach}
										</div>
									</div>
								{else if $RECENT_ACTIVITY->isUpdate()}
									<span class="glyphicon glyphicon-pencil bgDarkBlue"></span>
									<div class="timeline-item{if $NEW_CHANGE} bgWarning{/if}">
										<div class="pull-left paddingRight15 imageContainer">
											<img class="userImage img-circle" src="{$RECENT_ACTIVITY->getModifiedBy()->getImagePath()}">
										</div>
										<div class="timeline-body row no-margin">
											<span class="time pull-right">
												<span title="{$RECENT_ACTIVITY->getDisplayActivityTime()}">{Vtiger_Util_Helper::formatDateDiffInStrings($RECENT_ACTIVITY->getActivityTime())}</span>
											</span>
											<span><strong>{$RECENT_ACTIVITY->getModifiedBy()->getDisplayName()}&nbsp;</strong> {"LBL_UPDATED"|t:$MODULE_NAME}</span>
											{foreach item=FIELDMODEL from=$RECENT_ACTIVITY->getFieldInstances()}
												{if $FIELDMODEL && $FIELDMODEL->getFieldInstance() && $FIELDMODEL->getFieldInstance()->isViewable() && $FIELDMODEL->getFieldInstance()->getDisplayType() neq '5'}
													<div class='font-x-small updateInfoContainer'>
														<span>{vtranslate($FIELDMODEL->getName(),$MODULE_NAME)}</span>:&nbsp;
														{if $FIELDMODEL->get('prevalue') neq '' && $FIELDMODEL->get('postvalue') neq '' && !($FIELDMODEL->getFieldInstance()->getFieldDataType() eq 'reference' && ($FIELDMODEL->get('postvalue') eq '0' || $FIELDMODEL->get('prevalue') eq '0'))}
															&nbsp;{"LBL_FROM"|t}&nbsp;
															<strong class="moreContent">
																<span class="teaserContent">
																	{Vtiger_Util_Helper::toVtiger6SafeHTML($FIELDMODEL->getOldValue())}
																</span>
																{if $FIELDMODEL->has('fullPreValue')}
																	<span class="fullContent hide">
																		{$FIELDMODEL->get('fullPreValue')}
																	</span>
																	<button type="button" class="btn btn-info btn-xs moreBtn" data-on="{"LBL_MORE_BTN"|t}" data-off="{"LBL_HIDE_BTN"|t}">{"LBL_MORE_BTN"|t}</button>
																{/if}
															</strong>
														{else if $FIELDMODEL->get('postvalue') eq '' || ($FIELDMODEL->getFieldInstance()->getFieldDataType() eq 'reference' && $FIELDMODEL->get('postvalue') eq '0')}
															&nbsp; 
															<strong>
																{"LBL_DELETED"|t}
															</strong>
															( <del>{Vtiger_Util_Helper::toVtiger6SafeHTML($FIELDMODEL->getOldValue())}</del> )
														{else}
															&nbsp;{"LBL_CHANGED"|t}
														{/if}
														{if $FIELDMODEL->get('postvalue') neq '' && !($FIELDMODEL->getFieldInstance()->getFieldDataType() eq 'reference' && $FIELDMODEL->get('postvalue') eq '0')}
															&nbsp;{"LBL_TO"|t}&nbsp;
															<strong class="moreContent">
																<span class="teaserContent">
																	{Vtiger_Util_Helper::toVtiger6SafeHTML($FIELDMODEL->getNewValue())}
																</span>
																{if $FIELDMODEL->has('fullPostValue')}
																	<span class="fullContent hide">
																		{$FIELDMODEL->get('fullPostValue')}
																	</span>
																	<button type="button" class="btn btn-info btn-xs moreBtn" data-on="{"LBL_MORE_BTN"|t}" data-off="{"LBL_HIDE_BTN"|t}">{"LBL_MORE_BTN"|t}</button>
																{/if}
															</strong>
														{/if}
													</div>
												{/if}
											{/foreach}
										</div>
									</div>
								{else if ($RECENT_ACTIVITY->isRelationLink() || $RECENT_ACTIVITY->isRelationUnLink())}
									<span class="glyphicon glyphicon-link bgOrange"></span>
									<div class="timeline-item{if $NEW_CHANGE} bgWarning{/if}">
										<div class="pull-left paddingRight15 imageContainer">
											<img class="userImage img-circle" src="{$RECENT_ACTIVITY->getModifiedBy()->getImagePath()}">
										</div>
										<div class="timeline-body row no-margin">
											<div class="pull-right">
												<span class="time pull-right">
													<span title="{$RECENT_ACTIVITY->getDisplayActivityTime()}">{Vtiger_Util_Helper::formatDateDiffInStrings($RECENT_ACTIVITY->getActivityTime())}</span>
												</span>
											</div>
											<span>
												<strong>{$RECENT_ACTIVITY->getModifiedBy()->getName()}&nbsp;</strong>
											</span>
											{assign var=RELATION value=$RECENT_ACTIVITY->getRelationInstance()}
											<span>
												{if $RECENT_ACTIVITY->isRelationLink()}
													{"LBL_ADDED"|t:$MODULE_NAME}
												{else}
													{"LBL_REMOVED"|t:$MODULE_NAME}
												{/if}&nbsp;
											</span>
											<span>
												{if Users_Privileges_Model::isPermitted($RELATION->getLinkedRecord()->getModuleName(), 'DetailView', $RELATION->getLinkedRecord()->getId())}
													<strong class="moreContent">
														<span class="teaserContent">
															{Vtiger_Util_Helper::toVtiger6SafeHTML($RELATION->getValue())}
														</span>
														{if $RELATION->has('fullValue')}
															<span class="fullContent hide">
																{$RELATION->get('fullValue')}
															</span>
															<button type="button" class="btn btn-info btn-xs moreBtn" data-on="{"LBL_MORE_BTN"|t}" data-off="{"LBL_HIDE_BTN"|t}">{"LBL_MORE_BTN"|t}</button>
														{/if}
													</strong>
												{/if}
											</span>
											<span>&nbsp;({vtranslate('SINGLE_'|cat:$RELATION->getLinkedRecord()->getModuleName(), $RELATION->getLinkedRecord()->getModuleName())})</span>
										</div>
									</div>
								{else if $RECENT_ACTIVITY->isRestore()}

								{else if $RECENT_ACTIVITY->isConvertToAccount()}
									<span class="glyphicon glyphicon-transfer bgAzure"></span>
									<div class="timeline-item{if $NEW_CHANGE} bgWarning{/if}">
										<div class="pull-left paddingRight15 imageContainer">
											<img class="userImage img-circle" src="{$RECENT_ACTIVITY->getModifiedBy()->getImagePath()}">
										</div>
										<div class="timeline-body row no-margin">
											<span class="time pull-right">
												<span title="{$RECENT_ACTIVITY->getDisplayActivityTime()}">{Vtiger_Util_Helper::formatDateDiffInStrings($RECENT_ACTIVITY->getActivityTime())}</span>
											</span>
											<div class="pull-left">
												<strong>{"LBL_CONVERTED_FROM_LEAD"|t:$MODULE_NAME}</strong> 
											</div>
										</div>
									</div>
								{else if $RECENT_ACTIVITY->isDisplayed()}
									<span class="glyphicon glyphicon-th-list bgAzure"></span>
									<div class="timeline-item">
										<div class="pull-left paddingRight15 imageContainer">
											<img class="userImage img-circle" src="{$RECENT_ACTIVITY->getModifiedBy()->getImagePath()}">
										</div>
										<div class="timeline-body row no-margin">
											<span class="time pull-right">
												<span title="{$RECENT_ACTIVITY->getDisplayActivityTime()}">{Vtiger_Util_Helper::formatDateDiffInStrings($RECENT_ACTIVITY->getActivityTime())}</span>
											</span>
											<div class="pull-left">
												<strong>{$RECENT_ACTIVITY->getModifiedBy()->getName()}</strong>
												&nbsp;{"LBL_DISPLAYED"|t:$MODULE_NAME}
											</div>
										</div>
									</div>
								{/if}
							</li>
						{/if}
					{/foreach}
				</ul>
			</div>
		{else}
			<div class="summaryWidgetContainer">
				<p class="textAlignCenter">{"LBL_NO_RECENT_UPDATES"|t}</p>
			</div>
		{/if}
		<input type="hidden" id="newChange" value="{$NEW_CHANGE}" />
		<div id="moreLink">
			{if !$IS_READ_ONLY && $PAGING_MODEL->isNextPageExists()}
				<div class="pull-right">
					<button type="button" class="btn btn-primary btn-xs moreRecentUpdates">{"LBL_MORE"|t:$MODULE_NAME}..</button>
				</div>
			{/if}
		</div>
	</div>
<!--/layouts/basic/modules/Vtiger/RecentActivitiesTimeLine.tpl -->
{/strip}
