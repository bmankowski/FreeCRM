{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/IStorages/Hierarchy.tpl -->
	<div id="accountHierarchyContainer" class="modelContainer modal fade" tabindex="-1">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<button class="close" data-dismiss="modal" title="{"LBL_CLOSE"|t}">x</button>
					<h3 class="modal-title">{"LBL_SHOW_HIERARCHY"|t:$MODULE}</h3>
				</div>
				<div class="modal-body">
					<div id ="hierarchyScroll" style="margin-right: 8px;">
						<table class="table table-bordered">
							<thead>
								<tr class="blockHeader">
								{foreach item=HEADERNAME from=$HIERARCHY['header']}
									<th>{$HEADERNAME|t:$MODULE}</th>
								{/foreach}
								</tr>
							</thead>
						{foreach item=ENTRIES from=$HIERARCHY['entries']}
							<tbody>
								<tr>
								{foreach item=LISTFIELDS from=$ENTRIES}
									<td>{$LISTFIELDS}</td>
								{/foreach}
								</tr>
							</tbody>
						{/foreach}
						</table>
					</div>
				</div>
				<div class="modal-footer">
					<div class=" pull-right cancelLinkContainer">
						<button class="btn btn-warning" type="reset" data-dismiss="modal"><strong>{"LBL_CLOSE"|t:$MODULE}</strong></button>
					</div>
				</div>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/IStorages/Hierarchy.tpl -->
{/strip}
