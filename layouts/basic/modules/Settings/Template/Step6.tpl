{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/PDF/Step6.tpl -->
	<div class="pdfTemplateContents">
		<form name="EditPdfTemplate" action="index.php" method="post" id="pdf_step6" class="form-horizontal" enctype="multipart/form-data">
			<input type="hidden" name="module" value="Template">
			<input type="hidden" name="action" value="Save">
			<input type="hidden" name="parent" value="Settings">
			<input type="hidden" name="step" class="step" value="6" />
			<input type="hidden" name="record" value="{$RECORDID}" />
			<input type="hidden" name="watermark_image" value="{$PDF_MODEL->get('watermark_image')}" />

			<div class="padding1per stepBorder">
				<label>
					<strong>{'LBL_STEP_N'|t:$QUALIFIED_MODULE:6}: {"LBL_WATERMARK_DETAILS"|t:$QUALIFIED_MODULE}</strong>
				</label>
				<br>
				<div class="form-group">
					<label class="col-sm-3 control-label">
						{"LBL_WATERMARK_TYPE"|t:$QUALIFIED_MODULE}
					</label>
					<div class="col-sm-6 controls">
						<select class="chzn-select form-control" id="watermark_type" name="watermark_type" required="true">
							{foreach from=$WATERMARK_TYPES key=VALUE item=LABEL}
								<option value="{$VALUE}" {if $PDF_MODEL->get('watermark_type') eq $VALUE} selected {/if}>
									{$LABEL|t:$QUALIFIED_MODULE}
								</option>
							{/foreach}
						</select>
					</div>
				</div>
				<div class="form-group watertext {if $PDF_MODEL->get('watermark_type') neq $WATERMARK_TEXT}hide{/if}">
					<label class="col-sm-3 control-label">
						{"LBL_WATERMARK_TEXT"|t:$QUALIFIED_MODULE}
					</label>
					<div class="col-sm-6 controls">
						<input type="text" name="watermark_text" class="form-control" value="{$PDF_MODEL->get('watermark_text')}" id="watermark_text" />
					</div>
				</div>
				<div class="form-group watertext {if $PDF_MODEL->get('watermark_type') neq $WATERMARK_TEXT}hide{/if}">
					<label class="col-sm-3 control-label">
						{"LBL_WATERMARK_SIZE"|t:$QUALIFIED_MODULE}
					</label>
					<div class="col-sm-6 controls">
						<input type="number" name="watermark_size" class="form-control" value="{$WATERMARK_SIZE}" id="watermark_size" min="0" max="99" />
					</div>
				</div>
				<div class="form-group watertext {if $PDF_MODEL->get('watermark_type') neq $WATERMARK_TEXT}hide{/if}">
					<label class="col-sm-3 control-label">
						{"LBL_WATERMARK_ANGLE"|t:$QUALIFIED_MODULE}
					</label>
					<div class="col-sm-6 controls">
						<input type="number" name="watermark_angle" class="form-control" value="{$WATERMARK_ANGLE}" id="watermark_angle" min="0" max="360" />
					</div>
				</div>
				<div class="form-group waterimage {if $PDF_MODEL->get('watermark_type') eq $WATERMARK_TEXT}hide{/if}">
					<label class="col-sm-3 control-label">
						{"LBL_WATERMARK_IMAGE"|t:$QUALIFIED_MODULE}
					</label>
					<div class="col-sm-6 controls">
						<div id="watermark">
							{if $PDF_MODEL->get('watermark_image')}
								<img src="{$PDF_MODEL->get('watermark_image')}" class="col-md-9" />
							{/if}
						</div>
						<input type="file" name="watermark_image_file" accept="images/*" class="form-control" data-validation-engine='validate[required]' id="watermark_image" />
					</div>
				</div>
				<div class="form-group waterimage {if $PDF_MODEL->get('watermark_type') eq $WATERMARK_TEXT}hide{/if}">
					<label class="col-sm-3 control-label"></label>
					<div class="col-sm-3 controls">
						<button id="deleteWM" class="btn btn-danger {if $PDF_MODEL->get('watermark_image') eq ''}hide{/if}">{"LBL_DELETE_WM"|t:$QUALIFIED_MODULE}</button>
					</div>
					<div class="col-sm-3 controls">
						<button id="uploadWM" class="btn btn-success pull-right">{"LBL_UPLOAD_WM"|t:$QUALIFIED_MODULE}</button>
					</div>
				</div>
			</div>
			<br>
			<div class="pull-right">
				<button class="btn btn-danger backStep" type="button"><strong>{"LBL_BACK"|t:$QUALIFIED_MODULE}</strong></button>&nbsp;&nbsp;
				<button class="btn btn-success" type="submit"><strong>{"LBL_FINISH"|t:$QUALIFIED_MODULE}</strong></button>
			</div>
		</form>
	</div>
<!--/layouts/basic/modules/Settings/PDF/Step6.tpl -->
{/strip}
