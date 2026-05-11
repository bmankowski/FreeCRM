{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/PDF/Step7.tpl -->
	<div class="pdfTemplateContents">
		<form name="EditPdfTemplate" action="index.php" method="post" id="pdf_step7" class="form-horizontal">
			<input type="hidden" name="module" value="PDF">
			<input type="hidden" name="view" value="Edit">
			<input type="hidden" name="mode" value="Step8" />
			<input type="hidden" name="parent" value="Settings" />
			<input type="hidden" class="step" value="7" />
			<input type="hidden" name="record" value="{$RECORDID}" />

			<div class="padding1per stepBorder">
				<label>
					<strong>{'LBL_STEP_N'|t:$QUALIFIED_MODULE:7}: {"LBL_PERMISSIONS_DETAILS"|t:$QUALIFIED_MODULE}</strong>
				</label>
				<br>
				<div class="form-group">
					<div class="col-md-2 control-label">
						{"LBL_GROUP_MEMBERS"|t:"Settings:Groups"}
					</div>
					<div class="col-md-6 controls">
						<div class="row">
							<div class="col-md-6">
								<select class="select2 form-control" multiple="true" name="template_members[]" data-placeholder="{"LBL_ADD_USERS_ROLES"|t:"Settings:Groups"}">
									{assign 'TEMPLATE_MEMBERS' explode(',',$PDF_MODEL->get('template_members'))}
									{foreach from=$ALL_GROUP_MEMBERS key=GROUP_LABEL item=ALL_GROUP_MEMBERS_LIST}
										<optgroup label="{$GROUP_LABEL|t:$QUALIFIED_MODULE}">
											{foreach from=$ALL_GROUP_MEMBERS_LIST item=MEMBER}
												<option value="{$MEMBER->get('id')}"  data-member-type="{$GROUP_LABEL}" {if in_array($MEMBER->get('id'), $TEMPLATE_MEMBERS)}selected="true"{/if}>{$MEMBER->get('name')|t:$QUALIFIED_MODULE}</option>
											{/foreach}
										</optgroup>
									{/foreach}
								</select>
							</div>
						</div>
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
<!--/layouts/basic/modules/Settings/PDF/Step7.tpl -->
{/strip}
