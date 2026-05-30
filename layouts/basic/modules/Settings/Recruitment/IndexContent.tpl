{strip}
<!-- layouts/basic/modules/Settings/Recruitment/IndexContent.tpl -->
<div id="recruitmentSettingsContainer">
	<div class="widget_header row">
		<div class="col-xs-12">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			<p class="text-muted">{"LBL_RECRUITMENT_DESCRIPTION"|t:$QUALIFIED_MODULE}</p>
		</div>
	</div>

	{if !$IS_CONFIGURED}
		<div class="alert alert-warning" role="alert">
			{"LBL_TRANSITIONS_NOT_CONFIGURED"|t:$QUALIFIED_MODULE}
		</div>
	{/if}

	<div class="mb-3">
		<h4>{"LBL_STATUS_TRANSITIONS"|t:$QUALIFIED_MODULE}</h4>
		<p class="text-muted small">{"LBL_STATUS_TRANSITIONS_HELP"|t:$QUALIFIED_MODULE}</p>
	</div>

	<div class="table-responsive">
		<table class="table table-bordered table-sm js-transition-matrix">
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
					<th class="text-center small">{"LBL_ROW_ACTIONS"|t:$QUALIFIED_MODULE}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$STATUS_OPTIONS key=FROM_CODE item=FROM_LABEL}
					<tr data-from-row="{$FROM_CODE|escape}">
						<th scope="row" class="small">{$FROM_LABEL|escape}</th>
						{foreach from=$STATUS_OPTIONS key=TO_CODE item=TO_LABEL}
							<td class="text-center{if $FROM_CODE eq $TO_CODE} bg-light{/if}">
								{if $FROM_CODE neq $TO_CODE}
									<input type="checkbox"
									       class="js-transition-checkbox"
									       data-from="{$FROM_CODE|escape}"
									       data-to="{$TO_CODE|escape}"
									       data-col="{$TO_CODE|escape}"
									       {if isset($CHECKED_TRANSITIONS[$FROM_CODE][$TO_CODE])}checked="checked"{/if}
									       aria-label="{$FROM_LABEL|escape} → {$TO_LABEL|escape}" />
								{/if}
							</td>
						{/foreach}
						<td class="text-center text-nowrap">
							<button type="button" class="btn btn-xs btn-outline-secondary js-select-row" data-from="{$FROM_CODE|escape}">
								{"LBL_SELECT_ROW"|t:$QUALIFIED_MODULE}
							</button>
							<button type="button" class="btn btn-xs btn-outline-secondary js-clear-row" data-from="{$FROM_CODE|escape}">
								{"LBL_CLEAR_ROW"|t:$QUALIFIED_MODULE}
							</button>
						</td>
					</tr>
				{/foreach}
			</tbody>
			<tfoot>
				<tr>
					<th>{"LBL_COLUMN_ACTIONS"|t:$QUALIFIED_MODULE}</th>
					{foreach from=$STATUS_OPTIONS key=TO_CODE item=TO_LABEL}
						<td class="text-center">
							<button type="button" class="btn btn-xs btn-outline-secondary js-select-col" data-to="{$TO_CODE|escape}" title="{$TO_LABEL|escape}">
								+
							</button>
						</td>
					{/foreach}
					<td></td>
				</tr>
			</tfoot>
		</table>
	</div>

	<div class="mt-3">
		<button type="button" class="btn btn-success js-save-transitions">
			<span class="fas fa-save"></span>
			{"LBL_SAVE"|t:$QUALIFIED_MODULE}
		</button>
	</div>
</div>
<!-- /layouts/basic/modules/Settings/Recruitment/IndexContent.tpl -->
{/strip}
