{strip}
<!-- layouts/basic/modules/Vtiger/DetailViewMap.tpl -->
{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
	<input type="hidden" id="coordinates" value="{Vtiger_Util_Helper::toSafeHTML(\App\Json::encode($COORRDINATES))}">
	<div id="mapid" ></div>
<!--/layouts/basic/modules/Vtiger/DetailViewMap.tpl -->
{/strip}
