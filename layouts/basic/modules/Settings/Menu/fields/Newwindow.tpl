{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}

<div class="form-group">
	<label class="col-md-4 control-label">{"LBL_NEW_WINDOW"|t:$QUALIFIED_MODULE}:</label>
	<div class="col-md-7 checkboxForm">
		<input name="newwindow" type="checkbox" value="1" {if $RECORD && $RECORD->get('newwindow') eq 1} checked="checked" {/if}/>
	</div>
</div>
