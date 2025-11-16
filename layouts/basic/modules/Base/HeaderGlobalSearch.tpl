{strip}
	{*<!-- {[The file is published on the basis of FreeCRM Public License that can be found in the following directory: licenses/FreeCRMLicense.html]} --!>*}
<!-- layouts/basic/modules/Base/HeaderGlobalSearch.tpl -->
	{if \App\Core\AppConfig::performance('GLOBAL_SEARCH')}
		<div class="pull-left selectSearch">
			<div class="input-group globalSearchInput">
				<span class="input-group-btn">
					<select class="chzn-select basicSearchModulesList form-control col-md-5" title="{'LBL_SEARCH_MODULE'|t}">
						<option value="">{'LBL_ALL_RECORDS'|t}</option>
						{foreach key=SEARCHABLE_MODULE item=fieldObject from=$SEARCHABLE_MODULES}
							{if isset($SEARCHED_MODULE) && $SEARCHED_MODULE eq $SEARCHABLE_MODULE && $SEARCHED_MODULE !== 'All'}
								<option value="{$SEARCHABLE_MODULE}" selected>{$SEARCHABLE_MODULE|t:$SEARCHABLE_MODULE}</option>
							{else}
								<option value="{$SEARCHABLE_MODULE}">{$SEARCHABLE_MODULE|t:$SEARCHABLE_MODULE}</option>
							{/if}
						{/foreach}
					</select>
				</span>
				<input type="text" class="form-control globalSearchValue" title="{'LBL_GLOBAL_SEARCH'|t}" placeholder="{'LBL_GLOBAL_SEARCH'|t}" results="10" data-operator="contains" />
				<span class="input-group-btn">
					<button class="btn btn-default searchIcon" type="button">
						<span class="glyphicon glyphicon-search"></span>
					</button>
					{if \App\Core\AppConfig::search('GLOBAL_SEARCH_OPERATOR')}
						<div class="btn-group">
							<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<span class="glyphicon glyphicon-screenshot"></span>
							</button>
							<ul class="dropdown-menu globalSearchOperator">
								<li class="active"><a href="#" data-operator="contains">{'contains'|t}</a></li>
								<li><a href="#" data-operator="starts">{'starts with'|t}</a></li>
								<li><a href="#" data-operator="ends">{'ends with'|t}</a></li>
							</ul>
						</div>
					{/if}
					<button class="btn btn-default globalSearch" title="{'LBL_ADVANCE_SEARCH'|t}" type="button">
						<span class="glyphicon glyphicon-th-large"></span>
					</button>
				</span>
			</div>
		</div>
	{/if}
<!--/layouts/basic/modules/Base/HeaderGlobalSearch.tpl -->
{/strip}

