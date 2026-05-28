{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/dashboards/RssContents.tpl -->
	<table class="table table-condensed table-bordered">
		<thead>
			<tr>
				<th>{"LBL_SUBJECT"|t:$MODULE_NAME}</th>
				<th>{"LBL_SOURCE"|t:$MODULE_NAME}</th>
				<th>{"LBL_DATE"|t:$MODULE_NAME}</th>
			</tr>
		</thead>
		<tbody>
			{foreach from=$LIST_SUCJECTS item=SUBJECT}
				<tr>
					<td><a href="{$SUBJECT['link']}"><strong title="{\App\Modules\Base\Helpers\Util::toSafeHTML($SUBJECT['fullTitle'])}">{$SUBJECT['title']}</strong></a></td>
					<td>{$SUBJECT['source']}</td>
					<td>{$SUBJECT['date']}</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
<!--/layouts/basic/modules/Base/dashboards/RssContents.tpl -->
{/strip}
