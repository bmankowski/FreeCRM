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
	<!-- layouts/basic/modules/Settings/ConfReport/IndexContent.tpl -->
	<div class="">
		<div class="widget_header row">
			<div class="col-xs-10">
				{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
				{"LBL_CONFREPORT_DESCRIPTION"|t:$MODULE}
			</div>
			<div class="col-xs-2">
				<button class="btn btn-primary testSpeed pull-right">
					<span class="glyphicon glyphicon-dashboard" aria-hidden="true"></span>&nbsp;&nbsp;
					{"BTN_SERVER_SPEED_TEST"|t:$QUALIFIED_MODULE}
				</button>
			</div>
		</div>
		<ul class="nav nav-tabs">
			<li class="active"><a data-toggle="tab" href="#Configuration">{"LBL_YETIFORCE_ENGINE"|t:$MODULE}</a></li>
			<li><a data-toggle="tab" href="#Permissions">{"LBL_FILES_PERMISSIONS"|t:$MODULE}</a></li>
		</ul>
		<div class="tab-content">
			<div id="Configuration" class="tab-pane fade in active">
				<table class="table tableRWD table-bordered table-condensed themeTableColor confTable">
					<thead>
						<tr class="blockHeader">
							<th colspan="1" class="mediumWidthType">
								<span>{"LBL_LIBRARY"|t:$MODULE}</span>
							</th>
							<th colspan="1" class="mediumWidthType">
								<span>{"LBL_INSTALLED"|t:$MODULE}</span>
							</th>
							<th colspan="1" class="mediumWidthType">
								<span>{"LBL_MANDATORY"|t:$MODULE}</span>
							</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$CONFIGURATION_LIBRARY key=key item=item}
							<tr {if $item.status == 'LBL_NO'}class="danger" {/if}>
								<td><label>{$key|t:$MODULE}</label></td>
								<td><label>{$item.status|t:$MODULE}</label></td>
								<td><label>
										{if $item.mandatory}
											{"LBL_MANDATORY"|t:$MODULE}
										{else}
											{"LBL_OPTIONAL"|t:$MODULE}
										{/if}
									</label></td>
							</tr>
						{/foreach}
					</tbody>
				</table>
				<br />
				<table class="table tableRWD table-bordered table-condensed themeTableColor confTable">
					<thead>
						<tr class="blockHeader">
							<th colspan="1" class="mediumWidthType">
								<span>{"LBL_PARAMETER"|t:$MODULE}</span>
							</th>
							<th colspan="1" class="mediumWidthType">
								<span>{"LBL_RECOMMENDED"|t:$MODULE}</span>
							</th>
							<th colspan="1" class="mediumWidthType">
								<span>{"LBL_VALUE"|t:$MODULE}</span>
							</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$CONFIGURATION_VALUES key=key item=item}
							<tr {if $item.status}class="danger" {/if}>
								<td><label>{$key}</label></td>
								<td><label>{$item.prefer|t:$MODULE}</label></td>
								<td><label>{$item.current|t:$MODULE}</label></td>
							</tr>
						{/foreach}
					</tbody>
				</table>
				<br />
				<table class="table tableRWD table-bordered table-condensed themeTableColor confTable">
					<thead>
						<tr class="blockHeader">
							<th colspan="2" class="mediumWidthType">
								<h4>{"LBL_ENVIRONMENTAL_INFORMATION"|t:$MODULE}</h4>
							</th>
						</tr>
						<tr class="blockHeader">
							<th colspan="1" class="mediumWidthType">
								<span>{"LBL_PARAMETER"|t:$MODULE}</span>
							</th>
							<th colspan="1" class="mediumWidthType">
								<span>{"LBL_VALUE"|t:$MODULE}</span>
							</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$SYSTEM_INFO key=key item=item}
							<tr>
								<td><label>{$key|t:$MODULE}</label></td>
								<td><label>{$item}</label></td>
							</tr>
						{/foreach}
					</tbody>
				</table>
				<br />
				<table class="table tableRWD table-bordered table-condensed themeTableColor confTable">
					<thead>
						<tr class="blockHeader">
							<th colspan="2" class="mediumWidthType">
								<h4>{"LBL_HARDWARE_INFORMATION"|t:$MODULE}</h4>
							</th>
						</tr>
						<tr class="blockHeader">
							<th colspan="1" class="mediumWidthType">
								<span>{"LBL_PARAMETER"|t:$MODULE}</span>
							</th>
							<th colspan="1" class="mediumWidthType">
								<span>{"LBL_VALUE"|t:$MODULE}</span>
							</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$HARDWARE_INFO key=key item=item}
							<tr>
								<td><label>{$key|t:$MODULE}</label></td>
								<td>
									{if is_array($item)}
										{foreach from=$item item=row}
											<label>{$row}</label><br />
										{/foreach}
									{else}
										<label>{$item}</label>
									{/if}
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
				<br />
			</div>
			<div id="Permissions" class="tab-pane fade">
				<table class="table tableRWD table-bordered table-condensed themeTableColor confTable">
					<thead>
						<tr class="blockHeader">
							<th colspan="1" class="mediumWidthType">
								<span>{"LBL_FILE"|t:$MODULE}</span>
							</th>
							<th colspan="1" class="mediumWidthType">
								<span>{"LBL_PATH"|t:$MODULE}</span>
							</th>
							<th colspan="1" class="mediumWidthType">
								<span>{"LBL_PERMISSION"|t:$MODULE}</span>
							</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$PERMISSIONS_FILES key=key item=item}
							<tr {if $item.permission eq 'FailedPermission'}class="danger" {/if}>
								<td width="23%"><label class="marginRight5px">{$key|t:$MODULE}</label></td>
								<td width="23%"><label class="marginRight5px">{$item.path|t:$MODULE}</label></td>
								<td width="23%"><label class="marginRight5px">
										{if $item.permission eq 'FailedPermission'}
											{"LBL_FAILED_PERMISSION"|t:$MODULE}
										{else}
											{"LBL_TRUE_PERMISSION"|t:$MODULE}
										{/if}
									</label></td>
							</tr>
						{/foreach}
					</tbody>
				</table>

			</div>
		</div>
	</div>
	<!--/layouts/basic/modules/Settings/ConfReport/IndexContent.tpl -->
{/strip}





