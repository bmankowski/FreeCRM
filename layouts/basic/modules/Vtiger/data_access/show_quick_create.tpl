{strip}
<!-- layouts/basic/modules/Vtiger/data_access/show_quick_create.tpl -->
{assign var=modules value=$SAVED_DATA['modules']}
<div class="row">
	<div class="col-md-12 padding-bottom1per">
		<h5 class="padding-bottom1per"><strong>{"LBL_SELECT_OPTION"|t:"DataAccess"}:</strong></h5>
		<select name="modules" class="marginLeftZero col-md-6 select2">
			{foreach item=item key=key from=$CONFIG['modules']}
				<option value="{$key}" {if $key == $modules} selected {/if} >{$item|t:$key}</option>
			{/foreach}
		</select>
	</div>
</div>
<!--/layouts/basic/modules/Vtiger/data_access/show_quick_create.tpl -->
{/strip}
