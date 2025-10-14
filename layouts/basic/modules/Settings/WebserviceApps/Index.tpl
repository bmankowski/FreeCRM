{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/WebserviceApps/Index.tpl -->
	<div class="col-xs-12 paddingLRZero">
		<div class="table-responsive">
			<table class="table table-bordered table-condensed">
				<thead>
					<tr>
						<th><strong>{"LBL_APP_NAME"|t:$QUALIFIED_MODULE}</strong></th>
						<th><strong>{"LBL_ADDRESS_URL"|t:$QUALIFIED_MODULE}</strong></th>
						<th><strong>{"Status"|t:$QUALIFIED_MODULE}</strong></th>
						<th><strong>{"LBL_TYPE_SERVER"|t:$QUALIFIED_MODULE}</strong></th>
						<th><strong>{"LBL_API_KEY"|t:$QUALIFIED_MODULE}</strong></th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$LIST_SERVERS key=KEY item=SERVER}
						<tr data-id="{$KEY}">
							<td>{$SERVER['name']}</td>
							<td>{$SERVER['acceptable_url']}</td>
							<td>
								{if $SERVER['status'] eq 1}
									{"LBL_ACTIVE"|t:$QUALIFIED_MODULE}
								{else}
									{"LBL_INACTIVE"|t:$QUALIFIED_MODULE}
								{/if}
							</td>
							<td>
								{$SERVER['type']}
							</td>
							<td>
								<div class="action">
									{$SERVER['api_key']}
									<div class="pull-right">
										<button class="btn btn-primary btn-xs edit">
											<span class="glyphicon glyphicon-pencil cursorPointer"></span>
										</button>
										<button class="btn btn-danger btn-xs marginLeft5 remove">
											<span class="glyphicon glyphicon-trash cursorPointer"></span>
										</button>
									</div>
								</div>
							</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
<!--/layouts/basic/modules/Settings/WebserviceApps/Index.tpl -->
{/strip}
