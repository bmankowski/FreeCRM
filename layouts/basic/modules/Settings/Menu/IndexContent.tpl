{*/*+***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 *************************************************************************************************************************************/*}
<div class="menuConfigContainer">
	<div class="widget_header row">
		<div class="col-md-7">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			{"LBL_MENU_BUILDER_DESCRIPTION"|t:$QUALIFIED_MODULE}
		</div>
		<div class="col-md-5 row">
			<div class="col-xs-6 paddingLRZero">
				<button class="btn btn-default addMenu pull-right"><strong>{"LBL_ADD_MENU"|t:$QUALIFIED_MODULE}</strong></button>
			</div>
			<div class="col-xs-6 pull-right ">
				<select class="select2 form-control" name="roleMenu">
					<option value="0" {if $ROLEID eq 0} selected="" {/if}>{"LBL_DEFAULT_MENU"|t:$QUALIFIED_MODULE}</option>
					{foreach item=ROLE key=KEY from=$ALL_ROLES}
						<option value="{$KEY}" {if $ROLEID === $KEY} selected="" {/if}>{$ROLE->getName()|t}</option>
					{/foreach}
				</select>
			</div>
		</div>
	</div>
	<hr>
	{if !$DATA}
		<button class="btn btn-success copyMenu"><strong>{"LBL_COPY_MENU"|t:$QUALIFIED_MODULE}</strong></button>
	{/if}
	<div class="treeMenuContainer">
		<input type="hidden" id="treeLastID" value="{$LASTID}" />
		<input type="hidden" name="tree" id="treeValues" value='{$DATA_JSON}' />
		<div id="treeContent"></div>
	</div>
	<div class="modal fade copyMenuModal">
		<div class="modal-dialog modal-sm">
			<div class="modal-content">
				<form>
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title">{"LBL_COPY_MENU"|t:$QUALIFIED_MODULE}</h4>
					</div>
					<div class="modal-body">
						<select id="roleList" class="form-control" name="roles" data-validation-engine="validate[required]">
							<option value="0">{"LBL_DEFAULT_MENU"|t:$QUALIFIED_MODULE}</option>
							{foreach item=ROLE key=KEY from=$ROLES_CONTAIN_MENU}
								<option value="{$ROLE['roleId']}"  >{$ROLE['roleName']|t}</option>
							{/foreach}
						</select>
					</div>
					<div class="modal-footer">
						<button type="submit" class="btn btn-success saveButton">{"LBL_SAVE"|t:$QUALIFIED_MODULE}</button>
						<button type="button" class="btn btn-warning dismiss" data-dismiss="modal">{"LBL_CLOSE"|t:$QUALIFIED_MODULE}</button>
					</div>
				</form>
			</div>
		</div>
	</div>	
</div>
<div class="modal deleteAlert fade" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h3 class="modal-title">{"LBL_REMOVE_TITLE"|t:$QUALIFIED_MODULE}</h3>
			</div>
			<div class="modal-body">
				<p>{"LBL_REMOVE_DESC"|t:$QUALIFIED_MODULE}</p>
			</div>
			<div class="modal-footer">
				<div class="pull-right">
					<button class="btn btn-warning cancelLink" type="reset" data-dismiss="modal">{"LBL_CANCEL"|t:$QUALIFIED_MODULE}</button>
				</div>
				<div class="pull-right">
					<button class="btn btn-danger" data-dismiss="modal">{"LBL_REMOVE"|t:$QUALIFIED_MODULE}</button>
				</div>
			</div>
		</div>
	</div>
</div>
