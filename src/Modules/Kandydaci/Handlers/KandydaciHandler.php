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

use App\Events\EventHandler;

class KandydaciHandler {

    public function detailViewBefore(EventHandler $eventHandler): void {
        $params = $eventHandler->getParams();
        $recordModel = $eventHandler->getRecordModel();
    }

    public function entityBeforeSave(EventHandler $eventHandler): void {

        /** @var \App\Modules\Kandydaci\Models\Record $candidate */
        $candidate = $eventHandler->getRecordModel();
        $cvImgFieldName = "cv_img_file";
        if (!empty($candidate->get($cvImgFieldName))) {
            return;
        }
        $candidate->searchAndtransformDocumentToCV();

    }
}
