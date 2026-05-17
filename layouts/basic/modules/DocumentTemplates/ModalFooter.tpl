{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/DocumentTemplates/ModalFooter.tpl -->
	<div class="modal-footer">
		<div class="btn-group">
			<button id="generate_pdf" href="" target="_blank" data-url="index.php?module=DocumentTemplates&action=Export{$EXPORT_VARS}&template=" type="button" class="btn btn-success">{"LBL_GENERATE"|t:$QUALIFIED_MODULE}</button>
			<button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<span class="caret"></span>
				<span class="sr-only">Toggle Dropdown</span>
			</button>
			<ul class="dropdown-menu">
				<li><a href="#" id="single_pdf" data-url="index.php?module=DocumentTemplates&action=Export{$EXPORT_VARS}&single_pdf=1&template=">{"LBL_GENERATE_SINGLE"|t:$QUALIFIED_MODULE}</a></li>
				<li><a href="#" id="email_pdf" data-url="index.php?module=DocumentTemplates&action=Export{$EXPORT_VARS}&email_pdf=1&template=">{"LBL_SEND_EMAIL"|t:$QUALIFIED_MODULE}</a></li>
			</ul>
		</div>&nbsp;
		<button class="btn btn-warning" type="reset" data-dismiss="modal"><strong>{"LBL_CANCEL"|t:$MODULE}</strong></button>
	</div>
<!--/layouts/basic/modules/DocumentTemplates/ModalFooter.tpl -->
{/strip}
