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
<div class="">
	<div class="form-horizontal">
		<div class="form-group row">
			<label for="langs_list" class="control-label col-md-1" >{"Language"|t:$QUALIFIED_MODULE}:</label>
			<div class="col-md-3">
				<select multiple="multiple" class="form-control" id="langs_list">
					{foreach from=$LANGS item=LANG key=ID}
						<option value="{$LANG['prefix']}" {if $MODULE_MODEL->parse_data($LANG['prefix'],$REQUEST->get('lang'))}selected{/if}>{$LANG['label']}</option>
					{/foreach}
				</select>
			</div>
			<label class="col-md-1 control-label">{"Module"|t:$QUALIFIED_MODULE}:</label>
			<div class="col-md-3">
				<select class="form-control mods_list" id="mods_list">
					<optgroup label="{"Modules"|t:$QUALIFIED_MODULE}">
						{foreach from=$MODS['mods'] item=MOD key=ID}
							<option value="{$ID}" {if $ID == $REQUEST->get('mod')}selected{/if}>{$MOD|t:$MOD}</option>
						{/foreach}
					</optgroup>
					<optgroup label="{"LBL_SYSTEM_SETTINGS"|t:"Vtiger"}">
						{foreach from=$MODS['settings'] item=MOD key=ID}
							<option value="{$ID}" {if $ID == $REQUEST->get('mod')}selected{/if}>{$MOD|t:$MOD}</option>
						{/foreach}
					</optgroup>
				</select>
			</div>
			<div class="checkbox col-md-2">
				<label class="">
					<input type="checkbox" class="show_differences" name="show_differences" {if $SD == 1}checked{/if} value="1">&nbsp;{"LBL_SHOW_MISSING_TRANSLATIONS"|t:$QUALIFIED_MODULE}
				</label>
			</div>
			<button class="btn btn-primary add_translation col-md-2 pull-right {if $REQUEST->get('lang') eq ''}hide{/if}">{"LBL_ADD_Translate"|t:$QUALIFIED_MODULE}</button>
		</div>
	</div>
</div>
<br>
{if $DATA}
<div class="">
	<table class="table table-bordered table-condensed listViewEntriesTable" >
		<thead>
			<tr class="blockHeader">
				<th><strong>{"LBL_variable"|t:$QUALIFIED_MODULE}</strong></th>
				{foreach from=$DATA['langs'] item=item}
					<th><strong>{$item}</strong></th>
				{/foreach}
				<th></th>
			</tr>
		</thead>
		<tbody>
		{if $DATA['php']}
			{foreach from=$DATA['php'] item=langs key=lang_key}
				{assign var=TEMPDATA value = 1}
				{if $SD == 1}
					{assign var=TEMPDATA value = 0}
					{foreach from=$langs item=item key=lang}
						{if $item == NULL}
							{assign var=TEMPDATA value = 1}
						{/if}
					{/foreach}
				{/if}
				{if $TEMPDATA == 1}
					<tr data-langkey="{$lang_key}">
						<td>{$lang_key}</td>
						{foreach from=$langs item=item key=lang}
							<td><input 
								data-lang="{$lang}"
								data-type="php"
								name="{$lang_key}" 
								class="translation form-control {if $item == NULL}empty_value{/if}" 
								{if $item == NULL} placeholder="{"LBL_NoTranslation"|t:$QUALIFIED_MODULE}" {/if} 
								type="text" 
								value ="{$item}" />
							</td>
						{/foreach}
						<td>
							<a href="#" class="pull-right marginRight10px delete_translation" title="{"LBL_DELETE"|t}">
								<i class="glyphicon glyphicon-trash alignMiddle"></i>
							</a>
						</td>
					</tr>
				{/if}
			{/foreach}
		{/if}
		{if $DATA['js']}
			{foreach from=$DATA['js'] item=langs key=lang_key}
				{assign var=TEMPDATA value = 1}
				{if $SD == 1}
					{assign var=TEMPDATA value = 0}
					{foreach from=$langs item=item key=lang}
						{if $item == NULL}
							{assign var=TEMPDATA value = 1}
						{/if}
					{/foreach}
				{/if}
				{if $TEMPDATA == 1}
					<tr data-langkey="{$lang_key}">
						<td>{$lang_key}</td>
						{foreach from=$langs item=item key=lang}
							<td><input 
								data-lang="{$lang}"
								data-type="js"
								name="{$lang_key}" 
								class="translation form-control {if $item == NULL}empty_value{/if}" 
								{if $item == NULL} placeholder="{"LBL_NoTranslation"|t:$QUALIFIED_MODULE}" {/if} 
								type="text" 
								value ="{$item}" />
							</td>
						{/foreach}
						<td>
							<a href="#" class="pull-right marginRight10px delete_translation">
								<i class="glyphicon glyphicon-trash alignMiddle"></i>
							</a>
						</td>
					</tr>
				{/if}
			{/foreach}
			{/if}
		</tbody>
	</table>
</div>
{/if}
