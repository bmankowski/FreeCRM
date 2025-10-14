{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}

<div class="form-group">
	<label class="col-md-4 control-label">{"LBL_HOTKEY"|t:$QUALIFIED_MODULE}:</label>
	<div class="col-md-7">
		<div class="input-group">
			<input name="hotkey" class="form-control" type="text" value="{if $RECORD}{$RECORD->get('hotkey')}{/if}"/>
			<a class="input-group-addon testBtn">{"LBL_TEST_IT"|t:$QUALIFIED_MODULE}</a>
			<a class="input-group-addon popoverTooltip" target="_blank" href="https://github.com/ccampbell/mousetrap" data-toggle="popover" 
				data-content="{"LBL_MORE_INFO"|t:$QUALIFIED_MODULE}">
				<i class="glyphicon glyphicon-info-sign"></i>
			</a>
		</div>
	</div>
</div>
