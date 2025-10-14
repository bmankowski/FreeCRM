{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/KnowledgeBase/ContentsDefault.tpl -->
	<div class="col-xs-12 paddingLRZero">
		<h4>{"LBL_LIST_RECORDS"|t:$MODULE_NAME}</h4>
		<hr>
	</div>
	{if $ENTRIES}
		<table class="table table-striped dataTableWithDocuments">
			<thead>
				<tr>
					{foreach item=HEADER from=$HEADERS}
						<th>
							{$HEADER->get('label')|t:$MODULE_NAME}
						</th>
					{/foreach}
					<th></th>
				</tr>
			</thead>
			<tbody>
				{foreach item=ENTRY from=$ENTRIES name=listview}
					<tr>
						{foreach item=HEADER from=$HEADERS}
							{assign var=HEADERNAME value=$HEADER->get('name')}
							<td>
								{if $HEADER->getFieldDataType() eq 'sharedOwner' ||  $HEADER->getFieldDataType() eq 'tree'}
									{$ENTRY->getDisplayValue($HEADERNAME)}
								{else}
									{$ENTRY->get($HEADERNAME)}
								{/if}
							</td>
						{/foreach}
						<td>
							<div class="actions pull-right">
								<a href="{$ENTRY->getDetailViewUrl()}">
									<span class="glyphicon glyphicon-th-list alignMiddle" title="{"LBL_RECORD_DETAILS"|t:$MODULE_NAME}"></span>
								</a>
							</div>
						</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	{else}
		<div class="col-xs-12 paddingLRZero">
			<div class="alert alert-info">
				{"LBL_RECORDS_NO_FOUND"|t:$MODULE_NAME}
			</div>
		</div>
	{/if}
<!--/layouts/basic/modules/KnowledgeBase/ContentsDefault.tpl -->
{/strip}
