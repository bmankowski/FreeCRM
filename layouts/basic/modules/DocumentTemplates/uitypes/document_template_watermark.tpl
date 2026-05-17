{strip}
<input type="hidden" name="watermark_image" value="{$RECORD->get('watermark_image')}" />
<div class="form-group waterimage {if $RECORD->get('watermark_type') eq $WATERMARK_TEXT}hide{/if}">
	<div class="controls">
		<div id="watermark">
			{if $RECORD->get('watermark_image')}
				<img src="{$RECORD->get('watermark_image')}" class="col-md-9" alt="" />
			{/if}
		</div>
		<input type="file" name="watermark_image_file" accept="images/*" class="form-control" id="watermark_image_file" />
	</div>
</div>
<div class="form-group waterimage {if $RECORD->get('watermark_type') eq $WATERMARK_TEXT}hide{/if}">
	<div class="controls">
		<button type="button" id="deleteWM" class="btn btn-danger {if $RECORD->get('watermark_image') eq ''}hide{/if}">{"LBL_DELETE_WM"|t:$MODULE}</button>
		<button type="button" id="uploadWM" class="btn btn-success pull-right">{"LBL_UPLOAD_WM"|t:$MODULE}</button>
	</div>
</div>
{/strip}
