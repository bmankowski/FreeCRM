{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}

<div class="form-group">
	<label class="col-md-4 control-label">{"LBL_NAME"|t:$QUALIFIED_MODULE}:</label>
	<div class="col-md-7">
		<input name="label" class="form-control" type="text" value="{if $RECORD}{$RECORD->get('label')}{/if}" />
	</div>
</div>
<div class="form-group">
	<label class="col-md-4 control-label">{"LBL_SELECT_MODULE"|t:$QUALIFIED_MODULE}:</label>
	<div class="col-md-7">
		<select name="module" class="select2 form-control type">
			{foreach from=$MODULE_MODEL->getModulesList() item=ITEM}
				<option value="{$ITEM['tabid']}" {if $RECORD && $ITEM['tabid'] == $RECORD->get('module')} selected="" {/if}>{$ITEM['name']|t:$ITEM['name']}</option>
			{/foreach}
		</select>
	</div>
</div>
{include file='fields/Hotkey.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
<div class="form-group">
	<label class="col-md-4 control-label">{"LBL_ICON_NAME"|t:$QUALIFIED_MODULE}:</label>
	<div class="col-md-7">
		<div class="input-group">
			<input name="icon" class="form-control" type="text" value="{if $RECORD}{$RECORD->get('icon')}{/if}"/>
			<span class="input-group-btn">
				<button id="selectIconButton" class="btn btn-default" title="{"LBL_SELECT_ICON"|t:$QUALIFIED_MODULE}" type="button"><span class="glyphicon glyphicon-info-sign"></span></button>
			</span>
		</div>
	</div>
</div>
