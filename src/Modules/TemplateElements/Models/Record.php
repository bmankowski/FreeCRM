<?php

namespace App\Modules\TemplateElements\Models;

class Record extends \App\Modules\Base\Models\Record
{
	public static function getInstanceById($recordId, $module = null)
	{
		$recordId = (int) $recordId;
		$moduleName = is_object($module) && is_a($module, \App\Modules\Base\Models\Module::class)
			? $module->getName()
			: (is_string($module) ? $module : 'TemplateElements');
		try {
			return parent::getInstanceById($recordId, $moduleName);
		} catch (\App\Exceptions\NoPermittedToRecord $e) {
			$row = (new \App\Db\Query())
				->from('u_yf_templateelements')
				->where(['templateelementsid' => $recordId])
				->one();
			if ($row === false) {
				throw $e;
			}
			$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
			$instance = new self();
			$instance->setData($row);
			$instance->setId($recordId);
			if ($moduleModel) {
				$instance->setModuleFromInstance($moduleModel);
			}
			$instance->isNew = false;
			return $instance;
		}
	}

	public static function isDocumentLayoutType(string $type): bool
	{
		return $type === 'PLL_DOCUMENT_LAYOUT';
	}

	public function isDocumentLayout(): bool
	{
		return self::isDocumentLayoutType((string) $this->get('type'));
	}

	public static function getLayoutParts(array $row): array
	{
		return [
			'layout_header' => (string) ($row['layout_header'] ?? ''),
			'layout_body' => (string) ($row['layout_body'] ?? ''),
			'layout_footer' => (string) ($row['layout_footer'] ?? ''),
		];
	}

	public function save($request = null)
	{
		$type = (string) $this->get('type');
		if (!in_array($type, Module::getAllowedTypes(), true)) {
			throw new \App\Exceptions\AppException('Invalid template element type');
		}
		if (self::isDocumentLayoutType($type)) {
			$this->set('content', '');
		} else {
			$this->set('layout_header', '');
			$this->set('layout_body', '');
			$this->set('layout_footer', '');
		}
		if ($this->isNew() && empty($this->get('code'))) {
			$this->set('code', self::generateCode((string) $this->get('label')));
		}
		parent::save($request);
	}

	public static function isCodeUsed(string $code): bool
	{
		$needle = '$(dynamic : ' . $code . ')$';
		return (new \App\Db\Query())
			->from('u_yf_documenttemplates')
			->where(['or',
				['like', 'header_content', $needle],
				['like', 'body_content', $needle],
				['like', 'footer_content', $needle],
			])
			->exists();
	}

	public static function getActiveDocumentLayouts(?string $moduleName = null, ?string $language = null): array
	{
		$rows = self::getActiveElements($moduleName, $language);
		return array_values(array_filter($rows, static function (array $row): bool {
			return self::isDocumentLayoutType((string) ($row['type'] ?? ''));
		}));
	}

	public static function getActiveElements(?string $moduleName = null, ?string $language = null): array
	{
		$moduleName = $moduleName ?? '';
		$language = $language ?? \App\Runtime\Vtiger_Language_Handler::getLanguage();
		return (new \App\Db\Query())
			->from('u_yf_templateelements')
			->where(['status' => 1])
			->andWhere(['module_name' => ['', $moduleName]])
			->andWhere(['language' => ['', $language]])
			->orderBy(['sequence' => SORT_ASC, 'label' => SORT_ASC])
			->all();
	}

	public static function getActiveElementByCode(string $code, string $moduleName = '', ?string $language = null): ?array
	{
		$language = $language ?? \App\Runtime\Vtiger_Language_Handler::getLanguage();
		$rows = (new \App\Db\Query())
			->from('u_yf_templateelements')
			->where([
				'code' => $code,
				'status' => 1,
				'module_name' => ['', $moduleName],
				'language' => ['', $language],
			])
			->all();
		if (!$rows) {
			return null;
		}
		usort($rows, static function (array $left, array $right) use ($moduleName, $language): int {
			return self::scopeScore($right, $moduleName, $language) <=> self::scopeScore($left, $moduleName, $language);
		});
		return $rows[0];
	}

	protected static function scopeScore(array $row, string $moduleName, string $language): int
	{
		$score = 0;
		if ($moduleName !== '' && $row['module_name'] === $moduleName) {
			$score += 2;
		}
		if ($language !== '' && $row['language'] === $language) {
			$score += 1;
		}
		return $score;
	}

	public static function generateCode(string $label): string
	{
		$code = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
		$code = strtolower((string) $code);
		$code = preg_replace('/[^a-z0-9]+/', '_', $code);
		$code = trim((string) $code, '_');
		return $code ?: 'dynamic_element';
	}
}
