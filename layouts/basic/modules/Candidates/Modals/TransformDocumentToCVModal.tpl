{*<!-- {[The file is published on the basis of YetiForce Public License 4.0 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
	<!-- tpl-Candidates-Modals-TransformDocumentToCVModal -->
	<div class="modal-body">
		<form class="form-horizontal" name="action{$ACTION_NAME}" method="post" action="index.php">
			{App\Language::translate('LBL_TRANSFORM_DOCUMENT_TO_CV_DESC',$MODULE_NAME)}
                    <table>
                        <tbody>
                            <tr><td><input type="hidden" name="module" value="{$MODULE_NAME}" /></td></tr>
                            <tr><td><input type="hidden" name="action" value="{$ACTION_NAME}" /></td></tr>
                            <tr><td><input type="hidden" name="candidateId" value="{$CANDIDATE_ID}" /></td></tr>
                            <tr><td><input type="hidden" name="documentId" value="{$DOCUMENT_ID}" /></td></tr>
                        </tbody>
                    </table>
			{include file=vtemplate_path('ModalFooter.tpl','Base')}
		</form>
	</div> 
	<!-- /tpl-Candidates-Modals-TransformDocumentToCVModal -->
{/strip} 
    