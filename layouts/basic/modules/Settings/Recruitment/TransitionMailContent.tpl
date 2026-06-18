{strip}
<!-- layouts/basic/modules/Settings/Recruitment/TransitionMailContent.tpl -->
<div id="recruitmentTransitionMailContainer">
	<div class="widget_header row">
		<div class="col-xs-12">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			<p class="text-muted">{"LBL_TRANSITION_MAIL_HELP"|t:$QUALIFIED_MODULE}</p>
			{if $SHORT_NAME_OPTIONS|@count == 0}
				<div class="alert alert-warning" role="alert">
					{"LBL_NO_SHORT_NAMES"|t:$QUALIFIED_MODULE}
				</div>
			{/if}
		</div>
	</div>

	<div class="table-responsive">
		<table class="table table-bordered table-sm js-transition-mail-matrix">
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
							<td class="text-center align-top{if $FROM_CODE eq $TO_CODE} bg-light{/if}" style="min-width: 10rem;">
								{if $FROM_CODE neq $TO_CODE}
									{assign var=CELL_SHORT_NAMES value=[]}
									{if isset($MAIL_MATRIX[$FROM_CODE][$TO_CODE])}
										{assign var=CELL_SHORT_NAMES value=$MAIL_MATRIX[$FROM_CODE][$TO_CODE]}
									{/if}
									<div class="mb-1">
										<input type="checkbox"
										       class="js-mail-prompt-checkbox"
										       data-from="{$FROM_CODE|escape}"
										       data-to="{$TO_CODE|escape}"
										       {if $CELL_SHORT_NAMES|@count > 0}checked="checked"{/if}
										       aria-label="{"LBL_MAIL_PROMPT_ENABLE"|t:$QUALIFIED_MODULE}: {$FROM_LABEL|escape} → {$TO_LABEL|escape}" />
									</div>
									{if $SHORT_NAME_OPTIONS|@count > 0}
										<div class="js-mail-short-name-wrap mt-1{if $CELL_SHORT_NAMES|@count == 0} hide{/if}">
											<select class="form-control input-sm select2noactive js-mail-short-names" multiple="multiple"
											        data-from="{$FROM_CODE|escape}"
											        data-to="{$TO_CODE|escape}"
											        aria-label="{$FROM_LABEL|escape} → {$TO_LABEL|escape}">
												{foreach from=$SHORT_NAME_OPTIONS item=SHORT_NAME}
													<option value="{$SHORT_NAME|escape}"
													        {if in_array($SHORT_NAME, $CELL_SHORT_NAMES)}selected="selected"{/if}>
														{$SHORT_NAME|escape}
													</option>
												{/foreach}
											</select>
										</div>
									{/if}
								{/if}
							</td>
						{/foreach}
						<td class="text-center text-nowrap align-top">
							<button type="button" class="btn btn-xs btn-outline-secondary js-mail-select-row" data-from="{$FROM_CODE|escape}">
								{"LBL_SELECT_ROW"|t:$QUALIFIED_MODULE}
							</button>
							<button type="button" class="btn btn-xs btn-outline-secondary js-mail-clear-row" data-from="{$FROM_CODE|escape}">
								{"LBL_CLEAR_ROW"|t:$QUALIFIED_MODULE}
							</button>
						</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>

	<div class="mt-3">
		<button type="button" class="btn btn-success js-save-transition-mail">
			<span class="fas fa-save"></span>
			{"LBL_SAVE"|t:$QUALIFIED_MODULE}
		</button>
	</div>
</div>
<!-- /layouts/basic/modules/Settings/Recruitment/TransitionMailContent.tpl -->
{/strip}
