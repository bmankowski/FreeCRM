<?php

namespace App\Modules\Kandydaci\Files;

/**
 * DownloadFile class to handle files.
 *
 * @copyright YetiForce S.A.
 * @license YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Bartłomiej Mańkowski <bmankowski@itconnect.pl>
 */

/**
 * DownloadFile class to handle files.
 */
class GetCVImage extends \App\Modules\Base\Files\File {

    /**
     * Checking permission in get method.
     *
     * @param \App\Request $request
     *
     * @return bool
     */
    public function getCheckPermission(App\Request $request) {
        if (!\App\Privilege::isPermitted($request->getModule(), 'DetailView', $request->getInteger('record'))) {
            throw new \App\Exceptions\NoPermittedToRecord('ERR_NO_PERMISSIONS_FOR_THE_RECORD', 406);
        }
        return true;
    }

    /**
     * Download file.
     *
     * @param \App\Request $request
     *
     * @return string|bool
     */
    public function get(App\Request $request) {
//        $kandydaciRecordModel = Kandydaci_Record_Model::getInstanceById($request->getInteger('record'), $request->getModule());
        //Download the file
//        $kandydaciRecordModel->set('show', $request->getBoolean('show'));
        $this->getCVImage($request);
//		//Update the Download Count
//		$kandydaciRecordModel->updateDownloadCount();
        return false;
    }

    /**
     * Api function to get file.
     *
     * @param App\Request $request
     *
     * @return \App\Fields\File
     */
    public function api(App\Request $request): App\Fields\File {
//		$documentRecordModel = Kandydaci_Record_Model::getInstanceById($request->getInteger('record'), $request->getModule());
//		//Download the file
//		$documentRecordModel->set('return', true);
//		$file = $documentRecordModel->downloadFile();
//		//Update the Download Count
//		$documentRecordModel->updateDownloadCount();
//		return $file;

        return null;
    }

 

    public static function getCVImage($request) {
        
        $candidateId = filter_var($request->getInteger('record'), FILTER_VALIDATE_INT);
         $kandydatRecordModel = \App\Modules\Kandydaci\Models\Record::getInstanceById($candidateId, $request->getModule());
//        $kandydatRecordModel = \App\Modules\Kandydaci\Models\Record::getInstanceById(1261772, "Kandydaci");
        $pathInJSON = $kandydatRecordModel->get("cv_img_file");
        $item = reset(\App\Json::decode($pathInJSON));
        $file = \App\Fields\File::loadFromInfo([
                    'path' => ROOT_DIRECTORY . DIRECTORY_SEPARATOR . $item['path'],
                    'name' => $item['name'],
        ]);

        if (file_exists($file->getPath())) {
            header('Pragma: cache');
            header('Cache-control: max-age=86400, public');
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
            header('Content-type: ' . $file->getMimeType());
            header('Content-transfer-encoding: binary');
            header('Content-length: ' . $file->getSize());
            header('Content-disposition: inline; filename="' . $item['name'] . '"');
//            \App\Log::warning($file->getPath().$file->getName());
            readfile($file->getPath().$file->getName());
        }else{
            \App\Log::warning("The file does not exist");
        }
    }
}
