{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
* Contributor(s): YetiForce S.A.
********************************************************************************/
-->*}
{strip}
	<!-- tpl-Base-RelatedList -->
{*	Show REQUEST*}
{*	<p>Current URL: {$REQUEST_URL}</p>*}
{*	<ul>*}
{*		{foreach $ajax_params as $key => $value}*}
{*			<li>{$key}: {$value}</li>*}
{*		{/foreach}*}
{*	</ul>*}

	<div class="RelatedList relatedContainer{if $RELATED_VIEW === 'ListPreview'} relatedContainer--listPreview{/if}">
		{assign var=RELATED_MODULE_NAME value=$RELATED_MODULE->get('name')}
		{assign var=INVENTORY_MODULE value=$RELATED_MODULE->isInventory()}
		{assign var=RELATION_MODEL value=$VIEW_MODEL->getRelationModel()}
		{if $RELATED_VIEW === 'ListPreview' && $RELATED_MODULE_NAME eq 'Candidates'}
			<script>
				{literal}
				jQuery(function () {
					var container = jQuery('.RelatedList.relatedContainer');
					var iframe = container.find('iframe.listPreviewframe');
					if (!iframe.length) {
						return;
					}
					var listPane = container.find('.c-list-preview');
					var detailPane = container.find('.c-detail-preview');
					var divider = container.find('.c-list-preview-resizer');
					var storageKey = 'FreeCRM.ListPreview.ProjektyRekrutacyjne.Candidates.listWidthPx';

					var clamp = function (val, min, max) {
						return Math.max(min, Math.min(max, val));
					};
					var applyListWidth = function (px) {
						var totalW = container.find('.relatedContents').width() || container.width();
						if (!totalW) return;
						var dividerW = divider.outerWidth() || 10;
						var minList = 320;
						var minDetail = 320;
						var maxList = Math.max(minList, totalW - dividerW - minDetail);
						var w = clamp(px, minList, maxList);
						listPane.css({width: w + 'px'});
						detailPane.css({width: 'calc(100% - ' + (w + dividerW) + 'px)'});
					};

					// Restore last width if present
					try {
						var stored = parseInt(window.localStorage.getItem(storageKey) || '', 10);
						if (!isNaN(stored) && stored > 0) {
							applyListWidth(stored);
						}
					} catch (e) {}

					// Drag-resize handler
					var resizing = false;
					var onMove = function (ev) {
						if (!resizing) return;
						var pageX = ev.pageX;
						if (typeof pageX !== 'number') return;
						var left = container.find('.relatedContents').offset().left;
						var newW = pageX - left;
						applyListWidth(newW);
					};
					var onUp = function () {
						if (!resizing) return;
						resizing = false;
						container.removeClass('is-resizing');
						jQuery(document).off('mousemove.listPreviewResize', onMove).off('mouseup.listPreviewResize', onUp);
						try {
							window.localStorage.setItem(storageKey, String(listPane.outerWidth() || 0));
						} catch (e) {}
					};
					divider.off('mousedown.listPreviewResize').on('mousedown.listPreviewResize', function (ev) {
						ev.preventDefault();
						resizing = true;
						container.addClass('is-resizing');
						jQuery(document).on('mousemove.listPreviewResize', onMove).on('mouseup.listPreviewResize', onUp);
					});

					// Align iframe height to inner Preview document + match list/resizer column (fallback if module JS absent)
					var syncListPreviewLayout = function () {
						try {
							var frameEl = iframe[0];
							var iframeContentH = 200;
							if (frameEl && frameEl.contentDocument && frameEl.contentDocument.body) {
								var b = frameEl.contentDocument.body;
								var e = frameEl.contentDocument.documentElement;
								iframeContentH = Math.max(
									b.scrollHeight || 0, b.offsetHeight || 0,
									e.scrollHeight || 0, e.offsetHeight || 0
								);
								var previewWrap = frameEl.contentDocument.querySelector('.c-iframe-preview');
								if (previewWrap) {
									iframeContentH = Math.max(iframeContentH, previewWrap.scrollHeight || 0, previewWrap.offsetHeight || 0);
								}
							}
							iframeContentH = Math.min(Math.max(iframeContentH + 24, 200), 50000);
							if (frameEl.style && frameEl.style.setProperty) {
								frameEl.style.setProperty('height', iframeContentH + 'px', 'important');
								frameEl.style.setProperty('min-height', '0', 'important');
							} else {
								iframe.css({height: iframeContentH + 'px', flex: '0 0 auto', minHeight: '0'});
							}
							detailPane.css({height: 'auto', maxHeight: 'none', overflow: 'visible'});
							var headerBottom = container.find('.relatedHeader').length
								? container.find('.relatedHeader').offset().top + container.find('.relatedHeader').outerHeight()
								: container.offset().top;
							var footerH = jQuery('.js-footer').length ? jQuery('.js-footer').outerHeight() : 0;
							var viewportCap = Math.max(320, jQuery(window).height() - headerBottom - footerH - 20);
							var detailNatural = Math.ceil(detailPane.outerHeight(true));
							var targetColH = Math.max(viewportCap, detailNatural);
							listPane.add(divider).css({
								height: targetColH + 'px',
								minHeight: Math.min(viewportCap, targetColH) + 'px',
								maxHeight: 'none'
							});
							detailPane.css({minHeight: targetColH + 'px'});
						} catch (e) {}
					};
					syncListPreviewLayout();
					jQuery(window).off('resize.listPreviewHeight').on('resize.listPreviewHeight', function () {
						syncListPreviewLayout();
						applyListWidth(listPane.outerWidth() || listPane.width() || 0);
					});
					var update = function (recordUrl) {
						if (!recordUrl) return;
						try {
							var q = recordUrl.split('?')[1];
							if (q) {
								var pv = new URLSearchParams(q);
								var rid = pv.get('record');
								if (rid) {
									container.find('#candidateId').val(rid);
								}
							}
						} catch (e2) {}
						iframe.one('load.listPreviewInlineFallback', function () {
							syncListPreviewLayout();
							if (typeof window.requestAnimationFrame === 'function') {
								window.requestAnimationFrame(syncListPreviewLayout);
							}
							setTimeout(syncListPreviewLayout, 250);
						});
						iframe.attr('src', recordUrl.replace('view=Detail', 'view=Preview'));
					};
					// Click row or link -> update preview instead of navigation
					container.off('click.listPreview', '.listViewEntries, .listViewEntries a')
						.on('click.listPreview', '.listViewEntries, .listViewEntries a', function (e) {
							if (jQuery(e.target).closest('.listViewEntriesCheckBox, .leftRecordActions, .actions').length) {
								return;
							}
							e.preventDefault();
							e.stopPropagation();
							var row = jQuery(e.currentTarget).closest('.listViewEntries');
							update(row.data('recordurl'));
						});
					var closeRejectionMenu = function () {
						container.find('.c-candidate-thumb-actions')
							.removeClass('is-rejection-reasons-open')
							.find('.rejectCandidateManually')
							.attr('aria-expanded', 'false');
					};
					var sendRejection = function (reason) {
						var candidateId = container.find('#candidateId').val();
						var projectId = container.find('#projectId').val();
						if (!candidateId || !projectId) {
							Vtiger_Helper_Js.showPnotify({
								text: app.vtranslate('LBL_SELECT_RECORD', 'Vtiger'),
								type: 'error'
							});
							return;
						}
						var progressIndicator = jQuery.progressIndicator({position:'html',message:'',blockInfo:{enabled:false}});
						AppConnector.request({
							module: app.getModuleName(),
							action: 'RejectCandidateManuallyAjax',
							candidateId: candidateId,
							projectId: projectId,
							rejectionReason: reason
						}).done(function (data) {
							if (progressIndicator && progressIndicator.length) {
								try {
									progressIndicator.progressIndicator({mode:'hide'});
								} catch (e3) {}
								progressIndicator.remove();
							}
							if (data && data.result && data.result.success) {
								Vtiger_Helper_Js.showPnotify({
									text: app.vtranslate(data.result.message),
									type: 'success',
									animation: 'show'
								});
								var detailInstance = window.Vtiger_Detail_Js && Vtiger_Detail_Js.getInstance ? Vtiger_Detail_Js.getInstance() : null;
								if (detailInstance && typeof detailInstance.loadRelatedList === 'function') {
									detailInstance.loadRelatedList({page:1});
								} else {
									window.location.reload();
								}
							} else {
								Vtiger_Helper_Js.showPnotify({
									text: app.vtranslate('PLL_REJECT_FAILED'),
									type: 'error'
								});
							}
						}).fail(function () {
							if (progressIndicator && progressIndicator.length) {
								try {
									progressIndicator.progressIndicator({mode:'hide'});
								} catch (e4) {}
								progressIndicator.remove();
							}
							Vtiger_Helper_Js.showPnotify({
								text: app.vtranslate('PLL_REJECT_FAILED'),
								type: 'error'
							});
						});
					};
					jQuery(document).off('click.listPreviewRejectToggle', '.RelatedList.relatedContainer .rejectCandidateManually')
						.on('click.listPreviewRejectToggle', '.RelatedList.relatedContainer .rejectCandidateManually', function (e) {
							e.preventDefault();
							e.stopImmediatePropagation();
							var candidateId = container.find('#candidateId').val();
							var projectId = container.find('#projectId').val();
							if (!candidateId || !projectId) {
								Vtiger_Helper_Js.showPnotify({
									text: app.vtranslate('LBL_SELECT_RECORD', 'Vtiger'),
									type: 'error'
								});
								return false;
							}
							var dock = container.find('.c-candidate-thumb-actions');
							var shouldOpen = !dock.hasClass('is-rejection-reasons-open');
							closeRejectionMenu();
							dock.toggleClass('is-rejection-reasons-open', shouldOpen);
							jQuery(this).attr('aria-expanded', shouldOpen ? 'true' : 'false');
							return false;
						});
					jQuery(document).off('click.listPreviewRejectReason', '.RelatedList.relatedContainer .rejectCandidateReason')
						.on('click.listPreviewRejectReason', '.RelatedList.relatedContainer .rejectCandidateReason', function (e) {
							e.preventDefault();
							e.stopImmediatePropagation();
							closeRejectionMenu();
							sendRejection(jQuery(this).data('rejectionReason'));
							return false;
						});
					jQuery(document).off('keyup.listPreviewRejectMenu').on('keyup.listPreviewRejectMenu', function (e) {
						if (e.key === 'Escape') {
							closeRejectionMenu();
						}
					});
					jQuery(document).off('click.listPreviewRejectClose').on('click.listPreviewRejectClose', function (e) {
						if (!jQuery(e.target).closest('.RelatedList.relatedContainer .c-candidate-thumb-actions').length) {
							closeRejectionMenu();
						}
					});
					// Auto-load first record
					var firstRow = container.find('.listViewEntriesTable .listViewEntries').first();
					update(firstRow.data('recordurl'));
				});
				{/literal}
			</script>
		{/if}
		<input type="hidden" name="currentPageNum" value="{$PAGING_MODEL->getCurrentPage()}">
		<input type="hidden" name="relatedModuleName" class="relatedModuleName" value="{$RELATED_MODULE->get('name')}">
		<input type="hidden" id="orderBy" value="{$ORDER_BY_JSON}">
		<input type="hidden" value="{$RELATED_ENTIRES_COUNT}" id="noOfEntries">
		<input type='hidden' value="{$PAGING_MODEL->getPageLimit()}" id='pageLimit'>
		<input type='hidden' value="{$TOTAL_ENTRIES}" id='totalCount'>
		<input type="hidden" id="autoRefreshListOnChange" value="{\App\Core\AppConfig::performance('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE')}">
		<input type="hidden" class="relatedView" value="{$RELATED_VIEW}">
		<input type="hidden" id="selectedIds" name="selectedIds" data-selected-ids="">
		<input type="hidden" id="excludedIds" name="excludedIds" data-excluded-ids="">
		<input type="hidden" id="recordsCount" value=""/>
		<input type="hidden" id="tab_label" value="{$RELATION_TAB_LABEL}"/>
		<input type="hidden" id="relationId" value="{$RELATION_MODEL->getId()}"/>
		<input type="hidden" id="search_params" value="{$SEARCH_PARAMS_JSON}">
		<input type="hidden" class="js-empty-fields" data-js="value" value="{$LOCKED_EMPTY_FIELDS_JSON}"/>
		{if $SHOW_HEADER}
			{if !isset($CUSTOM_VIEW_LIST)}{assign var=CUSTOM_VIEW_LIST value=[]}{/if}
			{capture assign=RELATED_HEADER_HTML}
			<div class="relatedHeader mt-1">
				<div class="d-inline-flex flex-wrap w-100 justify-content-start">
					<div class="relatedHeader__toolbar u-w-sm-down-100 d-flex flex-wrap flex-sm-nowrap justify-content-start mb-1 {if $CUSTOM_VIEW_LIST}mb-lg-0{else}mb-md-0{/if}">
						{if isset($RELATED_LIST_LINKS['RELATEDLIST_MASSACTIONS']) && $RELATED_LIST_LINKS['RELATEDLIST_MASSACTIONS']|@count gt 0}
							<div class="btn-group listViewMassActions mr-sm-1 relatedViewGroup c-btn-block-sm-down mb-1 mb-sm-0">
								<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
									<strong>{"LBL_ACTIONS"|t:$RELATED_MODULE_NAME}</strong>&nbsp;&nbsp;<span class="caret"></span>
								</button>
								<ul class="dropdown-menu">
									{foreach item=ACTION_LINK from=$RELATED_LIST_LINKS['RELATEDLIST_MASSACTIONS']}
										<li id="{$RELATED_MODULE_NAME}_relatedList_massAction_{\App\Modules\Base\Helpers\Util::replaceSpaceWithUnderScores($ACTION_LINK->getLabel())}">
											<a href="javascript:void(0);"
												{if stripos($ACTION_LINK->getUrl(), 'javascript:')===0}
													onclick='{$ACTION_LINK->getUrl()|substr:strlen("javascript:")};'
												{else}
													onclick="Vtiger_ListView_Js.triggerMassAction('{$ACTION_LINK->getUrl()}')"
												{/if}>{$ACTION_LINK->getLabel()|t:$RELATED_MODULE_NAME}</a>
										</li>
									{/foreach}
								</ul>
							</div>
						{/if}
						{if isset($RELATED_LIST_LINKS['RELATEDLIST_VIEWS']) && $RELATED_LIST_LINKS['RELATEDLIST_VIEWS']|@count gt 0}
							<div class="btn-group mr-sm-1 relatedViewGroup c-btn-block-sm-down mb-1 mb-sm-0">
								{assign var=TEXT_HOLDER value=''}
								{foreach item=RELATEDLIST_VIEW from=$RELATED_LIST_LINKS['RELATEDLIST_VIEWS']}
									{if $RELATED_VIEW == $RELATEDLIST_VIEW->get('view')}
										{assign var=TEXT_HOLDER value=$RELATEDLIST_VIEW->getLabel()}
										{if $RELATEDLIST_VIEW->get('linkicon') neq ''}
											{assign var=BTN_ICON value=$RELATEDLIST_VIEW->get('linkicon')}
										{/if}
									{/if}
								{/foreach}
								<button type="button" class="btn btn-default dropdown-toggle relatedViewBtn" data-toggle="dropdown">
									{if $BTN_ICON}
										<span class="{$BTN_ICON} mr-1"></span>
									{else}
										<span class="fas fa-list mr-1"></span>
									{/if}
									<span class="textHolder">{\App\Language::translate($TEXT_HOLDER, $MODULE_NAME)}</span>
									<span class="caret"></span>
								</button>
								<ul class="dropdown-menu">
									{foreach item=RELATEDLIST_VIEW from=$RELATED_LIST_LINKS['RELATEDLIST_VIEWS']}
										<li>
											<a class="dropdown-item js-change-related-view" href="#" data-view="{$RELATEDLIST_VIEW->get('view')}" data-js="click">
												{if $RELATEDLIST_VIEW->get('linkicon') neq ''}
													<span class="{$RELATEDLIST_VIEW->get('linkicon')} mr-1"></span>
												{/if}
												{\App\Language::translate($RELATEDLIST_VIEW->getLabel(), $MODULE_NAME)}
											</a>
										</li>
									{/foreach}
								</ul>
							</div>
						{/if}
						{if isset($RELATED_LIST_LINKS['LISTVIEWBASIC'])}
							{foreach item=RELATED_LINK from=$RELATED_LIST_LINKS['LISTVIEWBASIC']}
								{if {\App\Security\Privilege::isPermitted($RELATED_MODULE_NAME, 'CreateView')} }
									<div class="btn-group mr-md-1 c-btn-block-sm-down">
										{assign var=IS_SELECT_BUTTON value={$RELATED_LINK->get('_selectRelation')}}
										<button type="button" class="btn btn-light addButton
											{if $IS_SELECT_BUTTON eq true} selectRelation {/if} modCT_{$RELATED_MODULE_NAME} {if !empty($RELATED_LINK->linkqcs)}quickCreateSupported{/if}" {' '}
											{if $IS_SELECT_BUTTON eq true}
												data-moduleName={$RELATED_LINK->get('_module')->get('name')} {/if}{' '}
												{if ($RELATED_LINK->isPageLoadLink())}{' '}
												{if $RELATION_FIELD} data-name="{$RELATION_FIELD->getName()}" {/if}{' '}
											data-url="{$RELATED_LINK->getUrl()}"
											{else}
											onclick='{$RELATED_LINK->getUrl()|substr:strlen("javascript:")};'
											{/if}{' '}
											{if $IS_SELECT_BUTTON neq true && stripos($RELATED_LINK->getUrl(), 'javascript:') !== 0}name="addButton" {/if}>
											{if $IS_SELECT_BUTTON eq false}
												<span class="{$RELATED_LINK->getIcon()} mr-1"></span>
											{/if}
											{if $IS_SELECT_BUTTON eq true}<span class="fas fa-search mr-1"></span>{/if}
											{$RELATED_LINK->getLabel()}
										</button>
									</div>
								{/if}
							{/foreach}
						{/if}
						{if isset($RELATED_LIST_LINKS['RELATEDLIST_BASIC'])}
							{foreach item=LINK from=$RELATED_LIST_LINKS['RELATEDLIST_BASIC']}
								{include file='ButtonLink.tpl'|@vtemplate_path:$MODULE BUTTON_VIEW='relatedListView' CLASS='mr-sm-1 c-btn-block-sm-down'}
							{/foreach}
						{/if}
					</div>
					{if is_array($CUSTOM_VIEW_LIST) && count($CUSTOM_VIEW_LIST) > 0}
						<div class="mr-auto col-xl-2 col-md-4 col-12 px-0 mb-md-0 mb-1">
							{if count($CUSTOM_VIEW_LIST) === 1}
								<input type="hidden" class="js-relation-cv-id"
									   value="{$SINGLE_CUSTOM_VIEW_ID}" data-js="value"/>
							{else}
								<div class="input-group">
									<div class="input-group-prepend">
										<div class="input-group-text">
											<span class="fas fa-filter"></span>
										</div>
									</div>
									<select class="form-control select2 js-relation-cv-id"
											data-js="change|select2|value">
										{foreach key=CV_ID item=CV_NAME from=$CUSTOM_VIEW_LIST}
											<option value="{$CV_ID}"
													{if $CV_ID == $VIEW_MODEL->get('viewId')}selected{/if}>{$CV_NAME}</option>
										{/foreach}
									</select>
								</div>
							{/if}
						</div>
					{/if}
					{if $RELATED_LIST_SUPPRESS_PAGINATION && $VIEW_MODEL}
						<input type="hidden" class="entityState"
							   value="{if $VIEW_MODEL->has('entityState')}{$VIEW_MODEL->get('entityState')}{else}Active{/if}">
					{/if}
					{if !$RELATED_LIST_SUPPRESS_PAGINATION}
					<div class="relatedHeader__pagination d-flex flex-wrap u-w-sm-down-100 justify-content-between justify-content-md-end">
						<div class="paginationDiv">
							{include file='Pagination.tpl'|@vtemplate_path:$MODULE_NAME VIEWNAME='related'}
						</div>
						{if $VIEW_MODEL}
							<div class="ml-1">
								{assign var=COLOR value=\App\Core\AppConfig::search('LIST_ENTITY_STATE_COLOR', [])}
								<input type="hidden" class="entityState"
									   value="{if $VIEW_MODEL->has('entityState')}{$VIEW_MODEL->get('entityState')}{else}Active{/if}">
								{if !$RELATED_LIST_SUPPRESS_ENTITY_STATE}
									<div class="dropdown dropdownEntityState u-remove-dropdown-icon">
										<button class="btn btn-light dropdown-toggle" type="button" id="dropdownEntityState"
												data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
											{if $VIEW_MODEL->get('entityState') === 'Archived'}
												<span class="fas fa-archive"></span>
											{elseif $VIEW_MODEL->get('entityState') === 'Trash'}
												<span class="fas fa-trash-alt"></span>
											{elseif $VIEW_MODEL->get('entityState') === 'All'}
												<span class="fas fa-bars"></span>
											{else}
												<span class="fas fa-undo-alt"></span>
											{/if}
										</button>
										<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownEntityState">
											<li {if !empty($COLOR['Active'])}style="border-color: {$COLOR['Active']};" {/if}>
												<a class="dropdown-item{if !$VIEW_MODEL->get('entityState') || $VIEW_MODEL->get('entityState') == 'Active'} active{/if}"
												   href="#" data-value="Active">
													<span class="fas fa-undo-alt mr-2"></span>
													{\App\Language::translate('LBL_ENTITY_STATE_ACTIVE')}
												</a>
											</li>
											<li {if !empty($COLOR['Archived'])}style="border-color: {$COLOR['Archived']};" {/if}>
												<a class="dropdown-item{if $VIEW_MODEL->get('entityState') == 'Archived'} active{/if}"
												   href="#" data-value="Archived">
													<span class="fas fa-archive mr-2"></span>
													{\App\Language::translate('LBL_ENTITY_STATE_ARCHIVED')}
												</a>
											</li>
											<li {if !empty($COLOR['Trash'])}style="border-color: {$COLOR['Trash']};" {/if}>
												<a class="dropdown-item{if $VIEW_MODEL->get('entityState') == 'Trash'} active{/if}"
												   href="#" data-value="Trash">
													<span class="fas fa-trash-alt mr-2"></span>
													{\App\Language::translate('LBL_ENTITY_STATE_TRASH')}
												</a>
											</li>
											<li>
												<a class="dropdown-item{if $VIEW_MODEL->get('entityState') == 'All'} active{/if}"
												   href="#" data-value="All">
													<span class="fas fa-bars mr-2"></span>
													{\App\Language::translate('LBL_ALL')}
												</a>
											</li>
										</ul>
									</div>
								{/if}
							</div>
						{/if}
					</div>
					{/if}
				</div>
			</div>
			{/capture}
			{if $RELATED_VIEW !== 'ListPreview'}
				{$RELATED_HEADER_HTML nofilter}
			{/if}
		{/if}
		{if $RELATED_VIEW === 'ListPreview'}
			<div class="relatedContents mt-1">
				<input type="hidden" id="defaultDetailViewName"
					   value="{\App\Core\AppConfig::module($MODULE_NAME, 'defaultDetailViewName')}"/>
				{if empty($RELATED_RECORDS)}
					<div class="c-list-preview c-list-preview--empty js-list-preview js-fixed-scroll" data-js="scroll">
						{if $SHOW_HEADER}
							{$RELATED_HEADER_HTML nofilter}
						{/if}
						<div class="c-list-preview__content js-list-preview--scroll" data-js="perfectScrollbar">
							<div id="recordsList">
								{include file='RelatedListContents.tpl'|@vtemplate_path:$MODULE}
							</div>
						</div>
					</div>
				{else}
					<div class="c-list-preview js-list-preview js-fixed-scroll" data-js="scroll">
						{if $SHOW_HEADER}
							{$RELATED_HEADER_HTML nofilter}
						{/if}
						<div class="c-list-preview__content js-list-preview--scroll" data-js="perfectScrollbar">
							<div id="recordsList">
								{include file='RelatedListContents.tpl'|@vtemplate_path:$MODULE}
							</div>
						</div>
					</div>
					<div class="c-list-preview-resizer js-list-preview-resizer" aria-hidden="true"></div>
					<div class="c-detail-preview js-detail-preview">
						{if $RELATED_MODULE_NAME eq 'Candidates' && $RELATION_MODEL->get('label') eq 'Screening'}
							<div class="c-candidate-thumb-actions" aria-label="{\App\Language::translate('LBL_ACTIONS', 'Vtiger')}">
								<div class="c-candidate-thumb-actions__inputs">
									<input type="hidden" id="projectId" value="{$PARENT_RECORD->getID()}"/>
									<input type="hidden" id="candidateId"/>
								</div>
								<div class="c-candidate-thumb-actions__buttons">
									<a href="javascript:void(0);"
									   class="btn btn-secondary acceptCandidateManually mb-0">
										<span class="fas fa-thumbs-up fa-2x px-2 py-2"></span>
									</a>
									<div class="c-candidate-reject-menu">
										<a href="javascript:void(0);"
										   class="btn btn-secondary rejectCandidateManually mb-0"
										   aria-haspopup="true"
										   aria-expanded="false"
										   title="{\App\Language::translate('LBL_SELECT_REJECTION_REASON', $MODULE_NAME)}">
											<span class="fas fa-thumbs-down fa-2x px-2 py-2"></span>
										</a>
										<div class="c-candidate-rejection-bubbles"
										     role="menu"
										     aria-label="{\App\Language::translate('LBL_SELECT_REJECTION_REASON', $MODULE_NAME)}">
											<button type="button"
											        class="c-candidate-rejection-bubble c-candidate-rejection-bubble--experience rejectCandidateReason"
											        data-rejection-reason="NO_EXPERIENCE"
											        title="{\App\Language::translate('PLL_REJECTION_REASON_NO_EXPERIENCE', $MODULE_NAME)}"
											        aria-label="{\App\Language::translate('PLL_REJECTION_REASON_NO_EXPERIENCE', $MODULE_NAME)}">
												<span class="fas fa-briefcase c-candidate-rejection-bubble__icon"></span>
												<span class="c-candidate-rejection-bubble__code">EXP</span>
											</button>
											<button type="button"
											        class="c-candidate-rejection-bubble c-candidate-rejection-bubble--skills rejectCandidateReason"
											        data-rejection-reason="MISSING_SKILLS"
											        title="{\App\Language::translate('PLL_REJECTION_REASON_MISSING_SKILLS', $MODULE_NAME)}"
											        aria-label="{\App\Language::translate('PLL_REJECTION_REASON_MISSING_SKILLS', $MODULE_NAME)}">
												<span class="fas fa-certificate c-candidate-rejection-bubble__icon"></span>
												<span class="c-candidate-rejection-bubble__code">SKILL</span>
											</button>
											<button type="button"
											        class="c-candidate-rejection-bubble c-candidate-rejection-bubble--fit rejectCandidateReason"
											        data-rejection-reason="PROFILE_FIT"
											        title="{\App\Language::translate('PLL_REJECTION_REASON_PROFILE_FIT', $MODULE_NAME)}"
											        aria-label="{\App\Language::translate('PLL_REJECTION_REASON_PROFILE_FIT', $MODULE_NAME)}">
												<span class="fas fa-bullseye c-candidate-rejection-bubble__icon"></span>
												<span class="c-candidate-rejection-bubble__code">FIT</span>
											</button>
											<button type="button"
											        class="c-candidate-rejection-bubble c-candidate-rejection-bubble--language rejectCandidateReason"
											        data-rejection-reason="MISSING_POLISH_LANGUAGE"
											        title="{\App\Language::translate('PLL_REJECTION_REASON_MISSING_POLISH_LANGUAGE', $MODULE_NAME)}"
											        aria-label="{\App\Language::translate('PLL_REJECTION_REASON_MISSING_POLISH_LANGUAGE', $MODULE_NAME)}">
												<span class="fas fa-comment-alt c-candidate-rejection-bubble__icon"></span>
												<span class="c-candidate-rejection-bubble__code">PL</span>
											</button>
											<button type="button"
											        class="c-candidate-rejection-bubble c-candidate-rejection-bubble--other-candidate rejectCandidateReason"
											        data-rejection-reason="OTHER_CANDIDATE_CHOSEN"
											        title="{\App\Language::translate('PLL_REJECTION_REASON_OTHER_CANDIDATE_CHOSEN', $MODULE_NAME)}"
											        aria-label="{\App\Language::translate('PLL_REJECTION_REASON_OTHER_CANDIDATE_CHOSEN', $MODULE_NAME)}">
												<span class="fas fa-user-check c-candidate-rejection-bubble__icon"></span>
												<span class="c-candidate-rejection-bubble__code">OTHER</span>
											</button>
											<button type="button"
											        class="c-candidate-rejection-bubble c-candidate-rejection-bubble--project-closed rejectCandidateReason"
											        data-rejection-reason="PROJECT_CLOSED"
											        title="{\App\Language::translate('PLL_REJECTION_REASON_PROJECT_CLOSED', $MODULE_NAME)}"
											        aria-label="{\App\Language::translate('PLL_REJECTION_REASON_PROJECT_CLOSED', $MODULE_NAME)}">
												<span class="fas fa-pause-circle c-candidate-rejection-bubble__icon"></span>
												<span class="c-candidate-rejection-bubble__code">HOLD</span>
											</button>
										</div>
									</div>
								</div>
							</div>
						{/if}
						<iframe class="listPreviewframe" frameborder="0"></iframe>
					</div>
				{/if}
			</div>
		{else}
			<div class="relatedContents mt-1">
				{if $RELATED_MODULE_NAME eq 'Candidates'}
					{include file='RelatedListContents.tpl'|@vtemplate_path:$MODULE}
				{else}
					{include file='RelatedListContents.tpl'|@vtemplate_path:$RELATED_MODULE->get('name')}
				{/if}
			</div>
		{/if}
	</div>
	<!-- /tpl-Base-RelatedList -->
{/strip}
