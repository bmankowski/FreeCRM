{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/Vtiger/IconsModal.tpl -->
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3 class="modal-title">{"LBL_SELECT_ICON"|t:$QUALIFIED_MODULE}</h3>
	</div>
	<div class="modal-body col-md-12">
		<input type="hidden" id="iconType" value="-" />
		<input type="hidden" id="iconName" value="-" />
		<div>
			<select class="form-control" id="iconsList" name="type">
				<option value="">-</option>
				{foreach from=\App\Modules\Settings\Vtiger\Models\Icons::getGlyphicon() key=NAME item=CLASS}
					<option value="glyphicon {$CLASS}" data-class="{$CLASS}" data-type="icon" title="{$NAME}">{$NAME}</option>
				{/foreach}
				{foreach from=\App\Modules\Settings\Vtiger\Models\Icons::getUserIcon() key=NAME item=CLASS}
					<option value="{$CLASS}" data-class="{$CLASS}" data-type="icon" title="{$NAME}">{$NAME}</option>
				{/foreach}
				{foreach from=\App\Modules\Settings\Vtiger\Models\Icons::getAdminIcon() key=NAME item=CLASS}
					<option value="{$CLASS}" data-class="{$CLASS}" data-type="icon" title="{$NAME}">{$NAME}</option>
				{/foreach}
				{foreach from=\App\Modules\Settings\Vtiger\Models\Icons::getAdditionalIcon() key=NAME item=CLASS}
					<option value="{$CLASS}" data-class="{$CLASS}" data-type="icon" title="{$NAME}">{$NAME}</option>
				{/foreach}
				{foreach from=\App\Modules\Settings\Vtiger\Models\Icons::getFontAwesomeIcon() key=NAME item=CLASS}
					<option value="fa {$CLASS}" data-class="{$CLASS}" data-type="icon" title="{$NAME}">{$NAME}</option>
				{/foreach}
				{foreach from=\App\Modules\Settings\Vtiger\Models\Icons::getImageIcon() key=NAME item=URL}
					<option value="{$URL}" data-type="image" title="{$NAME}">{$NAME}</option>
				{/foreach}
			</select>
		</div>
		<br/>
		<div>
			<div class="row">
				<div class="col-md-3">
					{"LBL_ICON_NAME"|t:$QUALIFIED_MODULE}:
				</div>
				<div class="col-md-9">
					<div class="iconName"></div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-3">
					{"LBL_ICON_EXAMPLE"|t:$QUALIFIED_MODULE}:
				</div>
				<div class="col-md-9">
					<div class="iconExample" style="font-size: 32px;"></div>
				</div>
			</div>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn btn-success" type="submit" name="saveButton">
			<strong>{"LBL_SELECT_OPTION"|t:$MODULE}</strong>
		</button>
		<button class="btn btn-warning" type="reset" data-dismiss="modal">
			<strong>{"LBL_CANCEL"|t:$MODULE}</strong>
		</button>
	</div>
<!--/layouts/basic/modules/Settings/Vtiger/IconsModal.tpl -->
{/strip}
