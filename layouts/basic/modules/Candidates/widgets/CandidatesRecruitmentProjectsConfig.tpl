{*<!-- {[The file is published on the basis of YetiForce Public License 6.5 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
<!-- layouts/basic/modules/Candidates/widgets/CandidatesRecruitmentProjectsConfig.tpl -->
<div class="modal fade" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<form class="form-modalAddWidget">
				<input type="hidden" name="wid" value="{$WID}">
				<input type="hidden" name="type" value="{$TYPE}">
				<div class="modal-header">
					<button type="button" data-dismiss="modal" class="close" title="{"LBL_CLOSE"|t:$QUALIFIED_MODULE}">×</button>
					<h3 id="massEditHeader" class="modal-title">{"Add widget"|t:$QUALIFIED_MODULE}</h3>
				</div>
				<div class="modal-body">
					<div class="modal-Fields">
						<div class="row">
							<div class="col-md-3 marginLeftZero"><strong>{"Type widget"|t:$QUALIFIED_MODULE}</strong>:</div>
							<div class="col-md-7">
								{$TYPE|t:$QUALIFIED_MODULE}
							</div>
							<div class="col-md-3 marginLeftZero"><label class="">{"Label"|t:$QUALIFIED_MODULE}:</label></div>
							<div class="col-md-7"><input name="label" class="form-control" type="text" value="{$WIDGETINFO['label']}" /></div>
						</div>
					</div>
				</div>
				{include file='ModalFooter.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
			</form>
		</div>
	</div>
</div>
<!--/layouts/basic/modules/Candidates/widgets/CandidatesRecruitmentProjectsConfig.tpl -->
{/strip}
