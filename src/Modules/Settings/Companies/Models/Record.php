<?php

namespace App\Modules\Settings\Companies\Models;



/**
 * Companies record model class
 * @package YetiForce.Settings.Model
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class Record extends \App\Modules\Settings\Base\Models\Record
{

	public static $logoNames = ['logo_login', 'logo_main', 'logo_mail'];
	public static $logoSupportedFormats = ['jpeg', 'jpg', 'png', 'gif', 'pjpeg', 'x-png'];

	/**
	 * Function to get the Id
	 * @return int Id
	 */
	public function getId()
	{
		return $this->get('id');
	}

	/**
	 * Function to get the Name
	 * @return string
	 */
	public function getName()
	{
		return $this->get('name');
	}

	/**
	 * Function to get the Edit View Url
	 * @return string URL
	 */
	public function getEditViewUrl($step = false)
	{
		return '?module=Companies&parent=Settings&view=Edit&record=' . $this->getId();
	}

	/**
	 * Function to get the Delete Action Url
	 * @return string URL
	 */
	public function getDeleteActionUrl()
	{
		return 'index.php?module=Companies&parent=Settings&action=DeleteAjax&record=' . $this->getId();
	}

	/**
	 * Function to get the Detail Url
	 * @return string URL
	 */
	public function getDetailViewUrl()
	{
		return '?module=Companies&parent=Settings&view=Detail&record=' . $this->getId();
	}

	/**
	 * Function to get the instance of companies record model
	 * @param int $id
	 * @return \self instance, if exists.
	 */
	public static function getInstance($id)
	{
		$db = \App\Db\Db::getInstance('admin');
		$row = (new \App\Db\Query())->from('s_#__companies')->where(['id' => $id])->one($db);
		$instance = false;
		if ($row) {
			$instance = new self();
			$instance->setData($row);
		}
		return $instance;
	}

	/**
	 * Function to save
	 */
	public function save($request = null)
	{
		$db = \App\Db\Db::getInstance('admin');
		$recordId = $this->getId();
		$params = $this->getData();
		if ($recordId) {
			$db->createCommand()->update('s_#__companies', $params, ['id' => $recordId])->execute();
		} else {
			$db->createCommand()->insert('s_#__companies', $params)->execute();
			$this->set('id', $db->getLastInsertID('s_#__companies_id_seq'));
		}
		$this->clearCompanyCache();
	}

	/**
	 * Clear cached company details and logos.
	 */
	public function clearCompanyCache(): void
	{
		\App\Cache\Cache::clearNamespace('CompanyDetail');
		\App\Cache\Cache::clearNamespace('CompanyLogo');
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param string $key
	 * @return string
	 */
	public function getDisplayValue($key)
	{
		$value = $this->get($key);
		switch ($key) {
			case 'default':
				$value = $this->getDisplayCheckboxValue($value);
				break;
			case 'tabid':
				$value = \App\Utils\ModuleUtils::getModuleName($value);
				break;
			case 'industry':
				$value = \App\Runtime\Vtiger_Language_Handler::translate($value);
				break;
			case 'logo_login':
			case 'logo_main':
			case 'logo_mail':
				$value = "<img src='{$this->getLogoPath($value)}' class='alignMiddle'/>";
				break;
		}
		return $value;
	}

	/**
	 * Function to get the Display Value, for the checbox field type with given DB Insert Value
	 * @param int $value
	 * @return string
	 */
	public function getDisplayCheckboxValue($value)
	{
		if (0 === $value) {
			$value = \App\Runtime\Vtiger_Language_Handler::translate('LBL_NO');
		} else {
			$value = \App\Runtime\Vtiger_Language_Handler::translate('LBL_YES');
		}
		return $value;
	}

	/**
	 * Function to delete the current Record Model
	 */
	public function delete()
	{
		$db = \App\Db\Db::getInstance('admin');
		$db->createCommand()
			->delete('s_#__companies', ['id' => $this->getId()])
			->execute();
		\App\Cache\Cache::clear();
	}

	/**
	 * Function to get the list view actions for the record
	 * @return \App\Modules\Base\Models\Link[] - Associate array of \App\Modules\Base\Models\Link instances
	 */
	public function getRecordLinks()
	{
		$links = [];
		$recordLinks = [
			[
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_EDIT_RECORD',
				'linkurl' => $this->getEditViewUrl(),
				'linkicon' => 'glyphicon glyphicon-pencil',
				'linkclass' => 'btn btn-xs btn-info',
			],
		];
		if (0 === $this->get('default')) {
			$recordLinks[] = [
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_DELETE_RECORD',
				'linkurl' => $this->getDeleteActionUrl(),
				'linkicon' => 'glyphicon glyphicon-trash',
				'linkclass' => 'btn btn-xs btn-danger',
			];
		}
		foreach ($recordLinks as $recordLink) {
			$links[] = \App\Modules\Base\Models\Link::getInstanceFromValues($recordLink);
		}
		return $links;
	}

	/**
	 * Function to get Logo path to display
	 * @param string $name logo name
	 * @return string path
	 */
	public function getLogoPath($name)
	{
		if (!$name || !is_file(\App\Core\Company::getLogoFilesystemPath($name))) {
			return '';
		}
		return \App\Core\Company::getLogoWebPath($name);
	}

	/**
	 * Function to save the logoinfo
	 * @param string $name logo field name (logo_login, logo_main, logo_mail)
	 * @return string|false Saved filename for DB, or false on failure
	 */
	public function saveLogo($name)
	{
		$uploadDir = \App\Core\Company::getLogoFilesystemDir();
		if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
			\App\Log\Log::error('Companies logo upload failed: cannot create directory ' . $uploadDir);
			return false;
		}
		if (!is_writable($uploadDir) && !@chmod($uploadDir, 0777)) {
			\App\Log\Log::error('Companies logo upload failed: directory not writable ' . $uploadDir);
			return false;
		}
		$sanitized = \App\Fields\File::sanitizeUploadFileName($_FILES[$name]['name']);
		$extension = strtolower(pathinfo($sanitized, PATHINFO_EXTENSION));
		if (!in_array($extension, self::$logoSupportedFormats, true)) {
			return false;
		}
		$fileName = $name . '.' . $extension;
		$targetPath = $uploadDir . $fileName;
		foreach (glob($uploadDir . $name . '.*') ?: [] as $oldFile) {
			if (!is_file($oldFile) || str_ends_with($oldFile, \App\Core\Company::getLogoBase64SidecarSuffix())) {
				continue;
			}
			\App\Core\Company::unlinkLogoFileAndSidecar(basename($oldFile));
		}
		if (!move_uploaded_file($_FILES[$name]['tmp_name'], $targetPath)) {
			\App\Log\Log::error('Companies logo upload failed: cannot move file to ' . $targetPath);
			return false;
		}
		@chmod($targetPath, 0664);
		\App\Core\Company::writeLogoBase64Sidecar($fileName);
		if ('logo_login' === $name) {
			@copy($targetPath, $uploadDir . 'application.ico');
		}
		return $fileName;
	}

	/**
	 * Function to check if company duplicated
	 * @param \App\Http\Vtiger_Request $request	
	 * @return boolean
	 */
	public function isCompanyDuplicated(\App\Http\Vtiger_Request $request)
	{
		$db = \App\Db\Db::getInstance('admin');
		$query = new \App\Db\Query();
		$query->from('s_#__companies')
			->where(['name' => $request->get('name')])
			->orWhere(['short_name' => $request->get('short_name')]);
		if ($request->get('record')) {
			$query->andWhere(['<>', 'id', $request->get('record')]);
		}
		return $query->exists($db);
	}

	/**
	 * Function to set companies not default
	 * @param string $name 
	 */
	public function setCompaniesNotDefault($default)
	{
		if ($default) {
			\App\Db\Db::getInstance('admin')->createCommand()->update('s_#__companies', ['default' => 0])->execute();
		}
	}

	/**
	 * Function to save company logos
	 * @return array{saved: array<string, string>, errors: string[]}
	 */
	public function saveCompanyLogos()
	{
		$savedLogos = [];
		$errors = [];
		$module = 'Settings:Companies';
		foreach (self::$logoNames as $image) {
			if (empty($_FILES[$image]['name'])) {
				continue;
			}
			$fieldLabel = \App\Runtime\Vtiger_Language_Handler::translate('LBL_' . strtoupper($image), $module);
			if (UPLOAD_ERR_OK !== (int) $_FILES[$image]['error']) {
				$errors[] = $fieldLabel . ': ' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_LOGO_UPLOAD_FAILED', $module);
				continue;
			}
			$fileInstance = \App\Fields\File::loadFromRequest($_FILES[$image]);
			if (!$fileInstance->validate('image')) {
				$errors[] = $fieldLabel . ': ' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_LOGO_UPLOAD_INVALID_FORMAT', $module);
				continue;
			}
			if ($fileInstance->getShortMimeType(0) !== 'image' || !in_array($fileInstance->getShortMimeType(1), self::$logoSupportedFormats, true)) {
				$errors[] = $fieldLabel . ': ' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_LOGO_UPLOAD_INVALID_FORMAT', $module);
				continue;
			}
			$savedFileName = $this->saveLogo($image);
			if ($savedFileName) {
				$savedLogos[$image] = $savedFileName;
			} else {
				$errors[] = $fieldLabel . ': ' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_LOGO_UPLOAD_WRITE_ERROR', $module);
			}
		}
		return ['saved' => $savedLogos, 'errors' => $errors];
	}
}
