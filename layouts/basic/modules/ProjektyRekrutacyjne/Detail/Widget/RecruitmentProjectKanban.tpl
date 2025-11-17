{*<!-- {[The file is published on the basis of YetiForce Public License 6.5 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
	<!-- tpl-RecruitmentProjectKanban -->
	{assign var=WIDGET_UID value="id-{App\Layout::getUniqueId($WIDGET['id']|cat:_)}"}
	{assign var=CANDIDATES_BY_STATUS value=$WIDGET['data']['candidatesByStatus']}

	{assign var=PROJECT_ID value=$WIDGET['data']['projectId']}
	<div>
		<input type="hidden" class="project-id" value="{$PROJECT_ID}"/>
		<table class=" table table-bordered">
			<thead>
			<tr>
				<th>{App\Language::translate("PPL_REJECTED_AFTER_CV",$MODULE_NAME)}</th>
				<th>{App\Language::translate("PPL_APPLIED",$MODULE_NAME)}</th>
				<th>{App\Language::translate("PPL_CANDIDATE_PASSED_SCREENING",$MODULE_NAME)}</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td class="w-25 candidate-status" data-value="PPL_REJECTED_AFTER_CV" tabindex="0">
					{foreach from=$CANDIDATES_BY_STATUS["PPL_REJECTED_AFTER_CV"] item=CANDIDATE name=candidatesFunnel}
						{assign var=PDF value=$CANDIDATE->getCVPathname()}
						{assign var=CANDIDATE_URL value=$CANDIDATE->getDetailViewUrl()}
						{assign var=CANDIDATE_ID value=$CANDIDATE->getId()}
						<div class="rounded-pill border p-1 m-1 text-center d-inline-block candidate"
							 datasrc="{$CANDIDATE_URL}" data-candidate-id="{$CANDIDATE_ID}"
							 data-candidate-order-number="{$smarty.foreach.candidatesFunnel.iteration}"
							 draggable="true">
							{$CANDIDATE->get('name')}
						</div>
					{/foreach}
				</td>
				<td class="w-25 candidate-status" data-value="PPL_APPLIED" tabindex="0">
					{foreach from=$CANDIDATES_BY_STATUS["PPL_APPLIED"] item=CANDIDATE name=candidatesFunnel}
						{assign var=PDF value=$CANDIDATE->getCVPathname()}
						{assign var=CANDIDATE_URL value=$CANDIDATE->getDetailViewUrl()}
						{assign var=CANDIDATE_ID value=$CANDIDATE->getId()}
						<div class="rounded-pill border p-1 m-1 text-center d-inline-block candidate"
							 datasrc="{$CANDIDATE_URL}" data-candidate-id="{$CANDIDATE_ID}"
							 data-candidate-order-number="{$smarty.foreach.candidatesFunnel.iteration}"
							 draggable="true">
							{$CANDIDATE->get('name')}
						</div>
					{/foreach}
				</td>
				<td class="w-25 candidate-status" data-value="PPL_CANDIDATE_PASSED_SCREENING" tabindex="0">
					{foreach from=$CANDIDATES_BY_STATUS["PPL_CANDIDATE_PASSED_SCREENING"] item=CANDIDATE name=candidatesFunnel}
						{assign var=PDF value=$CANDIDATE->getCVPathname()}
						{assign var=CANDIDATE_URL value=$CANDIDATE->getDetailViewUrl()}
						{assign var=CANDIDATE_ID value=$CANDIDATE->getId()}
						<div class="rounded-pill border p-1 m-1 text-center d-inline-block candidate"
							 datasrc="{$CANDIDATE_URL}" data-candidate-id="{$CANDIDATE_ID}"
							 data-candidate-order-number="{$smarty.foreach.candidatesFunnel.iteration}"
							 draggable="true">
							{$CANDIDATE->get('name')}
						</div>
					{/foreach}
				</td>
			</tr>
			</tbody>
		</table>
		<table class=" table table-bordered">
			<thead>
			<tr>
				<th>{App\Language::translate("PPL_CANDIDATE_PASSED_SCREENING",$MODULE_NAME)}</th>
				<th>{App\Language::translate("PPL_WAITING_FOR_INTERVIEW",$MODULE_NAME)}</th>
				<th>{App\Language::translate("PPL_TO_BE_SENT_TO_CLIENT",$MODULE_NAME)}</th>
				<th>{App\Language::translate("PPL_SENT_TO_CLIENT",$MODULE_NAME)}</th>
				<th>{App\Language::translate("PPL_ACCEPTED",$MODULE_NAME)}</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td class="w-20 candidate-status" data-value="PPL_CANDIDATE_PASSED_SCREENING" tabindex="0">
					{foreach from=$CANDIDATES_BY_STATUS["PPL_CANDIDATE_PASSED_SCREENING"] item=CANDIDATE name=candidatesFunnel}
						{assign var=PDF value=$CANDIDATE->getCVPathname()}
						{assign var=CANDIDATE_URL value=$CANDIDATE->getDetailViewUrl()}
						{assign var=CANDIDATE_ID value=$CANDIDATE->getId()}
						<div class="rounded-pill border p-1 m-1 text-center d-inline-block candidate"
							 datasrc="{$CANDIDATE_URL}" data-candidate-id="{$CANDIDATE_ID}"
							 data-candidate-order-number="{$smarty.foreach.candidatesFunnel.iteration}"
							 draggable="true">
							{$CANDIDATE->get('name')}
						</div>
					{/foreach}
				</td>
				<td class="w-20 candidate-status" data-value="PPL_WAITING_FOR_INTERVIEW" tabindex="0">
					{foreach from=$CANDIDATES_BY_STATUS["PPL_WAITING_FOR_INTERVIEW"] item=CANDIDATE name=candidatesFunnel}
						{assign var=PDF value=$CANDIDATE->getCVPathname()}
						{assign var=CANDIDATE_URL value=$CANDIDATE->getDetailViewUrl()}
						{assign var=CANDIDATE_ID value=$CANDIDATE->getId()}
						<div class="rounded-pill border p-1 m-1 text-center d-inline-block candidate"
							 datasrc="{$CANDIDATE_URL}" data-candidate-id="{$CANDIDATE_ID}"
							 data-candidate-order-number="{$smarty.foreach.candidatesFunnel.iteration}"
							 draggable="true">
							{$CANDIDATE->get('name')}
						</div>
					{/foreach}
				</td>
				<td class="w-20 candidate-status" data-value="PPL_TO_BE_SENT_TO_CLIENT" tabindex="0">
					{foreach from=$CANDIDATES_BY_STATUS["PPL_TO_BE_SENT_TO_CLIENT"] item=CANDIDATE name=candidatesFunnel}
						{assign var=PDF value=$CANDIDATE->getCVPathname()}
						{assign var=CANDIDATE_URL value=$CANDIDATE->getDetailViewUrl()}
						{assign var=CANDIDATE_ID value=$CANDIDATE->getId()}
						<div class="rounded-pill border p-1 m-1 text-center d-inline-block candidate"
							 datasrc="{$CANDIDATE_URL}" data-candidate-id="{$CANDIDATE_ID}"
							 data-candidate-order-number="{$smarty.foreach.candidatesFunnel.iteration}"
							 draggable="true">
							{$CANDIDATE->get('name')}
						</div>
					{/foreach}
				</td>
				<td class="w-20 candidate-status" data-value="PPL_SENT_TO_CLIENT" tabindex="0">
					{foreach from=$CANDIDATES_BY_STATUS["PPL_SENT_TO_CLIENT"] item=CANDIDATE name=candidatesFunnel}
						{assign var=PDF value=$CANDIDATE->getCVPathname()}
						{assign var=CANDIDATE_URL value=$CANDIDATE->getDetailViewUrl()}
						{assign var=CANDIDATE_ID value=$CANDIDATE->getId()}
						<div class="rounded-pill border p-1 m-1 text-center d-inline-block candidate"
							 datasrc="{$CANDIDATE_URL}" data-candidate-id="{$CANDIDATE_ID}"
							 data-candidate-order-number="{$smarty.foreach.candidatesFunnel.iteration}"
							 draggable="true">
							{$CANDIDATE->get('name')}
						</div>
					{/foreach}
				</td>
				<td class="w-25 candidate-status" data-value="PPL_ACCEPTED" tabindex="0">
					{foreach from=$CANDIDATES_BY_STATUS["PPL_ACCEPTED"] item=CANDIDATE name=candidatesFunnel}
						{assign var=PDF value=$CANDIDATE->getCVPathname()}
						{assign var=CANDIDATE_URL value=$CANDIDATE->getDetailViewUrl()}
						{assign var=CANDIDATE_ID value=$CANDIDATE->getId()}
						<div class="rounded-pill border p-1 m-1 text-center d-inline-block candidate"
							 datasrc="{$CANDIDATE_URL}" data-candidate-id="{$CANDIDATE_ID}"
							 data-candidate-order-number="{$smarty.foreach.candidatesFunnel.iteration}"
							 draggable="true">
							{$CANDIDATE->get('name')}
						</div>
					{/foreach}
				</td>
			</tr>
			</tbody>
		</table>
		<table class=" table table-bordered">
			<thead>
			<tr>
				<th>{App\Language::translate("PPL_REJECTED_AFTER_VERIFICATION",$MODULE_NAME)}</th>
				<th>{App\Language::translate("PPL_REJECTED_AFTER_INTERVIEW",$MODULE_NAME)}</th>
				<th>{App\Language::translate("PPL_OFFER_REJECTED_BY_CANDIDATE",$MODULE_NAME)}</th>
				<th>{App\Language::translate("PPL_REJECTED_BY_CLIENT",$MODULE_NAME)}</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td class="w-20 candidate-status" data-value="PPL_REJECTED_AFTER_VERIFICATION" tabindex="0">
					{foreach from=$CANDIDATES_BY_STATUS["PPL_REJECTED_AFTER_VERIFICATION"] item=CANDIDATE name=candidatesFunnel}
						{assign var=PDF value=$CANDIDATE->getCVPathname()}
						{assign var=CANDIDATE_URL value=$CANDIDATE->getDetailViewUrl()}
						{assign var=CANDIDATE_ID value=$CANDIDATE->getId()}
						<div class="rounded-pill border p-1 m-1 text-center d-inline-block candidate"
							 datasrc="{$CANDIDATE_URL}" data-candidate-id="{$CANDIDATE_ID}"
							 data-candidate-order-number="{$smarty.foreach.candidatesFunnel.iteration}"
							 draggable="true">
							{$CANDIDATE->get('name')}
						</div>
					{/foreach}
				</td>
				<td class="w-20 candidate-status" data-value="PPL_REJECTED_AFTER_INTERVIEW" tabindex="0">
					{foreach from=$CANDIDATES_BY_STATUS["PPL_REJECTED_AFTER_INTERVIEW"] item=CANDIDATE name=candidatesFunnel}
						{assign var=PDF value=$CANDIDATE->getCVPathname()}
						{assign var=CANDIDATE_URL value=$CANDIDATE->getDetailViewUrl()}
						{assign var=CANDIDATE_ID value=$CANDIDATE->getId()}
						<div class="rounded-pill border p-1 m-1 text-center d-inline-block candidate"
							 datasrc="{$CANDIDATE_URL}" data-candidate-id="{$CANDIDATE_ID}"
							 data-candidate-order-number="{$smarty.foreach.candidatesFunnel.iteration}"
							 draggable="true">
							{$CANDIDATE->get('name')}
						</div>
					{/foreach}
				</td>
				<td class="w-20 candidate-status" data-value="PPL_OFFER_REJECTED_BY_CANDIDATE" tabindex="0">
					{foreach from=$CANDIDATES_BY_STATUS["PPL_OFFER_REJECTED_BY_CANDIDATE"] item=CANDIDATE name=candidatesFunnel}
						{assign var=PDF value=$CANDIDATE->getCVPathname()}
						{assign var=CANDIDATE_URL value=$CANDIDATE->getDetailViewUrl()}
						{assign var=CANDIDATE_ID value=$CANDIDATE->getId()}
						<div class="rounded-pill border p-1 m-1 text-center d-inline-block candidate"
							 datasrc="{$CANDIDATE_URL}" data-candidate-id="{$CANDIDATE_ID}"
							 data-candidate-order-number="{$smarty.foreach.candidatesFunnel.iteration}"
							 draggable="true">
							{$CANDIDATE->get('name')}
						</div>
					{/foreach}
				</td>
				<td class="w-20 candidate-status" data-value="PPL_REJECTED_BY_CLIENT" tabindex="0">
					{foreach from=$CANDIDATES_BY_STATUS["PPL_REJECTED_BY_CLIENT"] item=CANDIDATE name=candidatesFunnel}
						{assign var=PDF value=$CANDIDATE->getCVPathname()}
						{assign var=CANDIDATE_URL value=$CANDIDATE->getDetailViewUrl()}
						{assign var=CANDIDATE_ID value=$CANDIDATE->getId()}
						<div class="rounded-pill border p-1 m-1 text-center d-inline-block candidate"
							 datasrc="{$CANDIDATE_URL}" data-candidate-id="{$CANDIDATE_ID}"
							 data-candidate-order-number="{$smarty.foreach.candidatesFunnel.iteration}"
							 draggable="true">
							{$CANDIDATE->get('name')}
						</div>
					{/foreach}
				</td>
			</tr>
			</tbody>
		</table>
	</div>
	<!-- /tpl-RecruitmentProjectKanban -->
{/strip}

