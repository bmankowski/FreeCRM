{*<!-- {[The file is published on the basis of YetiForce Public License 5.0 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
<!-- tpl-Base-DetailViewSummaryView -->
{strip}
    <div>
        {include file=$DETAIL_BLOCK_LINK_TOP_TEMPLATE TYPE_VIEW='SummaryTop'}
    </div>

    <div class="o-detail-widgets row no-gutters mx-n1">
        {if !empty($DETAILVIEW_WIDGETS[3])}
            {assign var=span value='4'}
        {elseif !empty($DETAILVIEW_WIDGETS[2])}
            {assign var=span value='6'}
        {else}
            {assign var=span value='12'}
        {/if}
        {foreach item=WIDGETCOLUMN from=$DETAILVIEW_WIDGETS}
            <div class="col-md-{$span} px-1">
                {foreach key=key item=WIDGET from=$WIDGETCOLUMN}
                    {assign var=FILE value='Detail/Widget/'|cat:$WIDGET['tpl']}
                    {include file=$WIDGET['detailWidgetTemplate']}
                {/foreach}
            </div>
        {/foreach}
    </div>
    <div>
        {include file=$DETAIL_BLOCK_LINK_BOTTOM_TEMPLATE TYPE_VIEW='SummaryBottom'}
    </div>
    <!-- /tpl-Base-DetailViewSummaryView -->
{/strip}
