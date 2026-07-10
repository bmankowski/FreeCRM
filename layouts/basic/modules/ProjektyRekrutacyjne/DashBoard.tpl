{*<!-- {[The file is published on the basis of YetiForce Public License 6.5 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
<!-- layouts/basic/modules/ProjektyRekrutacyjne/DashBoard.tpl -->
{extends file="MainLayout.tpl"|@vtemplate_path}

{function name=renderCandidateChips candidates=[]}
	{if $candidates|@count}
		{foreach from=$candidates item=CHIP}
			<div class="candidate candidate-chip"
				 datasrc="{$CHIP.url}"
				 data-candidate-id="{$CHIP.id}"
				 draggable="true">
				{$CHIP.name|escape}
			</div>
		{/foreach}
	{/if}
{/function}

{block name="content"}
<div class="mainContainer">
	<div class="contentsDiv col-md-12 marginLeftZero">
		{include file="dashboards/DashBoardHeader.tpl"|vtemplate_path:$MODULE_NAME
			DASHBOARDHEADER_TITLE='LBL_RECRUITMENT_PROJECTS_DASHBOARD'|t:$MODULE_NAME}

		<div class="recruitment-projects-dashboard">
			<input type="hidden" class="js-status-transitions" value="{$STATUS_TRANSITIONS_JSON}"/>

			{if $DASHBOARD_ROWS|@count == 0}
				<div class="alert alert-info">{'LBL_RECRUITMENT_DASHBOARD_EMPTY'|t:$MODULE_NAME}</div>
			{else}
				<table class="table table-bordered table-sm table-hover recruitment-projects-dashboard__table">
					<thead class="thead-light">
						<tr>
							<th>{'Kontrahent'|t:$MODULE_NAME}</th>
							<th>{'Nazwa Projektu'|t:$MODULE_NAME}</th>
							<th>{'Assigned To'|t:'Vtiger'}</th>
							<th class="text-center">{'PLL_CVS_APPLIED_NUMBER'|t:$MODULE_NAME}</th>
							{foreach from=$DASHBOARD_STATUS_COLUMNS item=STATUS}
								<th>{$STATUS|t:$MODULE_NAME}</th>
							{/foreach}
						</tr>
					</thead>
					<tbody>
						{foreach from=$DASHBOARD_ROWS item=ROW}
							<tr data-project-id="{$ROW.id}">
								<td>
									{if $ROW.clientUrl}
										<a href="{$ROW.clientUrl}">{$ROW.clientName|escape}</a>
									{else}
										{$ROW.clientName|escape}
									{/if}
								</td>
								<td><a href="{$ROW.detailUrl}"><strong>{$ROW.name|escape}</strong></a></td>
								<td>{$ROW.ownerName|escape}</td>
								<td class="text-center">{$ROW.appliedCount}</td>
								{foreach from=$DASHBOARD_STATUS_COLUMNS item=STATUS}
									<td class="candidate-status recruitment-projects-dashboard__candidates" data-value="{$STATUS}">
										{renderCandidateChips candidates=$ROW.candidates[$STATUS]}
									</td>
								{/foreach}
							</tr>
						{/foreach}
					</tbody>
				</table>
			{/if}
		</div>
	</div>
</div>
{/block}
<!-- /layouts/basic/modules/ProjektyRekrutacyjne/DashBoard.tpl -->
{/strip}
