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
 
 


class Kandydaci_RefreshDocsNumber_Handler {

    public function entityAfterLink(App\EventHandler $eventHandler) {
        $this->performCalculations($eventHandler);
    }

    public function entityAfterUnLink(App\EventHandler $eventHandler) {
        $this->performCalculations($eventHandler);
    }
 
    private function performCalculations(App\EventHandler $eventHandler) {
        $params = $eventHandler->getParams();
         
        //Obliczanie ilości dokumentów dowiązanych do Kandydata
        if ($params['sourceModule'] == "Kandydaci" && $params['destinationModule']=="Documents")
        {
            $kandydatId = $params["sourceRecordId"];
            $kandydatRecordModel = Vtiger_Record_Model::getInstanceById($kandydatId, 'Kandydaci');

            $count = (new \App\Db\Query())->from('vtiger_notes')->
                    innerJoin('vtiger_senotesrel', 'vtiger_senotesrel.notesid = vtiger_notes.notesid')->
                    innerJoin('vtiger_crmentity crm', 'crm.crmid = vtiger_senotesrel.crmid')->
                    innerJoin('vtiger_crmentity crm2', 'crm2.crmid = vtiger_notes.notesid')->
                    leftJoin('vtiger_seattachmentsrel', 'vtiger_seattachmentsrel.crmid = vtiger_notes.notesid')->
                    leftJoin('vtiger_attachments', 'vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid')->
                    innerJoin('vtiger_users', 'crm2.smcreatorid = vtiger_users.id')->
                    innerJoin('u_yf_kandydaci vr', 'vr.kandydaciid=vtiger_senotesrel.crmid')->
                    where(['vr.kandydaciid' => $kandydatId])->
                    andWhere(['crm2.deleted' => '0'])->
                    count();
            
            $kandydatRecordModel->set("ilosc_dokumentow_kandydata",$count);
            $kandydatRecordModel->save();
        }        
    }
}
