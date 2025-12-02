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




namespace App\Modules\Kandydaci\Handlers;

class KandydaciHandler {

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

        /** @var \App\Modules\Kandydaci\Models\Record $candidate */
        $candidate = $eventHandler->getRecordModel();
        $cvImgFieldName = "cv_img_file";
        if (!empty($candidate->get($cvImgFieldName))) {
            return;
        }
        $candidate->searchAndtransformDocumentToCV();

    }
}
