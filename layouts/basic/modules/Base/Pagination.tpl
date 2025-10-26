{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/Pagination.tpl -->
	{if empty($VIEWNAME)}
		{assign var=VIEWNAME value='list'}
	{/if}
	<nav>		
		<ul class="pagination" data-total-count="{$LISTVIEW_COUNT}">
			<li class="{if $PAGE_NUMBER eq 1} disabled {/if} pageNumber firstPage" data-id="1" >
				<span aria-hidden="true">{"LBL_FIRST"|t}</span>
			</li>
			<li class="{if !$PAGING_MODEL->isPrevPageExists() OR $PAGE_NUMBER eq 1} disabled {/if}" id="{$VIEWNAME}ViewPreviousPageButton">
				<span aria-hidden="true">&laquo;</span>
			</li>	
			{if $PAGE_COUNT neq 0}
				{assign var=PAGIN_TO value=$START_PAGIN_FROM+4}
				{for $PAGE_INDEX=$START_PAGIN_FROM to $PAGIN_TO}
					{if $PAGE_INDEX eq $PAGE_COUNT || $PAGE_INDEX eq $PAGIN_TO}
						{if $PAGE_COUNT > 5}
							<li {if $PAGE_COUNT eq 1} disabled {/if} >
								<a id="dLabel" data-target="#" data-toggle="dropdown" role="button" aria-expanded="true">
									...
								</a>
								<ul class="dropdown-menu listViewBasicAction" aria-labelledby="dLabel" id="{$VIEWNAME}ViewPageJumpDropDown">
									<li>
										<div>
											<div class="col-md-3 recentComments textAlignCenter pushUpandDown2per"><span>{"LBL_PAGE"|t}</span></div>
											<div class="col-md-3 recentComments">
												<input type="text" id="pageToJump" class="listViewPagingInput textAlignCenter form-control" title="{"LBL_LISTVIEW_PAGE_JUMP"|t}" value="{$PAGE_NUMBER}"/>
											</div>
											<div class="col-md-2 recentComments textAlignCenter pushUpandDown2per">
												{"LBL_OF"|t}
											</div>
											<div class="col-md-2 recentComments pushUpandDown2per textAlignCenter" id="totalPageCount">{$PAGE_COUNT}</div>
										</div>
									</li>
								</ul>
							</li>
						{/if}
						{break}
					{/if}
					<li class="pageNumber{if $PAGE_NUMBER eq $PAGE_INDEX} active disabled{/if}" data-id="{$PAGE_INDEX}">
						<a>{$PAGE_INDEX}</a>
					</li>
				{/for}
			{/if}
			{if $PAGE_INDEX <= $PAGE_COUNT}
				<li class="pageNumber{if $PAGE_NUMBER eq $PAGE_COUNT} active disabled{/if}" data-id="{$PAGE_COUNT}">
					<a>{$PAGE_COUNT}</a>
				</li>
			{/if}
			<li class="{if (!$PAGING_MODEL->isNextPageExists())}disabled{/if}" id="{$VIEWNAME}ViewNextPageButton">
				<span aria-hidden="true">&raquo;</span>
			</li>
			{if !$LISTVIEW_COUNT && $PAGING_MODEL->isNextPageExists()}
				<li class="popoverTooltip" id="totalCountBtn" data-content="{"LBL_WIDGET_FILTER_TOTAL_COUNT_INFO"|t}" >
					<a><span class="glyphicon glyphicon-equalizer"></span></a>
				</li>
			{/if}
			{if $LISTVIEW_COUNT}
				<li class="{if $PAGE_NUMBER eq $PAGE_COUNT or (!$PAGING_MODEL->isNextPageExists())} disabled {/if} pageNumber lastPage" data-id="{$PAGE_COUNT}" >
					<span aria-hidden="true">{"LBL_LAST"|t}</span>
				</li>
			{/if}
		</ul>
		<ul class="pageInfo">
			<li>
				<span>
					<span class="pageNumbersText">
						{$PAGING_MODEL->getRecordStartRange()} {"LBL_TO_LC"|t} {$PAGING_MODEL->getRecordEndRange()}
						{if $LISTVIEW_COUNT} ({$LISTVIEW_COUNT}){/if}
					</span>
				</span>
			</li>
		</ul>
	</nav>
<!--/layouts/basic/modules/Base/Pagination.tpl -->
{/strip}
