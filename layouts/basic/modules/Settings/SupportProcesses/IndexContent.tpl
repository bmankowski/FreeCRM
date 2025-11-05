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
	<!-- layouts/basic/modules/Settings/SupportProcesses/IndexContent.tpl -->
	<div class=" supportProcessesContainer">
		<div class="widget_header row">
			<div class="col-xs-12">
				{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			</div>
		</div>
		<ul id="tabs" class="nav nav-tabs " data-tabs="tabs">
			<li class="active"><a href="#general_configuration"
					data-toggle="tab">{"LBL_GENERAL_CONFIGURATION"|t:$QUALIFIED_MODULE} </a></li>
		</ul>
		<br />
		<div class="tab-content">
			<div class='editViewContainer tab-pane active' id="general_configuration">
				<table class="table tableRWD table-bordered table-condensed themeTableColor userTable">
					<thead>
						<tr class="blockHeader">
							<th class="mediumWidthType">
								<span>{"LBL_INFO"|t:$QUALIFIED_MODULE}</span>
							</th>
							<th class="mediumWidthType">
								<span>{"LBL_TYPE"|t:$QUALIFIED_MODULE}</span>
							</th>
						</tr>
					</thead>
					<tbody>
						<tr data-id="{$ITEM['user_id']}">
							<td><label>{"LBL_TICKET_STATUS_INFO"|t:$QUALIFIED_MODULE}</label></td>
							<td class="col-xs-6">
								<select class="chzn-select configField form-control status" multiple name="status">
									{foreach  item=STATUS from=$TICKETSTATUS}
										<option value="{$STATUS}" {if in_array($STATUS, $TICKETSTATUSNOTMODIFY)} selected {/if}>
											{$STATUS|t:'HelpDesk'}</option>
									{/foreach}
								</select>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<!--/layouts/basic/modules/Settings/SupportProcesses/IndexContent.tpl -->
{/strip}