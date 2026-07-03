<?php
namespace App\Email;

use App\Cache\Cache;

/**
 * Mail basic class
 * @package YetiForce.App
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Mail
{

	/**
	 * Get a list of all smtp servers
	 * @return array
	 */
	public static function getAll()
	{
		if (Cache::has('SmtpServers', 'all')) {
			return Cache::get('SmtpServers', 'all');
		}
		$all = (new \App\Db\Query())->from('s_#__mail_smtp')->indexBy('id')->all(\App\Db\Db::getInstance('admin'));
		Cache::save('SmtpServers', 'all', $all, Cache::LONG);
		return $all;
	}

	/**
	 * Get smtp server by id
	 * @param int $smtpId
	 * @return array
	 */
	public static function getSmtpById($smtpId)
	{
		if (Cache::has('SmtpServer', $smtpId)) {
			return Cache::get('SmtpServer', $smtpId);
		}
		$servers = static::getAll();
		$smtp = false;
		if (isset($servers[$smtpId])) {
			$smtp = $servers[$smtpId];
		}
		Cache::save('SmtpServer', $smtpId, $smtp, Cache::LONG);
		return $smtp;
	}

	/**
	 * Get default smtp Id
	 * @return int
	 */
	public static function getDefaultSmtp()
	{
		if (Cache::has('DefaultSmtp', '')) {
			return Cache::get('DefaultSmtp', '');
		}
		$id = (new \App\Db\Query())->select(['id'])->from('s_#__mail_smtp')->where(['default' => 1])->scalar(\App\Db\Db::getInstance('admin'));
		if (!$id) {
			$id = (new \App\Db\Query())->select(['id'])->from('s_#__mail_smtp')->limit(1)->scalar(\App\Db\Db::getInstance('admin'));
		}
		Cache::save('DefaultSmtp', '', $id, Cache::LONG);
		return $id;
	}

	/**
	 * Resolve SMTP server id from an email template row (smtp_id or legacy email_template_priority).
	 *
	 * @param array $template Row from getTemplete / getTempleteDetail
	 * @return int|null
	 */
	public static function resolveTemplateSmtpId(array $template)
	{
		if (!empty($template['smtp_id'])) {
			return (int) $template['smtp_id'];
		}
		if (!empty($template['email_template_priority'])) {
			return (int) $template['email_template_priority'];
		}
		return null;
	}

	/**
	 * Get templte list for module
	 * @param string|bool $moduleName
	 * @param string|bool $type
	 * @param bool $hideSystem When true, exclude templates with is_system = 1 (manual compose only)
	 * @return array
	 */
	public static function getTempleteList($moduleName = false, $type = false, $hideSystem = true)
	{
		$cacheKey = "$moduleName.$type";
		if (Cache::has('MailTempleteList', $cacheKey)) {
			return Cache::get('MailTempleteList', $cacheKey);
		}
		$query = (new \App\Db\Query())->select(['name' => 'u_#__emailtemplates.name', 'id' => 'u_#__emailtemplates.emailtemplatesid', 'moduleName' => 'u_#__emailtemplates.module', 'sender_type' => 'u_#__emailtemplates.sender_type', 'smtp_id' => 'u_#__emailtemplates.smtp_id'])->from('u_#__emailtemplates')
			->innerJoin('vtiger_crmentity', 'u_#__emailtemplates.emailtemplatesid = vtiger_crmentity.crmid')
			->where(['vtiger_crmentity.deleted' => 0]);
		if ($moduleName) {
			$query->andWhere(['u_#__emailtemplates.module' => $moduleName]);
		}
		if ($type) {
			$query->andWhere(['u_#__emailtemplates.email_template_type' => $type]);
		}
		if ($hideSystem) {
			$query->andWhere(['u_#__emailtemplates.is_system' => 0]);
		}
		$row = $query
			->orderBy([
				'u_#__emailtemplates.sequence' => SORT_ASC,
				'u_#__emailtemplates.name' => SORT_ASC,
				'u_#__emailtemplates.emailtemplatesid' => SORT_ASC,
			])
			->all();
		Cache::save('MailTempleteList', $cacheKey, $row, Cache::LONG);
		return $row;
	}

	/**
	 * Email templates for one or more modules (merged, deduplicated by id).
	 *
	 * @param string[] $moduleNames
	 * @param string|bool $type
	 * @param bool $hideSystem When true, exclude templates with is_system = 1 (manual compose only)
	 * @return array
	 */
	public static function getTempleteListForModules(array $moduleNames, $type = false, $hideSystem = true)
	{
		$moduleNames = array_values(array_unique(array_filter($moduleNames)));
		if ($moduleNames === []) {
			return static::getTempleteList(false, $type, $hideSystem);
		}
		if (count($moduleNames) === 1) {
			return static::getTempleteList($moduleNames[0], $type, $hideSystem);
		}
		$cacheKey = implode(',', $moduleNames) . '.' . (string) $type;
		if (Cache::has('MailTempleteList', $cacheKey)) {
			return Cache::get('MailTempleteList', $cacheKey);
		}
		$query = (new \App\Db\Query())->select(['name' => 'u_#__emailtemplates.name', 'id' => 'u_#__emailtemplates.emailtemplatesid', 'moduleName' => 'u_#__emailtemplates.module', 'sender_type' => 'u_#__emailtemplates.sender_type', 'smtp_id' => 'u_#__emailtemplates.smtp_id'])->from('u_#__emailtemplates')
			->innerJoin('vtiger_crmentity', 'u_#__emailtemplates.emailtemplatesid = vtiger_crmentity.crmid')
			->where(['vtiger_crmentity.deleted' => 0, 'u_#__emailtemplates.module' => $moduleNames]);
		if ($type) {
			$query->andWhere(['u_#__emailtemplates.email_template_type' => $type]);
		}
		if ($hideSystem) {
			$query->andWhere(['u_#__emailtemplates.is_system' => 0]);
		}
		$row = $query
			->orderBy([
				'u_#__emailtemplates.sequence' => SORT_ASC,
				'u_#__emailtemplates.name' => SORT_ASC,
				'u_#__emailtemplates.emailtemplatesid' => SORT_ASC,
			])
			->all();
		foreach ($row as &$templateRow) {
			$moduleLabel = \App\Runtime\Vtiger_Language_Handler::translate($templateRow['moduleName'], $templateRow['moduleName']);
			$templateRow['name'] = $templateRow['name'] . ' (' . $moduleLabel . ')';
		}
		unset($templateRow);
		Cache::save('MailTempleteList', $cacheKey, $row, Cache::LONG);
		return $row;
	}

	/**
	 * Get mail template
	 * @param int|string $id
	 * @return array
	 */
	public static function getTemplete($id, $parse = true)
	{
		$detail = static::getTempleteDetail($id);
		if (!$detail) {
			return false;
		}
		return array_merge(
			$detail, static::getAttachmentsFromTemplete($detail['emailtemplatesid'])
		);
	}

	/**
	 * Get mail template detail
	 * @param int|string $id
	 * @return array
	 */
	public static function getTempleteDetail($id)
	{
		if (Cache::has('MailTempleteDetail', $id)) {
			return Cache::get('MailTempleteDetail', $id);
		}
		$query = (new \App\Db\Query())->from('u_#__emailtemplates')
			->innerJoin('vtiger_crmentity', 'u_#__emailtemplates.emailtemplatesid = vtiger_crmentity.crmid')
			->where(['vtiger_crmentity.deleted' => 0]);
		if (is_numeric($id)) {
			$query->andWhere(['u_#__emailtemplates.emailtemplatesid' => $id]);
		} else {
			$query->andWhere(['u_#__emailtemplates.sys_name' => $id]);
		}
		$row = $query->one();
		Cache::save('MailTempleteDetail', $id, $row, Cache::LONG);
		return $row;
	}

	/**
	 * Get attachments email template
	 * @param int|string $id
	 * @return array
	 */
	public static function getAttachmentsFromTemplete($id)
	{
		if (Cache::has('MailAttachmentsFromTemplete', $id)) {
			return Cache::get('MailAttachmentsFromTemplete', $id);
		}
		$ids = \App\Modules\EmailTemplates\Models\TemplateAttachment::getDocumentIdsForTemplate((int) $id);
		$attachments = [];
		if ($ids !== []) {
			$attachments['attachments'] = ['ids' => $ids];
		}
		Cache::save('MailAttachmentsFromTemplete', $id, $attachments, Cache::LONG);
		return $attachments;
	}

	/**
	 * Get attachments from document
	 * @param int|int[] $ids
	 * @return array
	 */
	public static function getAttachmentsFromDocument($ids)
	{
		$idsList = is_array($ids) ? $ids : [$ids];
		$idsList = array_values(array_unique(array_filter(array_map('intval', $idsList), static fn (int $id): bool => $id > 0)));
		sort($idsList);
		if ($idsList === []) {
			return [];
		}
		$cacheId = 'v2:' . implode(',', $idsList);
		if (Cache::has('MailAttachmentsFromDocument', $cacheId)) {
			return Cache::get('MailAttachmentsFromDocument', $cacheId);
		}
		$query = (new \App\Db\Query())
			->select([
				'vtiger_notes.notesid',
				'vtiger_notes.title AS notes_title',
				'vtiger_notes.storage_path',
				'vtiger_notes.original_name',
			])
			->from('vtiger_notes')
			->innerJoin('vtiger_crmentity', 'vtiger_notes.notesid = vtiger_crmentity.crmid')
			->where(['vtiger_crmentity.deleted' => 0, 'vtiger_notes.notesid' => $idsList]);
		$attachments = [];
		$dataReader = $query->createCommand()->query();
		while ($row = $dataReader->read()) {
			$filePath = \App\Modules\Documents\Models\Record::resolveStoragePath(
				(string) ($row['storage_path'] ?? ''),
				(string) ($row['original_name'] ?? '') ?: null
			);
			if ($filePath !== false && is_file($filePath)) {
				$displayName = (string) ($row['notes_title'] ?? '');
				if ($displayName === '') {
					$displayName = (string) ($row['original_name'] ?? basename($filePath));
				}
				$attachments[$filePath] = $displayName;
			}
		}
		if (count($attachments) >= count($idsList)) {
			Cache::save('MailAttachmentsFromDocument', $cacheId, $attachments, Cache::LONG);
		}

		return $attachments;
	}

	public static function clearTemplateListCache(): void
	{
		\App\Cache\Cache::clearNamespace('MailTempleteList');
	}
}
