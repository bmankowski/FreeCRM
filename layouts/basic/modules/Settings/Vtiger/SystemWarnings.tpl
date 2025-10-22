{strip}
<!-- layouts/basic/modules/Settings/Vtiger/SystemWarnings.tpl -->
	{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
	<div class="warningsIndexPage">
		<div class="row">
			<div class="col-md-9 marginRight10">
				<div class="marginRight10" id="warningsContent">

				</div>
			</div>
			<div class="col-md-3 siteBarRight">
				<h4>{"LBL_WARNINGS_FOLDERS"|t:$MODULE}</h4>
				<hr>
				<div class="text-center marginBottom5">
					<input class="switchBtn" type="checkbox" title="{"LBL_WARNINGS_SWITCH"|t:$MODULE}" data-size="normal" data-label-width="5" data-handle-width="90" data-on-text="{"LBL_ACTIVE"|t:$MODULE}" data-off-text="{"LBL_ALL"|t}">
				</div>
				<hr>
				<input type="hidden" id="treeValues" value="{\App\Modules\Vtiger\Helpers\Util::toSafeHTML($FOLDERS)}">
				<div id="jstreeContainer"></div>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Settings/Vtiger/SystemWarnings.tpl -->
{/strip}
