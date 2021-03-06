<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini
 *
 * @copyright (c) 2010, 2014 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Components\DataGrid;
/**
 * @author Roman Sklenář
 */
/**
 * Lohini port
 * @author Lopo <lopo@lohini.net>
 */

/**
 * Defines method that must be implemented to allow a component act like a data grid action.
 */
interface IAction
{
	/**
	 * Gets action element template
	 *
	 * @return \Nette\Utils\Html
	 */
	function getHtml();
}
