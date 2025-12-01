<?php

/**
 * Create outsource offers action file.
 * Tworzenie oferty Mass Outsource na podstawie danych pobranych z Konsultanta, Kontaktów i Cenników 
 * @package   Action
 *
 * @copyright YetiForce Sp. z o.o
 * @license   YetiForce Public License 4.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Bartłomiej Mańkowski <bmankowski@itconnect.pl>
 */ 

/**
 * Create outsource offers action class.
 */
class Kandydaci_TransformDocumentToCV_Action extends Vtiger_Save_Action {

    public Documents_Record_Model $document;
    public Kandydaci_Record_Model $candidate;

    /** {@inheritdoc} */
    public function checkPermission(App\Request $request) {
        $this->candidate = $request->isEmpty('candidateId', true) ? null : Vtiger_Record_Model::getInstanceById($request->getInteger('candidateId'), $request->getModule());
        if (!$this->candidate || !$this->candidate->isViewable()) {
            throw new \App\Exceptions\NoPermittedToRecord('ERR_NO_PERMISSIONS_FOR_THE_RECORD', 406);
        }
        $this->document = $request->isEmpty('documentId', true) ? null : Vtiger_Record_Model::getInstanceById($request->getInteger('documentId'), "Documents");
        if (!$this->document || !$this->document->isViewable()) {
            throw new \App\Exceptions\NoPermittedToRecord('ERR_NO_PERMISSIONS_FOR_THE_RECORD', 406);
        }
    }

    /** Converting document to CV and loading it as an MultiAttachment
     * After adding document as a CV refresh Candidate's page */
    public function process(App\Request $request) {
        //Converting document to CV and loading it as an MultiAttachment
//        
        $this->candidate->transformDocumentToCV($this->document);
        $this->candidate->save();

        $loadUrl = $this->candidate->getDetailViewUrl();
        $response = new Vtiger_Response();
        $response->setResult(['redirect' => $loadUrl]);
        $response->emit();
    }
}
