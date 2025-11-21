<?php

namespace App\Modules\Settings\CurrencyUpdate\Models\BankModels;


/**
 * @package YetiForce.models
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 */

/**
 * Class for connection to Narodowy Bank Polski currency exchange rates
 */
class NBP extends \App\Modules\Settings\CurrencyUpdate\Models\AbstractBank
{
	/*
	 * Returns bank name
	 */

	public function getName()
	{
		return 'NBP';
	}
	/*
	 * Returns url sources from where exchange rates are taken from
	 */

	public function getSource()
	{
		return ['http://nbp.pl/kursy/xml/LastA.xml'];
	}
	/*
	 * Returns list of currencies supported by this bank
	 */

	public function getSupportedCurrencies()
	{
		$supportedCurrencies = [];
		$supportedCurrencies[\App\Modules\Settings\CurrencyUpdate\Models\Module::getCRMCurrencyName($this->getMainCurrencyCode())] = $this->getMainCurrencyCode();
		$dateCur = date('Y-m-d', strtotime('last monday'));
		$date = str_replace('-', '', $dateCur);
		$date = substr($date, 2);

		$txtSrc = 'http://www.nbp.pl/kursy/xml/dir.txt';
		$xmlSrc = 'http://nbp.pl/kursy/xml/';
		$newXmlSrc = '';

		// Set timeout context for file operations
		$context = stream_context_create([
			'http' => [
				'timeout' => 10  // 10 seconds timeout
			]
		]);

		$file = @file($txtSrc, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES, $context);
		if ($file === false) {
			\App\Log\Log::error('Failed to fetch NBP currency directory from: ' . $txtSrc);
			return $supportedCurrencies;
		}

		$fileNum = count($file);
		$numberOfDays = 1;
		$stateA = false;
		$maxDays = 30; // Maximum 30 days back

		while (!$stateA && $numberOfDays <= $maxDays) {
			for ($i = 0; $i < $fileNum; $i++) {
				$lineStart = strstr($file[$i], $date, true);
				if ($lineStart && $lineStart[0] == 'a') {
					$stateA = true;
					$newXmlSrc = $xmlSrc . $lineStart . $date . '.xml';
					break;
				}
			}

			if (!$stateA) {
				$newDate = strtotime("-$numberOfDays day", strtotime($dateCur));
				$newDate = date('Y-m-d', $newDate);

				$date = str_replace('-', '', $newDate);
				$date = substr($date, 2);
				$numberOfDays++;
			}
		}

		if (!$stateA || empty($newXmlSrc)) {
			\App\Log\Log::error('Could not find NBP currency file after checking ' . $maxDays . ' days');
			return $supportedCurrencies;
		}

		$xml = @simplexml_load_file($newXmlSrc, null, LIBXML_NOERROR | LIBXML_NOWARNING, '', false);
		if ($xml === false) {
			\App\Log\Log::error('Failed to load NBP currency XML from: ' . $newXmlSrc);
			return $supportedCurrencies;
		}

		$xmlObj = $xml->children();

		$num = count($xmlObj->pozycja);

		for ($i = 0; $i <= $num; $i++) {
			if (!$xmlObj->pozycja[$i]->nazwa_waluty) {
				continue;
			}
			$currencyCode = (string) $xmlObj->pozycja[$i]->kod_waluty;

			if ($currencyCode == 'XDR') {
				continue;
			}

			$currencyName = \App\Modules\Settings\CurrencyUpdate\Models\Module::getCRMCurrencyName($currencyCode);
			$supportedCurrencies[$currencyName] = $currencyCode;
		}

		return $supportedCurrencies;
	}
	/*
	 * Returns banks main currency 
	 */

	public function getMainCurrencyCode()
	{
		return 'PLN';
	}
	/*
	 * Fetch exchange rates
	 * @param <Array> $currencies - list of systems active currencies
	 * @param <Date> $date - date for which exchange is fetched
	 * @param boolean $cron - if true then it is fired by server and crms currency conversion rates are updated 
	 */

	public function getRates($otherCurrencyCode, $dateParam, $cron = false)
	{
		$moduleModel = \App\Modules\Settings\CurrencyUpdate\Models\Module::getCleanInstance();
		$selectedBank = $moduleModel->getActiveBankId();
		$yesterday = date('Y-m-d', strtotime('-1 day'));

		// check if data is correct, currency rates can be retrieved only for working days
		$lastWorkingDay = \vtlib\Functions:: getLastWorkingDay($yesterday);

		$today = date('Y-m-d');
		$mainCurrency = \vtlib\Functions:: getDefaultCurrencyInfo()['currency_code'];

		$dateCur = $dateParam;
		$chosenYear = date('Y', strtotime($dateCur));
		$date = str_replace('-', '', $dateCur);
		$date = substr($date, 2);

		if (date('Y') == $chosenYear) {
			$txtSrc = 'http://www.nbp.pl/kursy/xml/dir.txt';
		} else {
			$txtSrc = 'http://www.nbp.pl/kursy/xml/dir' . $chosenYear . '.txt';
		}
		$xmlSrc = 'http://nbp.pl/kursy/xml/';
		$newXmlSrc = '';

		// Set timeout context for file operations
		$context = stream_context_create([
			'http' => [
				'timeout' => 10  // 10 seconds timeout
			]
		]);

		$file = @file($txtSrc, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES, $context);
		if ($file === false) {
			\App\Log\Log::error('Failed to fetch NBP currency directory from: ' . $txtSrc);
			return;
		}

		$fileNum = count($file);
		$numberOfDays = 1;
		$stateA = false;
		$maxDays = 30; // Maximum 30 days back

		while (!$stateA && $file && $numberOfDays <= $maxDays) {
			for ($i = 0; $i < $fileNum; $i++) {
				$lineStart = strstr($file[$i], $date, true);
				if ($lineStart && $lineStart[0] == 'a') {
					$stateA = true;
					$newXmlSrc = $xmlSrc . $lineStart . $date . '.xml';
					break;
				}
			}

			if ($stateA === false) {
				$newDate = strtotime("-$numberOfDays day", strtotime($dateCur));
				$newDate = date('Y-m-d', $newDate);

				$date = str_replace('-', '', $newDate);
				$date = substr($date, 2);
				$numberOfDays++;
			}
		}

		if (!$stateA || empty($newXmlSrc)) {
			\App\Log\Log::error('Could not find NBP currency file after checking ' . $maxDays . ' days for date: ' . $dateCur);
			return;
		}

		$xml = @simplexml_load_file($newXmlSrc, null, LIBXML_NOERROR | LIBXML_NOWARNING);
		if ($xml === false) {
			\App\Log\Log::error('Failed to load NBP currency XML from: ' . $newXmlSrc);
			return;
		}

		$xmlObj = $xml->children();

		$num = count($xmlObj->pozycja);
		$datePublicationOfFile = (string) $xmlObj->data_publikacji;

		$exchangeRate = 1.0;
		// if currency is diffrent than PLN we need to calculate rate for converting other currencies to this one from PLN
		if ($mainCurrency != $this->getMainCurrencyCode()) {
			for ($i = 0; $i <= $num; $i++) {
				if ($xmlObj->pozycja[$i]->kod_waluty == $mainCurrency) {
					$exchangeRate = str_replace(',', '.', $xmlObj->pozycja[$i]->kurs_sredni);
				}
			}
		}

		for ($i = 0; $i <= $num; $i++) {
			if (!$xmlObj->pozycja[$i]->nazwa_waluty) {
				continue;
			}
			$currency = (string) $xmlObj->pozycja[$i]->kod_waluty;
			foreach ($otherCurrencyCode as $key => $currId) {
				if ($key == $currency && $currency != $mainCurrency) {
					$exchange = str_replace(',', '.', $xmlObj->pozycja[$i]->kurs_sredni);
					$exchange = $exchange / $xmlObj->pozycja[$i]->przelicznik;
					$exchangeVtiger = $exchangeRate / $exchange;
					$exchange = $exchange / $exchangeRate;

					if ($cron === true || ((strtotime($dateParam) == strtotime($today)) || (strtotime($dateParam) == strtotime($lastWorkingDay)))) {
						$moduleModel->setCRMConversionRate($currency, $exchangeVtiger);
					}

					$existingId = $moduleModel->getCurrencyRateId($currId, $datePublicationOfFile, $selectedBank);

					if ($existingId > 0) {
						$moduleModel->updateCurrencyRate($existingId, $exchange);
					} else {
						$moduleModel->addCurrencyRate($currId, $datePublicationOfFile, $exchange, $selectedBank);
					}
				}
			}
		}

		// currency diffrent than PLN, we need to add manually PLN rates
		if ($mainCurrency != $this->getMainCurrencyCode()) {
			$exchange = 1.00000 / $exchangeRate;
			$mainCurrencyId = false;
			foreach ($otherCurrencyCode as $code => $id) {
				if ($code == $this->getMainCurrencyCode()) {
					$mainCurrencyId = $id;
				}
			}

			if ($mainCurrencyId) {
				if ($cron === true || ((strtotime($dateParam) == strtotime($today)) || (strtotime($dateParam) == strtotime($lastWorkingDay)))) {
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
