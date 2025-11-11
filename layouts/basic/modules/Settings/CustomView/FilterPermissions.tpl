{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} -->*}
{strip}
<!-- layouts/basic/modules/Settings/CustomView/FilterPermissions.tpl -->
	{assign 'MEMBERS' $MEMBERS}
	{assign 'MEMBERS_DEFAULT' $MODULE_MODEL->getFilterPermissionsView($CVID, $TYPE)}
	<input type="hidden" id="cvid" value="{$CVID}" />
	<input type="hidden" id="sourceModule" value="{$SOURCE_MODULE}" />
	<input type="hidden" id="type" value="{$TYPE}" />
	<div class="modal-header">
		<div class="pull-left">
			<h3 class="modal-title">{"LBL_MANAGE_PERMISSIONS"|t:$MODULE_NAME}</h3>
		</div>
		<div class="clearfix"></div>
	</div>
	<div class="modal-body">
		<div class="">
			<div class="form-group">
				<label class="col-xs-12 control-label">
					{"LBL_ALL_GROUP_LIST"|t:$MODULE_NAME}
				</label>
				<div class="col-xs-10">
					<select class="select2 form-control add" id="allGroups" {if $IS_DEFAULT} disabled="disabled"{/if}>
						{foreach from=$MEMBERS key=GROUP_LABEL item=ALL_GROUP_MEMBERS}
							<optgroup label="{$GROUP_LABEL|t:$QUALIFIED_MODULE}">
								{foreach from=$ALL_GROUP_MEMBERS item=MEMBER key=QUALIFIEDID}
								{if is_array($MEMBERS_DEFAULT[$GROUP_LABEL]) && in_array($QUALIFIEDID,$MEMBERS_DEFAULT[$GROUP_LABEL])}{continue}{/if}
								<option value="{$MEMBER->get('id')}"  data-member-type="{$GROUP_LABEL}">{$MEMBER->get('name')|t:$QUALIFIED_MODULE}</option>
							{/foreach}
						</optgroup>
					{/foreach}
				</select>
			</div>
			<button type="button" class="btn btn-success moveItem" data-source="add" data-target="remove" data-action="add" title="{"LBL_ADD_PERMISSIONS"|t:$MODULE_NAME}" {if $IS_DEFAULT} disabled="disabled"{/if}><span class="glyphicon glyphicon-arrow-down"></span></button>
		</div>
		<div class="form-group">
			<label class="col-xs-12 control-label">
				{"LBL_GROUP_MEMBERS"|t:$MODULE_NAME}
			</label>
			<div class="col-xs-10">
				<select class="select2 form-control remove" id="groups">
					{foreach from=$MEMBERS_DEFAULT key=LABEL item=GROUP}
						<optgroup label="{$LABEL|t:$QUALIFIED_MODULE}">
							{foreach from=$GROUP item=USER}
								{assign 'MEMBER' $MEMBERS[$LABEL][$USER]}
								{if $MEMBER}
									<option value="{$USER}"  data-member-type="{$LABEL}">{$MEMBER->get('name')|t:$QUALIFIED_MODULE}</option>
								{/if}
							{/foreach}
						</optgroup>
					{/foreach}
				</select>
			</div>
			<button type="button" class="btn btn-danger moveItem" data-source="remove" data-target="add" data-action="remove" title="{"LBL_RECEIVE_PERMISSION"|t:$MODULE_NAME}"><span class="glyphicon glyphicon-arrow-up"></span></button>
		</div>
	</div>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-warning dismiss" data-dismiss="modal">{"LBL_CLOSE"|t:$MODULE_NAME}</button>
</div>
<!--/layouts/basic/modules/Settings/CustomView/FilterPermissions.tpl -->
{/strip}
