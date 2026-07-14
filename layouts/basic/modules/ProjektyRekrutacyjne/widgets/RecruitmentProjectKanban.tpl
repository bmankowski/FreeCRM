{*<!-- {[The file is published on the basis of YetiForce Public License 6.5 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
<!-- layouts/basic/modules/ProjektyRekrutacyjne/widgets/RecruitmentProjectKanban.tpl -->
{assign var=WIDGET_UID value="widget-{$WIDGET['id']}"}
{assign var=CANDIDATES_BY_STATUS value=$WIDGET['data']['candidatesByStatus']}
{assign var=PROJECT_ID value=$WIDGET['data']['projectId']}

{* Macro for rendering candidates in a status column *}
{function name=renderCandidates status=''}
	{if isset($CANDIDATES_BY_STATUS[$status]) && $CANDIDATES_BY_STATUS[$status]}
		{foreach from=$CANDIDATES_BY_STATUS[$status] item=CANDIDATE}
			<div class="candidate candidate-chip"
				 datasrc="{$CANDIDATE->getDetailViewUrl()}" 
				 data-candidate-id="{$CANDIDATE->getId()}"
				 draggable="true">
				{$CANDIDATE->get('name')}
			</div>
		{/foreach}
	{/if}
{/function}

<div class="summaryWidgetContainer recruitment-project-kanban" id="{$WIDGET_UID}">
	<input type="hidden" class="project-id" value="{$PROJECT_ID}"/>
	<input type="hidden" class="js-status-transitions" value="{$WIDGET['data']['statusTransitionsJson']}"/>
	
	{* Table 0: Entry — manual pool and AI-added candidates *}
	<table class="table table-bordered table-sm recruitment-kanban-entry">
		<thead class="thead-light">
		<tr>
			<th>
				{'PPL_MANUALLY_ADDED'|t:$MODULE_NAME}
				<button type="button" class="btn btn-sm js-kanban-add-manual-candidate" title="{'LBL_ADD_MANUAL_CANDIDATE'|t:$MODULE_NAME}" aria-label="{'LBL_ADD_MANUAL_CANDIDATE'|t:$MODULE_NAME}">+</button>
			</th>
			<th>{'PPL_AI_ADDED'|t:$MODULE_NAME}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td class="candidate-status" data-value="PPL_MANUALLY_ADDED">
				{renderCandidates status='PPL_MANUALLY_ADDED'}
			</td>
			<td class="candidate-status" data-value="PPL_AI_ADDED">
				{renderCandidates status='PPL_AI_ADDED'}
			</td>
		</tr>
		</tbody>
	</table>

	{* Table 1: Initial screening *}
	<table class="table table-bordered table-sm recruitment-kanban-screening">
		<thead class="thead-light">
		<tr>
			<th>{'PPL_REJECTED_AFTER_CV'|t:$MODULE_NAME}</th>
			<th>{'PPL_APPLIED'|t:$MODULE_NAME}</th>
			<th>{'PPL_CANDIDATE_PASSED_SCREENING'|t:$MODULE_NAME}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td class="candidate-status" data-value="PPL_REJECTED_AFTER_CV">
				{renderCandidates status='PPL_REJECTED_AFTER_CV'}
			</td>
			<td class="candidate-status" data-value="PPL_APPLIED">
				{renderCandidates status='PPL_APPLIED'}
			</td>
			<td class="candidate-status" data-value="PPL_CANDIDATE_PASSED_SCREENING">
				{renderCandidates status='PPL_CANDIDATE_PASSED_SCREENING'}
			</td>
		</tr>
		</tbody>
	</table>
	
	{* Table 2: Interview and offer process *}
	<table class="table table-bordered table-sm recruitment-kanban-quarters recruitment-kanban-offer">
		<thead class="thead-light">
		<tr>
			<th>{'PPL_WAITING_FOR_INTERVIEW'|t:$MODULE_NAME}</th>
			<th>{'PPL_HANDED_TO_SALES'|t:$MODULE_NAME}</th>
			<th>{'PPL_TO_BE_SENT_TO_CLIENT'|t:$MODULE_NAME}</th>
			<th>{'PPL_SENT_TO_CLIENT'|t:$MODULE_NAME}</th>
			<th>{'PPL_STAGE_1'|t:$MODULE_NAME}</th>
			<th>{'PPL_STAGE_2'|t:$MODULE_NAME}</th>
			<th>{'PPL_STAGE_3'|t:$MODULE_NAME}</th>
			<th>{'PPL_ACCEPTED'|t:$MODULE_NAME}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td class="candidate-status" data-value="PPL_WAITING_FOR_INTERVIEW">
				{renderCandidates status='PPL_WAITING_FOR_INTERVIEW'}
			</td>
			<td class="candidate-status" data-value="PPL_HANDED_TO_SALES">
				{renderCandidates status='PPL_HANDED_TO_SALES'}
			</td>
			<td class="candidate-status" data-value="PPL_TO_BE_SENT_TO_CLIENT">
				{renderCandidates status='PPL_TO_BE_SENT_TO_CLIENT'}
			</td>
			<td class="candidate-status" data-value="PPL_SENT_TO_CLIENT">
				{renderCandidates status='PPL_SENT_TO_CLIENT'}
			</td>
			<td class="candidate-status" data-value="PPL_STAGE_1">
				{renderCandidates status='PPL_STAGE_1'}
			</td>
			<td class="candidate-status" data-value="PPL_STAGE_2">
				{renderCandidates status='PPL_STAGE_2'}
			</td>
			<td class="candidate-status" data-value="PPL_STAGE_3">
				{renderCandidates status='PPL_STAGE_3'}
			</td>
			<td class="candidate-status" data-value="PPL_ACCEPTED">
				{renderCandidates status='PPL_ACCEPTED'}
			</td>
		</tr>
		</tbody>
	</table>
	
	{* Table 3: Rejections *}
	<table class="table table-bordered table-sm recruitment-kanban-quarters">
		<thead class="thead-light">
		<tr>
			<th>{'PPL_REJECTED_AFTER_VERIFICATION'|t:$MODULE_NAME}</th>
			<th>{'PPL_REJECTED_AFTER_INTERVIEW'|t:$MODULE_NAME}</th>
			<th>{'PPL_OFFER_REJECTED_BY_CANDIDATE'|t:$MODULE_NAME}</th>
			<th>{'PPL_REJECTED_BY_CLIENT'|t:$MODULE_NAME}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td class="candidate-status" data-value="PPL_REJECTED_AFTER_VERIFICATION">
				{renderCandidates status='PPL_REJECTED_AFTER_VERIFICATION'}
			</td>
			<td class="candidate-status" data-value="PPL_REJECTED_AFTER_INTERVIEW">
				{renderCandidates status='PPL_REJECTED_AFTER_INTERVIEW'}
			</td>
			<td class="candidate-status" data-value="PPL_OFFER_REJECTED_BY_CANDIDATE">
				{renderCandidates status='PPL_OFFER_REJECTED_BY_CANDIDATE'}
			</td>
			<td class="candidate-status" data-value="PPL_REJECTED_BY_CLIENT">
				{renderCandidates status='PPL_REJECTED_BY_CLIENT'}
			</td>
		</tr>
		</tbody>
	</table>
</div>
<!-- /layouts/basic/modules/ProjektyRekrutacyjne/widgets/RecruitmentProjectKanban.tpl -->
{/strip}
