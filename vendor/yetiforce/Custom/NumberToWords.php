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
		$minus = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_MINUS');
		$zero = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_ZERO');
		$one = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_ONE');
		$two = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_TWO');
		$three = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_THREE');
		$four = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_FOUR');
		$five = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_FIVE');
		$six = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SIX');
		$seven = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SEVEN');
		$eight = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_EIGHT');
		$nine = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_NINE');
		$ten = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_TEN');
		$eleven = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_ELEVEN');
		$twelve = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_TWELVE');
		$thirteen = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_THIRTEEN');
		$fourteen = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_FOURTEEN');
		$fifteen = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_FIFTEEN');
		$sixteen = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SIXTEEN');
		$seventeen = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SEVENTEEN');
		$eighteen = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_EIGHTEEN');
		$nineteen = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_NINETEEN');
		$twenty = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_TWENTY');
		$thirty = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_THIRTY');
		$forty = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_FORTY');
		$fifty = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_FIFTY');
		$sixty = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SIXTY');
		$seventy = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SEVENTY');
		$eighty = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_EIGHTY');
		$ninety = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_NINETY');
		$hundred = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_HUNDRED');
		$twoHundred = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_TWO_HUNDRED');
		$threeHundred = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_THREE_HUNDRED');
		$fourHundred = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_FOUR_HUNDRED');
		$fiveHundred = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_FIVE_HUNDRED');
		$sixHundred = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SIX_HUNDRED');
		$sevenHundred = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SEVEN_HUNDRED');
		$eightHundred = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_EIGHT_HUNDRED');
		$nineHundred = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_NINE_HUNDRED');
		$thousand = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_THOUSAND');
		$thousands = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_THOUSANDS');
		$thousandss = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_THOUSANDSS');
		$million = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_MILLION');
		$millions = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_MILLIONS');
		$millionss = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_MILLIONSS');
		$billion = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_BILLION');
		$billions = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_BILLIONS');
		$billionss = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_BILLIONSS');
		$trillion = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_TRILLION');
		$trillions = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_TRILLIONS');
		$trillionss = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_TRILLIONSS');
		$quadrillion = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_QUADRILLION');
		$quadrillions = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_QUADRILLIONS');
		$quadrillionss = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_QUADRILLIONSS');
		$quinrillion = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_QUINTILLION');
		$quinrillions = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_QUINTILLIONS');
		$quinrillionss = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_QUINTILLIONSS');
		$sextillion = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SEXTILLION');
		$sextillions = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SEXTILLIONS');
		$sextillionss = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SEXTILLIONSS');
		$septillion = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SEPTILLION');
		$septillions = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SEPTILLIONS');
		$septillionss = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SEPTILLIONSS');
		$nonillion = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_NONILLION');
		$nonillions = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_NONILLIONS');
		$nonillionss = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_NONILLIONSS');
		$undecillion = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_UNDECILLION');
		$undecillions = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_UNDECILLIONS');
		$undecillionss = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_UNDECILLIONSS');
		$tredecillion = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_TREDECILLION');
		$tredecillions = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_TREDECILLIONS');
		$tredecillionss = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_TREDECILLIONSS');
		$quindecillion = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_QUINDECILLION');
		$quindecillions = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_QUINDECILLIONS');
		$quindecillionss = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_QUINDECILLIONSS');
		$septendecillion = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SEPTENDECILLION');
		$septendecillions = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SEPTENDECILLIONS');
		$septendecillionss = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SEPTENDECILLIONSS');
		$novemdecillion = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_NOVEMDECILLION');
		$novemdecillions = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_NOVEMDECILLIONS');
		$novemdecillionss = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_NOVEMDECILLIONSS');

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

		if ($in{0} == '-') {
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
