{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/DocumentTemplates/EditHeader.tpl -->
	<div class="row widget_header">
		<div class="col-xs-12">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			{"LBL_TEMPLATE_DESCRIPTION"|t:$QUALIFIED_MODULE}
		</div>
	</div>
	<div class="editContainer">
		<div id="wizardSteps" class="current-{$CURRENT_STEP}">
			<ul class="crumbs marginLeftZero">
				<li class="first step zIndex8 {if $CURRENT_STEP eq 'step1'}active{/if}" id="step1">
					<a href="{if $RECORDID}index.php?module={$MODULE}amp;view=Edit&amp;record={$RECORDID}&amp;mode=Step1{else}index.php?module={$MODULE}amp;view=Edit&amp;mode=Step1{/if}">
						<span class="stepNum">1</span>
						<span class="stepText">{"LBL_DOCUMENT_DESCRIPTION"|t:$QUALIFIED_MODULE}</span>
					</a>
				</li>
				<li class="step zIndex7 {if $CURRENT_STEP eq 'step2'}active{/if}" id="step2">
					<a href="{if $RECORDID}index.php?module={$MODULE}amp;view=Edit&amp;record={$RECORDID}&amp;mode=Step2{else}#{/if}">
						<span class="stepNum">2</span>
						<span class="stepText">{"LBL_DOCUMENT_SETTINGS"|t:$QUALIFIED_MODULE}</span>
					</a>
				</li>
				<li class="step zIndex6 {if $CURRENT_STEP eq 'step3'}active{/if}" id="step3">
					<a href="{if $RECORDID}index.php?module={$MODULE}amp;view=Edit&amp;record={$RECORDID}&amp;mode=Step3{else}#{/if}">
						<span class="stepNum">3</span>
						<span class="stepText">{"LBL_DOCUMENT_CONTENT"|t:$QUALIFIED_MODULE}</span>
					</a>
				</li>
				<li class="step zIndex5 {if $CURRENT_STEP eq 'step4'}active{/if}" id="step4">
					<a href="{if $RECORDID}index.php?module={$MODULE}amp;view=Edit&amp;record={$RECORDID}&amp;mode=Step4{else}#{/if}">
						<span class="stepNum">4</span>
						<span class="stepText">{"LBL_DOCUMENT_FILTERS"|t:$QUALIFIED_MODULE}</span>
					</a>
				</li>
				<li class="step zIndex4 {if $CURRENT_STEP eq 'step5'}active{/if}" id="step5">
					<a href="{if $RECORDID}index.php?module={$MODULE}amp;view=Edit&amp;record={$RECORDID}&amp;mode=Step5{else}#{/if}">
						<span class="stepNum">5</span>
						<span class="stepText">{"LBL_DOCUMENT_PERMISSIONS"|t:$QUALIFIED_MODULE}</span>
					</a>
				</li>
				<li class="step last zIndex3 {if $CURRENT_STEP eq 'step6'}active{/if}" id="step6">
					<a href="{if $RECORDID}index.php?module={$MODULE}amp;view=Edit&amp;record={$RECORDID}&amp;mode=Step6{else}#{/if}">
						<span class="stepNum">6</span>
						<span class="stepText">{"LBL_DOCUMENT_WATERMARK"|t:$QUALIFIED_MODULE}</span>
					</a>
				</li>
			</ul>
		</div>
		<div class="clearfix"></div>
	</div>
<!--/layouts/basic/modules/DocumentTemplates/EditHeader.tpl -->
{/strip}
