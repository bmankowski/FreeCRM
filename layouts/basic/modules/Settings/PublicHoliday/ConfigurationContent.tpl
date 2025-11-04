{*<!--
/*********************************************************************************
FreeCRM - Customer Relationship Management System
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
**************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Settings/PublicHoliday/ConfigurationContent.tpl -->
<div class="" id="widgetsManagementEditorContainer">
	<div class="widget_header row">
		<div class="col-md-12">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			{"LBL_PUBLIC_HOLIDAY_DESCRIPTION"|t:$QUALIFIED_MODULE}
		</div>
	</div>
	<div class="contents tabbable">
		<div class="tab-content themeTableColor overflowVisible">
		<div class="tab-pane active" id="layoutDashBoards">
			<div class="btn-toolbar marginBottom10px">
				<button type="button" class="btn btn-success addDateWindow"><span class="glyphicon glyphicon-plus"></span>&nbsp;{"LBL_ADD_HOLIDAY"|t:$QUALIFIED_MODULE}</button>
			</div>
			<div id="moduleBlocks">
				<div style="border-radius: 4px 4px 0px 0px;background: white;" class="editFieldsTable block_1 marginBottom10px border1px">
					<div class="row no-margin">
						<table class="table table-bordered layoutBlockHeader">
							<tr>
								<td>
									<div class="col-xs-12 col-sm-6 col-md-6 paddingLRZero">
										<h4>{"LBL_HOLIDAY_LIST"|t:$QUALIFIED_MODULE}:</h4>
									</div>
									<div class="pull-right col-xs-12 col-sm-6 col-md-6 paddingLRZero">
										<div class="pull-right">
											<div class="col-xs-3 paddingTop10 paddingLRZero">
												<strong>{"LBL_DATE_RANGE"|t:$QUALIFIED_MODULE}:</strong>
											</div>
											<div class="col-xs-8 col-xs-pull-1">
												<input type="text" class="dateField dateFilter marginbottomZero form-control" data-date-format="{$CURRENTUSER->get('date_format')}" data-calendar-type="range" value="{$DATE}" />
											</div>
										</div>
									</div>
								</td>
							</tr>
						</table>
					</div>
					<table class="table tableRWD table-bordered ">
						<thead class='text-capitalize'>
						    <tr>
							<th><span class='marginLeft20'>{"LBL_DATE"|t:$QUALIFIED_MODULE}</span></th>
							<th><span class='marginLeft20'>{"LBL_DAY"|t:$QUALIFIED_MODULE}</span></th>
							<th><span class='marginLeft20'>{"LBL_DAY_NAME"|t:$QUALIFIED_MODULE}</span></th>
							<th><span class='marginLeft20'>{"LBL_HOLIDAY_TYPE"|t:$QUALIFIED_MODULE}</span></th>
							<th></th>
						    </tr>
						</thead>
						<tbody>
						{foreach item=HOLIDAY from=$HOLIDAYS}
							<tr class="holidayElement" data-holiday-id="{$HOLIDAY['id']}" data-holiday-type="{$HOLIDAY['type']}" data-holiday-name="{$HOLIDAY['name']}" data-holiday-date="{\App\Fields\DateTime::currentUserDisplayDate($HOLIDAY['date'])}">
								<td>
									<span class="fieldLabel marginLeft20">{\App\Fields\DateTime::currentUserDisplayDate($HOLIDAY['date'])}</span>
								</td>
								<td>
									<span class="fieldLabel marginLeft20">{$HOLIDAY['day']|t:$QUALIFIED_MODULE}</span>
								</td>
								<td>
									<span class="marginLeft20">{$HOLIDAY['name']|t:$QUALIFIED_MODULE}</span>
								</td>
								<td>
									<span class="marginLeft20">{$HOLIDAY['type']|t:$QUALIFIED_MODULE}</span>
								</td>
								<td>
									<div class='pull-right'>
										<a data-holiday-id="{$HOLIDAY['id']}" data-toggle="dropdown" class="dropdown-toggle editHoliday" href="javascript:void(0)">
											<span title="{"LBL_EDIT"|t:$QUALIFIED_MODULE}" class="glyphicon glyphicon-pencil alignMiddle"></span>
										</a>
										<a data-holiday-id="{$HOLIDAY['id']}" class="deleteHoliday" href="javascript:void(0)">
											<span title="{"LBL_DELETE"|t:$QUALIFIED_MODULE}" class="glyphicon glyphicon-trash alignMiddle"></span>
										</a>
									</div>
								</td>
							</tr>
						{/foreach}
						</tbody>
					</table>
				</div>
			</div>
			{* copy elements hide *}
			<div class="modal addDateWindowModal fade publicHolidayModal" tabindex="-1">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header contentsBackground">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
							<h3 class="modal-title">{"LBL_ADD_NEW_HOLIDAY"|t:$QUALIFIED_MODULE}</h3>
						</div>
						<form class="form-horizontal addDateWindowForm">
							<input type="hidden" name="holidayId" value="" />
							<div class="modal-body">
								<div class="form-group">
									<div class="col-sm-3 control-label">
										<span>{"LBL_DATE"|t:$QUALIFIED_MODULE}</span>
										<span class="redColor">*</span>
									</div>
									<div class="col-sm-6 controls">
										<input type="text" name="holidayDate" class="dateField form-control" data-date-format="{$CURRENTUSER->column_fields['date_format']}" value="{\App\Fields\DateTime::currentUserDisplayDate(date('Y-m-d'))}" required >

									</div>
								</div>
								<div class="form-group">
									<div class="col-sm-3 control-label">
										<span>{"LBL_HOLIDAY_TYPE"|t:$QUALIFIED_MODULE}</span>
										<span class="redColor">*</span>
									</div>
									<div class="col-sm-6 controls">
										 <select name="holidayType" class="form-control" required data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" >
											<option value="national">{"LBL_NATIONAL"|t:$QUALIFIED_MODULE}</option>
											<option value="ecclesiastical">{"LBL_ECCLESIASTICAL"|t:$QUALIFIED_MODULE}</option>
										</select> 
									</div>
								</div>
								<div class="form-group">
									<div class="col-sm-3 control-label">
										<span>{"LBL_DAY_NAME"|t:$QUALIFIED_MODULE}</span>
										<span class="redColor">*</span>
									</div>
									<div class="col-sm-6 controls">
										<input type="text" name="holidayName" value="" class="form-control" placeholder="{"LBL_DAY_NAME_DESC"|t:$QUALIFIED_MODULE}" required data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" />
									</div>
								</div>
							</div>
							{include file='ModalFooter.tpl'|@vtemplate_path:'Vtiger'}
						</form>
					</div>
				</div>
			</div>
		</div>
		</div>
	</div>
</div>
<!--/layouts/basic/modules/Settings/PublicHoliday/ConfigurationContent.tpl -->
{/strip}
