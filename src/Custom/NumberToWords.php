<?php
namespace App\Custom;

/**
 * Numbers to words converter class
 * @package YetiForce.Custom
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class NumberToWords
{

	/**
	 * @var array
	 */
	protected static $words = [];

	public static function initialize()
	{
		$minus = \App\Runtime\Vtiger_Language_Handler::translate('LBL_MINUS');
		$zero = \App\Runtime\Vtiger_Language_Handler::translate('LBL_ZERO');
		$one = \App\Runtime\Vtiger_Language_Handler::translate('LBL_ONE');
		$two = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TWO');
		$three = \App\Runtime\Vtiger_Language_Handler::translate('LBL_THREE');
		$four = \App\Runtime\Vtiger_Language_Handler::translate('LBL_FOUR');
		$five = \App\Runtime\Vtiger_Language_Handler::translate('LBL_FIVE');
		$six = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SIX');
		$seven = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEVEN');
		$eight = \App\Runtime\Vtiger_Language_Handler::translate('LBL_EIGHT');
		$nine = \App\Runtime\Vtiger_Language_Handler::translate('LBL_NINE');
		$ten = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TEN');
		$eleven = \App\Runtime\Vtiger_Language_Handler::translate('LBL_ELEVEN');
		$twelve = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TWELVE');
		$thirteen = \App\Runtime\Vtiger_Language_Handler::translate('LBL_THIRTEEN');
		$fourteen = \App\Runtime\Vtiger_Language_Handler::translate('LBL_FOURTEEN');
		$fifteen = \App\Runtime\Vtiger_Language_Handler::translate('LBL_FIFTEEN');
		$sixteen = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SIXTEEN');
		$seventeen = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEVENTEEN');
		$eighteen = \App\Runtime\Vtiger_Language_Handler::translate('LBL_EIGHTEEN');
		$nineteen = \App\Runtime\Vtiger_Language_Handler::translate('LBL_NINETEEN');
		$twenty = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TWENTY');
		$thirty = \App\Runtime\Vtiger_Language_Handler::translate('LBL_THIRTY');
		$forty = \App\Runtime\Vtiger_Language_Handler::translate('LBL_FORTY');
		$fifty = \App\Runtime\Vtiger_Language_Handler::translate('LBL_FIFTY');
		$sixty = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SIXTY');
		$seventy = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEVENTY');
		$eighty = \App\Runtime\Vtiger_Language_Handler::translate('LBL_EIGHTY');
		$ninety = \App\Runtime\Vtiger_Language_Handler::translate('LBL_NINETY');
		$hundred = \App\Runtime\Vtiger_Language_Handler::translate('LBL_HUNDRED');
		$twoHundred = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TWO_HUNDRED');
		$threeHundred = \App\Runtime\Vtiger_Language_Handler::translate('LBL_THREE_HUNDRED');
		$fourHundred = \App\Runtime\Vtiger_Language_Handler::translate('LBL_FOUR_HUNDRED');
		$fiveHundred = \App\Runtime\Vtiger_Language_Handler::translate('LBL_FIVE_HUNDRED');
		$sixHundred = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SIX_HUNDRED');
		$sevenHundred = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEVEN_HUNDRED');
		$eightHundred = \App\Runtime\Vtiger_Language_Handler::translate('LBL_EIGHT_HUNDRED');
		$nineHundred = \App\Runtime\Vtiger_Language_Handler::translate('LBL_NINE_HUNDRED');
		$thousand = \App\Runtime\Vtiger_Language_Handler::translate('LBL_THOUSAND');
		$thousands = \App\Runtime\Vtiger_Language_Handler::translate('LBL_THOUSANDS');
		$thousandss = \App\Runtime\Vtiger_Language_Handler::translate('LBL_THOUSANDSS');
		$million = \App\Runtime\Vtiger_Language_Handler::translate('LBL_MILLION');
		$millions = \App\Runtime\Vtiger_Language_Handler::translate('LBL_MILLIONS');
		$millionss = \App\Runtime\Vtiger_Language_Handler::translate('LBL_MILLIONSS');
		$billion = \App\Runtime\Vtiger_Language_Handler::translate('LBL_BILLION');
		$billions = \App\Runtime\Vtiger_Language_Handler::translate('LBL_BILLIONS');
		$billionss = \App\Runtime\Vtiger_Language_Handler::translate('LBL_BILLIONSS');
		$trillion = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TRILLION');
		$trillions = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TRILLIONS');
		$trillionss = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TRILLIONSS');
		$quadrillion = \App\Runtime\Vtiger_Language_Handler::translate('LBL_QUADRILLION');
		$quadrillions = \App\Runtime\Vtiger_Language_Handler::translate('LBL_QUADRILLIONS');
		$quadrillionss = \App\Runtime\Vtiger_Language_Handler::translate('LBL_QUADRILLIONSS');
		$quinrillion = \App\Runtime\Vtiger_Language_Handler::translate('LBL_QUINTILLION');
		$quinrillions = \App\Runtime\Vtiger_Language_Handler::translate('LBL_QUINTILLIONS');
		$quinrillionss = \App\Runtime\Vtiger_Language_Handler::translate('LBL_QUINTILLIONSS');
		$sextillion = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEXTILLION');
		$sextillions = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEXTILLIONS');
		$sextillionss = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEXTILLIONSS');
		$septillion = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEPTILLION');
		$septillions = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEPTILLIONS');
		$septillionss = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEPTILLIONSS');
		$nonillion = \App\Runtime\Vtiger_Language_Handler::translate('LBL_NONILLION');
		$nonillions = \App\Runtime\Vtiger_Language_Handler::translate('LBL_NONILLIONS');
		$nonillionss = \App\Runtime\Vtiger_Language_Handler::translate('LBL_NONILLIONSS');
		$undecillion = \App\Runtime\Vtiger_Language_Handler::translate('LBL_UNDECILLION');
		$undecillions = \App\Runtime\Vtiger_Language_Handler::translate('LBL_UNDECILLIONS');
		$undecillionss = \App\Runtime\Vtiger_Language_Handler::translate('LBL_UNDECILLIONSS');
		$tredecillion = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TREDECILLION');
		$tredecillions = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TREDECILLIONS');
		$tredecillionss = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TREDECILLIONSS');
		$quindecillion = \App\Runtime\Vtiger_Language_Handler::translate('LBL_QUINDECILLION');
		$quindecillions = \App\Runtime\Vtiger_Language_Handler::translate('LBL_QUINDECILLIONS');
		$quindecillionss = \App\Runtime\Vtiger_Language_Handler::translate('LBL_QUINDECILLIONSS');
		$septendecillion = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEPTENDECILLION');
		$septendecillions = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEPTENDECILLIONS');
		$septendecillionss = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEPTENDECILLIONSS');
		$novemdecillion = \App\Runtime\Vtiger_Language_Handler::translate('LBL_NOVEMDECILLION');
		$novemdecillions = \App\Runtime\Vtiger_Language_Handler::translate('LBL_NOVEMDECILLIONS');
		$novemdecillionss = \App\Runtime\Vtiger_Language_Handler::translate('LBL_NOVEMDECILLIONSS');

		$words = [
			$minus,
			[$zero, $one, $two, $three, $four, $five, $six, $seven, $eight, $nine],
			[$ten, $eleven, $twelve, $thirteen, $fourteen, $fifteen, $sixteen, $seventeen, $eighteen, $nineteen],
			[$ten, $twenty, $thirty, $forty, $fifty, $sixty, $seventy, $eighty, $ninety],
			[$hundred, $twoHundred, $threeHundred, $fourHundred, $fiveHundred, $sixHundred, $sevenHundred, $eightHundred, $nineHundred],
			[$thousand, $thousands, $thousandss],
			[$million, $millions, $millionss],
			[$billion, $billions, $billionss],
			[$trillion, $trillions, $trillionss],
			[$quadrillion, $quadrillions, $quadrillionss],
			[$quinrillion, $quinrillions, $quinrillionss],
			[$sextillion, $sextillions, $sextillionss],
			[$septillion, $septillions, $septillionss],
			[$nonillion, $nonillions, $nonillionss],
			[$undecillion, $undecillions, $undecillionss],
			[$tredecillion, $tredecillions, $tredecillionss],
			[$quindecillion, $quindecillions, $quindecillionss],
			[$septendecillion, $septendecillions, $septendecillionss],
			[$novemdecillion, $novemdecillions, $novemdecillionss]
		];

		self::$words = $words;
	}

	/**
	 * Podaje słowną wartość liczby całkowitej (równierz podaną w postaci stringa)
	 *
	 * @param integer $int
	 * @return string
	 */
	public static function integerNumberToWords($int)
	{
		static::initialize();
		$int = strval($int);
		$in = preg_replace('/[^-\d]+/', '', $int);

		$return = '';

		if (isset($in[0]) && $in[0] === '-') {
			$in = substr($in, 1);
			$return = self::$words[0] . ' ';
		}

		$txt = str_split(strrev($in), 3);

		if ($in == 0) {
			$return = self::$words[1][0] . ' ';
		}

		for ($i = count($txt) - 1; $i >= 0; $i--) {
			$number = (int) strrev($txt[$i]);

			if ($number > 0) {
				if ($i == 0) {
					$return .= self::number($number) . ' ';
				} else {
					$return .= ($number > 1 ? self::number($number) . ' ' : '')
						. self::inflection(self::$words[4 + $i], $number) . ' ';
				}
			}
		}

		return self::clear($return);
	}

	/**
	 * Podaje słowną wartość kwoty wraz z wartościami po kropce.
	 * Nie przyjmuje wartości przedzielonych przecinkami (jako wartości nie numerycznych).
	 *
	 * @param integer|string $amount
	 * @param string $currencyName
	 * @param string $centName
	 * @return string
	 * @throws \Exception
	 */
	public static function process($amount, $currencyName = 'zł', $centName = 'gr')
	{
		self::initialize();

		if (!is_numeric($amount)) {
			throw new \Exception('Nieprawidłowa kwota');
		}

		$amountString = number_format($amount, 2, '.', '');
		list($bigAmount, $smallAmount) = explode('.', $amountString);

		$bigAmount = static::integerNumberToWords($bigAmount) . ' ' . $currencyName . ' ';
		$smallAmount = static::integerNumberToWords($smallAmount) . ' ' . $centName;

		return self::clear($bigAmount . $smallAmount);
	}

	/**
	 * Czyści podwójne spacje i trimuje
	 *
	 * @param $string
	 * @return mixed
	 */
	protected static function clear($string)
	{
		return preg_replace('!\s+!', ' ', trim($string));
	}

	/**
	 * $inflections = Array('jeden','dwa','pięć')
	 *
	 * @param string[] $inflections
	 * @param $int
	 * @return mixed
	 */
	protected static function inflection(array $inflections, $int)
	{
		$txt = $inflections[2];

		if ($int == 1) {
			$txt = $inflections[0];
		}

		$units = intval(substr($int, -1));

		$rest = $int % 100;

		if (($units > 1 && $units < 5) & !($rest > 10 && $rest < 20)) {
			$txt = $inflections[1];
		}

		return $txt;
	}

	/**
	 * Odmiana dla liczb < 1000
	 *
	 * @param integer $int
	 * @return string
	 */
	protected static function number($int)
	{
		$return = '';

		$j = abs(intval($int));

		if ($j == 0) {
			return self::$words[1][0];
		}

		$units = $j % 10;
		$dozens = intval(($j % 100 - $units) / 10);
		$hundreds = intval(($j - $dozens * 10 - $units) / 100);

		if ($hundreds > 0) {
			$return .= self::$words[4][$hundreds - 1] . ' ';
		}

		if ($dozens > 0) {
			if ($dozens == 1) {
				$return .= self::$words[2][$units] . ' ';
			} else {
				$return .= self::$words[3][$dozens - 1] . ' ';
			}
		}

		if ($units > 0 && $dozens != 1) {
			$return .= self::$words[1][$units] . ' ';
		}

		return $return;
	}
}
