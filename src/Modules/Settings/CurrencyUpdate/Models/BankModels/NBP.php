<?php

namespace App\Modules\Settings\CurrencyUpdate\Models\BankModels;

/**
 * Class for connection to Narodowy Bank Polski currency exchange rates
 * 
 * @package FreeCRM
 * @license FreeCRM Public License 1.1
 * @author bmankowski@gmail.com
 */
class NBP extends \App\Modules\Settings\CurrencyUpdate\Models\AbstractBank
{
	private const XML_URL = 'https://static.nbp.pl/dane/kursy/xml/';
	private const TIMEOUT = 10;
	private const MAX_DAYS_BACK = 30;

	/**
	 * Returns bank name
	 */
	public function getName()
	{
		return 'NBP';
	}

	/**
	 * Returns url sources from where exchange rates are taken from
	 */
	public function getSource()
	{
		return ['https://static.nbp.pl/dane/kursy/xml/LastA.xml'];
	}

	/**
	 * Returns banks main currency 
	 */
	public function getMainCurrencyCode()
	{
		return 'PLN';
	}

	/**
	 * Find NBP XML file for given date
	 * @param string $date Date in Y-m-d format
	 * @return string|null XML file URL or null if not found
	 */
	private function findXmlFile($date)
	{
		$year = date('Y', strtotime($date));
		$currentYear = date('Y');
		
		$txtSrc = ($year == $currentYear) 
			? 'https://static.nbp.pl/dane/kursy/xml/dir.txt'
			: "https://static.nbp.pl/dane/kursy/xml/dir{$year}.txt";

		$context = stream_context_create([
			'http' => ['timeout' => self::TIMEOUT],
			'https' => ['timeout' => self::TIMEOUT]
		]);

		$file = @file($txtSrc, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES, $context);
		if ($file === false) {
			\App\Log\Log::error('Failed to fetch NBP currency directory from: ' . $txtSrc);
			return null;
		}

		$dateShort = substr(str_replace('-', '', $date), 2);
		$numberOfDays = 0;

		while ($numberOfDays < self::MAX_DAYS_BACK) {
			foreach ($file as $line) {
				$lineStart = strstr($line, $dateShort, true);
				if ($lineStart && $lineStart[0] == 'a') {
					return self::XML_URL . $lineStart . $dateShort . '.xml';
				}
			}

			// Try previous day
			$numberOfDays++;
			$date = date('Y-m-d', strtotime("-$numberOfDays day", strtotime($date)));
			$dateShort = substr(str_replace('-', '', $date), 2);
		}

		\App\Log\Log::error('Could not find NBP currency file after checking ' . self::MAX_DAYS_BACK . ' days');
		return null;
	}

	/**
	 * Load XML from NBP
	 * @param string $url XML file URL
	 * @return \SimpleXMLElement|null
	 */
	private function loadXml($url)
	{
		$context = stream_context_create([
			'http' => ['timeout' => self::TIMEOUT],
			'https' => ['timeout' => self::TIMEOUT]
		]);

		$xml = @simplexml_load_file($url, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING, '', false);
		if ($xml === false) {
			\App\Log\Log::error('Failed to load NBP currency XML from: ' . $url);
			return null;
		}

		return $xml;
	}

	/**
	 * Returns list of currencies supported by this bank
	 */
	public function getSupportedCurrencies()
	{
		$supportedCurrencies = [];
		$supportedCurrencies[\App\Modules\Settings\CurrencyUpdate\Models\Module::getCRMCurrencyName($this->getMainCurrencyCode())] = $this->getMainCurrencyCode();

		$xmlUrl = $this->findXmlFile(date('Y-m-d', strtotime('last monday')));
		if (!$xmlUrl) {
			return $supportedCurrencies;
		}

		$xml = $this->loadXml($xmlUrl);
		if (!$xml) {
			return $supportedCurrencies;
		}

		foreach ($xml->pozycja as $pozycja) {
			if (!$pozycja->nazwa_waluty) {
				continue;
			}

			$currencyCode = (string) $pozycja->kod_waluty;
			if ($currencyCode == 'XDR') {
				continue;
			}

			$currencyName = \App\Modules\Settings\CurrencyUpdate\Models\Module::getCRMCurrencyName($currencyCode);
			$supportedCurrencies[$currencyName] = $currencyCode;
		}

		return $supportedCurrencies;
	}
	/**
	 * Fetch exchange rates
	 * @param array $otherCurrencyCode List of systems active currencies
	 * @param string $dateParam Date for which exchange is fetched
	 * @param bool $cron If true then it is fired by server and crms currency conversion rates are updated 
	 */
	public function getRates($otherCurrencyCode, $dateParam, $cron = false)
	{
		$moduleModel = \App\Modules\Settings\CurrencyUpdate\Models\Module::getCleanInstance();
		$selectedBank = $moduleModel->getActiveBankId();
		$yesterday = date('Y-m-d', strtotime('-1 day'));
		$lastWorkingDay = \vtlib\Functions::getLastWorkingDay($yesterday);
		$today = date('Y-m-d');
		$mainCurrency = \vtlib\Functions::getDefaultCurrencyInfo()['currency_code'];

		$xmlUrl = $this->findXmlFile($dateParam);
		if (!$xmlUrl) {
			return;
		}

		$xml = $this->loadXml($xmlUrl);
		if (!$xml) {
			return;
		}

		$datePublicationOfFile = (string) $xml->data_publikacji;

		// Find exchange rate for main currency if it's not PLN
		$exchangeRate = 1.0;
		if ($mainCurrency != $this->getMainCurrencyCode()) {
			foreach ($xml->pozycja as $pozycja) {
				if ((string) $pozycja->kod_waluty == $mainCurrency) {
					$exchangeRate = str_replace(',', '.', (string) $pozycja->kurs_sredni);
					break;
				}
			}
		}

		$shouldUpdateCRM = $cron || (strtotime($dateParam) == strtotime($today)) || (strtotime($dateParam) == strtotime($lastWorkingDay));

		// Process each currency
		foreach ($xml->pozycja as $pozycja) {
			if (!$pozycja->nazwa_waluty) {
				continue;
			}

			$currency = (string) $pozycja->kod_waluty;
			if (!isset($otherCurrencyCode[$currency]) || $currency == $mainCurrency) {
				continue;
			}

			$currId = $otherCurrencyCode[$currency];
			$exchange = str_replace(',', '.', (string) $pozycja->kurs_sredni);
			$exchange = $exchange / (int) $pozycja->przelicznik;
			$exchangeVtiger = $exchangeRate / $exchange;
			$exchange = $exchange / $exchangeRate;

			if ($shouldUpdateCRM) {
				$moduleModel->setCRMConversionRate($currency, $exchangeVtiger);
			}

			$existingId = $moduleModel->getCurrencyRateId($currId, $datePublicationOfFile, $selectedBank);
			if ($existingId > 0) {
				$moduleModel->updateCurrencyRate($existingId, $exchange);
			} else {
				$moduleModel->addCurrencyRate($currId, $datePublicationOfFile, $exchange, $selectedBank);
			}
		}

		// If main currency is not PLN, add PLN rates manually
		if ($mainCurrency != $this->getMainCurrencyCode()) {
			$mainCurrencyId = $otherCurrencyCode[$this->getMainCurrencyCode()] ?? null;
			if ($mainCurrencyId) {
				$exchange = 1.0 / $exchangeRate;

				if ($shouldUpdateCRM) {
					$moduleModel->setCRMConversionRate($this->getMainCurrencyCode(), $exchangeRate);
				}

				$existingId = $moduleModel->getCurrencyRateId($mainCurrencyId, $datePublicationOfFile, $selectedBank);
				if ($existingId > 0) {
					$moduleModel->updateCurrencyRate($existingId, $exchange);
				} else {
					$moduleModel->addCurrencyRate($mainCurrencyId, $datePublicationOfFile, $exchange, $selectedBank);
				}
			}
		}
	}
}
