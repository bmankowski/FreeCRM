{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/Notifications/Configuration.tpl -->
	<div class="widget_header row">
		<div class="col-xs-12 col-sm-12 col-md-9">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
		</div>
		<div class="col-xs-12 col-sm-6 col-sm-offset-6 col-md-3 col-md-offset-0">
			<select class="chzn-select form-control" name="supportedModule" id="supportedModule">
				{foreach item=SUPPORTED_MODULE key=TAB_ID from=$SUPPORTED_MODULES}
					<option value="{$TAB_ID}" {if $TAB_ID eq $SELECTED_MODULE} selected {/if}>{$SUPPORTED_MODULE->getName()|t:$SUPPORTED_MODULE->getName()}</option>
				{/foreach}
			</select>
		</div>
	</div>
	{if AppConfig::module('ModTracker', 'WATCHDOG')}
		<div class="row">
			<div class="table-responsive padding10">
				<div class="col-xs-12 col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3">
					<table class="table table-bordered table-condensed dataTable" data-mode="base">
						<thead>
							<tr>
								<th>
									<strong>{"LBL_MEMBERS"|t:$QUALIFIED_MODULE}</strong>
									<button class="btn btn-xs btn-success pull-right addUser" type="button" data-editurl=""><span class="glyphicon glyphicon-plus"></span> {"LBL_ADD"|t:$QUALIFIED_MODULE}</button>
								</th>
							</tr>
						</thead>
						<tbody class="dropContainer groupMembersColors">
							{foreach from=$WATCHDOG_MODULE->getWatchingMembers(true) key=KEY item=MEMBER}
								<tr class="{$MEMBER.type}" data-value="{$MEMBER.member}" data-lock="{$MEMBER.lock}">
									<td><strong>{$MEMBER.name}</strong>
										<span title="{"LBL_DELETE"|t:$QUALIFIED_MODULE}" class="glyphicon glyphicon-trash pull-right marginIcon marginTop2 delete cursorPointer" data-mode="addOrRemoveMembers"></span>
										<span title="{"LBL_LOCK"|t:$QUALIFIED_MODULE}" class="fa {if $MEMBER.lock}fa-lock{else}fa-unlock-alt{/if} fa-lg pull-right marginTB3 lock cursorPointer" aria-hidden="true"  data-mode="lock"></span>
										{if $MEMBER.type neq 'Users'}
											<span title="{"LBL_EXCEPTIONS"|t:$QUALIFIED_MODULE}" class="glyphicon glyphicon-exclamation-sign pull-right marginIcon marginTop2 exceptions cursorPointer" aria-hidden="true" data-mode="exceptions"></span>
										{/if}
									</td>
								</tr>
							{/foreach}
						</tbody>
					</table>
				</div>
				<div class="col-xs-12 col-sm-2 col-md-2 groupMembersColors">
					<br/>
					<ul class="nav nav-pills nav-stacked">
						<li class="Users padding5per textAlignCenter"><strong>{"LBL_USERS"|t:$QUALIFIED_MODULE}</strong></li>
						<li class="Groups padding5per textAlignCenter"><strong>{"LBL_GROUPS"|t:$QUALIFIED_MODULE}</strong></li>
						<li class="Roles padding5per textAlignCenter"><strong>{"LBL_ROLES"|t:$QUALIFIED_MODULE}</strong></li>
						<li class="RoleAndSubordinates padding5per textAlignCenter"><strong>{"RoleAndSubordinates"|t:$QUALIFIED_MODULE}</strong></li>
					</ul>
				</div>
			</div>
		</div>
	{else}
		<div class="alert alert-danger fade in wa">
			{"LBL_NOTICE_CONFIG_WARNING"|t:$QUALIFIED_MODULE}
		</div>
	{/if}
<!--/layouts/basic/modules/Settings/Notifications/Configuration.tpl -->
{/strip}
