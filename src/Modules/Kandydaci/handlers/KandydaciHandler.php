<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of RefreshDocsNumber
 *
 * @author bmankowski
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




class Kandydaci_KandydaciHandler_Handler {

    public function detailViewBefore(App\EventHandler $eventHandler) {
        $params = $eventHandler->getParams();
        $recordModel = $eventHandler->getRecordModel();
    }

    /**
     *
     * @param App\EventHandler $eventHandler
     * @return type
     */
    public function entityBeforeSave(App\EventHandler $eventHandler) {

        /** @var Kandydaci_Record_Model $candidate */
        $candidate = $eventHandler->getRecordModel();
        $cvImgFieldName = "cv_img_file";
        if (!empty($candidate->get($cvImgFieldName))) {
            return;
        }
        $candidate->searchAndtransformDocumentToCV();

    }
}
