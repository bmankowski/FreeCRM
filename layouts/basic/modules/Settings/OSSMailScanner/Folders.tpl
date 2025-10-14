{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/OSSMailScanner/Folders.tpl -->
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3 class="modal-title">{"LBL_EDIT_FOLDER_ACCOUNT"|t:$MODULE_NAME} - {$ADDRESS_EMAIL}</h3>
	</div>
	<div class="modal-body col-md-12 tl-slide-content" data-user="{$RECORD}">
		{if count($MISSING_FOLDERS) > 0}
			<div class="alert alert-danger" role="alert">
				{"LBL_INFO_ABOUT_FOLDERS_TO_REMOVE"|t:$QUALIFIED_MODULE}
				<ul>
					{foreach from=$MISSING_FOLDERS item=$FOLDER_NAME}
						<li>{$FOLDER_NAME}</li>
					{/foreach}
				</ul>
			</div>
		{/if}
		{if $FOLDERS === false}
			<div class="alert alert-danger" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				{"ERR_INCORRECT_ACCESS_DATA"|t:$QUALIFIED_MODULE}
			</div>
		{else}
			<div class="alert alert-warning" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				{"LBL_ALERT_EDIT_FOLDER"|t:$MODULE_NAME}
			</div>
			<div class="row marginBottom5">
				<label class="col-sm-3 control-label">{"Received"|t:"OSSMailScanner"}</label>
				<div class="col-sm-6 controls">
					<select multiple name="Received" class="select2 form-control">
						{foreach key=FOLDER item=NAME from=$FOLDERS}
							<option value="{$FOLDER}" {if $SELECTED['Received'] && in_array($FOLDER,$SELECTED['Received'])}selected="selected"{/if}>
								{$NAME}
							</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="row marginBottom5">
				<label class="col-sm-3 control-label">{"Sent"|t:"OSSMailScanner"}</label>
				<div class="controls col-sm-6">
					<select multiple name="Sent" class="select2 form-control">
						{foreach key=FOLDER item=NAME from=$FOLDERS}
							<option value="{$FOLDER}" {if $SELECTED['Sent'] && in_array($FOLDER,$SELECTED['Sent'])}selected="selected"{/if}>
								{$NAME}
							</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="row marginBottom5">
				<label class="col-sm-3 control-label" >{"Spam"|t:"OSSMailScanner"}</label>
				<div class="col-sm-6 controls">
					<select multiple name="Spam" class="select2 form-control">
						{foreach key=FOLDER item=NAME from=$FOLDERS}
							<option value="{$FOLDER}" {if $SELECTED['Spam'] && in_array($FOLDER,$SELECTED['Spam'])}selected="selected"{/if}>
								{$NAME}
							</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="row marginBottom5">
				<label class="col-sm-3 control-label" >{"Trash"|t:"OSSMailScanner"}</label>
				<div class="col-sm-6 controls">
					<select multiple name="Trash" class="select2 form-control">
						{foreach key=FOLDER item=NAME from=$FOLDERS}
							<option value="{$FOLDER}" {if $SELECTED['Trash'] && in_array($FOLDER,$SELECTED['Trash'])}selected="selected"{/if}>
								{$NAME}
							</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="row">
				<label class="col-sm-3 control-label" >{"All_folder"|t:"OSSMailScanner"}</label>
				<div class="col-sm-6 controls">
					<select multiple name="All" class="select2 form-control">
						{foreach key=FOLDER item=NAME from=$FOLDERS}
							<option value="{$FOLDER}" {if $SELECTED['All'] && in_array($FOLDER,$SELECTED['All'])}selected="selected"{/if}>
								{$NAME}
							</option>
						{/foreach}
					</select>
				</div>
			</div>
		{/if}
	</div>
	<div class="modal-footer">
		<div class="pull-right">
			<button class="btn btn-success" type="submit" name="saveButton"><strong>{"LBL_SAVE"|t:$MODULE}</strong></button>
			<button class="btn btn-warning" type="reset" data-dismiss="modal"><strong>{"LBL_CANCEL"|t:$MODULE}</strong></button>
		</div>
	</div>
<!--/layouts/basic/modules/Settings/OSSMailScanner/Folders.tpl -->
{/strip}
