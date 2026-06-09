{strip}
    <!-- tpl-Candidates-Modals-ImportCandidatesModal- -->
    <div class="modal-body">
        <form class="form-horizontal" name="action{$ACTION_NAME}" method="post" action="index.php">
            <input type="hidden" name="module" value="{$MODULE_NAME}" />
            <input type="hidden" name="action" value="{$ACTION_NAME}" />
            <table style="width:100%">
                <tbody>
				{App\Language::translate('LBL_IMPORT_CANDIDATES_MANUALLY',$MODULE_NAME)}
				</tbody>
            </table>
        </form>
    </div>
    <!-- /tpl-Candidates-Modals-ImportCandidatesModal- -->
{/strip}

