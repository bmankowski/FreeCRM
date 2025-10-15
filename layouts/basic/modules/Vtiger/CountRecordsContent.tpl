{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Vtiger/CountRecordsContent.tpl -->
	<table class="table table-bordered table-condensed">
		<thead>
			<tr>
				<th>{"LBL_MODULE_NAME"|t:$MODULE_NAME}</th>
				<th>{"LBL_QTY"|t:$MODULE_NAME}</th>
			</tr>
		</thead>
		<tbody>
			{foreach from=$RELATED_MODULES item=RELATED_MODULE}
				<tr>
					<td>{$RELATED_MODULE|t:$RELATED_MODULE}</td>
					<td><span class="badge">{$COUNT_RECORDS[$RELATED_MODULE]}</span></td>
				</tr>
			{/foreach}
		</tbody>
	</table>
<!--/layouts/basic/modules/Vtiger/CountRecordsContent.tpl -->
{/strip}

