{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/MappedFields/Step4.tpl -->
	<div class="mfTemplateContents">
		<form name="editMFTemplate" action="index.php" method="post" id="mf_step4" class="form-horizontal">
			<input type="hidden" name="module" value="{$MAPPEDFIELDS_MODULE_MODEL->getName()}">
			<input type="hidden" name="view" value="Edit">
			<input type="hidden" name="mode" value="Step8" />
			<input type="hidden" name="parent" value="{$MAPPEDFIELDS_MODULE_MODEL->getParentName()}" />
			<input type="hidden" class="step" value="4" />
			<input type="hidden" name="record" value="{$RECORDID}" />
			<div class="col-md-12 paddingLRZero">
				<div class="panel panel-default">
					<div class="panel-heading">
						<label>
							<strong>{'LBL_STEP_N'|t:$QUALIFIED_MODULE, 4}: {"LBL_PERMISSIONS_DETAILS"|t:$QUALIFIED_MODULE}</strong>
						</label>
					</div>
					<div class="panel-body">
						<div class="form-group">
								<label class="col-md-3 control-label">
									{"LBL_GROUP_MEMBERS"|t:"Settings:Groups"}
								</label>
							<div class="col-md-8">
								<select class="selectize form-control" multiple="true" id="permissions" name="permissions[]" data-placeholder="{"LBL_ADD_USERS_ROLES"|t:"Settings:Groups"}">
									{assign 'TEMPLATE_MEMBERS' explode(',',$MAPPEDFIELDS_MODULE_MODEL->get('permissions'))}
									{foreach from=\App\Modules\Settings\Groups\Models\Member::getAll(false) key=GROUP_LABEL item=ALL_GROUP_MEMBERS}
										<optgroup label="{$GROUP_LABEL|t:$QUALIFIED_MODULE}">
											{foreach from=$ALL_GROUP_MEMBERS item=MEMBER}
												<option value="{$MEMBER->get('id')}"  data-member-type="{$GROUP_LABEL}" {if in_array($MEMBER->get('id'), $TEMPLATE_MEMBERS)}selected="true"{/if}>{$MEMBER->get('name')|t:$QUALIFIED_MODULE}</option>
											{/foreach}
										</optgroup>
									{/foreach}
								</select>
							</div>
						</div>
					</div>
					<div class="panel-footer clearfix">
						<div class="btn-toolbar pull-right">
							<button class="btn btn-danger backStep" type="button"><strong>{"LBL_BACK"|t:$QUALIFIED_MODULE}</strong></button>
							<button class="btn btn-success" type="submit"><strong>{"LBL_FINISH"|t:$QUALIFIED_MODULE}</strong></button>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
<!--/layouts/basic/modules/Settings/MappedFields/Step4.tpl -->
{/strip}
