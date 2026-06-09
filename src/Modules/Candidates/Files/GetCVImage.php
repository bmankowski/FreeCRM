<?php

namespace App\Modules\Candidates\Files;

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
     * @param \App\Http\Vtiger_Request $request
     *
     * @return bool
     */
    public function getCheckPermission(\App\Http\Vtiger_Request $request) {
        if (!\App\Security\Privilege::isPermitted($request->getModule(), 'DetailView', $request->getInteger('record'))) {
            throw new \App\Exceptions\NoPermittedToRecord('ERR_NO_PERMISSIONS_FOR_THE_RECORD', 406);
        }
        return true;
    }

    /**
     * Download file.
     *
     * @param \App\Http\Vtiger_Request $request
     *
     * @return string|bool
     */
    public function get(\App\Http\Vtiger_Request $request) {
        $this->getCVImage($request);
        return false;
    }

    /**
     * Api function to get file.
     *
     * @param \App\Http\Vtiger_Request $request
     *
     * @return \App\Fields\File
     */
    public function api(\App\Http\Vtiger_Request $request): ?\App\Fields\File {
        return null;
    }

 

    public static function getCVImage($request) {
        
        $candidateId = filter_var($request->getInteger('record'), FILTER_VALIDATE_INT);
        $candidateRecordModel = \App\Modules\Candidates\Models\Record::getInstanceById($candidateId, $request->getModule());
        $pathInJSON = $candidateRecordModel->get("cv_img_file");
        $items = \App\Utils\Json::decode($pathInJSON);
        $item = is_array($items) ? reset($items) : null;
        if (!is_array($item)) {
            \App\Log\Log::warning('Missing or invalid cv_img_file payload');
            return;
        }
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
            \App\Log\Log::warning("The file does not exist");
        }
    }
}
