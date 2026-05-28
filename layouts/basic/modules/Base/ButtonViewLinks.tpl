{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
	<!-- layouts/basic/modules/Base/ButtonViewLinks.tpl -->
	{if $LINKS && is_array($LINKS) && count($LINKS) gt 0}
		{if isset($BTN_GROUP) && !$BTN_GROUP}<div class="btn-group buttonTextHolder {if isset($CLASS)}{$CLASS}{/if}">{/if}
			<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
				<span class="glyphicon glyphicon-list" aria-hidden="true"></span>
				&nbsp;
				<span class="textHolder">{if isset($ACTIVE_SIDEBAR_LINK)}{$ACTIVE_SIDEBAR_LINK|t:$MODULE_NAME}{/if}</span>
				&nbsp;<span class="caret"></span>
			</button>
			<ul class="dropdown-menu">
				{foreach item=LINK from=$LINKS}
					<li{if $LINK->get('active')} class="active" {/if}>
						<a class="quickLinks" href="{$LINK->getUrl()}">
							{$LINK->getLabel()|t:$MODULE_NAME}
						</a>
						</li>
					{/foreach}
			</ul>
			{if isset($BTN_GROUP) && !$BTN_GROUP}
		</div>{/if}
	{/if}
	<!--/layouts/basic/modules/Base/ButtonViewLinks.tpl -->
{/strip}