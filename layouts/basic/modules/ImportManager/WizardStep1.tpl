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

				<div class="import-manager-wizard c-container">
					<div id="ImportManagerConfig" data-max-upload="{$IMPORT_CONFIG.maxUploadSizeMb|default:10}"
						data-preview-rows="{$IMPORT_CONFIG.previewRows|default:30}"
						data-chunk-size="{$IMPORT_CONFIG.chunkSize|default:200}"></div>

					<form id="ImportManagerStep1" class="import-manager-form" enctype="multipart/form-data" method="post">
						<input type="hidden" name="module" value="ImportManager" />
						<input type="hidden" name="view" value="Wizard" />
						<input type="hidden" name="batch_id" id="ImportManagerBatchId" />

						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label
										for="ImportManagerTargetModule">{\App\Language::translate('LBL_TARGET_MODULE', $MODULE_NAME)}</label>
								<select name="target_module" id="ImportManagerTargetModule" class="form-control" required>
									<option value="">{\App\Language::translate('LBL_SELECT_OPTION', $MODULE_NAME)}</option>
									{foreach from=$IMPORT_AVAILABLE_MODULES item=MODULE_ITEM}
										<option value="{$MODULE_ITEM.name}" {if $IMPORT_SELECTED_MODULE && $IMPORT_SELECTED_MODULE eq $MODULE_ITEM.name}selected{/if}>{$MODULE_ITEM.label}</option>
									{/foreach}
								</select>
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label
										for="ImportManagerFormat">{\App\Language::translate('LBL_FILE_FORMAT', $MODULE_NAME)}</label>
									<select name="format" id="ImportManagerFormat" class="form-control">
										<option value="csv">CSV</option>
										<option value="xml">XML</option>
										<option value="zip">ZIP</option>
									</select>
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label
										for="ImportManagerEncoding">{\App\Language::translate('LBL_ENCODING', $MODULE_NAME)}</label>
									<select name="encoding" id="ImportManagerEncoding" class="form-control">
										<option value="UTF-8" selected>UTF-8</option>
										<option value="Windows-1250">Windows-1250</option>
										<option value="ISO-8859-2">ISO-8859-2</option>
									</select>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label
										for="ImportManagerFile">{\App\Language::translate('LBL_SELECT_FILE', $MODULE_NAME)}</label>
									<input type="file" name="import_file" id="ImportManagerFile" class="form-control"
										required />
									<small class="text-muted">
										{\App\Language::translate('LBL_MAX_UPLOAD_LIMIT', $MODULE_NAME)}:
										{$IMPORT_CONFIG.maxUploadSizeMb|default:10} MB
									</small>
								</div>
							</div>
							<div class="col-md-3 csv-separator-options">
								<div class="form-group">
									<label
										for="ImportManagerDelimiter">{\App\Language::translate('LBL_DELIMITER', $MODULE_NAME)}</label>
									<select name="delimiter" id="ImportManagerDelimiter" class="form-control">
										<option value="">{\App\Language::translate('LBL_AUTO', $MODULE_NAME)}</option>
										<option value=",">,</option>
										<option value=";">;</option>
										<option value="\t">TAB</option>
										<option value="|">|</option>
									</select>
								</div>
							</div>
							<div class="col-md-3 csv-separator-options">
								<div class="form-group">
									<label
										for="ImportManagerEnclosure">{\App\Language::translate('LBL_TEXT_QUALIFIER', $MODULE_NAME)}</label>
									<select name="enclosure" id="ImportManagerEnclosure" class="form-control">
										<option value="&quot;">" (")</option>
										<option value="&#39;">' (')</option>
									</select>
								</div>
							</div>
						</div>

					<div class="row" id="ImportManagerXmlRow">
						<div class="col-md-6 js-import-xml-only" style="display: none;">
							<div class="form-group">
								<label
									for="ImportManagerXPath">{\App\Language::translate('LBL_XML_RECORD_PATH', $MODULE_NAME)}</label>
								<input type="text" name="xpath" id="ImportManagerXPath" class="form-control"
									placeholder="/Records/Record" />
								<small
									class="text-muted">{\App\Language::translate('LBL_XML_RECORD_PATH_DESC', $MODULE_NAME)}</small>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group mt-4">
								<button type="submit" class="btn btn-primary">
									<span class="fa fa-eye"></span>
									{\App\Language::translate('LBL_GENERATE_PREVIEW', $MODULE_NAME)}
								</button>
								<div class="mt-2">
									<small class="text-muted">
										{\App\Language::translate('LBL_PREVIEW_LIMIT', $MODULE_NAME)}:
										{$IMPORT_CONFIG.previewRows|default:30}
									</small>
								</div>
							</div>
						</div>
					</div>
					</form>

					<div id="ImportManagerPreview" class="import-preview card mt-3 d-none">
						<div class="card-header">
							<strong>{\App\Language::translate('LBL_IMPORT_PREVIEW', $MODULE_NAME)}</strong>
							<span class="badge badge-secondary ml-2" id="ImportManagerPreviewMeta"></span>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-bordered table-sm mb-0">
									<thead></thead>
									<tbody></tbody>
								</table>
							</div>
						</div>
					</div>

					<div id="ImportManagerStep2" class="card mt-4 d-none import-manager-step">
						<div class="card-header">
							<strong>{\App\Language::translate('LBL_STEP2_TITLE', $MODULE_NAME)}</strong>
						</div>
						<div class="card-body">
							<p class="text-muted mb-3">{\App\Language::translate('LBL_MAPPING_INSTRUCTIONS', $MODULE_NAME)}</p>
							<div class="table-responsive">
								<table class="table table-bordered table-sm mb-3" id="ImportManagerMappingTable">
									<thead>
										<tr>
											<th>{\App\Language::translate('LBL_SOURCE_COLUMN', $MODULE_NAME)}</th>
											<th>{\App\Language::translate('LBL_TARGET_FIELD', $MODULE_NAME)}</th>
											<th class="d-none d-md-table-cell">{\App\Language::translate('LBL_FIELD_DETAILS', $MODULE_NAME)}</th>
										</tr>
									</thead>
									<tbody></tbody>
								</table>
							</div>

							<div class="row">
								<div class="col-md-6">
									<div class="card h-100">
										<div class="card-header py-2">
											<strong>{\App\Language::translate('LBL_DEFAULT_VALUES', $MODULE_NAME)}</strong>
										</div>
										<div class="card-body">
											<div id="ImportManagerDefaultValues" class="default-values"></div>
											<button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="ImportManagerAddDefaultValue">
												<span class="fa fa-plus"></span>
												{\App\Language::translate('LBL_ADD_DEFAULT_VALUE', $MODULE_NAME)}
											</button>
										</div>
									</div>
								</div>
								<div class="col-md-6 mt-3 mt-md-0">
									<div class="card h-100">
										<div class="card-header py-2">
											<strong>{\App\Language::translate('LBL_DUPLICATE_SETS', $MODULE_NAME)}</strong>
										</div>
										<div class="card-body">
											<div class="mb-2">
												<p class="mb-1 font-weight-bold">{\App\Language::translate('LBL_REQUIRED_SETS', $MODULE_NAME)}</p>
												<div id="ImportManagerRequiredSets" class="badge-container"></div>
											</div>
											<div>
												<p class="mb-1 font-weight-bold">{\App\Language::translate('LBL_OPTIONAL_SETS', $MODULE_NAME)}</p>
												<div id="ImportManagerOptionalSets" class="optional-set-list"></div>
											</div>
											<div class="form-group mt-3 mb-0">
												<label for="ImportManagerDuplicateStrategy">{\App\Language::translate('LBL_DUPLICATE_STRATEGY', $MODULE_NAME)}</label>
												<select id="ImportManagerDuplicateStrategy" class="form-control">
													<option value="skip">{\App\Language::translate('LBL_STRATEGY_SKIP', $MODULE_NAME)}</option>
													<option value="overwrite">{\App\Language::translate('LBL_STRATEGY_OVERWRITE', $MODULE_NAME)}</option>
													<option value="merge">{\App\Language::translate('LBL_STRATEGY_MERGE', $MODULE_NAME)}</option>
												</select>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="text-right mt-3">
								<button type="button" class="btn btn-success" id="ImportManagerSaveMapping">
									<span class="fa fa-save"></span>
									{\App\Language::translate('LBL_SAVE_AND_CONTINUE', $MODULE_NAME)}
								</button>
							</div>
						</div>
					</div>

					<div id="ImportManagerStep3" class="card mt-4 d-none import-manager-step">
						<div class="card-header">
							<strong>{\App\Language::translate('LBL_STEP3_TITLE', $MODULE_NAME)}</strong>
						</div>
						<div class="card-body">
							<p class="text-muted">{\App\Language::translate('LBL_CONFIRMATION_DESCRIPTION', $MODULE_NAME)}</p>
							<div id="ImportManagerConfirmationStatus" class="alert alert-success d-none"></div>
							<dl class="row mb-0">
								<dt class="col-sm-4">{\App\Language::translate('LBL_SELECTED_MODULE', $MODULE_NAME)}</dt>
								<dd class="col-sm-8" id="ImportManagerSummaryModule">—</dd>

								<dt class="col-sm-4">{\App\Language::translate('LBL_SELECTED_FILE', $MODULE_NAME)}</dt>
								<dd class="col-sm-8" id="ImportManagerSummaryFile">—</dd>

								<dt class="col-sm-4">{\App\Language::translate('LBL_DUPLICATE_MODE', $MODULE_NAME)}</dt>
								<dd class="col-sm-8" id="ImportManagerSummaryStrategy">—</dd>

								<dt class="col-sm-4">{\App\Language::translate('LBL_MAPPED_FIELDS_COUNT', $MODULE_NAME)}</dt>
								<dd class="col-sm-8" id="ImportManagerSummaryFields">—</dd>
							</dl>

							<div class="alert alert-info mt-3 mb-0">
								<strong>{\App\Language::translate('LBL_PENDING_IMPLEMENTATION', $MODULE_NAME)}</strong>
								<div>{\App\Language::translate('LBL_START_IMPORT_SOON', $MODULE_NAME)}</div>
								<button type="button" class="btn btn-primary mt-2" id="ImportManagerStartImport" disabled>
									<span class="fa fa-play"></span>
									{\App\Language::translate('LBL_START_IMPORT_BUTTON', $MODULE_NAME)}
								</button>
							</div>
						</div>
					</div>

					{if !empty($IMPORT_RECENT_BATCHES)}
						<div class="card recent-imports mt-4">
							<div class="card-header">
								<strong>{\App\Language::translate('LBL_RECENT_IMPORTS', $MODULE_NAME)}</strong>
							</div>
							<div class="card-body">
								<ul class="list-group">
									{foreach from=$IMPORT_RECENT_BATCHES item=BATCH}
										<li class="list-group-item d-flex justify-content-between align-items-center">
											<div>
												<span class="badge badge-light mr-2">#{$BATCH.id}</span>
												{$BATCH.module} • {$BATCH.status}
											</div>
											<small class="text-muted">{$BATCH.created_at}</small>
										</li>
									{/foreach}
								</ul>
							</div>
						</div>
					{/if}
				</div>
			</div>
		</div>
	{/strip}
{/block}