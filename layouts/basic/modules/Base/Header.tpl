{strip}
	<!-- layouts/basic/modules/Base/Header.tpl -->
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

	<!DOCTYPE html>
	<html lang="{$HTMLLANG}">

	<head>
		<title>{$PAGETITLE|t:$QUALIFIED_MODULE}</title>
		<link REL="SHORTCUT ICON" HREF="{vimage_path('favicon.ico')}">
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="robots" content="noindex" />
		<link rel="preload" href="{vresource_url('libraries/resources/fonts/lato/lato-latin-400-normal.woff2')}" as="font" type="font/woff2" crossorigin>
		<link rel="preload" href="{vresource_url('libraries/resources/fonts/lato/lato-latin-ext-400-normal.woff2')}" as="font" type="font/woff2" crossorigin>

		{foreach key=index item=cssModel from=$STYLES}
			<link rel="{$cssModel->getRel()}" href="{vresource_url($cssModel->getHref())}" />
		{/foreach}
		{foreach key=index item=jsModel from=$HEADER_SCRIPTS}
			<script type="{$jsModel->getType()}" src="{vresource_url($jsModel->getSrc())}">
				</script>
		{/foreach}
		<!--[if IE]>
				<script type="text/javascript" src="libraries/html5shim/html5.js"></script>
				<script type="text/javascript" src="libraries/html5shim/respond.js"></script>
			<![endif]-->
		{* ends *}

		{* ADD <script> INCLUDES in JSResources.tpl - for better performance *}
		{if $USER_MODEL}
			{assign var="HEAD_LOCKS" value=$USER_MODEL->getHeadLocks()}
			{if $HEAD_LOCKS}
				<script type="text/javascript">
					{$HEAD_LOCKS}
					
				</script>
			{/if}
		{/if}
		{if \App\Debug\Debugger::isDebugBar()}
			{\App\Debug\Debugger::getDebugBar()->getJavascriptRenderer()->renderHead()}
		{/if}
	</head>

	<body data-language="{$LANGUAGE}" data-skinpath="{$SKIN_PATH}" data-layoutpath="{$LAYOUT_PATH}"
		{if $USER_MODEL}{$USER_MODEL->getBodyLocks()}{/if}>
		<div id="js_strings" class="hide noprint">{\App\Utils\Json::encode($LANGUAGE_STRINGS)}</div>
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
			<input type="hidden" id="backgroundClosingModal" value="{$BACKGROUND_CLOSING_MODAL}" />
			<input type="hidden" id="gsAutocomplete" value="{AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE')}" />
			<input type="hidden" id="gsMinLength" value="{AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE_MIN_LENGTH')}" />
			<input type="hidden" id="gsAmountResponse" value="{AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE_LIMIT')}" />
			<input type="hidden" id="module" value="{$MODULE}" />
			<input type="hidden" id="parent" value="{$PARENT_MODULE}" />
			<input type="hidden" id="view" value="{$VIEW}" />
			<input type="hidden" id="sounds"
				value="{\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode(AppConfig::sounds()))}" />
			<input type="hidden" id="intervalForNotificationNumberCheck"
				value="{AppConfig::performance('INTERVAL_FOR_NOTIFICATION_NUMBER_CHECK')}" />
			<input type="hidden" id="fieldsReferencesDependent"
				value="{AppConfig::security('FIELDS_REFERENCES_DEPENDENT')}" />
		</div>
	{* Announcements *}
	{assign var="ANNOUNCEMENTS" value=\App\Modules\Base\Models\Module::getInstance('Announcements')}
	{if $ANNOUNCEMENTS && $ANNOUNCEMENTS->checkActive($VIEW)}
		{include file='Announcement.tpl'|@vtemplate_path:$MODULE}
	{/if}
	{* Body.tpl provides self-contained navigation wrapper *}
	{if $SHOW_BODY_HEADER}
		{include file='Body.tpl'|@vtemplate_path:$MODULE}
	{/if}
	{* View content will be wrapped in div#page by PreProcess templates *}
	<!--/layouts/basic/modules/Base/Header.tpl -->
{/strip}