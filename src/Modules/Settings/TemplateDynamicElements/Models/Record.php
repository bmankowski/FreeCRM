<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

namespace App\Modules\Settings\TemplateDynamicElements\Models;

/**
 * Record model for PDF dynamic elements.
 */
class Record extends \App\Modules\Settings\Base\Models\Record
{
	protected $module;

	/**
	 * Returns default values for a new record.
	 */
	protected static function getDefaultData(): array
	{
		return [
			'code' => '',
			'label' => '',
			'type' => 'PLL_DOCUMENT_LAYOUT',
			'module_name' => '',
			'language' => '',
			'status' => 1,
			'sequence' => 0,
			'content' => '',
			'layout_header' => '',
			'layout_body' => '',
			'layout_footer' => '',
			'description' => '',
		];
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

	public function getId()
	{
		return $this->get('dynamicid');
	}

	public function getName()
	{
		return $this->get('label');
	}

	public function getEditViewUrl(): string
	{
		return 'index.php?module=TemplateDynamicElements&parent=Settings&view=Edit&record=' . $this->getId();
	}

	public function getDetailViewUrl(): string
	{
		return $this->getEditViewUrl();
	}

	public function getModule()
	{
		return $this->module;
	}

	public function setModule($moduleName)
	{
		if ($moduleName instanceof Module) {
			$this->module = $moduleName;
		} else {
			$this->module = \App\Modules\Settings\Base\Models\Module::getInstance($moduleName);
		}
		return $this;
	}

	public function getDisplayValue($key)
	{
		$value = $this->get($key);
		if ($key === 'status') {
			return (int) $value === 1 ? 'LBL_ACTIVE' : 'LBL_INACTIVE';
		}
		if ($key === 'module_name' && $value === '') {
			return 'LBL_GLOBAL';
		}
		if ($key === 'language' && $value === '') {
			return 'LBL_ALL_LANGUAGES';
		}
		return $value;
	}

	/**
	 * Returns list row actions.
	 */
	public function getRecordLinks(): array
	{
		$recordLinks = [
			[
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_EDIT_RECORD',
				'linkurl' => $this->getEditViewUrl(),
				'linkicon' => 'glyphicon glyphicon-pencil',
			],
			[
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_DELETE_RECORD',
				'linkurl' => "javascript:Settings_TemplateDynamicElements_ListView_Js.deleteDynamicElement('" . $this->getId() . "', event)",
				'linkicon' => 'glyphicon glyphicon-trash',
			],
		];
		$links = [];
		foreach ($recordLinks as $recordLink) {
			$links[] = \App\Modules\Base\Models\Link::getInstanceFromValues($recordLink);
		}
		return $links;
	}

	/**
	 * Returns a clean record model.
	 */
	public static function getCleanInstance(): self
	{
		$record = new self();
		$record->setData(self::getDefaultData());
		return $record;
	}

	/**
	 * Returns a record model by id.
	 */
	public static function getInstanceById($recordId): self
	{
		$row = (new \App\Db\Query())
			->from('a_yf_pdf_dynamic_elements')
			->where(['dynamicid' => (int) $recordId])
			->one();
		if (!$row) {
			throw new \App\Exceptions\AppException('Dynamic element does not exist');
		}
		$record = new self();
		$record->setData($row);
		return $record;
	}

	/**
	 * Saves the record.
	 */
	public function save(): void
	{
		$db = \App\Db\Db::getInstance('admin');
		$data = $this->getData();
		unset($data['dynamicid']);
		$data['status'] = (int) ($data['status'] ?? 0);
		$data['sequence'] = (int) ($data['sequence'] ?? 0);
		$data['module_name'] = (string) ($data['module_name'] ?? '');
		$data['language'] = (string) ($data['language'] ?? '');

		$type = (string) ($data['type'] ?? '');
		if (self::isDocumentLayoutType($type)) {
			$data['layout_header'] = (string) ($data['layout_header'] ?? '');
			$data['layout_body'] = (string) ($data['layout_body'] ?? '');
			$data['layout_footer'] = (string) ($data['layout_footer'] ?? '');
			$data['content'] = '';
		} else {
			$data['content'] = (string) ($data['content'] ?? '');
			$data['layout_header'] = '';
			$data['layout_body'] = '';
			$data['layout_footer'] = '';
		}

		if (empty($data['code'])) {
			$data['code'] = self::generateCode((string) $data['label']);
		}

		if ($this->getId()) {
			unset($data['code']);
			$db->createCommand()->update('a_#__pdf_dynamic_elements', $data, ['dynamicid' => $this->getId()])->execute();
		} else {
			$db->createCommand()->insert('a_#__pdf_dynamic_elements', $data)->execute();
			$this->set('dynamicid', $db->getLastInsertID('a_#__pdf_dynamic_elements_dynamicid_seq'));
			$this->set('code', $data['code']);
		}
	}

	/**
	 * Deletes the record.
	 */
	public function delete(): bool
	{
		return (bool) \App\Db\Db::getInstance('admin')->createCommand()
			->delete('a_#__pdf_dynamic_elements', ['dynamicid' => $this->getId()])
			->execute();
	}

	/**
	 * Checks if an element code is used by any PDF template content.
	 */
	public static function isCodeUsed(string $code): bool
	{
		$needle = '$(dynamic : ' . $code . ')$';
		return (new \App\Db\Query())
			->from('a_yf_pdf')
			->where(['or',
				['like', 'header_content', $needle],
				['like', 'body_content', $needle],
				['like', 'footer_content', $needle],
			])
			->exists();
	}

	/**
	 * Active elements of type document layout (for template wizard, step 2).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function getActiveDocumentLayouts(?string $moduleName = null, ?string $language = null): array
	{
		$rows = self::getActiveElements($moduleName, $language);
		return array_values(array_filter($rows, static function (array $row): bool {
			return self::isDocumentLayoutType((string) ($row['type'] ?? ''));
		}));
	}

	/**
	 * Returns active elements for editor dropdowns.
	 */
	public static function getActiveElements(?string $moduleName = null, ?string $language = null): array
	{
		$moduleName = $moduleName ?? '';
		$language = $language ?? \App\Runtime\Vtiger_Language_Handler::getLanguage();
		$query = (new \App\Db\Query())
			->from('a_yf_pdf_dynamic_elements')
			->where(['status' => 1])
			->andWhere(['module_name' => ['', $moduleName]])
			->andWhere(['language' => ['', $language]])
			->orderBy(['sequence' => SORT_ASC, 'label' => SORT_ASC]);

		return $query->all();
	}

	/**
	 * Finds the best active dynamic element for a code and scope.
	 */
	public static function getActiveElementByCode(string $code, string $moduleName = '', ?string $language = null): ?array
	{
		$language = $language ?? \App\Runtime\Vtiger_Language_Handler::getLanguage();
		$rows = (new \App\Db\Query())
			->from('a_yf_pdf_dynamic_elements')
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

	protected static function generateCode(string $label): string
	{
		$code = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
		$code = strtolower((string) $code);
		$code = preg_replace('/[^a-z0-9]+/', '_', $code);
		$code = trim((string) $code, '_');
		return $code ?: 'dynamic_element';
	}
}
