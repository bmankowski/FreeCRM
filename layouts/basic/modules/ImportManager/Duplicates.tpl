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

				<div id="ImportManagerRoot" class="import-manager-screen" data-view="duplicates">
					{include file='WizardSteps.tpl'|@vtemplate_path:$MODULE}

					{include file='BatchInfo.tpl'|@vtemplate_path:$MODULE}

					{assign var=DUPLICATE_VIEW value=$IMPORT_DUPLICATE_VIEW|default:[]}
					{assign var=REQUIRED_SETS value=$DUPLICATE_VIEW.required|default:[]}
					{assign var=SUGGESTED_SETS value=$DUPLICATE_VIEW.suggested|default:[]}
					
					{* Duplicates Configuration Card *}
					<div class="import-card import-card--primary">
						<div class="import-card__header import-card__header--compact">
							<div class="import-card__icon import-card__icon--small">
								<i class="fa fa-clone"></i>
							</div>
							<div class="import-card__title">
								<h5>{\App\Language::translate('LBL_DUPLICATE_SETS', $MODULE_NAME)}</h5>
								<span class="import-card__subtitle">{\App\Language::translate('LBL_DUPLICATE_SETS_DESC', $MODULE_NAME)|default:'Skonfiguruj reguły wykrywania duplikatów'}</span>
							</div>
						</div>
						<div class="import-card__body">
							{* Required Sets Section *}
							<div class="import-duplicates-panel">
								<div class="import-duplicates-panel__label">
									<i class="fa fa-layer-group"></i>
									{\App\Language::translate('LBL_ACTIVE_RULES', $MODULE_NAME)|default:'Aktywne reguły'}
								</div>
								<div id="ImportManagerRequiredSets" class="import-duplicates-panel__chips">
									{if $REQUIRED_SETS|@count gt 0}
										{foreach from=$REQUIRED_SETS item=SET}
											<span class="import-duplicates-chip js-duplicate-set"
												data-key="{$SET.key|escape}"
												data-fields="{$SET.fields|@json_encode|escape:'html'}">
												<i class="fa fa-check-circle import-duplicates-chip__icon"></i>
												{$SET.label|escape}
												<button type="button"
													class="import-duplicates-chip__remove js-remove-duplicate-set"
													aria-label="{\App\Language::translate('LBL_REMOVE', $MODULE_NAME)}"
													data-key="{$SET.key|escape}">
													<i class="fa fa-times"></i>
												</button>
											</span>
										{/foreach}
									{/if}
									<span class="import-duplicates-empty js-duplicates-empty" {if $REQUIRED_SETS|@count gt 0}style="display:none;"{/if}>
										{\App\Language::translate('LBL_NO_RULES_YET', $MODULE_NAME)|default:'Brak reguł - dodaj pierwszą'}
									</span>
								</div>
								<button type="button" class="import-duplicates-panel__add" id="ImportManagerAddDuplicateRule">
									<i class="fa fa-plus-circle"></i>
									<span>{\App\Language::translate('LBL_ADD_DUPLICATE_RULE', $MODULE_NAME)}</span>
								</button>
							</div>

							{* Suggested Sets Section *}
							<div class="import-duplicates-section mb-4" id="ImportManagerOptionalSetsSection" {if $SUGGESTED_SETS|@count eq 0}style="display:none;"{/if}>
								<div class="import-duplicates-section__header">
									<i class="fa fa-lightbulb import-duplicates-section__icon import-duplicates-section__icon--suggested"></i>
									<span class="import-duplicates-section__title">{\App\Language::translate('LBL_SUGGESTED_SETS', $MODULE_NAME)}</span>
								</div>
								<div id="ImportManagerOptionalSets" class="import-duplicates-suggestions">
									{foreach from=$SUGGESTED_SETS item=SET name=OptionalSetsLoop}
										{assign var=OPTIONAL_ID value='ImportManagerOptionalSet'|cat:$smarty.foreach.OptionalSetsLoop.index}
										<label class="import-duplicates-suggestion" for="{$OPTIONAL_ID}">
											<input type="checkbox"
												class="import-duplicates-suggestion__checkbox js-optional-set"
												id="{$OPTIONAL_ID}"
												data-key="{$SET.key|escape}"
												data-fields="{$SET.fields|@json_encode|escape:'html'}"
												data-label="{$SET.label|escape}"
												{if $SET.active}checked="checked"{/if} />
											<span class="import-duplicates-suggestion__content">
												<i class="fa fa-layer-group import-duplicates-suggestion__icon"></i>
												<span class="import-duplicates-suggestion__label">{$SET.label|escape}</span>
											</span>
											<span class="import-duplicates-suggestion__check">
												<i class="fa fa-check"></i>
											</span>
										</label>
									{/foreach}
								</div>
							</div>

							{* Strategy Selection *}
							<div class="import-duplicates-section">
								<div class="import-duplicates-section__header">
									<i class="fa fa-cogs import-duplicates-section__icon"></i>
									<span class="import-duplicates-section__title">{\App\Language::translate('LBL_DUPLICATE_STRATEGY', $MODULE_NAME)}</span>
								</div>
								<div class="import-strategy-selector">
									<select id="ImportManagerDuplicateStrategy" class="form-control import-select import-select--large">
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
									<div class="import-strategy-hints">
										<div class="import-strategy-hint" data-strategy="skip">
											<i class="fa fa-forward"></i>
											{\App\Language::translate('LBL_STRATEGY_SKIP_DESC', $MODULE_NAME)|default:'Pomija rekordy, które są duplikatami istniejących'}
										</div>
										<div class="import-strategy-hint" data-strategy="overwrite">
											<i class="fa fa-sync"></i>
											{\App\Language::translate('LBL_STRATEGY_OVERWRITE_DESC', $MODULE_NAME)|default:'Nadpisuje istniejące rekordy nowymi danymi'}
										</div>
										<div class="import-strategy-hint" data-strategy="merge">
											<i class="fa fa-compress-arrows-alt"></i>
											{\App\Language::translate('LBL_STRATEGY_MERGE_DESC', $MODULE_NAME)|default:'Łączy nowe dane z istniejącymi rekordami'}
										</div>
									</div>
								</div>
							</div>

							{* Footer Actions *}
							<div class="import-card__footer">
								<button type="button" class="btn btn-success import-btn import-btn--primary" id="ImportManagerSaveDuplicates">
									<i class="fa fa-arrow-right mr-2"></i>
									{\App\Language::translate('LBL_SAVE_AND_CONTINUE', $MODULE_NAME)}
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	{/strip}

	{* Modal template for adding duplicate rules - hidden, cloned by app.showModalWindow *}
	<div class="modal fade d-none" id="ImportManagerDuplicateRuleModal" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header import-modal__header">
						<div class="import-modal__title-wrapper">
							<div class="import-modal__icon">
								<i class="fa fa-plus-circle"></i>
							</div>
							<h5 class="modal-title">{\App\Language::translate('LBL_ADD_DUPLICATE_RULE', $MODULE_NAME)}</h5>
						</div>
						<button type="button" class="close import-modal__close" data-dismiss="modal" aria-label="Close">
							<i class="fa fa-times"></i>
						</button>
					</div>
					<div class="modal-body import-modal__body">
						<p class="import-modal__description">{\App\Language::translate('LBL_SELECT_FIELDS_FOR_DUPLICATE', $MODULE_NAME)}</p>
						<div id="ImportManagerDuplicateRuleFields" class="import-modal__fields">
							{if $IMPORT_FIELDS|@count gt 0}
								{foreach from=$IMPORT_FIELDS item=FIELD}
									{assign var=FIELD_ID value='duplicateField_'|cat:$FIELD.name|replace:' ':'_'}
									<label class="import-field-checkbox" for="{$FIELD_ID|escape}">
										<input type="checkbox"
											class="import-field-checkbox__input js-duplicate-field-checkbox"
											id="{$FIELD_ID|escape}"
											value="{$FIELD.name|escape}"
											data-label="{$FIELD.label|escape}" />
										<span class="import-field-checkbox__box">
											<i class="fa fa-check"></i>
										</span>
										<span class="import-field-checkbox__content">
											<span class="import-field-checkbox__label">{$FIELD.label|escape}</span>
											<span class="import-field-checkbox__code">{$FIELD.name|escape}</span>
										</span>
									</label>
								{/foreach}
							{else}
								<div class="import-modal__empty">
									<i class="fa fa-inbox"></i>
									{\App\Language::translate('LBL_NO_FIELDS_AVAILABLE', $MODULE_NAME)}
								</div>
							{/if}
						</div>
					</div>
					<div class="modal-footer import-modal__footer">
						<button type="button" class="btn btn-secondary import-btn import-btn--secondary" data-dismiss="modal">
							{\App\Language::translate('LBL_CANCEL', $MODULE_NAME)}
						</button>
						<button type="button" class="btn btn-primary import-btn import-btn--add" id="ImportManagerSaveDuplicateRule">
							<i class="fa fa-plus mr-2"></i>
							{\App\Language::translate('LBL_ADD', $MODULE_NAME)}
						</button>
					</div>
				</div>
			</div>
		</div>

	<script type="application/json" id="ImportManagerContext">
		{$IMPORT_CONTEXT_JSON nofilter}
	</script>
{/block}

