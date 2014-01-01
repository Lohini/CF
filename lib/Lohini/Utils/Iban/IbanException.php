<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini (http://lohini.net)
 *
 * @copyright (c) 2010, 2014 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Utils\Iban;

/**
 * Description of IbanException
 *
 * @author Lopo <lopo@lohini.net>
 */
class IbanException
extends \Exception
{
	const UNKNOWN_TYPE=1;
	const INVALID_ACCESS=2;


	/**
	 * @param string $type
	 * @return IbanException
	 */
	public static function unknownType($type)
	{
		return new self("Unknown type '$type'", self::UNKNOWN_TYPE);
	}

	/**
	 * @return IbanException
	 */
	public static function invalidAccess()
	{
		return new self('', self::INVALID_ACCESS);
	}
}
