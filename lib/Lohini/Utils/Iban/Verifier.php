<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini (http://lohini.net)
 *
 * @copyright (c) 2010, 2014 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Utils\Iban;
/**
 * based on PHP IBAN - http://code.google.com/p/php-iban - LGPLv3
 */
/**
 * Lohini port
 * @author Lopo <lopo@lohini.net>
 */

/**
 * IBAN verifier
 *
 * @author Lopo <lopo@lohini.net>
 */
class Verifier
extends \Nette\Object
{
	/** @var array */
	protected $registry;
	/** @var bool */
	public $disableIibanGmpExtension=FALSE;


	/**
	 * @param \Lohini\Utils\Iban\Registry $registry
	 */
	public function __construct(Registry $registry=NULL)
	{
		if ($registry) {
			$this->registry=$registry->getRegistry();
			}
	}

	/**
	 * Verify an IBAN number
	 *
	 * @param string $iban
	 * @return bool
	 * @note Input can be printed 'IIBAN xx xx xx...' or 'IBAN xx xx xx...' or machine 'xxxxx' format.
	 */
	public function verifyIban($iban)
	{
		// First convert to machine format.
		$iban=$this->machineFormat($iban);
		// Get country of IBAN
		$country=$this->getCountryPart($iban);

		// Test length of IBAN
		if (strlen($iban)!=$this->countryIbanLength($country)) {
			return FALSE;
			}

		# Get country-specific IBAN format regex
		$regex='/'.$this->countryIbanFormatRegex($country).'/';

		// Check regex
		if (!preg_match($regex, $iban)) {
			return FALSE;
			}
		// Regex passed, check checksum
		return $this->verifyChecksum($iban);
	}

	/**
	 * Convert an IBAN to machine format. To do this, we remove IBAN from the start, if present, and remove
	 * non basic roman letter / digit characters
	 *
	 * @param string $iban
	 * @return string
	 */
	protected function machineFormat($iban)
	{
		// Uppercase and trim spaces from left
		$iban=ltrim(strtoupper($iban));
		// Remove IIBAN or IBAN from start of string, if present
		$iban=preg_replace('/^I?IBAN/', '', $iban);
		// Remove all non basic roman letter / digit characters
		$iban=preg_replace('/[^a-zA-Z0-9]/', '', $iban);
		return $iban;
	}

	/**
	 * Convert an IBAN to human format. To do this, we simply insert spaces right now,
	 * as per the ECBS (European Committee for Banking Standards) recommendations
	 * available at: http://www.europeanpaymentscouncil.eu/knowledge_bank_download.cfm?file=ECBS%20standard%20implementation%20guidelines%20SIG203V3.2.pdf
	 *
	 * @param string $iban
	 * @return string
	 */
	public function humanFormat($iban)
	{
		// First verify validity, or return
		if (!$this->verifyIban($iban)) {
			return FALSE;
			}
		// Add spaces every four characters
		$human_iban='';
		for ($i=0; $i<strlen($iban); $i++) {
			$human_iban.=substr($iban, $i, 1);
			if (($i>0) && (($i+1)%4==0)) {
				$human_iban.=' ';
				}
			}
		return $human_iban;
	}

	/**
	 * Get the country part from an IBAN
	 *
	 * @param string $iban
	 * @return string
	 */
	protected function getCountryPart($iban)
	{
		$iban=$this->machineFormat($iban);
		return substr($iban, 0, 2);
	}

	/*
	 * Get the checksum part from an IBAN
	 *
	 * @param string $iban
	 * @return string
	 */
	public function getChecksumPart($iban)
	{
		$iban=$this->machineFormat($iban);
		return substr($iban, 2, 2);
	}

	/**
	 * Get the BBAN part from an IBAN
	 *
	 * @param string $iban
	 * @return string
	 */
	public function getBbanPart($iban)
	{
		$iban=$this->machineFormat($iban);
		return substr($iban, 4);
	}

	/**
	 * Check the checksum of an IBAN - code modified from Validate_Finance PEAR class
	 *
	 * @param string $iban
	 * @return bool
	 */
	protected function verifyChecksum($iban)
	{
		// convert to machine format
		$iban=$this->machineFormat($iban);
		// move first 4 chars (countrycode and checksum) to the end of the string
		$tempiban=substr($iban, 4).substr($iban, 0, 4);
		// subsitutute chars
		$tempiban=$this->replaceChecksumString($tempiban);
		# mod97-10
		return $this->mod97_10($tempiban);
	}

	/**
	 * Find the correct checksum for an IBAN
	 *
	 * @param string $iban The IBAN whose checksum should be calculated
	 * @return string
	 */
	protected function findChecksum($iban)
	{
		$iban=$this->machineFormat($iban);
		// move first 4 chars to right
		$left=substr($iban, 0, 2).'00'; // but set right-most 2 (checksum) to '00'
		$right=substr($iban, 4);
		// glue back together
		$tmp=$right.$left;
		// convert letters using conversion table
		$tmp=$this->replaceChecksumString($tmp);
		// get mod97-10 output
		$checksum=$this->mod97_10_checksum($tmp);
		// return 98 minus the mod97-10 output, left zero padded to two digits
		return str_pad((98-$checksum), 2, '0', STR_PAD_LEFT);
	}

	/**
	 * Set the correct checksum for an IBAN
	 *
	 * @param string $iban IBAN whose checksum should be set
	 * @return string
	 */
	public function setChecksum($iban)
	{
		$iban=$this->machineFormat($iban);
		return substr($iban, 0, 2).$this->findChecksum($iban).substr($iban, 4);
	}

	/**
	 * Character substitution required for IBAN MOD97-10 checksum validation/generation
	 *
	 * @param string $s Input string (IBAN)
	 * @return string
	 */
	protected function replaceChecksumString($s)
	{
		$iban_replace_chars=range('A', 'Z');
		foreach (range(10, 35) as $tempvalue) {
			$iban_replace_values[]=strval($tempvalue);
			}
		return str_replace($iban_replace_chars, $iban_replace_values, $s);
	}

	/*
	 * Same as below but actually returns resulting checksum
	 */
	protected function mod97_10_checksum($numeric_representation)
	{
		$checksum=intval(substr($numeric_representation, 0, 1));
		for ($position=1; $position<strlen($numeric_representation); $position++) {
			$checksum*=10;
			$checksum+=intval(substr($numeric_representation, $position, 1));
			$checksum%=97;
			}
		return $checksum;
	}

	/**
	 * Perform MOD97-10 checksum calculation ('Germanic-level effiency' version - thanks Chris!)
	 *
	 * @param string $numeric_representation Input string (IBAN)
	 */
	protected function mod97_10($numeric_representation)
	{
		// prefer php5 gmp extension if available
		if (!$this->disableIibanGmpExtension && function_exists('gmp_intval')) {
			return gmp_intval(gmp_mod(gmp_init($numeric_representation, 10), '97'))===1;
			}
		// manual processing (~3x slower)
		$length=strlen($numeric_representation);
		$rest='';
		$position=0;
		while ($position<$length) {
			$value=9-strlen($rest);
			$n=$rest.substr($numeric_representation, $position, $value);
			$rest=$n%97;
			$position=$position+$value;
			}
		return $rest===1;
	}

	/**
	 * Get an array of all the parts from an IBAN
	 *
	 * @param string $iban
	 * @return array
	 */
	protected function getParts($iban)
	{
		return [
			'country' => $this->getCountryPart($iban),
			'checksum' => $this->getChecksumPart($iban),
			'bban' => $this->getBbanPart($iban),
			'bank' => $this->getBankPart($iban),
			'country' => $this->getCountryPart($iban),
			'branch' => $this->getBranchPart($iban),
			'account' => $this->getAccountPart($iban)
			];
	}

	/**
	 * Get the Bank ID (institution code) from an IBAN
	 *
	 * @param string $iban
	 * @return string
	 */
	public function getBankPart($iban)
	{
		$iban=$this->machineFormat($iban);
		$country=$this->getCountryPart($iban);
		$start=$this->countryBankidStartOffset($country);
		$stop=$this->countryBankidStopOffset($country);
		if ($start!='' && $stop!='') {
			$bban=$this->getBbanPart($iban);
			return substr($bban, $start, ($stop-$start+1));
			}
		return '';
	}

	/**
	 * Get the Branch ID (sort code) from an IBAN
	 *
	 * @param string $iban
	 * @return string
	 */
	public function getBranchPart($iban)
	{
		$iban=$this->machineFormat($iban);
		$country=$this->getCountryPart($iban);
		$start=$this->countryBranchidStartOffset($country);
		$stop=$this->countryBbranchidStopOffset($country);
		if ($start!='' && $stop!='') {
			$bban=$this->getBbanPart($iban);
			return substr($bban, $start, ($stop-$start+1));
			}
		return '';
	}

	/**
	 * Get the (branch-local) account ID from an IBAN
	 *
	 * @param string $iban
	 * @return string
	 */
	public function getAccountPart($iban)
	{
		$iban=$this->machineFormat($iban);
		$country=$this->getCountryPart($iban);
		$start=$this->countryBbranchidStopOffset($country);
		if ($start=='') {
			$start=$this->countryBankidStopOffset($country);
			}
		if ($start!='') {
			$bban=$this->getBbanPart($iban);
			return substr($bban, $start+1);
			}
		return '';
	}

	/**
	 * Get the name of an IBAN country
	 *
	 * @param string $ibanCountry
	 */
	protected function countryName($ibanCountry)
	{
		return $this->getCountryInfo($ibanCountry, 'country_name');
	}

	/**
	 * Get the domestic example for an IBAN country
	 *
	 * @param type $ibanCountry
	 * @return type string
	 */
	protected function countryDomesticExample($ibanCountry)
	{
		return $this->getCountryInfo($ibanCountry, 'domestic_example');
	}

	/**
	 * Get the BBAN example for an IBAN country
	 *
	 * @param string $ibanCountry
	 * @return type string
	 */
	protected function countryBbanExample($ibanCountry)
	{
		return $this->getCountryInfo($ibanCountry, 'bban_example');
	}

	/**
	 * Get the BBAN format (in SWIFT format) for an IBAN country
	 *
	 * @param string $ibanCountry
	 * @return type string
	 */
	protected function countryBbanFormatSwift($ibanCountry)
	{
		return $this->getCountryInfo($ibanCountry, 'bban_format_swift');
	}

	/**
	 * Get the BBAN format (as a regular expression) for an IBAN country
	 *
	 * @param string $ibanCountry
	 * @return type string
	 */
	protected function countryBbanFormatRegex($ibanCountry)
	{
		return $this->getCountryInfo($ibanCountry, 'bban_format_regex');
	}

	/**
	 * Get the BBAN length for an IBAN country
	 *
	 * @param string $ibanCountry
	 * @return type string
	 */
	protected function countryBbanLength($ibanCountry)
	{
		return $this->getCountryInfo($ibanCountry, 'bban_length');
	}

	/**
	 * Get the IBAN example for an IBAN country
	 *
	 * @param string $ibanCountry
	 * @return type string
	 */
	protected function countryIbanExample($ibanCountry)
	{
		return $this->getCountryInfo($ibanCountry, 'iban_example');
	}

	/**
	 * Get the IBAN format (in SWIFT format) for an IBAN country
	 *
	 * @param string $ibanCountry
	 * @return type string
	 */
	protected function countryIbanFormatSwift($ibanCountry)
	{
		return $this->getCountryInfo($ibanCountry, 'iban_format_swift');
	}

	/**
	 * Get the IBAN format (as a regular expression) for an IBAN country
	 *
	 * @param string $ibanCountry
	 * @return type string
	 */
	protected function countryIbanFormatRegex($ibanCountry)
	{
		return $this->getCountryInfo($ibanCountry, 'iban_format_regex');
	}

	/**
	 * Get the IBAN length for an IBAN country
	 *
	 * @param string $ibanCountry
	 * @return type string
	 */
	protected function countryIbanLength($ibanCountry)
	{
		return $this->getCountryInfo($ibanCountry, 'iban_length');
	}

	/**
	 * Get the BBAN Bank ID start offset for an IBAN country
	 *
	 * @param string $ibanCountry
	 * @return type string
	 */
	protected function countryBankidStartOffset($ibanCountry)
	{
		return $this->getCountryInfo($ibanCountry, 'bban_bankid_start_offset');
	}

	/**
	 * Get the BBAN Bank ID stop offset for an IBAN country
	 *
	 * @param string $ibanCountry
	 * @return type string
	 */
	protected function countryBankidStopOffset($ibanCountry)
	{
		return $this->getCountryInfo($ibanCountry, 'bban_bankid_stop_offset');
	}

	/**
	 * Get the BBAN Branch ID start offset for an IBAN country
	 *
	 * @param string $ibanCountry
	 * @return type string
	 */
	protected function countryBranchidStartOffset($ibanCountry)
	{
		return $this->getCountryInfo($ibanCountry, 'bban_branchid_start_offset');
	}

	/**
	 * Get the BBAN Branch ID stop offset for an IBAN country
	 *
	 * @param string $ibanCountry
	 * @return type string
	 */
	protected function countryBbranchidStopOffset($ibanCountry)
	{
		return $this->getCountryInfo($ibanCountry, 'bban_branchid_stop_offset');
	}

	/**
	 * Get the registry edition for an IBAN country
	 *
	 * @param string $ibanCountry
	 * @return type string
	 */
	protected function countryRegistryEdition($ibanCountry)
	{
		return $this->getCountryInfo($ibanCountry, 'registry_edition');
	}

	/**
	 * Is the IBAN country a SEPA member?
	 *
	 * @param string $ibanCountry
	 * @return type string
	 */
	protected function countryIsSepa($ibanCountry)
	{
		return $this->getCountryInfo($ibanCountry, 'country_sepa');
	}

	/**
	 * Get the list of all IBAN countries
	 *
	 * @return array
	 */
	protected function countries()
	{
		return array_keys($this->registry);
	}

	/**
	 * Get information from the IBAN registry by example IBAN / code combination
	 *
	 * @param string $iban
	 * @param string $code
	 * @return bool
	 */
	protected function getInfo($iban, $code)
	{
		return $this->getCountryInfo($this->getCountryPart($iban), $code);
	}

	/**
	 * Get information from the IBAN registry by country / code combination
	 *
	 * @param string $country
	 * @param string $code
	 * @return bool
	 */
	protected function getCountryInfo($country, $code)
	{
		$country=strtoupper($country);
		$code=strtolower($code);
		if (array_key_exists($country, $this->registry) && array_key_exists($code, $this->registry[$country])) {
			return $this->registry[$country][$code];
			}
		return FALSE;
	}
}
