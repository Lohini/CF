<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini
 *
 * @copyright (c) 2010, 2012 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Package\Components;
/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
/**
 * Lohini port
 * @author Lopo <lopo@lohini.net>
 */

/**
 */
class Package
extends \Lohini\Packages\Package
{
	/**
	 * @param \Nette\Config\Configurator $config
	 * @param \Nette\Config\Compiler $compiler
	 * @param \Lohini\Packages\PackagesContainer $packages
	 */
	public function compile(\Nette\Config\Configurator $config, \Nette\Config\Compiler $compiler, \Lohini\Packages\PackagesContainer $packages)
	{
		// Kdyby Components
		$compiler->addExtension('header', new \Lohini\Components\Header\DI\HeaderExtension);
		$compiler->addExtension('kc', new DI\ComponentsExtension);
	}
}
