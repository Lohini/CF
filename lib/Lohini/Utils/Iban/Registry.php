<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini
 *
 * @copyright (c) 2010, 2014 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Utils\Iban;
/**
 * based on PHP IBAN r105 - http://code.google.com/p/php-iban - LGPLv3
 */
/**
 * Lohini port
 * @author Lopo <lopo@lohini.net>
 */

use Nette\Caching,
	Nette\Utils\Strings;

/**
 * IBAN registry
 *
 * @author Lopo <lopo@lohini.net>
 */
class Registry
extends \Nette\Caching\Cache
implements \IteratorAggregate
{
	const SOURCE='source',
		DATA='data',
		PARSED='parsed',
		REGISTRY='registry',
		ETAG='etag',
		MD5='md5';
	/** @var string */
	public $sourceUrl='http://www.swift.com/dsp/resources/documents/IBAN_Registry.txt';
	/** @var array Where to store the value of the included PHP cache file */
	private $_registry=[];
	/** @var string */
	private $_etag;
	/** @var string */
	private $_sourceData;
	/** @var array */
	private $_csvData=[];


	/**
	 * Parses the txt file and updates the cache files
	 *
	 * @return bool whether the file was correctly written to the disk
	 */
	public function updateCache($force=FALSE)
	{
		if (($this->_registry=$this->load(Registry::REGISTRY))!==NULL) {
			$cver=$this->load(Registry::ETAG);
			$this->getRemoteData($cver);
			if ($cver==$this->_etag && $this->load(Registry::MD5)==md5($this->_sourceData) && !$force) {
				return TRUE;
				}
			}

		$this->getRemoteData();
		$this->raw2csv();
		$this->csv2registry();

		// Save and return
		$expiration=\Nette\DateTime::from(time())->add(new \DateInterval('P2W'));
		$dependencies=[
			Caching\Cache::CONSTS => [
				'Nette\Framework::REVISION'
				],
			Caching\Cache::EXPIRATION => $expiration
			];
		$this->save(Registry::SOURCE, $this->sourceUrl, $dependencies);
		$this->save(Registry::DATA, $this->_sourceData, $dependencies);
		$this->save(Registry::ETAG, $this->_etag, $dependencies);
		$this->save(Registry::MD5, md5($this->_sourceData), $dependencies);
		$this->save(Registry::REGISTRY, $this->_registry, $dependencies);
		$this->release();
	}

	/**
	 * Loads the cache into object's properties
	 */
	private function loadCache()
	{
		$this->release();
		if (($this->_registry=$this->load(Registry::REGISTRY))===NULL
			|| $this->load(Registry::SOURCE)!=$this->sourceUrl
			) {
			$this->updateCache();
			}
		else {
			$this->_etag=$this->load(Registry::ETAG);
			}
	}

	/**
	 * Retrieve the remote data
	 *
	 * @param string $etag
	 * @throws IbanException
	 */
	private function getRemoteData($etag=NULL)
	{
		$curl=curl_init($this->sourceUrl);
		if (strlen($etag)) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, ['If-None-Match: '.$etag]);
			}
		curl_setopt($curl, CURLOPT_HEADER, TRUE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		$response=curl_exec($curl);
		$info=curl_getinfo($curl);
		@curl_close($curl);
		if ($info['http_code']!=200) {
			throw new IbanException('Error downloading Iban source data');
			}
		$this->_sourceData=Strings::substring($response, $info['header_size']);
		$etag=Strings::match(Strings::substring($response, 0, $info['header_size']), '~ETag\:\s(?P<value>.*)~');
		$this->_etag=isset($etag['value'])? Strings::replace($etag['value'], '~"~') : NULL;
	}

	/**
	 * converts the SWIFT IBAN format specifications to regular expressions
	 * eg: 4!n6!n1!n -> ^(\d{4})(\d{6})(\d{1})$
	 *
	 * @param string $swift
	 * @return string
	 * @throws IbanException
	 */
	protected function swift2regex($swift)
	{
		// first find tokens
		$matches=$this->swiftTokenize($swift);
		// now replace bits
		$tr="^$swift$";
		// loop through each matched token
		for ($i=0; $i<count($matches[0]); $i++) {
			// calculate replacement
			$replacement='(TOKEN)';
			switch ($matches[3][$i]) {
				case 'n': // type 'n'
					$replacement='(\d{length})';
					break;
				case 'c': // type 'c'
					$replacement='([A-Za-z\d]{length})';
					break;
				case 'a': // type 'a'
					$replacement='([A-Z]{length})';
					break;
				default:
					throw IbanException::unknownType($matches[3][$i]);
				}

			// now add length indicator to the token
			$length= $matches[2][$i]=='!'? $matches[1][$i] : '1,'.$matches[1][$i];
			$replacement=Strings::replace($replacement, '/length/', $length, 1);
			// finally, replace the entire token with the replacement
			$tr=Strings::replace($tr, '/'.$matches[0][$i].'/', $replacement, 1);
			}
		return $tr;
	}

	/**
	 * fetch individual tokens in a swift structural string
	 *
	 * @param string $string
	 * @param bool $calculateOffsets
	 * @return array
	 */
	protected function swiftTokenize($string, $calculateOffsets=FALSE)
	{
		$matches=Strings::matchAll($string, '/((?:\d*?[1-2])?\d)(!)?([anc])/', PREG_PATTERN_ORDER);
		if ($calculateOffsets) {
			$currentOffset=0;
			for ($i=0; $i<count($matches[0]); $i++) {
				$matches['offset'][$i]=$currentOffset;
				$currentOffset+=$matches[1][$i];
				}
			}
		return $matches;
	}

	/**
	 * Converts raw TXT to internal CSV
	 */
	private function raw2csv()
	{
		$verifier=new Verifier;
		$lines=preg_split('/[\r\n]+/', $this->_sourceData);
		foreach ($lines as $line) {
			// if it's not a blank line, and it's not the header row
			if ($line=='' || Strings::length($line)<10 || Strings::startsWith($line, 'SEPA Country') || Strings::startsWith($line, 'Name of country')) {
				continue;
				}
			// assigned fields to named variables
			list($countryName, $countryCode, $domesticExample, $bban, $bbanStructure,
				$bbanLength, $bbanBiPosition, $bbanBiLength, $bbanBiExample, $bbanExample,
				$iban, $ibanStructure, $ibanLength, $ibanElectronicExample, $ibanPrintExample,
				$countrySepa, $contactDetails
				)=array_map( // remove quotes and superfluous whitespace on fields that have them
					function($item) {
						return trim(trim($item, '"'), ' ');
						},
					explode("\t", $line) // extract individual tab-separated fields
					);
			// sanitise
			$countryCode=Strings::upper(substr($countryCode, 0, 2)); // sanitise comments away
			$bbanStructure=Strings::replace($bbanStructure, '/[:;]/'); // errors seen in Germany, Hungary entries
			$ibanStructure=Strings::replace($ibanStructure, '/, .*$/'); // duplicates for FO, GL seen in DK
			$ibanElectronicExample=Strings::replace($ibanElectronicExample, '/, .*$/'); // duplicates for FO, GL seen in DK
			switch ($countryCode) {
				case 'MU':
					$ibanElectronicExample=str_replace(' ', '', $ibanElectronicExample); // MU example has a spurious space
					break;
				case 'CZ':
					$ibanElectronicExample=Strings::replace($ibanElectronicExample, '/ \{10,}+$/'); // extra example for CZ
					$ibanPrintExample=Strings::replace($ibanPrintExample, '/^(CZ.. .... .... .... .... ....).*$/', '$1'); // extra example
					break;
				case 'FI':
					// remove additional example
					$ibanElectronicExample=Strings::replace($ibanElectronicExample, '/ or .*$/');
					// fix bban example to remove verbosity and match domestic example
					$bban='12345600000785';
					break;
				}
			$ibanPrintExample=Strings::replace($ibanPrintExample, '/, .*$/');

			// calculate $bban_regex from $bban_structure
			$bbanRegex=$this->swift2regex($bbanStructure);
			// calculate $iban_regex from $iban_structure
			$ibanRegex=$this->swift2regex($ibanStructure);
			// calculate numeric $bban_length
			$bbanLength=Strings::replace($bbanLength, '/[^\d]/');
			// calculate numeric $iban_length
			$ibanLength=Strings::replace($ibanLength, '/[^\d]/');

			/*
			 * calculate bban_bankid_<start|stop>_offset
			 * .... First we have to parse the freetext $bban_bi_position, eg: 
			 * Bank Identifier 1-3, Branch Identifier
			 * Position 1-2
			 * Positions 1-2
			 * Positions 1-3
			 * Positions 1-3 ;Branch is not available
			 * Positions 1-3, Branch identifier
			 * Positions 1-3, Branch identifier positions
			 * Positions 1-4
			 * Positions 1-4, Branch identifier
			 * Positions 1-4, Branch identifier positions
			 * Positions 1-5
			 * Positions 1-5 (positions 1-2 bank identifier; positions 3-5 branch identifier). In case of payment institutions Positions 1-5, Branch identifier positions
			 * Positions 1-6,  Branch identifier positions
			 * Positions 1-6. First two digits of bank identifier indicate the bank or banking group (For example, 1 or 2 for Nordea, 31 for Handelsbanken, 5 for cooperative banks etc)
			 * Positions 1-7
			 * Positions 1-8
			 * Positions 2-6, Branch identifier positions
			 * positions 1-3, Branch identifier positions
			 *
			 *  ... our algorithm is as follows:
			 *   - find all <digit>-<digit> tokens
			 */
			$matches=Strings::matchAll($bbanBiPosition, '/(\d)-(\d\d?)/', PREG_PATTERN_ORDER);
			// - discard overlaps ({1-5,1-2,3-5} becomes {1-2,3-5})
			$tmptokens=[];
			for ($j=0; $j<count($matches[0]); $j++) {
				$from=$matches[1][$j];
				$to=$matches[2][$j];
				// (if we don't yet have a match starting here, or it goes further,
				//  overwrite the match-from-this-position record)
				if (!isset($tmptokens[$from]) || $to<$tmptokens[$from]) {
					$tmptokens[$from]=$to;
					}
				}
			unset($matches); // done

			// - assume the token starting from position 1 is the bank identifier
			//  (or, if it does not exist, the token starting from position 2)
			$bbanBankidStartOffset=0; // decrement 1 on assignment
			if (isset($tmptokens[1])) {
				$bbanBankidStopOffset=$tmptokens[1]-1; // decrement 1 on assignment
				unset($tmptokens[1]);
				}
			else {
				$bbanBankidStopOffset=$tmptokens[2]-1; // decrement 1 on assignment
				unset($tmptokens[2]);
				}
			// - assume any subsequent token, if present, is the branch identifier.
			$tmpkeys=array_keys($tmptokens);
			$start=array_shift($tmpkeys);
			unset($tmpkeys); //done
			$bbanBranchidStartOffset= $bbanBranchidStopOffset= '';
			if ($start!='') {
				// we have a branch identifier!
				$bbanBranchidStartOffset=$start-1;
				$bbanBranchidStopOffset=$tmptokens[$start]-1;
				}
			else {
				/*
				 * (note: this codepath occurs for around two thirds of all records)
				 * we have not yet found a branch identifier. HOWEVER, we can analyse the
				 * structure of the BBAN to determine whether there is more than one
				 * remaining non-tiny field (tiny fields on the end of a BBAN typically
				 * being checksums) and, if so, assume that the first/shorter one is the branch identifier.
				 */
				$reducedBbanStructure=Strings::replace($bbanStructure, '/^\d+![nac]/');
				$tokens=$this->swiftTokenize($reducedBbanStructure, TRUE);
				// discard any tokens of length 1 or 2
				for ($t=0; $t<count($tokens[0]); $t++) {
					$tokens['discarded'][$t]= $tokens[1][$t]<3? 1 : 0;
					}
				// interesting fields are those that are not discarded...
				$interestingFieldCount= !isset($tokens['discarded'])
					? count($tokens[0])
					: count($tokens[0])-count($tokens['discarded']);
				// ...if we have at least two of them, there's a branchid-type field
				if ($interestingFieldCount>=2) {
					// now loop through until we assign the branchid start offset
					// (this occurs just after the first non-discarded field)
					$found=FALSE;
					for ($f=0; (!$found && $f<count($tokens[0])); $f++) {
						// if this is a non-discarded token, of >2 length...
						if ((!isset($tokens['discarded'][$f]) || $tokens['discarded'][$f]!=1) && $tokens[1][$f]>2) {
							// ... then assign.
							$preOffset=$bbanBankidStopOffset+1; // this is the offset before we reduced the structure to remove the bankid field
							$bbanBranchidStartOffset=$preOffset+$tokens['offset'][$f];
							$bbanBranchidStopOffset=$preOffset+$tokens['offset'][$f]+$tokens[1][$f]-1; // decrement by one on assignment
							$found=TRUE;
							}
						}
					}
				}

			/*
			 * calculate 1=Yes, 0=No for $country_sepa
			 * note: This is buggy due to the free inclusion of random text by the registry publishers.
			 *  Notably it requires modification for places like Finland and Portugal where these comments are known to exist.
			 */
			$countrySepa= Strings::lower($countrySepa)=='yes';
			// set registry edition
			$registryEdition=date('Y-m-d');

			// now prepare generate our registry lines...
			$toGenerate=[$countryCode => $countryName];
			switch ($countryCode) {
				case 'DK':
					$toGenerate=[
						'DK' => $countryName,
						'FO' => 'Faroe Islands',
						'GL' => 'Greenland'
						];
					break;
				case 'FR':
					$toGenerate=[
						'FR' => $countryName,
						'BL' => 'Saint Barthelemy',
						'GF' => 'French Guyana',
						'GP' => 'Guadelope',
						'MF' => 'Saint Martin (French Part)',
						'QM' => 'Martinique',
						'RE' => 'Reunion',
						'PF' => 'French Polynesia',
						'TF' => 'French Sounthern Territories',
						'YT' => 'Mayotte',
						'NC' => 'New Caledonia',
						'PM' => 'Saint Pierre et Miquelon',
						'WF' => 'Wallis and Futuna Islands'
						];
					break;
				}

			// output loop
			foreach ($toGenerate as $countryCode => $countryName) {
				$ibanElectronicExample=$verifier->setChecksum($countryCode.substr($ibanElectronicExample, 2));
				$ibanStructure=$countryCode.substr($ibanStructure, 2);
				$ibanRegex='^'.$countryCode.substr($ibanRegex, 3);
				$this->_csvData[]=[
					$countryCode,
					$countryName,
					$domesticExample,
					$bbanExample,
					$bbanStructure,
					$bbanRegex,
					$bbanLength,
					$ibanElectronicExample,
					$ibanStructure,
					$ibanRegex,
					$ibanLength,
					$bbanBankidStartOffset,
					$bbanBankidStopOffset,
					$bbanBranchidStartOffset,
					$bbanBranchidStopOffset,
					$registryEdition,
					$countrySepa
					];
				}
			}
	}

	/**
	 * Converts internal CSV to final registry
	 *
	 * @return array
	 */
	private function csv2registry()
	{
		foreach ($this->_csvData as $line) {
			list(
				$country, $countryName, $domesticExample, $bbanExample, $bbanFormatSwift,
				$bbanFormatRegex, $bbanLength, $ibanExample, $ibanFormatSwift, $ibanFormatRegex,
				$ibanLength, $bbanBankidStartOffset, $bbanBankidStopOffset, $bbanBranchidStartOffset, $bbanBRanchidStopOffset,
				$registryEdition, $countrySepa
				)=$line;
			$this->_registry[$country]=[
				'country' => $country,
				'country_name' => $countryName,
				'country_sepa' => $countrySepa,
				'domestic_example' => $domesticExample,
				'bban_example' => $bbanExample,
				'bban_format_swift' => $bbanFormatSwift,
				'bban_format_regex' => $bbanFormatRegex,
				'bban_length' => $bbanLength,
				'iban_example' => $ibanExample,
				'iban_format_swift' => $ibanFormatSwift,
				'iban_format_regex' => $ibanFormatRegex,
				'iban_length' => $ibanLength,
				'bban_bankid_start_offset' => $bbanBankidStartOffset,
				'bban_bankid_stop_offset' => $bbanBankidStopOffset,
				'bban_branchid_start_offset' => $bbanBranchidStartOffset,
				'bban_branchid_stop_offset' => $bbanBRanchidStopOffset,
				'registry_edition' => $registryEdition
				];
			}
		return $this->_registry;
	}

	
	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		$this->loadCache();
		return isset($this->_registry[$offset]);
	}

	/**
	 * @param mixed $offset
	 * @return mixed|NULL
	 */
	public function offsetGet($offset)
	{
		$this->loadCache();
		return isset($this->_registry[$offset])? $this->_registry[$offset] : NULL;
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @throws IbanException 
	 */
	public function offsetSet($offset, $value)
	{
		throw IbanException::invalidAccess();
	}

	/**
	 * @param mixed $offset
	 * @throws IbanException 
	 */
	public function offsetUnset($offset)
	{
		throw IbanException::invalidAccess();
	}

	/**
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		$this->loadCache();
		return new \ArrayIterator($this->_registry);
	}

	/**
	 * @return array
	 */
	public function getRegistry()
	{
		$this->loadCache();
		return $this->_registry;
	}

	/**
	 * @return array
	 */
	public function getInfo()
	{
		return [
			Registry::SOURCE => $this->load(Registry::SOURCE),
			Registry::ETAG => $this->load(Registry::ETAG)
			];
	}

	/**
	 * @return string
	 */
	public function getData()
	{
		return $this->load(Registry::DATA);
	}
}
