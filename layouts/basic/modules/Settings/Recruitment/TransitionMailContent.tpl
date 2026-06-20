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
									{assign var=CELL_TEMPLATES value=[]}
									{if isset($MAIL_MATRIX[$FROM_CODE][$TO_CODE])}
										{assign var=CELL_TEMPLATES value=$MAIL_MATRIX[$FROM_CODE][$TO_CODE]}
									{/if}
									<div class="mb-1">
										<input type="checkbox"
										       class="js-mail-prompt-checkbox"
										       data-from="{$FROM_CODE|escape}"
										       data-to="{$TO_CODE|escape}"
										       {if $CELL_TEMPLATES|@count > 0}checked="checked"{/if}
										       aria-label="{"LBL_MAIL_PROMPT_ENABLE"|t:$QUALIFIED_MODULE}: {$FROM_LABEL|escape} → {$TO_LABEL|escape}" />
									</div>
									{if $SHORT_NAME_OPTIONS|@count > 0}
										<div class="js-mail-templates-wrap mt-1{if $CELL_TEMPLATES|@count == 0} hide{/if}"
										     data-from="{$FROM_CODE|escape}"
										     data-to="{$TO_CODE|escape}">
											<div class="js-mail-template-pills mb-1">
												{foreach from=$CELL_TEMPLATES item=TEMPLATE}
													<span class="js-mail-template-pill transition-mail-pill"
													      data-short-name="{$TEMPLATE.shortName|escape}"
													      data-delivery-mode="{$TEMPLATE.deliveryMode|escape}">
														<span class="transition-mail-pill__name" title="{$TEMPLATE.shortName|escape}">{$TEMPLATE.shortName|escape}</span>
														<button type="button"
														        class="js-mail-pill-mode transition-mail-pill__mode transition-mail-pill__mode--{$TEMPLATE.deliveryMode|escape}"
														        title="{"LBL_DELIVERY_MODE_TOGGLE"|t:$QUALIFIED_MODULE}"
														        aria-label="{"LBL_DELIVERY_MODE_TOGGLE"|t:$QUALIFIED_MODULE}">
															{if $TEMPLATE.deliveryMode eq 'auto'}{"LBL_DELIVERY_AUTO"|t:$QUALIFIED_MODULE}{else}{"LBL_DELIVERY_PROMPT"|t:$QUALIFIED_MODULE}{/if}
														</button>
														<button type="button"
														        class="js-mail-pill-remove transition-mail-pill__remove"
						        title="{"LBL_PILL_REMOVE"|t:$QUALIFIED_MODULE}"
						        aria-label="{"LBL_PILL_REMOVE"|t:$QUALIFIED_MODULE}">&times;</button>
													</span>
												{/foreach}
											</div>
											<select class="form-control input-sm js-mail-add-template"
											        data-from="{$FROM_CODE|escape}"
											        data-to="{$TO_CODE|escape}"
											        aria-label="{"LBL_ADD_TEMPLATE"|t:$QUALIFIED_MODULE}: {$FROM_LABEL|escape} → {$TO_LABEL|escape}">
												<option value="">{"LBL_ADD_TEMPLATE"|t:$QUALIFIED_MODULE}</option>
												{foreach from=$SHORT_NAME_OPTIONS item=SHORT_NAME}
													<option value="{$SHORT_NAME|escape}">{$SHORT_NAME|escape}</option>
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
