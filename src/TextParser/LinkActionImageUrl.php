<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\TextParser;

use App\Modules\LinkAction\Services\LinkActionConfig;
use App\Modules\LinkAction\Services\LinkActionToken;

class LinkActionImageUrl extends Base
{
	public $name = 'LBL_LINK_ACTION_IMAGE_URL';

	public $type = 'mail';

	public function isActive(): bool
	{
		if (!parent::isActive()) {
			return false;
		}
		[$action, $scope, $emailField] = $this->resolveParams();
		$moduleName = (string) ($this->textParser->moduleName ?? '');
		if ($moduleName === '' || LinkActionConfig::moduleConfig($moduleName) === null) {
			return false;
		}
		return LinkActionConfig::isActionAllowed($moduleName, $action, $scope)
			&& LinkActionConfig::isEmailFieldAllowed($moduleName, $emailField);
	}

	public function process(): string
	{
		[$action, $scope, $emailField] = $this->resolveParams();
		$moduleName = (string) $this->textParser->moduleName;
		$recordModel = $this->textParser->recordModel;
		if (!$recordModel || !$recordModel->getId()) {
			return '';
		}

		$mailMessageId = (int) ($this->textParser->mailMessageId ?? 0);
		if ($mailMessageId <= 0) {
			return '';
		}

		$email = (string) $recordModel->get($emailField);
		if ($email === '') {
			return '';
		}

		$tokenService = new LinkActionToken();
		$payload = $tokenService->buildPayload(
			$moduleName,
			(int) $recordModel->getId(),
			$emailField,
			$email,
			$action,
			$scope,
			$mailMessageId
		);
		$url = $tokenService->buildImageUrl($payload);
		return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * @return array{0:string,1:string,2:string}
	 */
	private function resolveParams(): array
	{
		$params = is_array($this->params) ? $this->params : [];
		$action = (string) ($params[0] ?? 'open');
		$scope = (string) ($params[1] ?? 'email');
		$emailField = (string) ($params[2] ?? '');
		if ($emailField === '') {
			$config = LinkActionConfig::moduleConfig((string) ($this->textParser->moduleName ?? ''));
			$emailField = (string) ($config['default_email_field'] ?? 'newsletter_email');
		}
		return [$action, $scope, $emailField];
	}
}
