{strip}
<!-- layouts/basic/modules/Base/DetailViewMap.tpl -->
{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
	<input type="hidden" id="coordinates" value="{\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($COORRDINATES))}">
	<div id="mapid" ></div>
<!--/layouts/basic/modules/Base/DetailViewMap.tpl -->
{/strip}
