{*<!-- {[The file is published on the basis of YetiForce Public License 4.0 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
	<!-- tpl-Kandydaci-Modals-TransformCandidateToConsultant -->
	<div class="modal-body">
		<form class="form-horizontal" name="action{$ACTION_NAME}" method="post" action="index.php">
			{App\Language::translate('LBL_TRANSFORM_CANDIDATE_TO_CONSULTANT_DESC',$MODULE_NAME)}
                        <code>{$RECORD_STRUCTURE|@var_dump}</code> 
                    <table>
                        <tbody>
                            <tr><td><input type="hidden" name="module" value="{$MODULE_NAME}" /></td></tr>
                            <tr><td><input type="hidden" name="action" value="{$ACTION_NAME}" /></td></tr>
                            <tr><td><input type="hidden" name="record" value="{$RECORD_ID}" /></td></tr>
                            <tr><td><label class="font-weight-bold mb-0">Stanowisko</label></td><td><input type="text" name="" value="{$POSITION_NAME}" /></td></tr>
                            <tr><td><label class="font-weight-bold mb-0">Od kiedy zaczyna</label></td><td><input type="text" class="dateField datepicker form-control" name="" value="" /></td></tr>
                            <tr><td><label class="font-weight-bold mb-0">Klauzule</label></td><td><select name="" value=><option value="tak">tak</option><option value="nie">nie</option></select></td></tr>
                            <tr><td><label class="font-weight-bold mb-0">Forma współpracy</label></td><td><input type="text" name="" value="" /></td></tr>
                            <tr><td><label class="font-weight-bold mb-0">Stawka godzinowa brutto (lub netto z faktury) lub stawka miesięczna brutto w przypadku UoP:</label></td><td><input type="text" name="" value="" /></td></tr>
                            <tr><td><label class="font-weight-bold mb-0">Wymiar czasu pracy w miesiącu:</label></td><td><input type="text" name="" value="" /></td></tr>
                            <tr><td><label class="font-weight-bold mb-0">Kierownik/Lider Zespołu po stronie Klienta:</label></td><td><input type="text" class="name"  value="" /></td></tr>
                            <tr><td><label class="font-weight-bold mb-0">Kiedy ma być kontakt od nas:</label></td><td><input type="text" name="" value="" /></td></tr>
                            <tr><td><label class="font-weight-bold mb-0">Rodzaj umowy (Outsource/FixPrice/Try&Hire)</label></td><td><input type="text" name="" value="" /></td></tr>
                            <tr><td><label class="font-weight-bold mb-0">Czy ITC daje telefon?</label></td><td><select name="" value=><option value="tak">tak</option><option value="nie">nie</option></select></td></tr>
                            <tr><td><label class="font-weight-bold mb-0">Czy ITC daje laptop?</label></td><td><select name="" value=><option value="tak">tak</option><option value="nie">nie</option></select></td></tr>
                        </tbody>
                    </table>
		</form>
	</div> 
	<!-- /tpl-Kandydaci-Modals-TransformCandidateToConsultant -->
{/strip} 
    