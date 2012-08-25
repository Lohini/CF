<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini
 *
 * @copyright (c) 2010, 2012 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini;
/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
/**
 * Lohini port
 * @author Lopo <lopo@lohini.net>
 */

/**
 */
final class CF
{
	const NAME='Lohini Components & Features';
	const VERSION='0.0.1';
	const REVISION='$WCREV$ released on $WCDATE$';


	/**
	 * @throws \Nette\StaticClassException
	 */
	final public function __construct()
	{
		throw new \Nette\StaticClassException('Cannot instantiate static class '.get_class($this));
	}

	/**
	 * @return array
	 */
	public static function getDefaultPackages()
	{
		return array(
			'Lohini\Package\Components\Package',
			'Lohini\Package\Cf\Package',
			);
	}

	/**
	 * @return \Kdyby\Packages\PackagesList
	 */
	public static function createPackagesList()
	{
		return new Packages\PackagesList(
			array_merge(Core::getDefaultPackages(), static::getDefaultPackages())
			);
	}
}
