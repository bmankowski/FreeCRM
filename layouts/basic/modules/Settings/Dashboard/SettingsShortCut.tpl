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
<!-- layouts/basic/modules/Settings/Base/SettingsShortCut.tpl -->
	<div id="shortcut_{$SETTINGS_SHORTCUT->getId()}" style="margin-left: 20px !important;" data-actionurl="{$SETTINGS_SHORTCUT->getPinUnpinActionUrl()}" class="col-md-3 contentsBackground well cursorPointer moduleBlock" data-url="{$SETTINGS_SHORTCUT->getUrl()}">
		<button data-id="{$SETTINGS_SHORTCUT->getId()}" title="{"LBL_REMOVE"|t:$QUALIFIED_MODULE}" title="Close" type="button" class="unpin close">x</button>
		<h5 class="themeTextColor">{$SETTINGS_SHORTCUT->get('name')|t:\App\Modules\Base\Models\Menu::getModuleNameFromUrl($SETTINGS_SHORTCUT->get('linkto'))}</h5>
		<div>{$SETTINGS_SHORTCUT->get('description')|t:\App\Modules\Base\Models\Menu::getModuleNameFromUrl($SETTINGS_SHORTCUT->get('linkto'))}</div>
	</div>
<!--/layouts/basic/modules/Settings/Base/SettingsShortCut.tpl -->
{/strip}	
