<?php

namespace App\Modules\Base\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the FreeCRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * Author: bmankowski@gmail.com
 * ********************************************************************************** */

/**
 * Renders an error page (record not found, no permissions, generic) inside the
 * full CRM layout (menu, header, footer). Used by WebUI as the preferred way to
 * present request exceptions to an authenticated user; falls back to the
 * standalone error templates when the layout cannot be rendered.
 */
class Error extends \App\Modules\Base\Views\Basic
{
	/** @var \Throwable|null */
	protected $exception;

	public function setException(\Throwable $exception): self
	{
		$this->exception = $exception;
		return $this;
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);

		$messageKey = $this->getMessageKey();
		[$titleKey, $alertClass] = $this->getPresentation($messageKey);

		$viewer->assign('ERROR_TITLE', $titleKey);
		$viewer->assign('ERROR_CLASS', $alertClass);
		$viewer->assign('MESSAGE', $messageKey);
		$viewer->assign('STACK_TRACE', $this->getStackTrace());
		$viewer->assign('PAGETITLE', \App\Runtime\Vtiger_Language_Handler::translate($titleKey));

		$viewer->view('ErrorInLayout.tpl', $request->getModule());
	}

	private function getMessageKey(): string
	{
		if ($this->exception && $this->exception->getMessage() !== '') {
			return $this->exception->getMessage();
		}
		return 'LBL_ERROR';
	}

	/**
	 * @return array{0:string,1:string} title language key and bootstrap alert class
	 */
	private function getPresentation(string $messageKey): array
	{
		if ($this->exception instanceof \App\Exceptions\NoPermittedToRecord) {
			if ($messageKey === 'LBL_RECORD_NOT_FOUND') {
				return ['LBL_RECORD_NOT_FOUND_TITLE', 'alert-info'];
			}
			if ($messageKey === 'LBL_RECORD_DELETE') {
				return ['LBL_RECORD_DELETE_TITLE', 'alert-info'];
			}
			return ['LBL_PERMISSION_DENIED', 'alert-warning'];
		}
		if (
			$this->exception instanceof \App\Exceptions\NoPermitted
			|| $this->exception instanceof \WebServiceException
		) {
			return ['LBL_PERMISSION_DENIED', 'alert-warning'];
		}
		return ['LBL_ERROR', 'alert-danger'];
	}

	private function getStackTrace(): string
	{
		if (!\App\Core\AppConfig::debug('DISPLAY_DEBUG_BACKTRACE') || !$this->exception) {
			return '';
		}
		return str_replace(
			ROOT_DIRECTORY . DIRECTORY_SEPARATOR,
			'',
			$this->exception->getTraceAsString()
		);
	}
}
