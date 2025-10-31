{strip}
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
	<!-- layouts/basic/modules/Base/MainLayout.tpl -->
	<!DOCTYPE html>
	<html lang="{$HTMLLANG}">

	<head>
		{block name="head"}
			{block name="title"}<title>{$PAGETITLE|t:$QUALIFIED_MODULE}</title>{/block}

			{block name="meta"}
				<link REL="SHORTCUT ICON" HREF="{vimage_path('favicon.ico')}">
				<meta name="viewport" content="width=device-width, initial-scale=1.0" />
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
				<meta name="robots" content="noindex" />
			{/block}

			{block name="styles"}
				{foreach key=index item=cssModel from=$STYLES}
					<link rel="{$cssModel->getRel()}" href="{vresource_url($cssModel->getHref())}" />
				{/foreach}
			{/block}

			{block name="header_scripts"}
				{foreach key=index item=jsModel from=$HEADER_SCRIPTS}
					<script type="{$jsModel->getType()}" src="{vresource_url($jsModel->getSrc())}">
														</script>
				{/foreach}
				<!--[if IE]>
				<script type="text/javascript" src="libraries/html5shim/html5.js"></script>
				<script type="text/javascript" src="libraries/html5shim/respond.js"></script>
			<![endif]-->
			{/block}

			{block name="head_custom"}
				{if $USER_MODEL}
					{assign var="HEAD_LOCKS" value=$USER_MODEL->getHeadLocks()}
					{if $HEAD_LOCKS}
						<script type="text/javascript">
							{$HEAD_LOCKS}
						</script>
					{/if}
				{/if}
				{if \App\Debugger::isDebugBar()}
					{\App\Debugger::getDebugBar()->getJavascriptRenderer()->renderHead()}
				{/if}
			{/block}
		{/block}
	</head>

	<body {block name="body_attrs"}data-language="{$LANGUAGE}" data-skinpath="{$SKIN_PATH}" data-layoutpath="{$LAYOUT_PATH}"
			{if $USER_MODEL}{$USER_MODEL->getBodyLocks()}{/if}{/block}>

			{block name="body_start"}
				<div id="js_strings" class="hide noprint">{\App\Json::encode($LANGUAGE_STRINGS)}</div>
				<div id="configuration">
					{if $USER_MODEL}
						<input type="hidden" id="start_day" value="{$USER_MODEL->get('dayoftheweek')}" />
						<input type="hidden" id="row_type" value="{$USER_MODEL->get('rowheight')}" />
						<input type="hidden" id="current_user_id" value="{$USER_MODEL->get('id')}" />
						<input type="hidden" id="userDateFormat" value="{$USER_MODEL->get('date_format')}" />
						<input type="hidden" id="userTimeFormat" value="{$USER_MODEL->get('hour_format')}" />
						<input type="hidden" id="numberOfCurrencyDecimal" value="{$USER_MODEL->get('no_of_currency_decimals')}" />
						<input type="hidden" id="currencyGroupingSeparator" value="{$USER_MODEL->get('currency_grouping_separator')}" />
						<input type="hidden" id="currencyDecimalSeparator" value="{$USER_MODEL->get('currency_decimal_separator')}" />
						<input type="hidden" id="currencyGroupingPattern" value="{$USER_MODEL->get('currency_grouping_pattern')}" />
						<input type="hidden" id="truncateTrailingZeros" value="{$USER_MODEL->get('truncate_trailing_zeros')}" />
					{/if}
					<input type="hidden" id="backgroundClosingModal" value="{vglobal('backgroundClosingModal')}" />
					<input type="hidden" id="gsAutocomplete" value="{AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE')}" />
					<input type="hidden" id="gsMinLength" value="{AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE_MIN_LENGTH')}" />
					<input type="hidden" id="gsAmountResponse" value="{AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE_LIMIT')}" />
					<input type="hidden" id="module" value="{$MODULE}" />
					<input type="hidden" id="parent" value="{$PARENT_MODULE}" />
					<input type="hidden" id="view" value="{$VIEW}" />
					<input type="hidden" id="sounds"
						value="{\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Json::encode(AppConfig::sounds()))}" />
					<input type="hidden" id="intervalForNotificationNumberCheck"
						value="{AppConfig::performance('INTERVAL_FOR_NOTIFICATION_NUMBER_CHECK')}" />
					<input type="hidden" id="fieldsReferencesDependent"
						value="{AppConfig::security('FIELDS_REFERENCES_DEPENDENT')}" />
				</div>
			{/block}

			{block name="announcements"}
				{assign var="ANNOUNCEMENTS" value=\App\Modules\Base\Models\Module::getInstance('Announcements')}
				{if $ANNOUNCEMENTS && $ANNOUNCEMENTS->checkActive($VIEW)}
					{include file='Announcement.tpl'|@vtemplate_path:$MODULE}
				{/if}
			{/block}

			{block name="main_container"}
				<div class="container-fluid container-fluid-main">
					<div class="baseContainer {if AppConfig::module('Users','IS_VISIBLE_USER_INFO_FOOTER')}userInfoFooter{/if}">
						{block name="navigation"}
							{assign var=LEFTPANELHIDE value=$USER_MODEL->get('leftpanelhide')}
							{include file='BodyHeaderMobile.tpl'|@vtemplate_path:$MODULE}
							<div class="mobileLeftPanel noSpaces">
								{include file='BodyLeft.tpl'|@vtemplate_path:$MODULE DEVICE=Mobile}
							</div>
							<div class="leftPanel noSpaces">
								{include file='BodyLeft.tpl'|@vtemplate_path:$MODULE DEVICE=Desktop}
							</div>
							{include file='BodyHeader.tpl'|@vtemplate_path:$MODULE}
						{/block}

						{block name="main_content"}
							<div class="basePanel noSpaces {if $LEFTPANELHIDE} menuOpen{/if}">
								<div class="mainBody {if AppConfig::module('Users','IS_VISIBLE_USER_INFO_FOOTER')}userInfoFooter{/if}">
									<div class="container-fluid bodyContent bodyContents noSpaces">
										{block name="content"}
											<!-- Child templates define their content here -->
										{/block}
									</div> <!-- close bodyContent -->
								</div> <!-- close mainBody -->
							</div> <!-- close basePanel -->
						{/block}

					</div> <!-- close baseContainer -->
				</div> <!-- close container-fluid-main -->
			{/block}

			{block name="footer"}
				{include file='Footer.tpl'|@vtemplate_path:$MODULE}
			{/block}

			<!--/layouts/basic/modules/Base/MainLayout.tpl -->
	{/strip}