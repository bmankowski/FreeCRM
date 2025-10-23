{strip}
<!-- layouts/basic/modules/Settings/SystemWarnings/YetiForce/Stats.tpl -->
	<form class="form-horizontal row validateForm" method="post" action="index.php">
		<h3 class="marginTB3">
			{'LBL_STATS'|t:'Settings:SystemWarnings'}
		</h3>
		<p>{'LBL_STATS_DESC'|t:'Settings:SystemWarnings'}</p>
		{assign var=COMPANY value=\App\Company::getInstanceById()}
		<div class="input-group">
			<span class="input-group-addon">
				<input type="checkbox" checked>
			</span>
			<input type="text" name="company_name" class="form-control" placeholder="{'LBL_NAME'|t:'Settings:Companies'}" value="{$COMPANY->get('name')}">
		</div><br>
		<div class="input-group">
			<span class="input-group-addon">
				<input type="checkbox" checked disabled>
			</span>
			<select class="select2 form-control" name="company_industry" data-validation-engine="validate[required]">
				{foreach from=\App\Modules\Settings\Companies\Models\Module::getIndustryList() item=ITEM}
					<option value="{$ITEM}" {if $COMPANY->get('industry') eq $ITEM}selected{/if}>{$ITEM|t}</option>
				{/foreach}
			</select>
		</div><br>
		<div class="input-group">
			<span class="input-group-addon">
				<input type="checkbox" checked disabled>
			</span>
			<input type="text" name="company_city" class="form-control" data-validation-engine="validate[required]" placeholder="{'LBL_CITY'|t:'Settings:Companies'}" value="{$COMPANY->get('city')}">
		</div><br>
		<div class="input-group">
			<span class="input-group-addon">
				<input type="checkbox" checked disabled>
			</span>
			<input type="text" name="company_country" class="form-control" data-validation-engine="validate[required]" placeholder="{'LBL_COUNTRY'|t:'Settings:Companies'}" value="{$COMPANY->get('country')}">
		</div><br>
		<div class="input-group">
			<span class="input-group-addon">
				<input type="checkbox" checked>
			</span>
			<input type="text" name="company_website" class="form-control" placeholder="{'LBL_WEBSITE'|t:'Settings:Companies'}" value="{$COMPANY->get('website')}">
		</div><br>
		<div class="input-group">
			<span class="input-group-addon">
				<input type="checkbox" checked>
			</span>
			<input type="text" name="company_email" class="form-control" placeholder="{'LBL_EMAIL'|t:'Settings:Companies'}" value="{$COMPANY->get('email')}">
		</div><br>
		<div class="pull-right">
			<button type="button" class="btn btn-success ajaxBtn">
				<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
				&nbsp;&nbsp;{'LBL_SEND'|t:'Settings:SystemWarnings'}
			</button>&nbsp;&nbsp;
			<button type="button" class="btn btn-danger cancel">
				<span class="glyphicon glyphicon-ban-circle" aria-hidden="true"></span>
				&nbsp;&nbsp;{'LBL_REMIND_LATER'|t:'Settings:SystemWarnings'}
			</button>
		</div>
		<div class="clearfix"></div>
	</form>
<!--/layouts/basic/modules/Settings/SystemWarnings/YetiForce/Stats.tpl -->
{/strip}
