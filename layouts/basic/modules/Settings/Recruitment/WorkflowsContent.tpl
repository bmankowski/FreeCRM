{strip}
<!-- layouts/basic/modules/Settings/Recruitment/WorkflowsContent.tpl -->
<div id="recruitmentWorkflowsContainer">
	<div class="widget_header row">
		<div class="col-xs-12">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			<p class="text-muted">{"LBL_RECRUITMENT_WORKFLOWS_HELP"|t:$QUALIFIED_MODULE}</p>
		</div>
	</div>

	<div class="mb-3">
		<a href="{$CREATE_WORKFLOW_URL|escape}" class="btn btn-success">
			<span class="glyphicon glyphicon-plus"></span>
			{"LBL_ADD_WORKFLOW"|t:$QUALIFIED_MODULE}
		</a>
		<a href="{$VIEW_ALL_WORKFLOWS_URL|escape}" class="btn btn-default">
			{"LBL_VIEW_ALL_WORKFLOWS"|t:$QUALIFIED_MODULE}
		</a>
	</div>

	<div class="table-responsive">
		<table class="table table-bordered table-sm js-workflow-matrix">
			<thead class="thead-light">
				<tr>
					<th>{"LBL_FROM_TO"|t:$QUALIFIED_MODULE}</th>
					{foreach from=$STATUS_OPTIONS key=STATUS_CODE item=STATUS_LABEL}
						<th class="text-center small" title="{$STATUS_LABEL|escape}">
							<span class="d-inline-block text-truncate" style="max-width: 6rem; writing-mode: vertical-rl; transform: rotate(180deg);">
								{$STATUS_LABEL|escape}
							</span>
						</th>
					{/foreach}
				</tr>
			</thead>
			<tbody>
				{foreach from=$STATUS_OPTIONS key=FROM_CODE item=FROM_LABEL}
					<tr data-from-row="{$FROM_CODE|escape}">
						<th scope="row" class="small">{$FROM_LABEL|escape}</th>
						{foreach from=$STATUS_OPTIONS key=TO_CODE item=TO_LABEL}
							{assign var=CELL_WORKFLOWS value=$TRANSITION_WORKFLOW_MAP[$FROM_CODE][$TO_CODE]|default:[]}
							<td class="text-center{if $FROM_CODE eq $TO_CODE} bg-light{/if}">
								{if $FROM_CODE neq $TO_CODE}
									<div class="js-workflow-cell small">
										{if $CELL_WORKFLOWS|@count > 0}
											<a href="#"
											   class="js-workflow-badge label label-info"
											   role="button"
											   tabindex="0"
											   data-workflows="{$CELL_WORKFLOWS|@json_encode|escape:'html'}">
												{$CELL_WORKFLOWS|@count}
											</a>
										{/if}
										<a href="{$CREATE_TRANSITION_WORKFLOW_URLS[$FROM_CODE][$TO_CODE]|escape}"
										   class="js-add-workflow-for-transition"
										   title="{"LBL_ADD_WORKFLOW_FOR_TRANSITION"|t:$QUALIFIED_MODULE}">
											<span class="glyphicon glyphicon-plus-sign text-muted"></span>
										</a>
									</div>
								{/if}
							</td>
						{/foreach}
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
</div>
<!-- /layouts/basic/modules/Settings/Recruitment/WorkflowsContent.tpl -->
{/strip}
