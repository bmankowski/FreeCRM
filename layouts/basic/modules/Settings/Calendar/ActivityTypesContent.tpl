{*<!--
/*+***********************************************************************************************************************************
FreeCRM - Customer Relationship Management System
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 *************************************************************************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Settings/Calendar/ActivityTypesContent.tpl -->
<div class=" ActivityTypes">
	<div class="widget_header row">
		<div class="col-md-12">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			{"LBL_ACTIVITY_TYPES_DESCRIPTION"|t:$QUALIFIED_MODULE}
		</div>
	<hr>
	</div>
	<div class=" contents tabbable">
		<table class="table customTableRWD table-bordered table-condensed listViewEntriesTable">
			<thead>
				<tr class="blockHeader">
					<th><strong>{"LBL_ACTIVITY_NAME"|t:$QUALIFIED_MODULE}</strong></th>
					<th><strong>{"LBL_MODULE"|t:$QUALIFIED_MODULE}</strong></th>
					<th><strong>{"LBL_ACTIVE"|t:$QUALIFIED_MODULE}</strong></th>
					<th><strong>{"LBL_COLOR"|t:$QUALIFIED_MODULE}</strong></th>
					<th data-hide='phone'><strong>{"LBL_TOOLS"|t:$QUALIFIED_MODULE}</strong></th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$MODULE_MODEL->getCalendarViewTypes() item=item key=key}
					<tr data-viewtypesid="{$item.id}" data-color="{$item.defaultcolor}">
						<td>{$item.fieldname|t:$item.module}</td>
						<td>{$item.module|t:$item.module}</td>
						<td>
							<label class="">
								<input class="activeType" type="checkbox" name="active" value="1" {if $item.active eq '1'}checked=""{/if}>
							</label> 
						</td>
						<td class="calendarColor" style="background: {$item.defaultcolor};"></td>
						<td>
							<button class="btn btn-primary marginLeftZero updateColor">{"LBL_UPDATE_COLOR"|t:$QUALIFIED_MODULE}</button>&ensp;
							<button class="btn btn-info generateColor">{"LBL_GENERATE_COLOR"|t:$QUALIFIED_MODULE}</button>
						</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
	<div class="modal editColorContainer fade" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header contentsBackground">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h3 class="modal-title">{"LBL_EDIT_COLOR"|t:$QUALIFIED_MODULE}</h3>
				</div>
				<div class="modal-body">
					<form class="form-horizontal">
						<input type="hidden" class="selectedColor" value="" />
						<div class="form-group">
							<label class=" col-sm-3 control-label">{"LBL_SELECT_COLOR"|t:$QUALIFIED_MODULE}</label>
							<div class=" col-sm-8 controls">
								<p class="calendarColorPicker"></p>
							</div>
						</div>
					</form>
				</div>
				{include file='ModalFooter.tpl'|@vtemplate_path:$MODULE}
			</div>
		</div>
	</div>
</div>
<!--/layouts/basic/modules/Settings/Calendar/ActivityTypesContent.tpl -->
{/strip}

