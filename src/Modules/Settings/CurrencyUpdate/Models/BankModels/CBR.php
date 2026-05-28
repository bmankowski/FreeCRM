<?php

namespace App\Modules\Settings\CurrencyUpdate\Models\BankModels;


/**
 * @package YetiForce.models
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 */

/**
 * Class for connection to Central Bank of Russia currency exchange rates
 */
class CBR extends \App\Modules\Settings\CurrencyUpdate\Models\AbstractBank
{
	/*
	 * Returns bank name
	 */

	public function getName()
	{
		return 'CBR';
	}
	/*
	 * Returns url sources from where exchange rates are taken from
	 */

	public function getSource()
	{
		return ['http://www.cbr.ru/DailyInfoWebServ/DailyInfo.asmx?WSDL'];
	}
	/*
	 * Returns list of currencies supported by this bank
	 */

	public function getSupportedCurrencies()
	{
		$supportedCurrencies = [];
		$supportedCurrencies[\App\Modules\Settings\CurrencyUpdate\Models\Module::getCRMCurrencyName($this->getMainCurrencyCode())] = $this->getMainCurrencyCode();
		$source = $this->getSource();

		$client = new \SoapClient($source[0]);
		$curs = $client->GetCursOnDate(array("On_date" => date('Y-m-d')));
		$ratesXml = new \SimpleXMLElement($curs->GetCursOnDateResult->any);

		foreach ($ratesXml->ValuteData[0] as $currency) {
			$currencyCode = (string) $currency->VchCode;
			$currencyName = \App\Modules\Settings\CurrencyUpdate\Models\Module::getCRMCurrencyName($currencyCode);
			if ($currencyName) {
				$supportedCurrencies[$currencyName] = $currencyCode;
			}
		}

		return $supportedCurrencies;
	}
	/*
	 * Returns banks main currency 
	 */

	public function getMainCurrencyCode()
	{
		return 'RUB';
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

		$source = $this->getSource();
		$client = new \SoapClient($source[0]);
		$curs = $client->GetCursOnDate(array('On_date' => $dateCur));
		$ratesXml = new \SimpleXMLElement($curs->GetCursOnDateResult->any);

		$datePublicationOfFile = $dateCur;

		$exchangeRate = 1.0;
		// if currency is diffrent than RUB we need to calculate rate for converting other currencies to this one from RUB
		if ($mainCurrency != $this->getMainCurrencyCode()) {
			foreach ($ratesXml->ValuteData[0] as $currencyRate) {
				if ($currencyRate->VchCode == $mainCurrency) {
					echo $currencyRate->VchCode . ' == ' . $mainCurrency . ' = ' . $currencyRate->Vcurs;
					$exchangeRate = $currencyRate->Vcurs;
				}
			}
		}

		foreach ($ratesXml->ValuteData[0] as $currencyRate) {
			$currency = (string) $currencyRate->VchCode;
			foreach ($otherCurrencyCode as $key => $currId) {
				if ($key == $currency && $currency != $mainCurrency) {
					$curs = (string) $currencyRate->Vcurs;
					$nom = (string) $currencyRate->Vnom;
					$exchange = $curs / $nom;

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

		// currency diffrent than RUB, we need to add manually RUB rates
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
