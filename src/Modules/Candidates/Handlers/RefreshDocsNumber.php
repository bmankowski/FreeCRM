<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of RefreshDocsNumber
 *
 * @author    Bartłomiej Mańkowski <bmankowski@itconnect.pl>
 */
/*
  $data = [
  'CRMEntity' => $this->getParentModuleModel()->getEntityInstance(),
  'sourceModule' => $sourceModuleName,
  'sourceRecordId' => $sourceRecordId,
  'destinationModule' => $this->getRelationModuleModel()->getName(),
  'relationId' => $this->getId(),
  ];
  
 * $data['destinationRecordId'] = $destinationRecordId;
			
 * $eventHandler->setParams($data);
 * 

 */
 
 


namespace App\Modules\Candidates\Handlers;

class RefreshDocsNumber {

    public function entityAfterLink(\App\Events\EventHandler $eventHandler) {
        $this->performCalculations($eventHandler);
    }

    public function entityAfterUnLink(\App\Events\EventHandler $eventHandler) {
        $this->performCalculations($eventHandler);
    }
 
    private function performCalculations(\App\Events\EventHandler $eventHandler) {
        $params = $eventHandler->getParams();
         
        if ($params['sourceModule'] == "Candidates" && $params['destinationModule']=="Documents")
        {
            $candidateId = $params["sourceRecordId"];
            $candidateRecord = \App\Modules\Base\Models\Record::getInstanceById($candidateId, 'Candidates');

            $count = (new \App\Db\Query())->from('vtiger_notes')->
                    innerJoin('vtiger_senotesrel', 'vtiger_senotesrel.notesid = vtiger_notes.notesid')->
                    innerJoin('vtiger_crmentity crm', 'crm.crmid = vtiger_senotesrel.crmid')->
                    innerJoin('vtiger_crmentity crm2', 'crm2.crmid = vtiger_notes.notesid')->
                    innerJoin('vtiger_users', 'crm2.smcreatorid = vtiger_users.id')->
                    innerJoin('u_yf_candidates vr', 'vr.candidatesid=vtiger_senotesrel.crmid')->
                    where(['vr.candidatesid' => $candidateId])->
                    andWhere(['crm2.deleted' => '0'])->
                    count();
            
            $candidateRecord->set("documents_count",$count);
            $candidateRecord->save();
        }        
    }
}
