{extends file='MainLayout.tpl'|@vtemplate_path:$MODULE}

{block name='content'}
	{strip}
		<div class="mainContainer">
			<div class="contentsDiv">
				<div class="widget_header row marginBottom10px">
					<div class="col-sm-12">
						{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
					</div>
				</div>

				<div id="ImportManagerRoot" data-view="duplicates">
					{include file='WizardSteps.tpl'|@vtemplate_path:$MODULE}

					<div class="card mb-3 import-manager-batch-card">
						<div class="card-body d-flex flex-wrap align-items-center">
							<div class="mr-4 mb-2">
								<span class="text-muted text-uppercase small d-block">{\App\Language::translate('LBL_BATCH_ID', $MODULE_NAME)}</span>
								<strong>#{$IMPORT_BATCH.id}</strong>
							</div>
							<div class="mr-4 mb-2">
								<span class="text-muted text-uppercase small d-block">{\App\Language::translate('LBL_SELECTED_MODULE', $MODULE_NAME)}</span>
								<strong>{$IMPORT_BATCH.module}</strong>
							</div>
							{if $IMPORT_BATCH.file_name}
								<div class="mr-4 mb-2">
									<span class="text-muted text-uppercase small d-block">{\App\Language::translate('LBL_SELECTED_FILE', $MODULE_NAME)}</span>
									<strong>{$IMPORT_BATCH.file_name}</strong>
								</div>
							{/if}
							<div class="mr-4 mb-2">
								<span class="text-muted text-uppercase small d-block">{\App\Language::translate('LBL_STATUS', $MODULE_NAME)}</span>
								<strong>{$IMPORT_BATCH.status}</strong>
							</div>
							{if isset($IMPORT_DEFINITION.duplicateStrategy)}
								<div class="mb-2">
									<span class="text-muted text-uppercase small d-block">{\App\Language::translate('LBL_DUPLICATE_STRATEGY', $MODULE_NAME)}</span>
									<strong>{$IMPORT_DEFINITION.duplicateStrategy}</strong>
								</div>
							{/if}
						</div>
					</div>

					{assign var=DUPLICATE_VIEW value=$IMPORT_DUPLICATE_VIEW|default:[]}
					{assign var=REQUIRED_SETS value=$DUPLICATE_VIEW.required|default:[]}
					{assign var=SUGGESTED_SETS value=$DUPLICATE_VIEW.suggested|default:[]}
					<div class="card mb-3 import-duplicates-card">
						<div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap">
							<strong>{\App\Language::translate('LBL_DUPLICATE_SETS', $MODULE_NAME)}</strong>
							<button type="button" class="btn btn-primary btn-sm d-flex align-items-center" id="ImportManagerAddDuplicateRule">
								<span class="fa fa-plus mr-1"></span>
								{\App\Language::translate('LBL_ADD_DUPLICATE_RULE', $MODULE_NAME)}
							</button>
						</div>
						<div class="card-body">
							<div class="mb-4">
								<p class="mb-2 font-weight-bold text-uppercase small text-muted">{\App\Language::translate('LBL_REQUIRED_SETS', $MODULE_NAME)}</p>
								<div id="ImportManagerRequiredSets" class="import-duplicates-chipset">
									{if $REQUIRED_SETS|@count gt 0}
										{foreach from=$REQUIRED_SETS item=SET}
											<span class="import-duplicates-chip js-duplicate-set"
												data-key="{$SET.key|escape}"
												data-fields="{$SET.fields|@json_encode|escape:'html'}">
												{$SET.label|escape}
												<button type="button"
													class="import-duplicates-chip__remove js-remove-duplicate-set"
													aria-label="{\App\Language::translate('LBL_REMOVE', $MODULE_NAME)}"
													data-key="{$SET.key|escape}">
													<span class="fa fa-times"></span>
												</button>
											</span>
										{/foreach}
									{else}
										<span class="text-muted js-duplicates-empty">
											{\App\Language::translate('LBL_DUPLICATES_NOT_CONFIGURED', $MODULE_NAME)}
										</span>
									{/if}
								</div>
							</div>

							<div class="mb-4" id="ImportManagerOptionalSetsSection" {if $SUGGESTED_SETS|@count eq 0}style="display:none;"{/if}>
								<p class="mb-2 font-weight-bold text-uppercase small text-muted">{\App\Language::translate('LBL_SUGGESTED_SETS', $MODULE_NAME)}</p>
								<div id="ImportManagerOptionalSets" class="import-duplicates-list">
									{foreach from=$SUGGESTED_SETS item=SET name=OptionalSetsLoop}
										{assign var=OPTIONAL_ID value='ImportManagerOptionalSet'|cat:$smarty.foreach.OptionalSetsLoop.index}
										<div class="form-check import-duplicates-list__item">
											<input type="checkbox"
												class="form-check-input js-optional-set"
												id="{$OPTIONAL_ID}"
												data-key="{$SET.key|escape}"
												data-fields="{$SET.fields|@json_encode|escape:'html'}"
												data-label="{$SET.label|escape}"
												{if $SET.active}checked="checked"{/if} />
											<label class="form-check-label" for="{$OPTIONAL_ID}">
												{$SET.label|escape}
											</label>
										</div>
									{/foreach}
								</div>
							</div>

							<div class="form-group">
								<label for="ImportManagerDuplicateStrategy" class="text-uppercase small text-muted font-weight-bold">
									{\App\Language::translate('LBL_DUPLICATE_STRATEGY', $MODULE_NAME)}
								</label>
								<select id="ImportManagerDuplicateStrategy" class="form-control">
									<option value="skip" {if $IMPORT_DEFINITION.duplicateStrategy eq 'skip'}selected="selected"{/if}>
										{\App\Language::translate('LBL_STRATEGY_SKIP', $MODULE_NAME)}
									</option>
									<option value="overwrite" {if $IMPORT_DEFINITION.duplicateStrategy eq 'overwrite'}selected="selected"{/if}>
										{\App\Language::translate('LBL_STRATEGY_OVERWRITE', $MODULE_NAME)}
									</option>
									<option value="merge" {if $IMPORT_DEFINITION.duplicateStrategy eq 'merge'}selected="selected"{/if}>
										{\App\Language::translate('LBL_STRATEGY_MERGE', $MODULE_NAME)}
									</option>
								</select>
							</div>

							<div class="text-right">
								<button type="button" class="btn btn-primary" id="ImportManagerSaveDuplicates">
									<span class="fa fa-save mr-1"></span>
									{\App\Language::translate('LBL_SAVE_DUPLICATES', $MODULE_NAME)}
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="ImportManagerDuplicateRuleModal" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">{\App\Language::translate('LBL_ADD_DUPLICATE_RULE', $MODULE_NAME)}</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<p class="text-muted mb-3">{\App\Language::translate('LBL_SELECT_FIELDS_FOR_DUPLICATE', $MODULE_NAME)}</p>
						<div id="ImportManagerDuplicateRuleFields" style="max-height: 400px; overflow-y: auto;">
							{if $IMPORT_FIELDS|@count gt 0}
								{foreach from=$IMPORT_FIELDS item=FIELD}
									{assign var=FIELD_ID value='duplicateField_'|cat:$FIELD.name|replace:' ':'_'}
									<div class="form-check mb-2 border rounded p-2 d-flex align-items-center" style="cursor:pointer;">
										<input type="checkbox"
											class="form-check-input js-duplicate-field-checkbox"
											id="{$FIELD_ID|escape}"
											value="{$FIELD.name|escape}"
											data-label="{$FIELD.label|escape}" />
										<label class="form-check-label ml-2 flex-fill" for="{$FIELD_ID|escape}">
											{$FIELD.label|escape}
										</label>
									</div>
								{/foreach}
							{else}
								<div class="text-muted">
									{\App\Language::translate('LBL_NO_FIELDS_AVAILABLE', $MODULE_NAME)}
								</div>
							{/if}
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">
							{\App\Language::translate('LBL_CANCEL', $MODULE_NAME)}
						</button>
						<button type="button" class="btn btn-primary" id="ImportManagerSaveDuplicateRule">
							{\App\Language::translate('LBL_ADD', $MODULE_NAME)}
						</button>
					</div>
				</div>
			</div>
		</div>

		<script type="application/json" id="ImportManagerContext">
			{$IMPORT_CONTEXT_JSON nofilter}
		</script>
	{/strip}
{/block}

