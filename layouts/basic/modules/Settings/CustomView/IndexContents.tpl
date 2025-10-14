{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/CustomView/IndexContents.tpl -->
	<input id="addFilterUrl" type="hidden" value="{$MODULE_MODEL->getCreateFilterUrl($SOURCE_MODULE_ID)}"/>
	<div class="table-responsive">
		<table class="table table-striped table-bordered table-condensed listViewEntriesTable">
			<thead>
				<tr class="blockHeader">
					<th></th>
					<th><strong>{"ViewName"|t:$QUALIFIED_MODULE}</strong></th>
					<th><strong>{"SetDefault"|t:$QUALIFIED_MODULE}</strong></th>
					<th><strong>{"Privileges"|t:$QUALIFIED_MODULE}</strong></th>
					<th><strong>{"LBL_FEATURED_LABELS"|t:$QUALIFIED_MODULE}</strong></th>
					<th><strong>{"LBL_SORTING"|t:$QUALIFIED_MODULE}</strong></th>
					<th><strong>{"LBL_CREATED_BY"|t:$QUALIFIED_MODULE}</strong></th>
					<th><strong>{"Actions"|t:$QUALIFIED_MODULE}</strong></th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$MODULE_MODEL->getCustomViews($SOURCE_MODULE_ID) item=item key=key}
					<tr data-cvid="{$key}" data-mod="{$item['entitytype']}">
						<td>
							<img src="{vimage_path('drag.png')}" border="0" title="{"LBL_DRAG"|t:$QUALIFIED_MODULE}"/>
						</td>
						{if $item['viewname'] eq 'All'}
							<td>{vtranslate('All',$item['entitytype'])}</td>
						{else}
							<td>{$item['viewname']}</td>
						{/if}
						<td>
							<input class="switchBtn updateField" type="checkbox" name="setdefault" {if $item['setdefault']}checked disabled="disabled"{/if} data-size="small" data-label-width="5" data-on-text="{"LBL_YES"|t}" data-off-text="{"LBL_NO"|t}" value="1">
							&nbsp;&nbsp;
							<button type="button" class="btn btn-default btn-sm showModal" data-url="{$MODULE_MODEL->getUrlDefaultUsers($SOURCE_MODULE_ID,$key, $item['setdefault'])}"><span class="glyphicon glyphicon-user"></span></button>
						</td>
						<td>
							<input class="switchBtn updateField" type="checkbox" name="privileges" {if $item['privileges']}checked{/if} data-size="small" data-label-width="5" data-on-text="{"LBL_YES"|t}" data-off-text="{"LBL_NO"|t}" value="1">
						</td>
						<td>
							<input class="switchBtn updateField" type="checkbox" name="featured" {if $item['featured']}checked{/if} data-size="small" data-label-width="5" data-on-text="{"LBL_YES"|t}" data-off-text="{"LBL_NO"|t}" value="1">
							&nbsp;&nbsp;
							<button type="button" class="btn btn-default btn-sm showModal" data-url="{$MODULE_MODEL->getFeaturedFilterUrl($SOURCE_MODULE_ID,$key)}"><span class="glyphicon glyphicon-user"></span></button>
						</td>
						<td>
							<button type="button" id="sort" name="sort" class="btn btn-default btn-sm showModal" data-url="{$MODULE_MODEL->getSortingFilterUrl($SOURCE_MODULE_ID,$key)}"><span class="glyphicon glyphicon-sort"></span></button>
						</td>
						<td>{vtlib\Functions::getOwnerRecordLabel($item['userid'])}</td>
						<td>
							<button class="btn btn-primary marginLeftZero btn-sm update" data-cvid="{$key}" data-editurl="{$MODULE_MODEL->GetUrlToEdit($item['entitytype'],$key)}">{"Edit"|t:$QUALIFIED_MODULE}</button>
							{if $item['presence'] eq 1}
								<button class="btn btn-danger marginLeftZero btn-sm delete marginRight10" data-cvid="{$key}">{"Delete"|t:$QUALIFIED_MODULE}</button>
							{/if}
						</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
<!--/layouts/basic/modules/Settings/CustomView/IndexContents.tpl -->
{/strip}
