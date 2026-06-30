<?php

namespace App\Modules\Candidates\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce S.A.
 * *********************************************************************************** */

/**
 * Class Documents_Record_Model.
 */
class Record extends \App\Modules\Base\Models\Record {


	public function getRecruitmentProjectsWithStatus() {
		$query = "select rel.crmid project_id, relcrmid candidate_id, u.nazwa_projektu, recruitment_status_rel, comment_rel, rel_created_time,rel_created_user
from u_yf_projekty_rekrutacyjne_relations_members_entity rel
         inner join vtiger_crmentity e on rel.crmid = e.crmid
inner join u_yf_projektyrekrutacyjne u on rel.crmid = u.projektyrekrutacyjneid
where e.deleted = 0
and relcrmid=".$this->getId();
		$connection = \App\Db\Db::getInstance();
		$command = $connection->createCommand($query);
		$rows = $command->queryAll();
		return $rows;
	}
 
    public function getCVPathname(): string {
        if (!empty($value = $this->get("cv_img_file"))) {
            $jsonData = \App\Utils\Json::decode($value);
//            TODO Check if file exists
            if (!is_array($jsonData) || !isset($jsonData[0]['key'])) {
                return "";
            }

            return '/file.php?module=Candidates&action=MultiAttachment&field=cv_img_file&record='
                . $this->getId() . '&key=' . rawurlencode((string) $jsonData[0]['key']);
        }

        return "";
    }

	/**
	 * @return array<string, mixed>|null
	 */
	public function getCvImgFileItem(): ?array
	{
		$value = $this->get('cv_img_file');
		if ($value === '' || $value === null) {
			return null;
		}
		$items = \App\Utils\Json::decode((string) $value);
		if (!\is_array($items) || !isset($items[0]) || !\is_array($items[0])) {
			return null;
		}

		return $items[0];
	}

	public function getCurrentCvSourceDocumentId(): ?int
	{
		$item = $this->getCvImgFileItem();
		if ($item === null) {
			return null;
		}
		if (!empty($item['sourceDocumentId'])) {
			$id = (int) $item['sourceDocumentId'];
			return $id > 0 ? $id : null;
		}
		$name = (string) ($item['name'] ?? '');
		if (preg_match('/^(\d+)_[0-9a-f]{8}$/i', $name, $matches)) {
			$id = (int) $matches[1];
			return $id > 0 ? $id : null;
		}

		return null;
	}

	public function resolveCvImgFileAbsolutePath(): ?string
	{
		$item = $this->getCvImgFileItem();
		if ($item === null || empty($item['key']) || empty($item['path'])) {
			return null;
		}
		$path = \App\Modules\Candidates\Files\MultiAttachment::resolveAbsolutePath($item, (string) $item['key']);

		return $path !== null && is_readable($path) ? $path : null;
	}

    /**
     * Used for scripting purposes only.
     * @return null
     */
    public function getDocumentThatSeemsToBeCV() {
        $candidateId = $this->getId();
        if (empty($candidateId)) {
            return;
        }
        $query = " select x.candidateid, x.notesid, x.storage_path, x.original_name, x.mime_type
            from
            (
            select e.crmid as candidateId, dokument.notesid, dokument.storage_path, dokument.original_name, dokument.mime_type, row_number() over (partition by e.crmid order by kandydat_dokument.notesid desc) as ord_number
            from vtiger_crmentity e inner join vtiger_senotesrel kandydat_dokument on (e.crmid=kandydat_dokument.crmid)
            inner join vtiger_notes dokument on (kandydat_dokument.notesid=dokument.notesid)
            where dokument.original_name not rlike 'PRO[0-9]'
            and dokument.original_name not rlike 'PRO_[0-9]'
            and dokument.original_name not like '%umowa%'
            and dokument.original_name not like '%zgoda%'
            and dokument.original_name not like '%zgody%'
            and dokument.original_name not like '%skierowania%'
            and dokument.original_name not like '%adzczenie%'
            and dokument.original_name not like '%sprzet.jpg%'
            and dokument.original_name not like '%obowi%'
            and dokument.original_name not like '%szablon%'
            and dokument.original_name <> 'NDA.pdf'
            and dokument.title not like '%kwestionariusz%'
            and dokument.title not like '%aneks%'
            and dokument.title not like '%blind%'
            and dokument.title not like '%maile%'
            and dokument.title not like '%umowa%'
            and dokument.title not like '%list%'
            and dokument.title not like '%certyfikat%'
            and dokument.title not like '%szablon%'
            and dokument.title not like '%informacyjny%'
            and dokument.title not like '%zgoda%'
            and lower(dokument.title) <> 'lm'
            and lower(dokument.title) <> 'oświadczenie'
            and lower(dokument.title) <> 'raport'
            and lower(dokument.title) <> 'zaświadczenie'
            and lower(dokument.title) <> 'referencje'
            and lower(dokument.title) <> 'portfolio'
            and lower(dokument.title) <> 'rodo'
            and lower(dokument.title) <> 'projekty'
            and lower(dokument.title) <> 'ml'
            and lower(dokument.title) <> 'cl'
            and lower(dokument.title) not like  'znajomość'
            and lower(dokument.title) not like  '%oferta%'
            and lower(dokument.title) not like  '%rekomendacja%'
            and lower(dokument.title) not like  '%formularz aplika%'
            and lower(dokument.title) not like  '%zadanie rekru%'
            and lower(dokument.title) not like  '%wniosek%'
            and lower(dokument.title) not like  '%opis załącznikó%'
            and lower(dokument.title) not like  '%Projekty Kandydata%'
            and lower(dokument.title) not like  '%PRO %'
            and lower(dokument.title) not like  '%załącznik%'
            and lower(dokument.title) not like  '%zadania%'
            and lower(dokument.title) not like  '%l.szkoleń%'
            and lower(dokument.title) not like  '%lm_%'
            and lower(dokument.title) not like  '%lm %'
            and lower(dokument.title) not like  '%wpis do%'
            and lower(dokument.title) not like  '%profil zawodow%'
            and lower(dokument.title) not like  '%znajomość jęz%'
            and lower(dokument.title) not like  '%potwierdzenie_prac%'
            and dokument.title not like '%zgoda%'
            and dokument.title not like '%Potwierdzenia%'
            and lower(substr(dokument.original_name,-4)) in ('.pdf','docx','.doc','.jpg','.gif','.png')
            and dokument.location_type = 'internal'
            and dokument.active = 1
            and dokument.storage_path is not null
            and e.setype='Candidates'
            and e.crmid = $candidateId
            ) as x
            where x.ord_number = 1";

//        \App\Log::var_dump($query);
        $connection = \App\Db\Db::getInstance();
        $command = $connection->createCommand($query);
        $rows = $command->queryOne();
        if (!$rows) {

            return null;
        }
        $documentRecordModel = \App\Modules\Documents\Models\Record::getInstanceById($rows["notesid"], "Documents");
        return $documentRecordModel;
    }


    public function searchAndtransformDocumentToCV() {
        $document = $this->getDocumentThatSeemsToBeCV();
        if (empty($document)) {
            return;
        }
        $this->transformDocumentToCV($document);
    }

    /**
     * Upload and save attachment.
     * @return array
     */
    public function transformDocumentToCV($document) {
		if (empty($document)) {
			return;
		}
        $hash = hash('crc32', hrtime(true));
        $storagePath = (string) $document->get('storage_path');
        $srcPath = \App\Modules\Documents\Models\Record::resolveStoragePath(
            $storagePath,
            (string) ($document->get('original_name') ?: null)
        );
        if ($srcPath === false || !is_file($srcPath)) {
            \App\Log\Log::warning('[transformDocumentToCV] Cannot locate source document file. ' . \App\Utils\Json::encode([
                'candidateId' => $this->getId(),
                'documentId' => $document->getId(),
                'storage_path' => $storagePath,
                'computedPath' => $srcPath,
            ]));
            return;
        }
        $filenameBase = $document->getId() . "_" . $hash;
        $tmpDir = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }
        $originalExt = strtolower((string) pathinfo((string) ($document->get('original_name') ?? ''), PATHINFO_EXTENSION));
        $tmpSourcePath = $tmpDir . $filenameBase . ($originalExt ? ('.' . $originalExt) : '');
        // Locating the file and copying to temporary location
        if (!copy($srcPath, $tmpSourcePath)) {
            $errors = error_get_last();
            \App\Log\Log::warning('[transformDocumentToCV] copy() failed. ' . \App\Utils\Json::encode([
                'srcPath' => $srcPath,
                'pathToFile' => $tmpSourcePath,
                'error' => $errors,
            ]));
            return;
        }
        $finalPath = $tmpSourcePath;
        switch ($document->get('mime_type')) {
            case "application/msword":
            case "application/vnd.openxmlformats-officedocument.wordprocessingml.document":
                // doc/docx -> pdf (same base name), then fall through to pdf -> jpg
                @shell_exec('doc2pdf ' . escapeshellarg($finalPath));
                $pdfPath = preg_replace('/\.[^.]+$/', '', $finalPath) . '.pdf';
                if (is_string($pdfPath) && file_exists($pdfPath)) {
                    $finalPath = $pdfPath;
                }
                // no break
            case "application/pdf":
                // pdf -> jpg (single combined image), stored as a real *.jpg file (important for mime detection and <img> rendering)
                $base = preg_replace('/\.[^.]+$/', '', $finalPath);
                $jpgOut = $base . '.jpg';
                @shell_exec('pdftoppm -jpeg -r 100 ' . escapeshellarg($finalPath) . ' ' . escapeshellarg($base));
                @shell_exec('convert -trim -bordercolor white -border 20 ' . escapeshellarg($base) . '-*.jpg -append ' . escapeshellarg($jpgOut));
                if (file_exists($jpgOut)) {
                    $finalPath = $jpgOut;
                }
                break;
            case "image/jpeg":
            case "image/gif":
            case "image/png":
                // Keep as-is
                break;
            default:
                return;
        }

        $file = \App\Fields\File::loadFromPath($finalPath);
        $file->getSize();
        $file->getMimeType();
        $file->getExtension();
        $key = hash_file('sha256', $finalPath);

        $fieldName = "cv_img_file";
        // Store CV image in standard storage path (relative to ROOT_DIRECTORY).
        $uploadFilePath = \vtlib\Functions::initStorageFileDirectory('MultiImage');

        $destPath = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . $uploadFilePath . $key;
        if (!copy($finalPath, $destPath)) {
            $errors = error_get_last();
            \App\Log\Log::warning('[transformDocumentToCV] copy() to storage failed. ' . \App\Utils\Json::encode($errors));
            return;
        }
        $newFile = [[
        'name' => $file->getSanitizeName(),
        'size' => $file->getSize(),
        'key' => $key,
        'path' => $uploadFilePath,
        'type' => $file->getMimeType(),
        'sourceDocumentId' => (int) $document->getId(),
        ]];
        $fieldValue = \App\Utils\Json::encode($newFile);
        $this->set($fieldName, $fieldValue);
    }

    /**
     * Function serve script use only
     * @param int $candidateId
     * @param int $attachmentsid
     * @param string $dbFilename
     * @param string $path
     * @param string $mimeType
     * @return mixed
     */
    public static function transformFileToCV($candidateId, $attachmentsid, $dbFilename, $path, $mimeType) {

        $FILE_ACTION_NAME = 'MultiAttachment';
        $fieldName = "cv_img_file";

        //Adding hash to the document name
        $hash = hash('crc32', hrtime(true));
        $filename = $attachmentsid . "_" . $hash;
        $pathToFile = "/var/www/yetiforce/cache/tmp/" . $filename;
        // Locating the file and copying to temporary location
        $srcPath = $path . $attachmentsid;
        if (!file_exists($srcPath)) {
            $srcPath .= "_" . $dbFilename;
            if (!file_exists($srcPath)) {
                \App\Log\Log::warning("Cannot locate $srcPath. Leaving.");
                return;
            }
        }

        if (!copy($srcPath, $pathToFile)) {
            $errors = error_get_last();
            \App\Log\Log::warning('[transformFileToCV] copy() failed. ' . \App\Utils\Json::encode($errors));
            return;
        }
//        echo "mimeType=$mimeType\n";
        switch ($mimeType) {
            case "application/msword":
            case "application/vnd.openxmlformats-officedocument.wordprocessingml.document":
                shell_exec("doc2pdf $pathToFile");
                shell_exec("rm $pathToFile");
                shell_exec("mv $pathToFile.pdf $pathToFile");
            case "application/pdf":
                // Converting it to .jpg files - one for every document page and overwriting it
                // 100 seems a best quality/size ratio

                shell_exec("pdftoppm -jpeg -r 100 " . $pathToFile . " " . $pathToFile);
                // Trimming all jpgs (removing blank spaces), giving them border and the merging all created .jpgs to one final .jpg
                shell_exec("convert  -trim -bordercolor white -border 20 " . $pathToFile . "*.jpg -append " . $pathToFile);
            // TODO:
//                 Remove all temporary jpg files
            case "image/jpeg":
            case "image/pjpeg":
            case "image/gif":
            case "image/png":
                break;
            default:
                return;
        }

        $file = \App\Fields\File::loadFromPath($pathToFile);
        $file->getSize();
        $file->getMimeType();
        $file->getExtension();
        $key = hash_file('sha256', $pathToFile);
        $storageDir = $FILE_ACTION_NAME . DIRECTORY_SEPARATOR . 'Candidates' . DIRECTORY_SEPARATOR . $fieldName;
        $uploadFilePath = \vtlib\Functions::initStorageFileDirectory($storageDir);

        if (!copy($pathToFile, $uploadFilePath . $key)) {
            $errors = error_get_last();
            \App\Log\Log::warning('[transformFileToCV] copy() to storage failed. ' . \App\Utils\Json::encode($errors));
        }
        $newFile = [[
        'name' => $file->getSanitizeName(),
        'size' => $file->getSize(),
        'path' => $uploadFilePath . $key,
        'key' => $key,
        'type' => $file->getMimeType(),
        ]];
        $fieldValue = \App\Utils\Json::encode($newFile);

        $candidate = \App\Modules\Base\Models\Record::getInstanceById($candidateId);
        $candidate->set($fieldName, $fieldValue);
//        $candidate->save();
    }

	/** {@inheritdoc} */
	public function getRelatedListLeftSideLinks(\App\Modules\Base\Models\Record $parentRecord, array $context): array
	{
		$prepend = RelatedListLeftSideEmail::asLinks((int) $this->getId(), $parentRecord, $context);

		return array_merge($prepend, parent::getRelatedListLeftSideLinks($parentRecord, $context));
	}
}
