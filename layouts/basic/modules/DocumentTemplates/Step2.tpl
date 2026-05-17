{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/DocumentTemplates/Step2.tpl -->
	<div class="pdfTemplateContents">
		<form name="EditPdfTemplate" action="index.php" method="post" id="pdf_step2" class="form-horizontal" data-has-template-content="{if !empty($STEP2_HAS_TEMPLATE_CONTENT)}1{else}0{/if}">
			<input type="hidden" name="module" value="{$MODULE}">
			<input type="hidden" name="view" value="Edit">
			<input type="hidden" name="mode" value="Step3" />
			<input type="hidden" class="step" value="2" />
			<input type="hidden" name="record" value="{$RECORDID}" />

			<div class="padding1per stepBorder">
				<label>
					<strong>{'LBL_STEP_N'|t:$QUALIFIED_MODULE:2}: {"LBL_DOCUMENT_SETTINGS_DETAILS"|t:$QUALIFIED_MODULE}</strong>
				</label>
				<br>
				<div class="form-group">
					<label class="col-sm-3 control-label">
						{"LBL_STEP2_DOCUMENT_LAYOUT_SOURCE"|t:$QUALIFIED_MODULE}
					</label>
					<div class="col-sm-6 controls">
						<select class="select2 form-control" id="document_layout_source" name="document_layout_source">
							<option value="">{"LBL_STEP2_DOCUMENT_LAYOUT_NONE"|t:$QUALIFIED_MODULE}</option>
							{foreach from=$DOCUMENT_LAYOUT_OPTIONS item=LAYOUT}
								<option value="{$LAYOUT.dynamicid}">{$LAYOUT.label|escape}</option>
							{/foreach}
						</select>
						<p class="help-block">{"LBL_STEP2_DOCUMENT_LAYOUT_HELP"|t:$QUALIFIED_MODULE}</p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label">
						{"LBL_PAGE_FORMAT"|t:$QUALIFIED_MODULE}<span class="redColor">*</span>
					</label>
					<div class="col-sm-6 controls">
						<select class="select2 form-control rtl" id="page_format" name="page_format" data-validation-engine="validate[required]">
							<option value="" selected="">{"LBL_SELECT"|t:$QUALIFIED_MODULE}</option>
							{foreach item=FORMAT from=$PAGE_FORMATS}
								<option value="{$FORMAT}" {if $PDF_MODEL->get('page_format') eq $FORMAT} selected="selected" {/if}>
									{$FORMAT|t:$QUALIFIED_MODULE}
								</option>
							{/foreach}
						</select>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label">
						{"LBL_MAIN_MARGIN"|t:$QUALIFIED_MODULE}
					</label>
					<div class="col-sm-6 row">
						{if $PDF_MODEL->get('margin_chkbox') === 1}
							{assign 'MARGIN_CHECKED' true}
						{else}
							{assign 'MARGIN_CHECKED' false}
						{/if}
						<div class="col-sm-1">
							<input type="checkbox" id="margin_chkbox" name="margin_chkbox" value="1" {if $MARGIN_CHECKED eq 'true'}checked="checked"{/if} />
						</div>
						<div class="col-sm-2">
							<input type="text" class="form-control margin_inputs {if $MARGIN_CHECKED eq 'true'}hide{/if}" name="margin_top" id="margin_top" value="{$PDF_MODEL->get('margin_top')}" placeholder="{"LBL_TOP"|t:$QUALIFIED_MODULE}" title="{"LBL_TOP_IN_MILIMETERS"|t:$QUALIFIED_MODULE}" />
						</div>
						<div class="col-sm-2">
							<input type="text" class="form-control margin_inputs {if $MARGIN_CHECKED eq 'true'}hide{/if}" name="margin_right" id="margin_right" value="{$PDF_MODEL->get('margin_right')}" placeholder="{"LBL_RIGHT"|t:$QUALIFIED_MODULE}" title="{"LBL_RIGHT_IN_MILIMETERS"|t:$QUALIFIED_MODULE}" />
						</div>
						<div class="col-sm-2">
							<input type="text" class="form-control margin_inputs {if $MARGIN_CHECKED eq 'true'}hide{/if}" name="margin_bottom" id="margin_bottom" value="{$PDF_MODEL->get('margin_bottom')}" placeholder="{"LBL_BOTTOM"|t:$QUALIFIED_MODULE}" title="{"LBL_BOTTOM_IN_MILIMETERS"|t:$QUALIFIED_MODULE}" />
						</div>
						<div class="col-sm-2">
							<input type="text" class="form-control margin_inputs {if $MARGIN_CHECKED eq 'true'}hide{/if}" name="margin_left" id="margin_left" value="{$PDF_MODEL->get('margin_left')}" placeholder="{"LBL_LEFT"|t:$QUALIFIED_MODULE}" title="{"LBL_LEFT_IN_MILIMETERS"|t:$QUALIFIED_MODULE}" />
						</div>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label">&nbsp;</label>
					<div class="col-sm-9 row">
						<div class="col-sm-offset-1 col-sm-2">
							<input type="text" class="form-control margin_inputs {if $MARGIN_CHECKED eq 'true'}hide{/if}" name="header_height" id="header_height" value="{$PDF_MODEL->get('header_height')}" placeholder="{"LBL_HEADER_HEIGHT"|t:$QUALIFIED_MODULE}" title="{"LBL_HEADER_IN_MILIMETERS"|t:$QUALIFIED_MODULE}" />
						</div>
						<div class="col-sm-2">
							<input type="text" class="form-control margin_inputs {if $MARGIN_CHECKED eq 'true'}hide{/if}" name="footer_height" id="footer_height" value="{$PDF_MODEL->get('footer_height')}" placeholder="{"LBL_FOOTER_HEIGHT"|t:$QUALIFIED_MODULE}" title="{"LBL_FOOTER_IN_MILIMETERS"|t:$QUALIFIED_MODULE}" />
						</div>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label">
						{"LBL_PAGE_ORIENTATION"|t:$QUALIFIED_MODULE}
					</label>
					<div class="col-sm-6 controls">
						<select class="select2 form-control" id="page_orientation" name="page_orientation">
							<option value="PLL_PORTRAIT" {if $PDF_MODEL->get('page_orientation') eq 'PLL_PORTRAIT'} selected="selected" {/if}>
								{"PLL_PORTRAIT"|t:$QUALIFIED_MODULE}
							</option>
							<option value="PLL_LANDSCAPE" {if $PDF_MODEL->get('page_orientation') eq 'PLL_LANDSCAPE'} selected="selected" {/if}>
								{"PLL_LANDSCAPE"|t:$QUALIFIED_MODULE}
							</option>
						</select>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label">
						{"LBL_LANGUAGE_CHOICE"|t:$QUALIFIED_MODULE}
					</label>
					<div class="col-sm-6 controls">
						<select class="select2 form-control" id="language" name="language">
							<option value="">{'LBL_DEFAULT'|t}</option>
							{foreach from=$LANGUAGES key=CODE item=NAME}
								<option value="{$CODE}" {if $PDF_MODEL->get('language') eq $CODE} selected="selected" {/if}>
									{$NAME|t:$QUALIFIED_MODULE}
								</option>
							{/foreach}
						</select>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label">
						{"LBL_FILENAME"|t:$QUALIFIED_MODULE}
					</label>
					<div class="col-sm-6 controls">
						<input type="text" name="filename" class="form-control" value="{$PDF_MODEL->get('filename')}" id="filename" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label">
						{"LBL_VISIBILITY"|t:$QUALIFIED_MODULE}<span class="redColor">*</span>
					</label>
					<div class="col-sm-6 controls">
						{assign 'VISIBILITY' explode(',',$PDF_MODEL->get('visibility'))}
						<select class="select2 form-control rtl" data-tags="false" id="visibility" name="visibility" multiple="multiple" data-validation-engine="validate[required]">
							<option value="PLL_LISTVIEW" {if in_array('PLL_LISTVIEW', $VISIBILITY)}selected="selected"{/if}>{"PLL_LISTVIEW"|t:$QUALIFIED_MODULE}</option> 
							<option value="PLL_DETAILVIEW" {if in_array('PLL_DETAILVIEW', $VISIBILITY)}selected="selected"{/if}>{"PLL_DETAILVIEW"|t:$QUALIFIED_MODULE}</option> 
						</select>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label">
						{"LBL_DEFAULT_TPL"|t:$QUALIFIED_MODULE}
					</label>
					<div class="col-sm-6">
						{if $PDF_MODEL->get('default') === 0}
							{assign 'DEFAULT' false}
						{else}
							{assign 'DEFAULT' true}
						{/if}
						<input type="checkbox" id="default" name="default" value="1" {if $DEFAULT eq 'true'}checked="checked"{/if} />
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label">
						{"LBL_GENERATE_ONE_PDF"|t:$QUALIFIED_MODULE}
						<span class="popoverTooltip delay0"  data-placement="top"
							  data-content="{"LBL_GENERATE_ONE_PDF_INFO"|t:$QUALIFIED_MODULE}">
							<span class="glyphicon glyphicon-info-sign"></span>
						</span>
					</label>
					<div class="col-sm-6">
						{if $PDF_MODEL->get('one_pdf') == 0}
							{assign 'ONE_PDF' false}
						{else}
							{assign 'ONE_PDF' true}
						{/if}
						<input type="checkbox" id="one_pdf" name="one_pdf" value="1" {if $ONE_PDF eq 'true'}checked="checked"{/if} />
					</div>
				</div>
			</div>
			<br>
			<div class="pull-right">
				<button class="btn btn-danger backStep" type="button"><strong>{"LBL_BACK"|t:$QUALIFIED_MODULE}</strong></button>&nbsp;&nbsp;
				<button class="btn btn-success" type="submit"><strong>{"LBL_NEXT"|t:$QUALIFIED_MODULE}</strong></button>&nbsp;&nbsp;
				<button class="btn btn-warning cancelLink" type="reset">{"LBL_CANCEL"|t:$QUALIFIED_MODULE}</button>
			</div>
		</form>
	</div>
<!--/layouts/basic/modules/DocumentTemplates/Step2.tpl -->
{/strip}
