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

					{include file='WizardStep1Upload.tpl'|@vtemplate_path:$MODULE}

					<div id="ImportManagerStepsContainer"></div>

					<template id="ImportManagerStep2Template">
						{include file='WizardStep2Mapping.tpl'|@vtemplate_path:$MODULE}
					</template>

					<template id="ImportManagerStep3Template">
						{include file='WizardStep3Confirmation.tpl'|@vtemplate_path:$MODULE}
					</template>

					<template id="ImportManagerStep4Template">
						{include file='WizardStep4Retry.tpl'|@vtemplate_path:$MODULE}
					</template>

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