{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Announcements/UsersList.tpl -->
	<div class="">
		<table class="table table-striped">
			<thead>
				<tr> 
					<th>{"LBL_USER"|t:$MODULE_NAME}</th>
					<th class="text-center">{"LBL_ACCEPT_ANNOUNCEMENT"|t:$MODULE_NAME}</th>
					<th class="text-center">{"LBL_DATE"|t:$MODULE_NAME}</th>
				</tr> 
			</thead>
			<tbody>
				{foreach item=USER key=USERID from=$USERS}
					{assign var=STATUS value=isset($USER['status']) && $USER['status'] == 1}
					<tr data-id="{$USERID}" class="{if $STATUS}success{else}danger{/if}">
						<td>{$USER['name']}</td>
						<td class="text-center">
							{if $STATUS}
								<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
							{else}
								<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
							{/if}
						</td>
						<td class="text-center">
							{if isset($USER['date'])}
								{\App\Modules\Vtiger\helpers\Util::formatDateTimeIntoDayString($USER['date'])}&nbsp;
								- {\App\Modules\Vtiger\helpers\Util::formatDateDiffInStrings($USER['date'])}	
							{/if}
						</td>
					</tr> 
				{/foreach}
			</tbody> 
		</table>
	</div>
<!--/layouts/basic/modules/Announcements/UsersList.tpl -->
{/strip}
