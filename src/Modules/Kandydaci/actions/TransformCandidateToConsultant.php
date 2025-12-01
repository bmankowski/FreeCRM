<?php

/**
 * Create outsource offers action file.
 * Tworzenie oferty Mass Outsource na podstawie danych pobranych z Konsultanta, Kontaktów i Cenników 
 * @package   Action
 *
 * @copyright YetiForce Sp. z o.o
 * @license   YetiForce Public License 4.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Arkadiusz Sołek <a.solek@yetiforce.com>
 */

/**
 * Create outsource offers action class.
 */
class Kandydaci_TransformCandidateToConsultant_Action extends Vtiger_Save_Action {

    /** {@inheritdoc} */ 
    public function checkPermission(App\Request $request) {
          \App\Log::warning("Hello1");
        $this->record = $request->isEmpty('record', true) ? null : Vtiger_Record_Model::getInstanceById($request->getInteger('record'), $request->getModule());
        if (!$this->record || !$this->record->isViewable() || !Vtiger_Record_Model::getCleanInstance('OfertyMassOutsource')->isCreateable()) {
            throw new \App\Exceptions\NoPermittedToRecord('ERR_NO_PERMISSIONS_FOR_THE_RECORD', 406);
        }
    }
 
    /** {@inheritdoc} */
    public function process(App\Request $request) {
         
        
        
        $candidate = $this->record;
        
        \App\Log::warning("Hello2".$request->get("test"));
        
        $formData = ["hrm" => $request->get("hrm")];
        
        $newConsultant = $this->createConsultantFromCandidate($candidate,$formData);
        if ($newConsultant->isViewable()) {
            $loadUrl = $newConsultant->getDetailViewUrl();
        } else {
            $loadUrl = $consultant->getDetailViewUrl();
        }
        $response = new Vtiger_Response();
        $response->setResult(['redirect' => $loadUrl]);
        $response->emit();
    } 

    protected static function createConsultantFromCandidate($candidateRecordModel,$formData) {
        $newConsultant = Vtiger_Record_Model::getCleanInstance('Konsultanci');
        $newConsultant->set('candidate_id', $candidateRecordModel->getId());
        $newConsultant->set('name', $candidateRecordModel->get('name'));
        
        $newConsultant->set('test', $formData["hrm"]);

        
        
        
//        if (!empty($candidateRecordModel->get("remuneration_id")) && !empty($actualRemunerationTerms = Vtiger_Record_Model::getInstanceById($candidateRecordModel->get("remuneration_id"), 'RemunerationTerms'))) {
//            $newConsultant->set('stawka_do_wyplaty', $actualRemunerationTerms->get('stawka_dla_czlowieka'));
//            $newConsultant->set('rodzaj_stawki_dla_czlowieka', $actualRemunerationTerms->get('rodzaj_stawki_dla_czlowieka'));
//            $newConsultant->set('dodatki', $actualRemunerationTerms->get('dodatki'));
//            $newConsultant->set('dodatek_komputer', $actualRemunerationTerms->get('stawka_za_komputer')); // TEGO POLA JUŻ NIE MA W RENUMERATION
//            $newConsultant->set('dodatek_telefon', $actualRemunerationTerms->get('stawka_za_telefon'));
//            $newConsultant->set('wymiar_czasu_pracy', $actualRemunerationTerms->get('wymiar_czasu_pracy'));
//            if (!empty($adresat = $actualRemunerationTerms->get('hrm_id'))) { // TEGO POLA JUŻ NIE MA W RENUMERATION
//                $recordModelAdresat = Vtiger_Record_Model::getInstanceById($adresat, 'Contacts');
//                $newConsultant->set('adresat', $recordModelAdresat->get('firstname') . ' ' . $recordModelAdresat->get('lastname'));
//                if ($recordModelAdresat->get('departament')) {
//                    $templateId = $recordModelAdresat->getField('departament')->getFieldParams();
//                    $departamentTreeData = \App\Fields\Tree::getValueByTreeId($templateId, $recordModelAdresat->get('departament'));
//                    if ($departamentTreeData['depth'] > 0) {
//                        $pieces = explode('::', $departamentTreeData['parentTree']);
//                        array_pop($pieces);
//                        $parent = end($pieces);
//                        $pionTreeData = \App\Fields\Tree::getValueByTreeId($templateId, $parent);
//                        $newConsultant->set('pion', $pionTreeData['label']); // OfertyMassOutsource(pion)  -> text,  Contacts(departament)  -> tree
//                    }
//                    $newConsultant->set('departament', $departamentTreeData['label']); // OfertyMassOutsource(departament)  -> text,  Contacts(departament)  -> tree
//                }
//            }
//            if (!empty($zamawiajacyId = $actualRemunerationTerms->get('zamawiajacy_id'))) {
//                $recordModelZamawiajacy = Vtiger_Record_Model::getInstanceById($zamawiajacyId, 'Contacts');
//                $newConsultant->set('zamawiajacy_tekst', $recordModelZamawiajacy->get('firstname') . ' ' . $recordModelZamawiajacy->get('lastname'));
//                $newConsultant->set('email_zamawiajacego', $recordModelZamawiajacy->get('email'));
//                $newConsultant->set('zamawiajacy', $zamawiajacyId);
//            }
//            if (!empty($cennikmo = $actualRemunerationTerms->get('cennikmo_id'))) {
//                $newConsultant->set('cennikid', $cennikmo); //OfertyMassOutsource(cennikid)  -> słownikowe CennikOrangeMassOutsource(id) -> słownikowe
//            }
//        }

        $newConsultant->save();
        $newConsultant->clearPrivilegesCache();
        return $newConsultant;
    }

}
    