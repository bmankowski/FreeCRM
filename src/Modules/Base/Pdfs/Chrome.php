<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

namespace App\Modules\Base\Pdfs;

/**
 * PDF renderer backed by headless Chromium.
 */
class Chrome extends AbstractPDF
{
	protected $header = '';
	protected $footer = '';
	protected $parameters = [];
	protected $watermarkHtml = '';

	/** {@inheritdoc} */
	public function pdf()
	{
		return null;
	}

	/** {@inheritdoc} */
	public function getLibraryName()
	{
		return $this->library;
	}

	/** {@inheritdoc} */
	public function setLibraryName($name)
	{
		$this->library = $name;
	}

	/** {@inheritdoc} */
	public function getTemplateId()
	{
		return $this->templateId;
	}

	/** {@inheritdoc} */
	public function setTemplateId($id)
	{
		$this->templateId = $id;
	}

	/** {@inheritdoc} */
	public function getRecordId()
	{
		return $this->recordId;
	}

	/** {@inheritdoc} */
	public function setRecordId($id)
	{
		$this->recordId = $id;
	}

	/** {@inheritdoc} */
	public function getModuleName()
	{
		return $this->moduleName;
	}

	/** {@inheritdoc} */
	public function setModuleName($name)
	{
		$this->moduleName = $name;
	}

	/** {@inheritdoc} */
	public function setTopMargin($margin)
	{
		$this->parameters['margin-top'] = $margin;
	}

	/** {@inheritdoc} */
	public function setBottomMargin($margin)
	{
		$this->parameters['margin-bottom'] = $margin;
	}

	/** {@inheritdoc} */
	public function setLeftMargin($margin)
	{
		$this->parameters['margin-left'] = $margin;
	}

	/** {@inheritdoc} */
	public function setRightMargin($margin)
	{
		$this->parameters['margin-right'] = $margin;
	}

	/** {@inheritdoc} */
	public function setPageSize($format, $orientation)
	{
		$this->parameters['page_format'] = $format;
		$this->parameters['page_orientation'] = $orientation;
	}

	/** {@inheritdoc} */
	public function parseParams(array &$params)
	{
		foreach ($params as $param => $value) {
			$this->parameters[$param] = $value;
		}
	}

	/** {@inheritdoc} */
	public function setTitle($title)
	{
		$this->parameters['title'] = $title;
	}

	/** {@inheritdoc} */
	public function setAuthor($author)
	{
		$this->parameters['author'] = $author;
	}

	/** {@inheritdoc} */
	public function setCreator($creator)
	{
		$this->parameters['creator'] = $creator;
	}

	/** {@inheritdoc} */
	public function setSubject($subject)
	{
		$this->parameters['subject'] = $subject;
	}

	/** {@inheritdoc} */
	public function setKeywords($keywords)
	{
		$this->parameters['keywords'] = $keywords;
	}

	/** {@inheritdoc} */
	public function setHeader($name, $header)
	{
		$this->header = $header;
	}

	/** {@inheritdoc} */
	public function setFooter($name, $footer)
	{
		$this->footer = $footer;
	}

	/** {@inheritdoc} */
	public function loadHTML($html)
	{
		$this->html = $html;
	}

	public function setWaterMark($templateModel)
	{
		if ((int) $templateModel->get('watermark_type') === \App\Modules\Base\Models\DocumentTemplate::WATERMARK_TYPE_TEXT && $templateModel->get('watermark_text')) {
			$text = \App\Modules\Base\Helpers\Util::toSafeHTML($templateModel->get('watermark_text'));
			$this->watermarkHtml = '<div class="pdf-watermark pdf-watermark-text">' . $text . '</div>';
		}
	}

	/**
	 * Output content to PDF.
	 *
	 * @param string $fileName
	 * @param string $dest
	 */
	public function output($fileName = '', $dest = '')
	{
		$targetFile = $fileName ?: $this->createTempFile('pdf');
		$htmlFile = $this->createTempFile('html');
		$profileDir = $this->createTempDir();

		file_put_contents($htmlFile, $this->buildDocumentHtml());
		$this->runChrome($htmlFile, $targetFile, $profileDir);

		if (!is_file($targetFile) || filesize($targetFile) === 0) {
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_EXPORT_ERROR', $this->moduleName ?: 'Vtiger'));
		}

		$this->cleanup([$htmlFile], [$profileDir]);
		if ($dest === 'F' || $fileName) {
			return;
		}

		$this->sendPdfToBrowser($targetFile);
		$this->cleanup([$targetFile], []);
	}

	/** {@inheritdoc} */
	public function export($recordId, $moduleName, $templateId, $filePath = '', $saveFlag = '')
	{
		$template = \App\Modules\Base\Models\DocumentTemplate::getInstanceById($templateId, $moduleName);
		$template->setMainRecordId($recordId);

		$pdf = new self();
		$pdf->setTemplateId($templateId);
		$pdf->setRecordId($recordId);
		$pdf->setModuleName($moduleName);
		$pdf->setWaterMark($template);
		$pdf->setLanguage($template->get('language'));
		$pdf->setFileName($template->get('filename'));

		$parameters = $template->getParameters();
		$pdf->parseParams($parameters);
		$pdf->setHeader('Header', $template->getHeader());
		$pdf->setFooter('Footer', $template->getFooter());
		$pdf->loadHTML($template->getBody());
		$pdf->output($filePath, $saveFlag);
	}

	protected function buildDocumentHtml()
	{
		$baseUrl = rtrim((string) \App\Core\AppConfig::main('pdfChromeBaseUrl'), '/') . '/';
		$title = \App\Modules\Base\Helpers\Util::toSafeHTML((string) ($this->parameters['title'] ?? $this->getFileName()));
		return '<!doctype html><html><head><meta charset="UTF-8"><base href="' . $baseUrl . '"><title>' . $title . '</title><style>' .
			$this->buildPrintCss() . '</style></head><body>' .
			$this->watermarkHtml .
			'<header class="pdf-header">' . $this->header . '</header>' .
			'<main class="pdf-body">' . $this->html . '</main>' .
			'<footer class="pdf-footer">' . $this->footer . '</footer>' .
			'</body></html>';
	}

	protected function buildPrintCss()
	{
		$format = preg_replace('/[^A-Za-z0-9]/', '', (string) ($this->parameters['page_format'] ?? 'A4')) ?: 'A4';
		$orientation = ($this->parameters['page_orientation'] ?? 'PLL_PORTRAIT') === 'PLL_LANDSCAPE' ? 'landscape' : 'portrait';
		$top = $this->normalizeMargin($this->parameters['margin-top'] ?? 15);
		$right = $this->normalizeMargin($this->parameters['margin-right'] ?? 15);
		$bottom = $this->normalizeMargin($this->parameters['margin-bottom'] ?? 15);
		$left = $this->normalizeMargin($this->parameters['margin-left'] ?? 15);

		return '@page{size:' . $format . ' ' . $orientation . ';margin:' . $top . ' ' . $right . ' ' . $bottom . ' ' . $left . ';}' .
			'html,body{font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#222;}' .
			'body{margin:0;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' .
			'.pdf-header{position:fixed;top:0;left:0;right:0;}' .
			'.pdf-footer{position:fixed;bottom:0;left:0;right:0;}' .
			'.pdf-body{position:relative;z-index:1;}' .
			'.pdf-watermark{position:fixed;top:45%;left:0;right:0;text-align:center;z-index:0;opacity:.15;pointer-events:none;}' .
			'.pdf-watermark-text{font-size:72px;transform:rotate(-35deg);transform-origin:center;}' .
			'img{max-width:100%;}' .
			\App\Utils\TemplateStyles::getCss();
	}

	protected function normalizeMargin($value)
	{
		return is_numeric($value) ? ((float) $value) . 'mm' : '15mm';
	}

	protected function runChrome($htmlFile, $targetFile, $profileDir)
	{
		$binary = (string) \App\Core\AppConfig::main('pdfChromeBinary');
		$homeDir = $profileDir . '/home';
		$configDir = $profileDir . '/config';
		$cacheDir = $profileDir . '/cache';
		$crashDir = $profileDir . '/crashes';
		foreach ([$homeDir, $configDir, $cacheDir, $crashDir] as $dir) {
			if (!is_dir($dir)) {
				mkdir($dir, 0700, true);
			}
		}
		$command = [
			$binary ?: '/usr/bin/chromium',
			'--headless=new',
			'--disable-gpu',
			'--no-sandbox',
			'--disable-breakpad',
			'--disable-crash-reporter',
			'--disable-crashpad',
			'--disable-dev-shm-usage',
			'--no-pdf-header-footer',
			'--user-data-dir=' . $profileDir,
			'--crash-dumps-dir=' . $crashDir,
			'--print-to-pdf=' . $targetFile,
			'file://' . $htmlFile,
		];

		$descriptorSpec = [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open($command, $descriptorSpec, $pipes, ROOT_DIRECTORY, [
			'HOME' => $homeDir,
			'XDG_CONFIG_HOME' => $configDir,
			'XDG_CACHE_HOME' => $cacheDir,
		]);
		if (!is_resource($process)) {
			throw new \App\Exceptions\AppException('Unable to start Chromium PDF renderer.');
		}
		$output = stream_get_contents($pipes[1]);
		$error = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$exitCode = proc_close($process);
		if ($exitCode !== 0) {
			\App\Log\Log::error('Chromium PDF renderer failed: ' . trim($output . "\n" . $error));
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_EXPORT_ERROR', $this->moduleName ?: 'Vtiger'));
		}
	}

	protected function sendPdfToBrowser($filePath)
	{
		$name = ($this->getFileName() ?: 'document') . '.pdf';
		header('Content-Type: application/pdf');
		header('Content-Disposition: inline; filename="' . str_replace('"', '', $name) . '"');
		header('Content-Length: ' . filesize($filePath));
		readfile($filePath);
	}

	protected function createTempFile($extension)
	{
		$this->ensureCacheDir();
		$file = tempnam(ROOT_DIRECTORY . '/cache/pdf', 'chrome_pdf_');
		$target = $file . '.' . $extension;
		rename($file, $target);
		return $target;
	}

	protected function createTempDir()
	{
		$this->ensureCacheDir();
		$dir = ROOT_DIRECTORY . '/cache/pdf/chrome_profile_' . uniqid('', true);
		mkdir($dir, 0700, true);
		return $dir;
	}

	protected function ensureCacheDir()
	{
		$dir = ROOT_DIRECTORY . '/cache/pdf';
		if (!is_dir($dir)) {
			mkdir($dir, 0775, true);
		}
	}

	protected function cleanup(array $files, array $dirs)
	{
		foreach ($files as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}
		foreach ($dirs as $dir) {
			if (is_dir($dir)) {
				$this->removeDir($dir);
			}
		}
	}

	protected function removeDir($dir)
	{
		$items = scandir($dir);
		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $item;
			is_dir($path) ? $this->removeDir($path) : unlink($path);
		}
		rmdir($dir);
	}
}
