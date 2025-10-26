{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/ButtonViewLinks.tpl -->
	{if $LINKS && is_array($LINKS) && count($LINKS) gt 0}
		{assign var=TEXT_HOLDER value=''}
		{foreach item=LINK from=$LINKS}
			{assign var=LINK_PARAMS value=vtlib\Functions::getQueryParams($LINK->getUrl())}
			{if $smarty.get.module == $LINK_PARAMS['module'] && $smarty.get.view == $LINK_PARAMS['view']}
				{assign var=TEXT_HOLDER value=$LINK->getLabel()}
			{/if} 
		{/foreach}
		
		{if isset($BTN_GROUP) && !$BTN_GROUP}<div class="btn-group buttonTextHolder {if isset($CLASS)}{$CLASS}{/if}">{/if} 
			<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
				<span class="glyphicon glyphicon-list" aria-hidden="true"></span>
				&nbsp;
				<span class="textHolder">{$TEXT_HOLDER|t:$MODULE_NAME}</span>
				&nbsp;<span class="caret"></span>
			</button>
			<ul class="dropdown-menu">
				{foreach item=LINK from=$LINKS}
					<li>
						<a class="quickLinks" href="{$LINK->getUrl()}">
							{$LINK->getLabel()|t:$MODULE_NAME}
						</a>
					</li>
				{/foreach}
			</ul>
			{if isset($BTN_GROUP) && !$BTN_GROUP}</div>{/if} 
		{/if} 
<!--/layouts/basic/modules/Base/ButtonViewLinks.tpl -->
	{/strip}
