<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini
 *
 * @copyright (c) 2010, 2012 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Components\Header\DI;
/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
/**
 * Lohini port
 * @author Lopo <lopo@lohini.net>
 */

/**
 */
class HeaderExtension
extends \Nette\Config\CompilerExtension
{
	public function loadConfiguration()
	{
		$builder=$this->getContainerBuilder();

		$builder->addDefinition($this->prefix('control'))
			->setClass('Lohini\Components\Header\HeaderControl');

		$builder->getDefinition('nette.latte')
			->addSetup('Lohini\Components\Header\HeadMacro::install(?->compiler)', array('@self'));
	}
}
