{strip}
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">
	<div class="widget_header row">
		<div class="col-md-8">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			{'LBL_AIPROMPTS_DESCRIPTION'|t:$QUALIFIED_MODULE}
		</div>
		<div class="col-md-4 marginbottomZero">
			<div class="pull-right btn-toolbar"><span class="actionImages">
					<a class="btn btn-info" href="{$RECORD_MODEL->getEditViewUrl()}">
						<span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>&nbsp;
						<strong>{'LBL_EDIT_RECORD'|t:$QUALIFIED_MODULE}</strong>
					</a>
					<a class="btn btn-danger marginLeft5" href="{$RECORD_MODEL->getDeleteActionUrl()}">
						<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>&nbsp;
						<strong>{'LBL_DELETE_RECORD'|t:$QUALIFIED_MODULE}</strong>
					</a>
			</div>
		</div>
	</div>
	<div class="detailViewInfo">
		{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
		<table class="table table-bordered">
			<thead>
				<tr class="blockHeader">
					<th colspan="2" class="{$WIDTHTYPE} col-md-12"><strong>{'LBL_AIPROMPT_DETAIL'|t:$QUALIFIED_MODULE}</strong></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3"><label class="pull-right">{'LBL_NAME'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">{$RECORD_MODEL->getDisplayValue('name')}</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3"><label class="pull-right">{'LBL_ACTION_KEY'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">{$RECORD_MODEL->getDisplayValue('action_key')}</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3"><label class="pull-right">{'LBL_PLACEHOLDERS'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{foreach from=$PLACEHOLDERS item=PH name=phloop}
							<code>&#123;&#123;{$PH}&#125;&#125;</code>{if !$smarty.foreach.phloop.last}, {/if}
						{/foreach}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3"><label class="pull-right">{'LBL_ACTIVE'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">{$RECORD_MODEL->getDisplayValue('active')}</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3"><label class="pull-right">{'LBL_PROMPT_BODY'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8"><pre style="white-space:pre-wrap;margin:0;">{$RECORD_MODEL->getDisplayValue('prompt_body')}</pre></td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3"><label class="pull-right">{'LBL_MODIFIEDTIME'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">{$RECORD_MODEL->getDisplayValue('modifiedtime')}</td>
				</tr>
			</tbody>
		</table>
	</div>
			</div>
		</div>
{/block}
{/strip}
