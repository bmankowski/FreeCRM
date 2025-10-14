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
<!-- layouts/basic/modules/Vtiger/dashboards/DashboardHeaderIcons.tpl -->
{if isset($SETTING_EXIST)}
	<a class="btn btn-xs btn-default" name="dfilter">
		<span class='icon-cog' border='0' align="absmiddle" title="{"LBL_FILTER"|t}" alt="{"LBL_FILTER"|t}"></span>
	</a>
{/if}
<a class="btn btn-xs btn-default" href="javascript:void(0);" name="drefresh" data-url="{$WIDGET->getUrl()}&content=data">
	<span class="glyphicon glyphicon-refresh" hspace="2" border="0" align="absmiddle" title="{"LBL_REFRESH"|t}" alt="{"LBL_REFRESH"|t}"></span>
</a>
{if !$WIDGET->isDefault()}
	<a name="dclose" class="widget btn btn-xs btn-default" data-url="{$WIDGET->getDeleteUrl()}">
		<span class="glyphicon glyphicon-remove" hspace="2" border="0" align="absmiddle" title="{"LBL_CLOSE"|t}" alt="{"LBL_CLOSE"|t}"></span>
	</a>
{/if}
<!--/layouts/basic/modules/Vtiger/dashboards/DashboardHeaderIcons.tpl -->
{/strip}
