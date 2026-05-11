{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/AdvanceFilterExpressions.tpl -->
	{if empty($MODULE_MODEL)}
		{assign var=MODULE_MODEL value=\App\Modules\Base\Models\Module::getInstance($SOURCE_MODULE)}
	{/if}
	{if empty($DATE_FILTERS)}
		{assign var=DATE_FILTERS value=array()}
	{/if}
	{if empty($ADVANCED_FILTER_OPTIONS)}
		{assign var=ADVANCED_FILTER_OPTIONS value=array()}
	{/if}
	{if empty($ADVANCED_FILTER_OPTIONS_BY_TYPE)}
		{assign var=ADVANCED_FILTER_OPTIONS_BY_TYPE value=array()}
	{/if}
	{if empty($FIELD_EXPRESSIONS)}
		{assign var=FIELD_EXPRESSIONS value=array()}
	{/if}
	{if empty($META_VARIABLES)}
		{assign var=META_VARIABLES value=array()}
	{/if}
	{assign var=COLUMNNAME_API value='getName'}
	<div class="padding1per stepBorder">
		<div id="advanceFilterContainer" class="row conditionsContainer padding1per">
			<h5 class="padding-bottom1per col-md-10"><strong>{"LBL_CHOOSE_FILTER_CONDITIONS"|t:$MODULE}</strong></h5>
			<div class="col-md-10" >
				{include file='AdvanceFilter.tpl'|@vtemplate_path}
			</div>
			{include file="FieldExpressions.tpl"|@vtemplate_path}
		</div>
	</div>
<!--/layouts/basic/modules/Base/AdvanceFilterExpressions.tpl -->
{/strip}
