{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/PDF/EditHeader.tpl -->
	<div class="row widget_header">
		<div class="col-xs-12">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			{"LBL_PDF_DESCRIPTION"|t:$QUALIFIED_MODULE}
		</div>
	</div>
	<div class="editContainer">
		<div id="breadcrumb">
			<ul class="crumbs marginLeftZero">
				<li class="first step zIndex8" id="step1">
					<a>
						<span class="stepNum">1</span>
						<span class="stepText">{"LBL_DOCUMENT_DESCRIPTION"|t:$QUALIFIED_MODULE}</span>
					</a>
				</li>
				<li class="step zIndex7" id="step2">
					<a>
						<span class="stepNum">2</span>
						<span class="stepText">{"LBL_DOCUMENT_SETTINGS"|t:$QUALIFIED_MODULE}</span>
					</a>
				</li>
				<li class="step zIndex6" id="step3">
					<a>
						<span class="stepNum">3</span>
						<span class="stepText">{"LBL_DOCUMENT_HEADER"|t:$QUALIFIED_MODULE}</span>
					</a>
				</li>
				<li class="step zIndex5" id="step4">
					<a>
						<span class="stepNum">4</span>
						<span class="stepText">{"LBL_DOCUMENT_BODY"|t:$QUALIFIED_MODULE}</span>
					</a>
				</li>
				<li class="step zIndex4" id="step5">
					<a>
						<span class="stepNum">5</span>
						<span class="stepText">{"LBL_DOCUMENT_FOOTER"|t:$QUALIFIED_MODULE}</span>
					</a>
				</li>
				<li class="step zIndex3" id="step6">
					<a>
						<span class="stepNum">6</span>
						<span class="stepText">{"LBL_DOCUMENT_EXCEPTIONS"|t:$QUALIFIED_MODULE}</span>
					</a>
				</li>
				<li class="step zIndex2" id="step7">
					<a>
						<span class="stepNum">7</span>
						<span class="stepText">{"LBL_DOCUMENT_PERMISSIONS"|t:$QUALIFIED_MODULE}</span>
					</a>
				</li>
				<li class="step last zIndex1" id="step8">
					<a>
						<span class="stepNum">8</span>
						<span class="stepText">{"LBL_DOCUMENT_WATERMARK"|t:$QUALIFIED_MODULE}</span>
					</a>
				</li>
			</ul>
		</div>
		<div class="clearfix"></div>
	</div>
<!--/layouts/basic/modules/Settings/PDF/EditHeader.tpl -->
{/strip}
