{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/ExportPDF.tpl -->
	<div class="modal fade" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
	<form id="pdfExportModal" action="index.php" target="_blank" method="{if isset($EXPORT_VARS.fromview) && $EXPORT_VARS.fromview eq 'Detail'}GET{else}POST{/if}"{if !empty($AUTO_GENERATE_SINGLE_PDF_TEMPLATE)} data-auto-single-pdf-template="1"{/if}>
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
		<table class="table table-condensed table-hover">
				<thead>
					<tr>
						<th class="text-center" style="width:40px;"></th>
						<th>{"LBL_PRIMARY_NAME"|t:"DocumentTemplates"}</th>
						<th>{"LBL_SECONDARY_NAME"|t:"DocumentTemplates"}</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$TEMPLATES item=TEMPLATE}
						<tr>
							<td class="text-center">
								<input type="checkbox" id="pdfTpl{$TEMPLATE->getId()}" name="pdf_template[]" class="checkbox" value="{$TEMPLATE->getId()}" {if $TEMPLATE->get('default') eq 1}checked="checked"{/if} />
							</td>
							<td>
								<label for="pdfTpl{$TEMPLATE->getId()}" class="u-cursor-pointer mb-0">{$TEMPLATE->get('primary_name')}</label>
							</td>
							<td>{$TEMPLATE->get('secondary_name')}</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
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
