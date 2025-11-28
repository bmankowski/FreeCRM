<form id="ImportManagerStep1" class="import-manager-form" enctype="multipart/form-data" method="post">
	<input type="hidden" name="module" value="ImportManager" />
	<input type="hidden" name="view" value="Wizard" />
	<input type="hidden" name="batch_id" id="ImportManagerBatchId" />

	<div class="row">
		<div class="col-md-6">
			<div class="form-group">
				<label for="ImportManagerTargetModule">{\App\Language::translate('LBL_TARGET_MODULE', $MODULE_NAME)}</label>
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
				<label for="ImportManagerFormat">{\App\Language::translate('LBL_FILE_FORMAT', $MODULE_NAME)}</label>
				<select name="format" id="ImportManagerFormat" class="form-control">
					<option value="csv">CSV</option>
					<option value="xml">XML</option>
					<option value="zip">ZIP</option>
				</select>
			</div>
		</div>
		<div class="col-md-3">
			<div class="form-group">
				<label for="ImportManagerEncoding">{\App\Language::translate('LBL_ENCODING', $MODULE_NAME)}</label>
				<select name="encoding" id="ImportManagerEncoding" class="form-control">
					<option value="UTF-8" selected>UTF-8</option>
					<option value="Windows-1250">Windows-1250</option>
					<option value="ISO-8859-2">ISO-8859-2</option>
				</select>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-md-3">
			<div class="form-group">
				<label for="ImportManagerPreviewRows">{\App\Language::translate('LBL_PREVIEW_LIMIT', $MODULE_NAME)}</label>
				<input type="number" name="preview_rows" id="ImportManagerPreviewRows" class="form-control" min="1" max="1000" value="{$IMPORT_CONFIG.previewRows|default:30}" />
				<small class="text-muted">{\App\Language::translate('LBL_PREVIEW_LIMIT_DESC', $MODULE_NAME)}</small>
			</div>
		</div>
		<div class="col-md-3 csv-separator-options">
			<div class="form-group">
				<label for="ImportManagerDelimiter">{\App\Language::translate('LBL_DELIMITER', $MODULE_NAME)}</label>
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
				<label for="ImportManagerEnclosure">{\App\Language::translate('LBL_TEXT_QUALIFIER', $MODULE_NAME)}</label>
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
				<label for="ImportManagerXPath">{\App\Language::translate('LBL_XML_RECORD_PATH', $MODULE_NAME)}</label>
				<input type="text" name="xpath" id="ImportManagerXPath" class="form-control" placeholder="/Records/Record" />
				<small class="text-muted">{\App\Language::translate('LBL_XML_RECORD_PATH_DESC', $MODULE_NAME)}</small>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<div class="form-group mt-2">
				<label for="ImportManagerFile">{\App\Language::translate('LBL_SELECT_FILE', $MODULE_NAME)}</label>
				<input type="file" name="import_file" id="ImportManagerFile" class="form-control" required />
				<small class="text-muted">
					{\App\Language::translate('LBL_MAX_UPLOAD_LIMIT', $MODULE_NAME)}:
					{$IMPORT_CONFIG.maxUploadSizeMb|default:10} MB
				</small>
			</div>
		</div>
	</div>
</form>

<div id="ImportManagerPreview" class="import-preview card mt-3 d-none">
	<div class="card-header">
		<div class="d-flex justify-content-between align-items-center flex-wrap">
			<strong>{\App\Language::translate('LBL_IMPORT_PREVIEW', $MODULE_NAME)}</strong>
			<div class="mt-2 mt-md-0" id="ImportManagerPreviewMeta">
				<span class="badge badge-light mr-1" id="ImportManagerPreviewFileName"></span>
				<span class="badge badge-info mr-1" id="ImportManagerPreviewEncoding"></span>
				<span class="badge badge-info" id="ImportManagerPreviewDelimiter"></span>
			</div>
		</div>
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

