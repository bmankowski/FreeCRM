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

			<div id="ImportManagerRoot" class="import-manager-screen" data-view="upload">
				{include file='WizardSteps.tpl'|@vtemplate_path:$MODULE}

				<div class="import-manager-wizard">
						<div id="ImportManagerConfig"
							data-max-upload="{$IMPORT_CONFIG.maxUploadSizeMb|default:10}"
							data-preview-rows="{$IMPORT_CONFIG.previewRows|default:30}"
							data-chunk-size="{$IMPORT_CONFIG.chunkSize|default:200}"></div>

						<form id="ImportManagerStep1" class="import-upload-form" enctype="multipart/form-data" method="post">
							<input type="hidden" name="module" value="ImportManager" />
							<input type="hidden" name="view" value="Upload" />
							<input type="hidden" name="batch_id" id="ImportManagerBatchId" />

							<div class="row g-4 align-items-stretch">
								{* Left column - Main settings *}
								<div class="col-md-8 d-flex">
									<div class="import-card import-card--primary w-100">
										<div class="import-card__header">
											<div class="import-card__icon">
												<i class="fa fa-cog"></i>
											</div>
											<div class="import-card__title">
												<h5>{\App\Language::translate('LBL_IMPORT_SETTINGS', $MODULE_NAME)|default:'Ustawienia importu'}</h5>
												<span class="import-card__subtitle">{\App\Language::translate('LBL_CONFIGURE_IMPORT', $MODULE_NAME)|default:'Skonfiguruj parametry importu danych'}</span>
											</div>
										</div>
										<div class="import-card__body">
											<div class="row g-3">
												<div class="col-md-6">
													<div class="import-field">
														<label class="import-field__label" for="ImportManagerTargetModule">
															<i class="fa fa-cube import-field__icon"></i>
															{\App\Language::translate('LBL_TARGET_MODULE', $MODULE_NAME)}
															<span class="import-field__required">*</span>
														</label>
														<select name="target_module" id="ImportManagerTargetModule" class="form-control import-select" required>
															<option value="">{\App\Language::translate('LBL_SELECT_OPTION', $MODULE_NAME)}</option>
															{foreach from=$IMPORT_AVAILABLE_MODULES item=MODULE_ITEM}
																<option value="{$MODULE_ITEM.name}" {if $IMPORT_SELECTED_MODULE && $IMPORT_SELECTED_MODULE eq $MODULE_ITEM.name}selected{/if}>{$MODULE_ITEM.label}</option>
															{/foreach}
														</select>
													</div>
												</div>
												<div class="col-md-3">
													<div class="import-field">
														<label class="import-field__label" for="ImportManagerFormat">
															<i class="fa fa-file-alt import-field__icon"></i>
															{\App\Language::translate('LBL_FILE_FORMAT', $MODULE_NAME)}
														</label>
														<select name="format" id="ImportManagerFormat" class="form-control import-select">
															<option value="csv">CSV</option>
															<option value="xml">XML</option>
															<option value="zip">ZIP</option>
														</select>
													</div>
												</div>
												<div class="col-md-3">
													<div class="import-field">
														<label class="import-field__label" for="ImportManagerEncoding">
															<i class="fa fa-font import-field__icon"></i>
															{\App\Language::translate('LBL_ENCODING', $MODULE_NAME)}
														</label>
														<select name="encoding" id="ImportManagerEncoding" class="form-control import-select">
															<option value="UTF-8" selected>UTF-8</option>
															<option value="Windows-1250">Windows-1250</option>
															<option value="ISO-8859-2">ISO-8859-2</option>
														</select>
													</div>
												</div>
											</div>

											<hr class="import-divider">

											<div class="row g-3">
												<div class="col-md-4">
													<div class="import-field">
														<label class="import-field__label" for="ImportManagerPreviewRows">
															<i class="fa fa-eye import-field__icon"></i>
															{\App\Language::translate('LBL_PREVIEW_LIMIT', $MODULE_NAME)}
														</label>
														<input type="number" name="preview_rows" id="ImportManagerPreviewRows" 
															class="form-control import-input" min="1" max="1000" 
															value="{$IMPORT_CONFIG.previewRows|default:30}" />
														<small class="import-field__hint">{\App\Language::translate('LBL_PREVIEW_LIMIT_DESC', $MODULE_NAME)}</small>
													</div>
												</div>
												<div class="col-md-4 csv-separator-options">
													<div class="import-field">
														<label class="import-field__label" for="ImportManagerDelimiter">
															<i class="fa fa-grip-lines-vertical import-field__icon"></i>
															{\App\Language::translate('LBL_DELIMITER', $MODULE_NAME)}
														</label>
														<select name="delimiter" id="ImportManagerDelimiter" class="form-control import-select">
															<option value="">{\App\Language::translate('LBL_AUTO', $MODULE_NAME)}</option>
															<option value=",">,</option>
															<option value=";">;</option>
															<option value="\t">TAB</option>
															<option value="|">|</option>
														</select>
													</div>
												</div>
												<div class="col-md-4 csv-separator-options">
													<div class="import-field">
														<label class="import-field__label" for="ImportManagerEnclosure">
															<i class="fa fa-quote-right import-field__icon"></i>
															{\App\Language::translate('LBL_TEXT_QUALIFIER', $MODULE_NAME)}
														</label>
														<select name="enclosure" id="ImportManagerEnclosure" class="form-control import-select">
															<option value="&quot;">" (")</option>
															<option value="&#39;">' (')</option>
														</select>
													</div>
												</div>
											</div>

											<div class="row g-3" id="ImportManagerXmlRow">
												<div class="col-md-6 js-import-xml-only" style="display: none;">
													<div class="import-field">
														<label class="import-field__label" for="ImportManagerXPath">
															<i class="fa fa-code import-field__icon"></i>
															{\App\Language::translate('LBL_XML_RECORD_PATH', $MODULE_NAME)}
														</label>
														<input type="text" name="xpath" id="ImportManagerXPath" 
															class="form-control import-input" placeholder="/Records/Record" />
														<small class="import-field__hint">{\App\Language::translate('LBL_XML_RECORD_PATH_DESC', $MODULE_NAME)}</small>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>

								{* Right column - File upload *}
								<div class="col-md-4 d-flex">
									<div class="import-card import-card--upload w-100">
										<div class="import-card__body">
											<div class="import-dropzone" id="ImportManagerDropzone">
												<input type="file" name="import_file" id="ImportManagerFile" class="import-dropzone__input" required />
												<div class="import-dropzone__content">
													<div class="import-dropzone__icon">
														<i class="fa fa-file-upload"></i>
													</div>
													<div class="import-dropzone__text">
														<span class="import-dropzone__main">{\App\Language::translate('LBL_DROP_FILE_HERE', $MODULE_NAME)|default:'Upuść plik tutaj'}</span>
														<span class="import-dropzone__sub">{\App\Language::translate('LBL_OR_CLICK_TO_BROWSE', $MODULE_NAME)|default:'lub kliknij aby przeglądać'}</span>
													</div>
													<div class="import-dropzone__info">
														<i class="fa fa-info-circle"></i>
														Max: {$IMPORT_CONFIG.maxUploadSizeMb|default:10} MB
													</div>
												</div>
												<div class="import-dropzone__preview" style="display: none;">
													<div class="import-dropzone__file-icon">
														<i class="fa fa-file-csv"></i>
													</div>
													<div class="import-dropzone__file-info">
														<span class="import-dropzone__file-name"></span>
														<span class="import-dropzone__file-size"></span>
													</div>
													<button type="button" class="import-dropzone__remove" title="{\App\Language::translate('LBL_REMOVE', $MODULE_NAME)|default:'Usuń'}">
														<i class="fa fa-times"></i>
													</button>
												</div>
											</div>
											
											<div class="import-formats-hint mt-3">
												<span class="import-formats-hint__title">{\App\Language::translate('LBL_SUPPORTED_FORMATS', $MODULE_NAME)|default:'Obsługiwane formaty'}:</span>
												<div class="import-formats-hint__list">
													<span class="import-format-badge"><i class="fa fa-file-csv"></i> CSV</span>
													<span class="import-format-badge"><i class="fa fa-file-code"></i> XML</span>
													<span class="import-format-badge"><i class="fa fa-file-archive"></i> ZIP</span>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							
							{* Submit button - hidden by default, shown when file and module are selected *}
							<div class="import-card__footer mt-4" style="display: none;">
								<button type="submit" id="ImportManagerSubmit" class="import-btn import-btn--primary">
									<i class="fa fa-arrow-right me-2"></i>
									{\App\Language::translate('LBL_NEXT_STEP', $MODULE_NAME)|default:'Dalej'}
								</button>
							</div>
						</form>

						{if !empty($IMPORT_RECENT_BATCHES)}
							<div class="import-card import-card--info mt-4">
								<div class="import-card__header import-card__header--compact">
									<div class="import-card__icon import-card__icon--small">
										<i class="fa fa-history"></i>
									</div>
									<div class="import-card__title">
										<h5>{\App\Language::translate('LBL_RECENT_IMPORTS', $MODULE_NAME)}</h5>
										<span class="import-card__subtitle">{\App\Language::translate('LBL_RECENT_IMPORTS_HELP', $MODULE_NAME)}</span>
									</div>
								</div>
								<div class="import-card__body p-0">
									<div class="import-recent-list">
										{foreach from=$IMPORT_RECENT_BATCHES item=BATCH}
											<div class="import-recent-item">
												<div class="import-recent-item__main">
													<span class="import-recent-item__id">#{$BATCH.id}</span>
													<span class="import-recent-item__module">{$BATCH.module}</span>
													<span class="import-recent-item__status import-recent-item__status--{$BATCH.status|lower}">{$BATCH.status}</span>
													{if $BATCH.progress}
														<span class="import-recent-item__progress">{$BATCH.progress}</span>
													{/if}
												</div>
												<div class="import-recent-item__actions">
													<span class="import-recent-item__date">
														<i class="fa fa-clock-o"></i>
														{$BATCH.created_at}
													</span>
													{if $BATCH.continue_url}
														<a class="import-btn import-btn--small" href="{$BATCH.continue_url|escape:'html'}">
															<i class="fa fa-arrow-right"></i>
															{\App\Language::translate('LBL_CONTINUE_STEP', $MODULE_NAME)}
														</a>
													{else}
														<span class="import-recent-item__disabled">
															{\App\Language::translate('LBL_STEP_DISABLED', $MODULE_NAME)}
														</span>
													{/if}
												</div>
											</div>
										{/foreach}
									</div>
								</div>
							</div>
						{/if}
					</div>
				</div>
			</div>
		</div>

		<script type="application/json" id="ImportManagerContext">
			{$IMPORT_CONTEXT_JSON nofilter}
		</script>
	{/strip}
{/block}

