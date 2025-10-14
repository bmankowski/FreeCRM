{*<!--
/*+***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 *************************************************************************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Settings/Password/Index.tpl -->
<div class="">
	<div class="clearfix treeView">
		<form id="PassForm" class="form-horizontal">
			<div class="widget_header row">
				<div class="col-md-12">
				    {include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
				    &nbsp;{"LBL_PASSWORD_DESCRIPTION"|t:$QUALIFIED_MODULE}</div>
			</div>
			<table class="table table-bordered table-condensed themeTableColor">
				<thead>
					<tr class="blockHeader"><th colspan="2" class="mediumWidthType">{"LBL_Password_Header"|t:$QUALIFIED_MODULE}</th></tr>
				</thead>
				<tbody>
					<tr>
						<td width="30%"><label class="muted pull-right marginRight10px">{"Minimum password length"|t:$QUALIFIED_MODULE}</label></td>
						<td style="border-left: none;">
							<div class="col-xs-5">
								<input class="form-control" type="text" name="min_length" id="min_length"  title="{"Minimum password length"|t:$QUALIFIED_MODULE}" value="{$DETAIL['min_length']}" />
							</div>
						</td>
					</tr>
					<tr>
						<td width="30%"><label class="muted pull-right marginRight10px">{"Maximum password length"|t:$QUALIFIED_MODULE}</label></td>
						<td style="border-left: none;">
							<div class="col-xs-5">
								<input class="form-control" type="text" name="max_length" id="max_length" title="{"Maximum password length"|t:$QUALIFIED_MODULE}" value="{$DETAIL['max_length']}" />
							</div>
						</td>
					</tr>
					<tr>
						<td width="30%"><label class="muted pull-right marginRight10px">{"Uppercase letters from A to Z"|t:$QUALIFIED_MODULE}</label></td>
						<td style="border-left: none;">
							<div class="col-xs-5">	
								<input type="checkbox" name="big_letters" title="{"Uppercase letters from A to Z"|t:$QUALIFIED_MODULE}" id="big_letters" {if $DETAIL['big_letters'] == 'true' }checked{/if} />
							</div>
						</td>
					</tr>
					<tr>
						<td width="30%"><label class="muted pull-right marginRight10px">{"Lowercase letters a to z"|t:$QUALIFIED_MODULE}</label></td>
						<td style="border-left: none;">
							<div class="col-xs-5">
								<input type="checkbox" name="small_letters" title="{"Lowercase letters a to z"|t:$QUALIFIED_MODULE}" id="small_letters" {if $DETAIL['small_letters'] == 'true'}checked{/if} />
							</div>
						</td>
					</tr>
					<tr>
						<td width="30%"><label class="muted pull-right marginRight10px">{"Password should contain numbers"|t:$QUALIFIED_MODULE}</label></td>
						<td style="border-left: none;">
							<div class="col-xs-5">
								<input type="checkbox" name="numbers" title="{"Password should contain numbers"|t:$QUALIFIED_MODULE}" id="numbers" {if $DETAIL['numbers'] == 'true'}checked{/if} />
							</div>
						</td>
					</tr>
					<tr>
						<td width="30%"><label class="muted pull-right marginRight10px">{"Password should contain special characters"|t:$QUALIFIED_MODULE}</label></td>
						<td style="border-left: none;">
							<div class="col-xs-5">
								<input type="checkbox" name="special" title="{"Password should contain special characters"|t:$QUALIFIED_MODULE}" id="special"  {if $DETAIL['special'] == 'true'}checked{/if} />
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</form>
	</div>
</div>
<!--/layouts/basic/modules/Settings/Password/Index.tpl -->
{/strip}
