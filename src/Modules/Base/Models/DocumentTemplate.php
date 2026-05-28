<?php

namespace App\Modules\Base\Models;

/**
 * Basic PDF Model Class
 * @package YetiForce.PDF
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

class DocumentTemplate extends \App\Runtime\BaseModel
{

	const WATERMARK_TYPE_TEXT = 0;
	const WATERMARK_TYPE_IMAGE = 1;

	public static $baseTable = 'u_yf_documenttemplates';
	public static $baseIndex = 'documenttemplatesid';
	protected $recordCache = [];
	protected $recordId;
	protected $viewToPicklistValue = ['Detail' => 'PLL_DETAILVIEW', 'List' => 'PLL_LISTVIEW'];

	/**
	 * Function to get watermark type
	 * @return array
	 */
	public function getWatermarkType()
	{
		return [
			self::WATERMARK_TYPE_TEXT => 'PLL_TEXT',
			self::WATERMARK_TYPE_IMAGE => 'PLL_IMAGE'
		];
	}

	/**
	 * Function to get the id of the record
	 * @return <Number> - Record Id
	 */
	public function getId()
	{
		return $this->get(self::$baseIndex);
	}

	/**
	 * Fuction to get the Name of the record
	 * @return string - Entity Name of the record
	 */
	public function getName()
	{
		$displayName = $this->get('primary_name');
		return \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\ListViewUtils::decodeHtml($displayName));
	}

	public function get($key)
	{
		if ($key === 'conditions' && !is_array(parent::get($key))) {
			return json_decode(parent::get($key), true);
		} else {
			return parent::get($key);
		}
	}

	public function getRaw($key)
	{
		return parent::get($key);
	}

	/**
	 * Get record id for which template is generated
	 * @return <Integer> - id of a main module record
	 */
	public function getMainRecordId()
	{
		if (is_array($this->recordId))
			return reset($this->recordId);
		return $this->recordId;
	}

	/**
	 * Get records id for which template is generated
	 * @return <Array> - ids of a main module record
	 */
	public function getRecordIds()
	{
		return $this->recordId;
	}

	/**
	 * Sets record id for which template will be generated
	 * @param <Integer> $id
	 */
	public function setMainRecordId($id)
	{
		$this->recordId = $id;
	}

	public function getModule()
	{
		return \App\Modules\Base\Models\Module::getInstance($this->get('module_name'));
	}

	/**
	 * Check if pdf templates are avauble for this record, user and view
	 * @param integer $recordId - id of a record
	 * @param string $moduleName - name of the module
	 * @param string $view - modules view - Detail or List
	 * @return bool true or false
	 */
	public function checkActiveTemplates($recordId, $moduleName, $view)
	{
		$templates = $this->getActiveTemplatesForRecord($recordId, $view, $moduleName);

		if (count($templates) > 0) {
			return true;
		} else {
			return false;
		}
	}

	public function getActiveTemplatesForRecord($recordId, $view, $moduleName = false)
	{

		if (!\App\Utils\Utils::isRecordExists($recordId)) {
			return [];
		}
		if (!$moduleName) {
			$moduleName = \App\Records\Record::getType($recordId);
		}

		$templates = $this->getTemplatesByModule($moduleName);
		foreach ($templates as $id => &$template) {
			$template->setMainRecordId($recordId);
			if (!$template->isVisible($view) || !$template->checkFiltersForRecord($recordId) || !$template->checkUserPermissions()) {
				unset($templates[$id]);
			}
		}
		return $templates;
	}

	public function getActiveTemplatesForModule($moduleName, $view)
	{
		$templates = $this->getTemplatesByModule($moduleName);
		foreach ($templates as $id => &$template) {
			if (!$template->isVisible($view) || !$template->checkUserPermissions()) {
				unset($templates[$id]);
			}
		}
		return $templates;
	}

	/**
	 * Returns template records by module name
	 * @param string $moduleName - module name for which template was created
	 * @return array of template record models
	 */
	public static function getTemplatesByModule($moduleName)
	{

		$dataReader = (new \App\Db\Query())->from(self::$baseTable)
				->where(['module_name' => $moduleName, 'status' => 1])
				->createCommand()->query();
		$templates = [];
		while ($row = $dataReader->read()) {
			$handlerClass = \App\Core\Loader::getComponentClassName('Model', 'DocumentTemplate', $moduleName);
			$pdf = new $handlerClass();
			$pdf->setData($row);
			$templates[] = $pdf;
		}
		return $templates;
	}

	/**
	 * Get document template instance by id
	 * @param int $recordId
	 * @param string $moduleName
	 * @return \App\Modules\Base\Models\DocumentTemplate|boolean
	 */
	public static function getInstanceById($recordId, $moduleName = 'Vtiger')
	{
		$pdf = \App\Cache\Cache::get('DocumentTemplateModel', $recordId);
		if ($pdf) {
			return $pdf;
		}
		$row = (new \App\Db\Query())->from(self::$baseTable)->where([self::$baseIndex => $recordId])->one();
		if ($row === false) {
			return false;
		}
		if ($moduleName == 'Vtiger' && isset($row['module_name'])) {
			$moduleName = $row['module_name'];
		}

		$handlerClass = \App\Core\Loader::getComponentClassName('Model', 'DocumentTemplate', $moduleName);
		$pdf = new $handlerClass();
		$pdf->setData($row);
		\App\Cache\Cache::save('DocumentTemplateModel', $recordId, $pdf);
		return $pdf;
	}

	/**
	 * Function returns valuetype of the field filter
	 * @return string
	 */
	public function getFieldFilterValueType($fieldname)
	{
		$conditions = $this->get('conditions');
		if (!empty($conditions) && is_array($conditions)) {
			foreach ($conditions as $filter) {
				if ($fieldname == $filter['fieldname']) {
					return $filter['valuetype'];
				}
			}
		}
		return false;
	}

	public function deleteConditions()
	{
		$db = \App\Database\PearDatabase::getInstance();
		$db->update(self::$baseTable, [
			'conditions' => ''
			], self::$baseIndex . ' = ? LIMIT 1', [$this->getId()]
		);
	}

	public function isVisible($view)
	{
		$visibility = explode(',', $this->get('visibility'));
		if (in_array($this->viewToPicklistValue[$view], $visibility)) {
			return true;
		}
		return false;
	}

	/**
	 * Function to check filters for record
	 * @param int $recordId
	 * @return boolean
	 */
	public function checkFiltersForRecord($recordId)
	{
		$key = $this->getId() . '_' . $recordId;
		if (\App\Cache\Cache::has(__METHOD__, $key)) {
			return \App\Cache\Cache::get(__METHOD__, $key);
		}
		$conditionStrategy = new \App\Modules\Workflow\VTJsonCondition();
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId);
		$conditions = htmlspecialchars_decode($this->getRaw('conditions'));
		$test = $conditionStrategy->evaluate($conditions, $recordModel);
		\App\Cache\Cache::save(__METHOD__, $key, $test);
		return $test;
	}

	public function checkUserPermissions()
	{
		$permissions = $this->get('template_members');
		if (empty($permissions)) {
			return true;
		}
		$currentUser = \App\User\CurrentUser::get();
		$permissions = explode(',', $permissions);
		$getTypes = [];
		foreach ($permissions as $name) {
			$valueType = explode(':', $name);
			$getTypes[$valueType[0]][] = $valueType[1];
		}
		if (in_array('Users:' . $currentUser->getId(), $permissions)) { // check user id
			return true;
		} elseif (in_array('Roles:' . $currentUser->getRole(), $permissions)) {
			return true;
		} elseif (array_key_exists('Groups', $getTypes)) {
			$accessibleGroups = array_keys(\App\Fields\Owner::getInstance($this->get('module_name'), $currentUser)->getAccessibleGroupForModule());
			$groups = array_intersect($getTypes['Groups'], $currentUser->getGroups());
			if (array_intersect($groups, $accessibleGroups)) {
				return true;
			}
		}
		if (array_key_exists('RoleAndSubordinates', $getTypes)) {
			$roles = $currentUser->getParentRoles();
			$roles[] = $currentUser->getRole();
			if (array_intersect($getTypes['RoleAndSubordinates'], array_filter($roles))) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns array of template parameters understood by the pdf engine
	 * @return <Array> - array of parameters
	 */
	public function getParameters()
	{
		$parameters = [];
		$parameters['page_format'] = $this->get('page_format');
		$parameters['page_orientation'] = $this->get('page_orientation');
		// margins
		if ($this->get('margin_chkbox') == 0) {
			$parameters['margin-top'] = $this->get('margin_top');
			$parameters['margin-right'] = $this->get('margin_right');
			$parameters['margin-bottom'] = $this->get('margin_bottom');
			$parameters['margin-left'] = $this->get('margin_left');
		} else {
			$parameters['margin-top'] = '';
			$parameters['margin-right'] = '';
			$parameters['margin-bottom'] = '';
			$parameters['margin-left'] = '';
		}

		// metadata
		$companyDetails = \App\Core\Company::getInstanceById()->getData();
		$organizationName = $companyDetails['organizationname'] ?? '';
		if ($this->get('metatags_status') == 0) {
			$parameters['title'] = $this->get('meta_title');
			$parameters['author'] = $this->resolveMetaValue($this->get('meta_author'), $organizationName);
			$parameters['creator'] = $this->resolveMetaValue($this->get('meta_creator'), $organizationName);
			$parameters['subject'] = $this->get('meta_subject');
			$parameters['keywords'] = $this->get('meta_keywords');
		} else {
			$parameters['title'] = $this->get('primary_name');
			$parameters['author'] = $organizationName;
			$parameters['creator'] = $this->getMainRecordOwnerLabel() ?: $organizationName;
			$parameters['subject'] = $this->get('secondary_name');

			// preparing keywords
			unset($companyDetails['id']);
			unset($companyDetails['logo']);
			unset($companyDetails['logoname']);
			$parameters['keywords'] = implode(', ', $companyDetails);
		}

		return $parameters;
	}

	protected function resolveMetaValue($value, $organizationName)
	{
		switch ($value) {
			case 'PLL_COMPANY_NAME':
				return $organizationName;
			case 'PLL_USER_CREATING':
				$currentUser = \App\User\CurrentUser::get();
				return $currentUser ? $currentUser->getDisplayName() : '';
			case 'PLL_RECORD_OWNER':
				return $this->getMainRecordOwnerLabel();
			default:
				return $value;
		}
	}

	protected function getMainRecordOwnerLabel()
	{
		$recordId = $this->getMainRecordId();
		if (empty($recordId)) {
			return '';
		}
		$ownerId = (new \App\Db\Query())->select(['smownerid'])
			->from('vtiger_crmentity')
			->where(['crmid' => $recordId, 'deleted' => 0])
			->scalar();
		return $ownerId ? (string) \App\Fields\Owner::getLabel($ownerId) : '';
	}

	/**
	 * Returns page format
	 * @return string page format
	 */
	public function getFormat()
	{
		$format = $this->get('page_format');
		$orientation = $this->get('page_orientation');
		if ($orientation === 'PLL_LANDSCAPE') {
			$format .= '-L';
		} else {
			$format .= '-P';
		}
		return $format;
	}

	/**
	 * Get header content
	 * @param bool $raw - if true return unparsed header
	 * @return string - header content
	 */
	public function getHeader($raw = false)
	{
		if ($raw) {
			return $this->get('header_content');
		}
		$textParser = \App\TextParser\TextParser::getInstanceById($this->getMainRecordId(), $this->get('module_name'));
		$textParser->setType('pdf');
		$textParser->setParams($this->getTextParserParams());
		if ($this->get('language')) {
			$textParser->setLanguage($this->get('language'));
		}
		return $textParser->setContent($this->get('header_content'))->parse()->getContent();
	}

	/**
	 * Get body content
	 * @param bool $raw - if true return unparsed header
	 * @return string - body content
	 */
	public function getFooter($raw = false)
	{
		if ($raw) {
			return $this->get('footer_content');
		}
		$textParser = \App\TextParser\TextParser::getInstanceById($this->getMainRecordId(), $this->get('module_name'));
		$textParser->setType('pdf');
		$textParser->setParams($this->getTextParserParams());
		if ($this->get('language')) {
			$textParser->setLanguage($this->get('language'));
		}
		return $textParser->setContent($this->get('footer_content'))->parse()->getContent();
	}

	/**
	 * Get body content
	 * @param bool $raw - if true return unparsed header
	 * @return string - body content
	 */
	public function getBody($raw = false)
	{
		if ($raw) {
			return $this->get('body_content');
		}
		$textParser = \App\TextParser\TextParser::getInstanceById($this->getMainRecordId(), $this->get('module_name'));
		$textParser->setType('pdf');
		$textParser->setParams($this->getTextParserParams());
		if ($this->get('language')) {
			$textParser->setLanguage($this->get('language'));
		}
		return $textParser->setContent($this->get('body_content'))->parse()->getContent();
	}

	protected function getTextParserParams()
	{
		return [
			'pdf' => $this,
		];
	}

	protected function getGeneratorFooter()
	{
		$cacheKey = 'generatorFooter|' . $this->getMainRecordId() . '|' . $this->get('language');
		if (isset($this->recordCache[$cacheKey])) {
			return $this->recordCache[$cacheKey];
		}
		$textParser = \App\TextParser\TextParser::getInstanceById($this->getMainRecordId(), $this->get('module_name'));
		$textParser->setType('pdf');
		$textParser->setParams(['pdf' => $this]);
		if ($this->get('language')) {
			$textParser->setLanguage($this->get('language'));
		}
		return $this->recordCache[$cacheKey] = $textParser->setContent('$(dynamic : pdf_generator_footer)$')->parse()->getContent();
	}

	/**
	 * Export record to PDF file
	 * @param int $recordId - id of a record
	 * @param string $moduleName - name of records module
	 * @param int $templateId - id of pdf template
	 * @param string $filePath - path name for saving pdf file
	 * @param string $saveFlag - save option flag
	 */
	public static function exportToPdf($recordId, $moduleName, $templateId, $filePath = '', $saveFlag = '')
	{
		$handlerClass = static::getPdfRendererClass($moduleName);
		$pdf = new $handlerClass();
		$pdf->export($recordId, $moduleName, $templateId, $filePath, $saveFlag);
	}

	public static function getPdfRendererClass($moduleName)
	{
		return \App\Core\Loader::getComponentClassName('Pdf', static::getPdfRendererName(), $moduleName);
	}

	public static function getPdfRendererName()
	{
		return 'Chrome';
	}

	public static function attachToEmail($salt)
	{
		header('Location: index.php?module=OSSMail&view=compose&pdf_path=' . $salt);
	}

	public static function zipAndDownload(array $fileNames)
	{

		//create the object
		$zip = new ZipArchive();

		mt_srand(time());
		$postfix = time() . '_' . mt_rand(0, 1000);
		$zipPath = 'storage/';
		$zipName = "pdfZipFile_{$postfix}.zip";
		$fileName = $zipPath . $zipName;

		//create the file and throw the error if unsuccessful
		if ($zip->open($zipPath . $zipName, ZIPARCHIVE::CREATE) !== true) {
			\App\Log\Log::error("cannot open <$zipPath.$zipName>\n");
			throw new \App\Exceptions\NoPermitted("cannot open <$zipPath.$zipName>");
		}

		//add each files of $file_name array to archive
		foreach ($fileNames as $file) {
			$zip->addFile($file, basename($file));
		}
		$zip->close();

		// delete added pdf files
		foreach ($fileNames as $file) {
			unlink($file);
		}
		$mimeType = \App\Fields\File::getMimeContentType($fileName);
		$size = filesize($fileName);
		$name = basename($fileName);

		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Content-Type: $mimeType");
		header('Content-Disposition: attachment; filename="' . $name . '";');
		header("Accept-Ranges: bytes");
		header('Content-Length: ' . $size);

		print readfile($fileName);
		// delete temporary zip file and saved pdf files
		unlink($fileName);
	}
}
