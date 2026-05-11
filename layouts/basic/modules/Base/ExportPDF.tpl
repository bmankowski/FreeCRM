{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/ExportPDF.tpl -->
	<div class="modal fade" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
	<form id="pdfExportModal" action="index.php" target="_blank" method="{if isset($EXPORT_VARS.fromview) && $EXPORT_VARS.fromview eq 'Detail'}GET{else}POST{/if}">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3 id="massEditHeader" class="modal-title">{"LBL_GENERATE_PDF_FILE"|t:$MODULE_NAME}</h3>
	</div>
	<div class="modal-body">
		<input type="hidden" name="all_records" id="all_records" value="{\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($ALL_RECORDS))}" />
		<input type="hidden" name="module" value="{$MODULE_NAME}" />
		<input type="hidden" name="action" value="PDF" />
		<input type="hidden" name="mode" value="generate" />
		<input type="hidden" name="selectedRecords" value="[]" />
		<input type="hidden" name="validRecords" value="[]" />
		<input type="hidden" name="template" value="[]" />
		<input type="hidden" name="single_pdf" value="0" />
		<input type="hidden" name="email_pdf" value="0" />
		{foreach from=$EXPORT_VARS key=INDEX item=VALUE}
			<input type="hidden" name="{$INDEX}" value="{$VALUE}" />
		{/foreach}
		<div class="panel panel-default">
			<div class="panel-heading"><strong>{"LBL_AVAILABLE_TEMPLATES"|t:$MODULE_NAME}</strong></div>
			<div class="panel-body">
				{foreach from=$TEMPLATES item=TEMPLATE}
					<div class="form-group row form-horizontal">
						<label class="col-sm-6 control-label" for="pdfTpl{$TEMPLATE->getId()}">
							{$TEMPLATE->get('primary_name')}<br />
							<span class="secondaryName">{$TEMPLATE->get('secondary_name')}</span>
						</label>
						<div class="col-sm-6 control-group">
							<input type="checkbox" id="pdfTpl{$TEMPLATE->getId()}" name="pdf_template[]" class="checkbox" value="{$TEMPLATE->getId()}" {if $TEMPLATE->get('default') eq 1}checked="checked"{/if} />
						</div>
					</div>
				{/foreach}
			</div>
		</div>
	</div>
	<div class="modal-footer">
		<div class="btn-group">
			<button id="generate_pdf" type="submit" class="btn btn-success">{"LBL_GENERATE"|t:$MODULE_NAME}</button>
			<button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<span class="caret"></span>
				<span class="sr-only">Toggle Dropdown</span>
			</button>
			<ul class="dropdown-menu">
				<li>
					<a href="#" id="single_pdf">
						{"LBL_GENERATE_SINGLE"|t:$MODULE_NAME}
					</a>
				</li>
				<li>
					<a href="#" id="email_pdf">
						{"LBL_SEND_EMAIL"|t:$MODULE_NAME}
					</a>
				</li>
			</ul>
		</div>&nbsp;
		<button class="btn btn-warning" type="reset" data-dismiss="modal"><strong>{"LBL_CANCEL"|t:$MODULE_NAME}</strong></button>
	</div>
	</form>
			</div>
		</div>
	</div>
	{if isset($SCRIPTS) && !empty($SCRIPTS)}
		{foreach key=index item=jsModel from=$SCRIPTS}
			<script type="{$jsModel->getType()}" src="{vresource_url($jsModel->getSrc())}"></script>
		{/foreach}
	{/if}
<!--/layouts/basic/modules/Base/ExportPDF.tpl -->
{/strip}
