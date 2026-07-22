<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\Settings\AiPrompts\Models;

use App\Ai\Prompt\ActionRegistry;

class Record extends \App\Modules\Settings\Base\Models\Record
{
	/** @var Module|null */
	protected $module;

	public function getId()
	{
		return $this->get('id');
	}

	public function getName(): string
	{
		return (string) ($this->get('name') ?? '');
	}

	public function getModule()
	{
		if ($this->module === null) {
			$this->module = \App\Modules\Settings\Base\Models\Module::getInstance('Settings:AiPrompts');
		}

		return $this->module;
	}

	public function setModule(\App\Modules\Settings\Base\Models\Module $module): self
	{
		$this->module = $module;

		return $this;
	}

	public function getDeleteActionUrl(): string
	{
		return 'index.php?module=AiPrompts&parent=Settings&action=DeleteAjax&record=' . $this->getId();
	}

	public function getDetailViewUrl(): string
	{
		return 'index.php?module=AiPrompts&parent=Settings&view=Detail&record=' . $this->getId();
	}

	public function getEditViewUrl(): string
	{
		return 'index.php?module=AiPrompts&parent=Settings&view=Edit&record=' . $this->getId();
	}

	public function getRecordLinks(): array
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
			[
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_DELETE_RECORD',
				'linkurl' => $this->getDeleteActionUrl(),
				'linkicon' => 'glyphicon glyphicon-trash',
				'linkclass' => 'btn btn-xs btn-danger',
			],
		];
		foreach ($recordLinks as $recordLink) {
			$links[] = \App\Modules\Base\Models\Link::getInstanceFromValues($recordLink);
		}

		return $links;
	}

	public function getDisplayValue(string $key): string
	{
		$value = $this->get($key);
		switch ($key) {
			case 'active':
				return \App\Runtime\Vtiger_Language_Handler::translate(
					((int) $value === 1) ? 'LBL_YES' : 'LBL_NO',
					'Vtiger'
				);
			case 'action_key':
				$keyStr = (string) $value;
				if (ActionRegistry::isKnown($keyStr)) {
					$label = ActionRegistry::all()[$keyStr]['label'];
					$translated = \App\Runtime\Vtiger_Language_Handler::translate($label, 'Settings:AiPrompts');

					return $translated !== $label ? $translated . ' (' . $keyStr . ')' : $keyStr;
				}

				return $keyStr;
			case 'prompt_body':
				return \App\Security\Purifier::encodeHtml((string) ($value ?? ''));
			default:
				return (string) ($value ?? '');
		}
	}

	public function delete(): void
	{
		\App\Db\Db::getInstance()->createCommand()
			->delete('s_#__ai_prompts', ['id' => $this->getId(), 'userid' => null])
			->execute();
	}

	public static function getInstanceById($id): ?self
	{
		$row = (new \App\Db\Query())
			->from('s_#__ai_prompts')
			->where(['id' => (int) $id, 'userid' => null])
			->one();
		if (!$row) {
			return null;
		}
		$instance = new self();
		$instance->setData($row);

		return $instance;
	}

	public static function getCleanInstance(): self
	{
		$instance = new self();
		$instance->setModule(\App\Modules\Settings\Base\Models\Module::getInstance('Settings:AiPrompts'));
		$instance->setData([
			'action_key' => ActionRegistry::MAIL_IMPROVE,
			'name' => '',
			'prompt_body' => '',
			'userid' => null,
			'active' => 1,
		]);

		return $instance;
	}

	/**
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	public function save(): void
	{
		$actionKey = trim((string) $this->get('action_key'));
		$name = trim((string) $this->get('name'));
		$promptBody = trim((string) $this->get('prompt_body'));
		$active = (int) $this->get('active') === 1 ? 1 : 0;

		if ($actionKey === '' || !ActionRegistry::isKnown($actionKey)) {
			throw new \InvalidArgumentException('LBL_INVALID_ACTION_KEY');
		}
		if ($name === '') {
			throw new \InvalidArgumentException('LBL_NAME_REQUIRED');
		}
		if ($promptBody === '') {
			throw new \InvalidArgumentException('LBL_PROMPT_BODY_REQUIRED');
		}

		$db = \App\Db\Db::getInstance();
		$now = date('Y-m-d H:i:s');
		$id = (int) $this->getId();

		$dupQuery = (new \App\Db\Query())
			->from('s_#__ai_prompts')
			->where(['action_key' => $actionKey, 'userid' => null]);
		if ($id > 0) {
			$dupQuery->andWhere(['<>', 'id', $id]);
		}
		if ($dupQuery->exists()) {
			throw new \RuntimeException('LBL_DUPLICATE_ACTION_KEY');
		}

		$params = [
			'action_key' => $actionKey,
			'name' => $name,
			'prompt_body' => $promptBody,
			'userid' => null,
			'active' => $active,
			'modifiedtime' => $now,
		];

		if ($id <= 0) {
			$params['createdtime'] = $now;
			$db->createCommand()->insert('s_#__ai_prompts', $params)->execute();
			$this->set('id', $db->getLastInsertID('s_#__ai_prompts_id_seq'));
		} else {
			$db->createCommand()->update('s_#__ai_prompts', $params, [
				'id' => $id,
				'userid' => null,
			])->execute();
		}
		$this->setData(array_merge($this->getData(), $params));
	}
}
